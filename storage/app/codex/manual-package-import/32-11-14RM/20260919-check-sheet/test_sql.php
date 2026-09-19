<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local DB only');
$admin=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$scratch='avia_m95_checksheet_'.bin2hex(random_bytes(4));
$exists=$admin->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$exists->execute([$scratch]);
if((int)$exists->fetchColumn())throw new RuntimeException('Scratch exists');
$admin->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo=null;
function must(bool $v,string $message):void{if(!$v)throw new RuntimeException($message);}
function runRaw(PDO $p,string $sql):array{
 $q=$p->query($sql);$rows=[];
 do{if($q->columnCount())$rows=array_merge($rows,$q->fetchAll(PDO::FETCH_ASSOC));}while($q->nextRowset());
 $q->closeCursor();return $rows;
}
function finalStatus(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function digest(PDO $p):string{return hash('sha256',json_encode($p->query('SELECT * FROM manual_in_process_check_sheets ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));}
$prefix=__DIR__.'/32-11-14RM_20260919_v01_check_sheet';
$sql=file_get_contents($prefix.'_import.sql');$verify=file_get_contents($prefix.'_verify.sql');
$expected=json_decode(file_get_contents($prefix.'_source.json'),true,512,JSON_THROW_ON_ERROR);
$report=['production_modified'=>false,'working_database_modified'=>false,'raw_file_including_comments'=>true,'sql_sha256'=>hash('sha256',$sql),'verify_sha256'=>hash('sha256',$verify)];
try{
 $pdo=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
 $pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec("INSERT INTO manuals VALUES(95,'32-11-14RM',NULL),(999,'UNRELATED',NULL)");
 runRaw($pdo,file_get_contents(dirname(__DIR__,3).'/in-process-check-sheet/20260917/in_process_check_sheet_20260917_v01_schema.sql'));
 must(!preg_match('/^\s*--\S/m',$sql),'Malformed comment');
 must(finalStatus(runRaw($pdo,$sql))==='SUCCESS','First import');
 must(runRaw($pdo,$verify)[0]['check_sheet_verification']==='PASS','Exact verification');
 $actual=json_decode($pdo->query('SELECT content FROM manual_in_process_check_sheets WHERE manual_id=95')->fetchColumn(),true);
 must($actual==$expected,'JSON differs from extracted source');
 $before=digest($pdo);must(finalStatus(runRaw($pdo,$sql))==='SUCCESS'&&digest($pdo)===$before,'Repeat changed rows');
 $report['first']='SUCCESS';$report['repeat']='SUCCESS, byte-identical';$report['verify']='PASS';
 foreach(['wrong_number','deleted_manual','conflicting_template'] as $scenario){
  if($scenario==='wrong_number')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=95");
  if($scenario==='deleted_manual')$pdo->exec("UPDATE manuals SET deleted_at='2026-09-19 00:00:00' WHERE id=95");
  if($scenario==='conflicting_template')$pdo->exec("UPDATE manual_in_process_check_sheets SET content_sha256=REPEAT('a',64),content='{}' WHERE manual_id=95");
  $before=digest($pdo);must(finalStatus(runRaw($pdo,$sql))==='BLOCKED / ROLLED BACK'&&digest($pdo)===$before,'Unsafe '.$scenario);
  $report['negative'][$scenario]='BLOCKED, unchanged';
  $pdo->exec("UPDATE manuals SET number='32-11-14RM',deleted_at=NULL WHERE id=95");
 }
 must((int)$pdo->query('SELECT COUNT(*) FROM manual_in_process_check_sheets')->fetchColumn()===1,'Unexpected template');
 $report['result']='PASS';
}finally{
 $pdo=null;
 if(!preg_match('/^avia_m95_checksheet_[a-f0-9]{8}$/',$scratch))throw new RuntimeException('Invalid scratch');
 $admin->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;
}
file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
