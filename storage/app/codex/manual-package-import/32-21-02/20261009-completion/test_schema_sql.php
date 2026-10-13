<?php
require dirname(__DIR__,6).'/vendor/autoload.php';$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();if(!in_array($db->getConfig('host'),['localhost','127.0.0.1','::1']))throw new RuntimeException('Local only');
$cfg=$db->getConfig();$scratch='avia_m42_schema_'.bin2hex(random_bytes(4));$db->statement('CREATE DATABASE `'.$scratch.'`');
$p=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch,$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function runSchema($p,$sql){$s=$p->query($sql);$status=null;do{if($s->columnCount())foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r)if(isset($r['final_status']))$status=$r['final_status'];}while($s->nextRowset());return $status;}
try{$sql=file_get_contents(__DIR__.'/32-21-02_20261009_v01_completion_schema.sql');
 if(runSchema($p,$sql)!=='STOP - PREREQUISITE MISSING')throw new RuntimeException('Missing prerequisite');
 $p->exec('CREATE TABLE manual_part_group_coverages(id BIGINT PRIMARY KEY,choice_slot VARCHAR(80));CREATE TABLE workorder_assy_configuration_choices(id BIGINT PRIMARY KEY)');
 foreach([1,2] as $n)if(runSchema($p,$sql)!=='SCHEMA READY')throw new RuntimeException('Schema run'.$n);
 file_put_contents(__DIR__.'/schema-sql-test.json',json_encode(['result'=>'PASS','raw_file'=>true,'missing_prerequisites_stop'=>true,'first_and_repeat'=>true,'scratch_removed'=>true]));echo 'Schema raw SQL first/repeat and missing-prerequisite stop PASS';
}finally{$db->statement('DROP DATABASE `'.$scratch.'`');}
