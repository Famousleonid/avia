<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m12_review_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function result($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function check($b,$m){if(!$b)throw new RuntimeException($m);}
function digest($db,$tables){$all=[];foreach($tables as $t)$all[$t]=$db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($all));}
$snap=json_decode(file_get_contents(__DIR__.'/production-handoff-snapshot.json'),true,512,JSON_THROW_ON_ERROR);$report=['result'=>'FAIL','production_modified'=>false,'working_database_modified'=>false];
try{
 foreach($snap['schema'] as $t=>$schema){
  if($t==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),title VARCHAR(255),log_card_order JSON NULL,deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$schema[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
 }
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(12,'32-10-15','Leg Assy, Strut, MLG'),(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num,kit) VALUES(9999,'UNRELATED','Preserve','1-1',1)");
 foreach($snap['data'] as $table=>$rows){if($table==='manuals')continue;foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}}
 $unrelated=$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC);
 $sql=file_get_contents(__DIR__.'/32-10-15_20261020_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-10-15_20261020_v01_verify.sql');
 check(result(raw($db,$sql))==='SUCCESS - REVIEWED PACKAGE','Initial raw import');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Audit '.json_encode($r));
 $tables=array_keys($snap['schema']);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PACKAGE' && $after===digest($db,$tables),'Repeat differs');
 check($unrelated===$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC),'Unrelated data changed');
 $report['positive']=['raw_import'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'unrelated_preserved'=>true];
 // Use the real application family and composition services against scratch data.
 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 $manual=App\Models\Manual::findOrFail(12);$actual=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['parts'])->pluck('ipl')->all();
 $log=json_decode(file_get_contents(__DIR__.'/log-card-plan.json'),true);
 check($actual===$log['ordered_ipls'],'Runtime Log Card exact order');
 $gs=App\Models\ManualPartGroup::where('manual_id',12)->with('options.coverages')->get();
 $composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($gs);
 $iplIds=App\Models\Component::where('manual_id',12)->pluck('id','ipl_num');$inverse=$iplIds->flip();
 $svc=app(App\Services\WorkorderAssyConfiguration::class);

 foreach(['4-95A'=>['4-96','4-96A'],'4-95B'=>['4-96A','4-96'],'6-230'=>['6-231','6-231A'],'6-230A'=>['6-231A','6-231'],'6-85B'=>['6-89A','6-89B'],'7-5C'=>['7-55C','7-55']] as $parent=>$pair){
  $g=$gs->firstWhere('code','m12-assy-'.$parent);
  $ipls=$composition[$g->id]->map(fn($id)=>$inverse[$id])->all();
  check(in_array($pair[0],$ipls,true),'Exact applicable member '.$parent);
  check(!in_array($pair[1],$ipls,true),'Inapplicable variant leaked '.$parent);
 }
 $g=$gs->firstWhere('code','m12-assy-3-205');
 $members=$svc->members($g->options->first(),[]);
 check($g->options->first()->coverages->where('component_id',$iplIds['3-209'])->first()->qty==4,'WOW per-parent rivet quantity');
 foreach(['1-1','2-5','3-255','6-100','6-300','5-5'] as $i)check(!$gs->contains('code','m12-assy-'.$i),'Deferred parent must not have inferred composition '.$i);
 foreach($snap['data']['components'] as $before){$afterPart=$db->query('SELECT * FROM components WHERE id='.(int)$before['id'])->fetch(PDO::FETCH_ASSOC);foreach($before as $k=>$v){if(in_array($k,['units_assy','is_bush','bush_ipl_num','ndt_list','cad_list','paint_list','log_card','kit'],true))continue;if($k==='part_number'&&$before['id']==7524){check($afterPart[$k]==='17342-1','Approved PN correction');continue;}check($afterPart[$k]==$v,'Preserved existing field '.$before['id'].'/'.$k);}}
 check($snap['data']['component_assemblies']==$db->query('SELECT * FROM component_assemblies ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'Legacy assemblies unchanged');
 $report['runtime']=['log_card_parts'=>count($actual),'groups'=>$gs->count(),'exact_applicability'=>'PASS','per_parent_quantities'=>'PASS','deferred_parents'=>'PASS','existing_ids_fields_preserved'=>true];
 foreach([
 'wrong_manual'=>["UPDATE manuals SET number='WRONG' WHERE id=12","UPDATE manuals SET number='32-10-15' WHERE id=12"],
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE manual_id=12 AND ipl_num='1-25'","UPDATE components SET part_number='17342-1' WHERE manual_id=12 AND ipl_num='1-25'"],
 'changed_group_qty'=>['UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1','UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1'],
 'manual_order'=>["UPDATE manuals SET log_card_order='[999999]' WHERE id=12",null]
 ] as $case=>$pair){$oldOrder=$db->query('SELECT log_card_order FROM manuals WHERE id=12')->fetchColumn();$db->exec($pair[0]);$before=digest($db,$tables);check(result(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED' && $before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';if($pair[1])$db->exec($pair[1]);else $db->prepare('UPDATE manuals SET log_card_order=? WHERE id=12')->execute([$oldOrder]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
