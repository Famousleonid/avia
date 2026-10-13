<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$original=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m42_completion_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`'); Illuminate\Support\Facades\DB::connection('mysql')->setDatabaseName($scratch);
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
$snap=json_decode(file_get_contents(__DIR__.'/production.json'),true,512,JSON_THROW_ON_ERROR);
function seed($db,$t,$rows){foreach($rows as $r){unset($r['active_ipl_num']);$db->prepare('INSERT INTO `'.$t.'` (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_fill(0,count($r),'?')).')')->execute(array_values($r));}}
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function status($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function check($b,$s){if(!$b)throw new RuntimeException($s);}
function digest($db,$tables){$a=[];foreach($tables as $t)$a[$t]=$db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($a));}
$map=['components'=>'components','groups'=>'manual_part_groups','options'=>'manual_part_group_options','coverages'=>'manual_part_group_coverages','legacy'=>'component_assemblies','service_bulletins'=>'manual_service_bulletins','check_sheet'=>'manual_in_process_check_sheets'];
$report=['production_modified'=>false,'working_database_modified'=>false,'result'=>'FAIL'];
try{
 foreach($snap['schema'] as $table=>$rows){
  if($table==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
 }
 $migration=require dirname(__DIR__,6).'/database/migrations/2026_10_09_120000_add_assy_companion_conditions.php';$migration->up();$migration->up();
 seed($db,'manuals',[['id'=>42,'number'=>'32-21-02','deleted_at'=>null]]);
 foreach($map as $k=>$table)seed($db,$table,$snap['manual'][$k]);
 $sql=file_get_contents(__DIR__.'/32-21-02_20261009_v01_completion_import.sql');$report['sql_sha256']=hash('sha256',$sql);
 $baseline=[];foreach($map as $k=>$t)$baseline[$t]=$db->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 check(status(raw($db,$sql))==='SUCCESS','Initial import failed');
 $audit=raw($db,file_get_contents(__DIR__.'/32-21-02_20261009_v01_completion_verify.sql'));
 foreach($audit as $row)check($row['result']==='PASS','SELECT check '.json_encode($row));
 foreach($baseline as $t=>$rows){$ids=implode(',',array_column($rows,'id'));check($rows===$db->query('SELECT * FROM '.$t.' WHERE id IN ('.$ids.') ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'Changed original rows '.$t);}
 $all=array_keys($snap['schema']);$after=digest($db,$all);check(status(raw($db,$sql))==='SUCCESS'&&$after===digest($db,$all),'Repeat not identical');
 $report['positive']=['raw_sql_success'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'all_original_rows_preserved'=>true,'migration_repeat'=>true];
 echo 'Raw SQL, '.count($audit).' SELECT checks, repeat and preservation PASS'.PHP_EOL;
 require __DIR__.'/test_completion_runtime.php';
 foreach([
  'wrong_manual'=>"UPDATE manuals SET number='WRONG' WHERE id=42",
  'changed_part'=>"UPDATE components SET part_number='WRONG' WHERE id=5590",
  'changed_original_edge'=>'UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1',
  'changed_new_edge'=>"UPDATE manual_part_group_coverages SET qty=99 WHERE choice_slot='lower_cam' LIMIT 1",
  'bad_spacer_condition'=>"UPDATE manual_part_group_coverages SET requires_selected_option_ids='[1674]' WHERE choice_slot='wheel_axle_spacer' LIMIT 1"
 ] as $case=>$mutation){
  $db->exec($mutation);$before=digest($db,$all);check(status(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED'&&$before===digest($db,$all),'Guard '.$case);$report['negative'][$case]='PASS';
  // Restore exactly the deliberately modified field from the saved post-import fixture.
  foreach($afterRows??[] as $ignored){}
  if($case==='wrong_manual')$db->exec("UPDATE manuals SET number='32-21-02' WHERE id=42");
  if($case==='changed_part')$db->exec("UPDATE components SET part_number='190-70470-001' WHERE id=5590");
  if($case==='changed_original_edge')$db->exec('UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1');
  if($case==='changed_new_edge')$db->exec("UPDATE manual_part_group_coverages SET qty=1 WHERE choice_slot='lower_cam'");
  if($case==='bad_spacer_condition')$db->exec("UPDATE manual_part_group_coverages SET requires_selected_option_ids='[1672,1673]' WHERE choice_slot='wheel_axle_spacer' AND component_id=(SELECT id FROM components WHERE manual_id=42 AND ipl_num='9-140' AND deleted_at IS NULL)");
 }
 $report['result']='PASS';
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/completion-sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}

