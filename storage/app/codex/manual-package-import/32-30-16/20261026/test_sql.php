<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m51_review_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
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
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(51,'32-30-16','Leg Assy, Strut, MLG'),(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num,kit) VALUES(9999,'UNRELATED','Preserve','1-1',1)");
 foreach($snap['data'] as $table=>$rows){if($table==='manuals')continue;foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}}

 $v01Ids=[];
 $unrelated=$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC);
 $sql=file_get_contents(__DIR__.'/32-30-16_20261026_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-30-16_20261026_v01_verify.sql');
 $first=result(raw($db,$sql));
 if($first!=='SUCCESS - REVIEWED PACKAGE'){
  $debug='';foreach(explode("\n",$sql) as $n=>$line){$debug.=$line."\n";if(str_starts_with($line,'SET @m51_ok=COALESCE'))$debug.="SELECT ".($n+1)." debug_line,@m51_ok ok;\n";}
  $rows=raw($db,$debug);foreach($rows as $row){if(isset($row['debug_line'])&&!(int)$row['ok']){$report['first_failed_guard_line']=$row['debug_line'];$report['guard_sql']=explode("\n",$sql)[$row['debug_line']-1];break;}}
 }
 check($first==='SUCCESS - REVIEWED PACKAGE','Initial raw import');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Audit '.json_encode($r));
 $tables=array_keys($snap['schema']);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PACKAGE' && $after===digest($db,$tables),'Repeat differs');
 check($unrelated===$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC),'Unrelated data changed');

 if($v01Ids)check($v01Ids===$db->query('SELECT id,ipl_num,part_number FROM components WHERE manual_id=51 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'v01 IDs preserved');
 foreach($snap['data']['processes'] as $proc){
  $now=$db->query('SELECT * FROM processes WHERE id='.(int)$proc['id'])->fetch(PDO::FETCH_ASSOC);
  check($now==$proc,'Shared process definition changed '.$proc['id']);
 }
 $report['positive']=['raw_import'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'unrelated_preserved'=>true];

 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 $gs=App\Models\ManualPartGroup::where('manual_id',51)->with('options.coverages')->get();
 $composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($gs);
 $ids=App\Models\Component::where('manual_id',51)->pluck('id','ipl_num');$inverse=$ids->flip();
 $coverageSvc=app(App\Services\PartGroupCoverageResolver::class);$familyMap=$coverageSvc->bundleIplFamiliesForManuals([51],$gs);
 $options=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');$byGroup=$gs->keyBy('id');
 $method=new ReflectionMethod($coverageSvc,'expandBundleMember');$method->setAccessible(true);
 foreach(['1-40'=>['1-45'=>1,'1-50'=>2,'1-55'=>1],'1-100'=>['1-105'=>1,'1-110'=>2,'1-115'=>1]] as $head=>$members){
  $group=$gs->firstWhere('code','m51-assy-'.$head);$got=$composition[$group->id]->map(fn($id)=>$inverse[$id])->all();$want=array_merge([$head],array_keys($members));sort($got);sort($want);check($got===$want,'Exact composition '.$head);
  $opt=$group->options->first();$coverage=[];
  foreach($opt->coverages as $member){$args=[&$coverage,$member,1,'prl','test',$group,$opt,$byGroup,$options,[],$familyMap,[],false,[]];$method->invokeArgs($coverageSvc,$args);}
  foreach($members as $ipl=>$qty)check(($coverage[$ids[$ipl]]['covered_qty']??0)===$qty,'Quantity '.$ipl);
 }
 $report['runtime']='PASS: exact two ASSY compositions, bushing quantity 2 each';
 check($db->query("SELECT name FROM components WHERE id=15560")->fetchColumn()==='ROD. PISTON','Existing ID/name preserved');
 foreach(['std_processes','manual_processes','manual_service_bulletins','manual_in_process_check_sheets','component_assemblies'] as $t)check(count($snap['data'][$t])===(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn(),'Preserve '.$t);
 // PDF-only repeat must preserve independently assigned workflow values.
 $db->exec("UPDATE components SET log_card=1,kit=1,ndt_list=1,cad_list=1,paint_list=1 WHERE manual_id=51 AND ipl_num='1-85'");
 $db->exec("UPDATE manuals SET log_card_order='[15560]' WHERE id=51");
 $preserve=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PACKAGE' && $preserve===digest($db,$tables),'PDF-only preserves workflow');
 $report['workflow_preserved']=true;
 foreach([
 'wrong_manual'=>["UPDATE manuals SET number='WRONG' WHERE id=51","UPDATE manuals SET number='32-30-16' WHERE id=51"],
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE manual_id=51 AND ipl_num='1-5'","UPDATE components SET part_number='104471709' WHERE manual_id=51 AND ipl_num='1-5'"],
 'changed_group_qty'=>['UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1','UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1'],
 'existing_quantity'=>["UPDATE components SET units_assy=9 WHERE id=15560","UPDATE components SET units_assy=1 WHERE id=15560"]
 ] as $case=>$pair){$db->exec($pair[0]);$before=digest($db,$tables);check(result(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED' && $before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';$db->exec($pair[1]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
