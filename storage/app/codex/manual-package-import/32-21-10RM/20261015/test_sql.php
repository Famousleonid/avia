<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m27_review_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
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
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(27,'32-21-10RM','Leg Assy, Strut, MLG'),(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num,kit) VALUES(9999,'UNRELATED','Preserve','1-1',1)");
 $unrelated=$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC);
 $sql=file_get_contents(__DIR__.'/32-21-10RM_20261015_v01_partial_import.sql');$verify=file_get_contents(__DIR__.'/32-21-10RM_20261015_v01_partial_verify.sql');
 check(result(raw($db,$sql))==='SUCCESS - REVIEWED PARTIAL PACKAGE','Initial raw import');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Audit '.json_encode($r));
 $tables=array_keys($snap['schema']);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PARTIAL PACKAGE' && $after===digest($db,$tables),'Repeat differs');
 check($unrelated===$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC),'Unrelated data changed');
 $report['positive']=['raw_import'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'unrelated_preserved'=>true];
 // Use the real application family and composition services against scratch data.
 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 $manual=App\Models\Manual::findOrFail(27);$actual=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['parts'])->pluck('ipl')->all();
 $log=json_decode(file_get_contents(__DIR__.'/log-card-plan.json'),true);
 check($actual===$log['ordered_ipls'],'Runtime Log Card exact order');
 check(app(App\Services\ManualLogCardOrder::class)->groups($manual)->pluck('key')->all()===array_keys($log['families']),'Runtime family order');
 $gs=App\Models\ManualPartGroup::where('manual_id',27)->with('options.coverages')->get();
 $composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($gs);
 $iplIds=App\Models\Component::where('manual_id',27)->pluck('id','ipl_num');
 $inverse=$iplIds->flip();
 $torque=$gs->firstWhere('code','m27-assy-3A-290A');
 $wanted=['3A-290A','3A-300A','3A-300B','3A-310A','9-40A'];
 foreach([10,20,30] as $base)for($n=0;$n<4;$n++)$wanted[]='9-'.($base+$n).'A';
 $actualParts=$composition[$torque->id]->map(fn($id)=>$inverse[$id])->sort()->values()->all();sort($wanted);check($wanted===$actualParts,'Torque Link nested full bushing families');
 foreach(['10-1A'=>'10-20A','10-1B'=>'10-20B'] as $parent=>$cylinder){$group=$gs->firstWhere('code','m27-assy-'.$parent);$wanted=[$parent,'10-10A',$cylinder];sort($wanted);$actualParts=$composition[$group->id]->map(fn($id)=>$inverse[$id])->sort()->values()->all();check($wanted===$actualParts,'Cylinder effectivity '.$parent);}
 $svc=app(App\Services\WorkorderAssyConfiguration::class);
 foreach(['5','5A'] as $fig){$o=$gs->firstWhere('code','m27-assy-'.$fig.'-10A')->options->first();$base=$svc->members($o,[]);check($base->count()===8,'Unselected tube has only common members');foreach($o->coverages->where('choice_slot','wheel_axle') as $edge){$selected=$svc->members($o,[$o->id.'|wheel_axle'=>$edge->id]);check($selected->count()===9 && $selected->where('choice_slot','wheel_axle')->count()===1,'Exactly one nested axle branch');}}
 foreach(['4-110A'=>'4-140A','4A-110A'=>'4A-140A','4A-110B'=>'4A-140B'] as $parent=>$rim){$g=$gs->firstWhere('code','m27-assy-'.$parent);$ipls=$composition[$g->id]->map(fn($id)=>$inverse[$id])->all();check(in_array($rim,$ipls,true),'Confirmed cuff rim '.$parent);foreach(['4A-140A','4A-140B'] as $other)if($other!==$rim)check(!in_array($other,$ipls,true),'Cuff variant leaked '.$parent);}
 $g=$gs->firstWhere('code','m27-assy-6-70A');$ipls=$composition[$g->id]->map(fn($id)=>$inverse[$id])->all();foreach(['7-80A','7-85A','6-80A','6-80B','6-81A','6-81B','7-33A'] as $i)check(in_array($i,$ipls,true),'Main Fitting missing '.$i);foreach(['7-30B','7-80B','7-85B'] as $i)check(!in_array($i,$ipls,true),'Main Fitting effectivity leak '.$i);
 foreach(['6A-70A','6A-70B','6A-70C','6A-70D','6A-70E','6A-70F','6A-70G','3-190B','3A-190B','5A-64A','5A-64B'] as $i)check(!$gs->firstWhere('code','m27-assy-'.$i),'Deferred group present '.$i);
 $report['runtime']=['log_card_parts'=>count($actual),'log_card_families'=>count($log['families']),'groups'=>$gs->count(),'nested_torque_link'=>'PASS','cylinder_effectivity'=>'PASS','sliding_tube_choice'=>'PASS'];
 foreach([
 'wrong_manual'=>["UPDATE manuals SET number='WRONG' WHERE id=27","UPDATE manuals SET number='32-21-10RM' WHERE id=27"],
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE manual_id=27 AND ipl_num='4A-140B'","UPDATE components SET part_number='1170-3201' WHERE manual_id=27 AND ipl_num='4A-140B'"],
 'changed_group_qty'=>['UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1','UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1'],
 'manual_order'=>["UPDATE manuals SET log_card_order='[999999]' WHERE id=27",null]
 ] as $case=>$pair){$oldOrder=$db->query('SELECT log_card_order FROM manuals WHERE id=27')->fetchColumn();$db->exec($pair[0]);$before=digest($db,$tables);check(result(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED' && $before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';if($pair[1])$db->exec($pair[1]);else $db->prepare('UPDATE manuals SET log_card_order=? WHERE id=27')->execute([$oldOrder]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
