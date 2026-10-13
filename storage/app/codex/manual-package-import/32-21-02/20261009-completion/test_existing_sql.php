<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$scratch='avia_m42_config_20261009_'.bin2hex(random_bytes(4));
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
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
 seed($db,'manuals',[['id'=>42,'number'=>'32-21-02','deleted_at'=>null]]);
 foreach($map as $k=>$table)seed($db,$table,$snap['manual'][$k]);
 $sql=file_get_contents(__DIR__.'/../20260924-config/32-21-02_20260924_v01_config_import.sql');
 $report['sql_sha256']=hash('sha256',$sql);
 $preservedTables=array_values(array_filter($map,fn($t)=>$t!=='manual_part_group_coverages'));
 $before=digest($db,$preservedTables);
 $originalEdges=$db->query('SELECT * FROM manual_part_group_coverages ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 check(status(raw($db,$sql))==='SUCCESS','Initial import');
 check((int)$db->query('SELECT COUNT(*) FROM manual_part_group_coverages')->fetchColumn()===4158,'Expected 127 additions');
 check($before===digest($db,$preservedTables),'Unrelated data changed');
 $oldIds=implode(',',array_column($originalEdges,'id'));
 check($originalEdges===$db->query('SELECT * FROM manual_part_group_coverages WHERE id IN ('.$oldIds.') ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'Original edges changed');
 $all=array_keys($snap['schema']);$after=digest($db,$all);
 check(status(raw($db,$sql))==='SUCCESS'&&$after===digest($db,$all),'Repeat not idempotent');
 $report['positive']=['raw_file_success'=>true,'new_links'=>127,'repeat_identical'=>true,'existing_4031_edges_preserved'=>true,'parts_flags_groups_options_sb_check_sheet_legacy_preserved'=>true];
 $db->exec("UPDATE manuals SET number='WRONG' WHERE id=42");$before=digest($db,$all);
 check(status(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED'&&$before===digest($db,$all),'Wrong manual guard');
 $report['negative']['wrong_manual']='PASS';$db->exec("UPDATE manuals SET number='32-21-02' WHERE id=42");
 $db->exec("UPDATE manual_part_group_options SET part_number='WRONG' WHERE id=1683");$before=digest($db,$all);
 check(status(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED'&&$before===digest($db,$all),'Wrong PN guard');
 $report['negative']['wrong_parent_pn']='PASS';
 $report['result']='PASS';
}finally{
 $pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;
 file_put_contents(__DIR__.'/old-config-sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
