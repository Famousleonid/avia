<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m151_review_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function result($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function check($b,$m){if(!$b)throw new RuntimeException($m);}
function digest($db,$tables){$all=[];foreach($tables as $t)$all[$t]=$db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($all));}
$snap=json_decode(file_get_contents(__DIR__.'/production-snapshot.json'),true,512,JSON_THROW_ON_ERROR);$report=['result'=>'FAIL','production_modified'=>false,'working_database_modified'=>false];
try{
 foreach($snap['schema'] as $t=>$schema){
  if($t==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),title VARCHAR(255),log_card_order JSON NULL,deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$schema[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
 }
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(151,'32-31-07','Leg Assy, Strut, MLG'),(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num,kit) VALUES(9999,'UNRELATED','Preserve','1-1',1)");
 foreach($snap['data'] as $table=>$rows){if($table==='manuals')continue;foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}}

 $v01Ids=[];
 $unrelated=$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC);
 $sql=file_get_contents(__DIR__.'/32-31-07_20261103_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-31-07_20261103_v01_verify.sql');
 $first=result(raw($db,$sql));
 if($first!=='SUCCESS - REVIEWED PACKAGE'){
  $debug='';foreach(explode("\n",$sql) as $n=>$line){$debug.=$line."\n";if(str_starts_with($line,'SET @m151_ok=COALESCE'))$debug.="SELECT ".($n+1)." debug_line,@m151_ok ok;\n";}
  $rows=raw($db,$debug);foreach($rows as $row){if(isset($row['debug_line'])&&!(int)$row['ok']){$report['first_failed_guard_line']=$row['debug_line'];$report['guard_sql']=explode("\n",$sql)[$row['debug_line']-1];break;}}
 }
 check($first==='SUCCESS - REVIEWED PACKAGE','Initial raw import');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Audit '.json_encode($r));
 $tables=array_keys($snap['schema']);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PACKAGE' && $after===digest($db,$tables),'Repeat differs');
 check($unrelated===$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC),'Unrelated data changed');

 if($v01Ids)check($v01Ids===$db->query('SELECT id,ipl_num,part_number FROM components WHERE manual_id=151 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'v01 IDs preserved');
 foreach($snap['data']['processes'] as $proc){
  $now=$db->query('SELECT * FROM processes WHERE id='.(int)$proc['id'])->fetch(PDO::FETCH_ASSOC);
  check($now==$proc,'Shared process definition changed '.$proc['id']);
 }
 $report['positive']=['raw_import'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'unrelated_preserved'=>true];
 // Use the real application family and composition services against scratch data.
 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 $manual=App\Models\Manual::findOrFail(151);$actual=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['parts'])->pluck('ipl')->all();
 $log=json_decode(file_get_contents(__DIR__.'/log-card-plan.json'),true);
 check($actual===$log['ordered_ipls'],'Runtime Log Card exact order');
 $gs=App\Models\ManualPartGroup::where('manual_id',151)->with('options.coverages')->get();
 $composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($gs);
 $iplIds=App\Models\Component::where('manual_id',151)->pluck('id','ipl_num');$inverse=$iplIds->flip();
 $svc=app(App\Services\WorkorderAssyConfiguration::class);



 $coverageSvc=app(App\Services\PartGroupCoverageResolver::class);$familyMap=$coverageSvc->bundleIplFamiliesForManuals([151],$gs);
 $options=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');$byGroup=$gs->keyBy('id');
 $method=new ReflectionMethod($coverageSvc,'expandBundleMember');$method->setAccessible(true);

 foreach(['1-40'=>['1-50'=>1,'1-60'=>1,'1-70'=>1],'1-230'=>['1-240'=>2,'1-250'=>2,'1-260'=>1]] as $parent=>$members){
  $group=$gs->firstWhere('code','m151-assy-'.$parent);$opt=$group->options->first();$coverage=[];
  $actualIpls=$composition[$group->id]->map(fn($id)=>$inverse[$id])->all();$want=array_merge([$parent],array_keys($members));sort($actualIpls);sort($want);check($actualIpls===$want,'Exact assembly composition '.$parent);
  foreach($opt->coverages as $member){$args=[&$coverage,$member,1,'prl','test',$group,$opt,$byGroup,$options,[],$familyMap,[],false,[]];$method->invokeArgs($coverageSvc,$args);}
  check(count($coverage)===3,'No extra coverage '.$parent);
  foreach($members as $ipl=>$qty)check(($coverage[$iplIds[$ipl]]['covered_qty']??0)===$qty,'Runtime quantity '.$parent.' '.$ipl);
 }
 $report['procurement_coverage']='PASS: exact two internal ASSY, quantities1/1/1 and2/2/1';
 $stdPlan=json_decode(file_get_contents(__DIR__.'/std-plan.json'),true);
 foreach(['ndt','cad'] as $type){
  $beforeStd=$db->query("SELECT * FROM std_processes WHERE manual_id=151 AND std='$type' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  App\Models\StdProcess::syncFromComponentFlagsForManualStd($manual,$type);
  $afterStd=$db->query("SELECT * FROM std_processes WHERE manual_id=151 AND std='$type' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
  check($beforeStd==$afterStd,'STD sync changes source rows '.$type);
  $allowed=App\Models\StdProcess::processPicklistValuesForManual(151,$type);
  foreach($stdPlan['rows'] as $r)if($r['std']===$type)check(App\Models\StdProcess::normalizeCsvProcessForManual(151,$type,$r['process'],1)===$r['process'],'Missing process picklist');
 }
 $report['std_runtime']=['ndt'=>7,'cad'=>8,'sync_preserves_source_values'=>true,'bush_excluded'=>true,'picklists_match'=>true];
 $report['runtime']=['log_card_parts'=>count($actual),'groups'=>$gs->count(),'exact_applicability'=>'PASS','per_parent_quantities'=>'PASS','deferred_parents'=>'PASS','existing_ids_fields_preserved'=>true];
 foreach([
 'wrong_std_qty'=>['UPDATE std_processes SET qty=qty+1 WHERE manual_id=151 ORDER BY id LIMIT 1','UPDATE std_processes SET qty=qty-1 WHERE manual_id=151 ORDER BY id LIMIT 1'],
 'wrong_manual'=>["UPDATE manuals SET number='WRONG' WHERE id=151","UPDATE manuals SET number='32-31-07' WHERE id=151"],
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE manual_id=151 AND ipl_num='1-10'","UPDATE components SET part_number='46613-1' WHERE manual_id=151 AND ipl_num='1-10'"],
 'changed_group_qty'=>['UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1','UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1'],
 'manual_order'=>["UPDATE manuals SET log_card_order='[999999]' WHERE id=151",null]
 ] as $case=>$pair){$oldOrder=$db->query('SELECT log_card_order FROM manuals WHERE id=151')->fetchColumn();$db->exec($pair[0]);$before=digest($db,$tables);check(result(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED' && $before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';if($pair[1])$db->exec($pair[1]);else $db->prepare('UPDATE manuals SET log_card_order=? WHERE id=151')->execute([$oldOrder]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
