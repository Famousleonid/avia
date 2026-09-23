<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if (!in_array($cfg['host'],['127.0.0.1','localhost','::1'],true)) throw new RuntimeException('Local DB only');
$server=Illuminate\Support\Facades\DB::connection()->getPdo();
$scratch='avia_kit_audit_20260921_'.bin2hex(random_bytes(4));
$server->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
$snapshot=json_decode(file_get_contents(__DIR__.'/production.json'),true,512,JSON_THROW_ON_ERROR);
$plan=json_decode(file_get_contents(__DIR__.'/plan.json'),true,512,JSON_THROW_ON_ERROR);
function check(bool $ok,string $why):void { if(!$ok) throw new RuntimeException($why); }
function raw(PDO $pdo,string $sql):array {
    $stmt=$pdo->query($sql);$rows=[];
    do {if($stmt->columnCount())$rows=array_merge($rows,$stmt->fetchAll(PDO::FETCH_ASSOC));}while($stmt->nextRowset());
    $stmt->closeCursor(); return $rows;
}
function result(array $rows):string {foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function state(PDO $pdo):array{return ['manuals'=>$pdo->query('SELECT * FROM manuals ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'components'=>$pdo->query('SELECT * FROM components ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];}
function fixture(PDO $pdo,array $source):void {
    check((bool)preg_match('/^avia_kit_audit_20260921_[a-f0-9]{8}$/',$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
    foreach(['components','manuals'] as $table)$pdo->exec('DROP TABLE IF EXISTS '.$table);
    $pdo->exec('CREATE TABLE manuals(id BIGINT PRIMARY KEY,number VARCHAR(255),title VARCHAR(255),deleted_at VARCHAR(30) NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE components(id BIGINT PRIMARY KEY,manual_id BIGINT,ipl_num VARCHAR(255),part_number VARCHAR(255),name VARCHAR(255),kit TINYINT,ndt_list TINYINT,cad_list TINYINT,paint_list TINYINT,stress_relief_list TINYINT,log_card TINYINT,deleted_at VARCHAR(30) NULL,updated_at VARCHAR(30) NULL) ENGINE=InnoDB');
    $pdo->beginTransaction();
    foreach(['manuals','components'] as $table)foreach($source[$table] as $row){
        $sql='INSERT INTO '.$table.' (`'.implode('`,`',array_keys($row)).'`) VALUES ('.implode(',',array_fill(0,count($row),'?')).')';
        $pdo->prepare($sql)->execute(array_values($row));
    }
    $pdo->commit();
}
$prefix=__DIR__.'/kit_explicit_code_20260921_v01_';
$sql=file_get_contents($prefix.'import.sql');$verify=file_get_contents($prefix.'verify.sql');$undo=file_get_contents($prefix.'undo.sql');
$report=['production_modified'=>false,'working_database_modified'=>false,'raw_sql_sha256'=>hash('sha256',$sql),'tests'=>[]];
try {
    fixture($pdo,$snapshot);$before=state($pdo);$expected=$before;
    $ids=array_column(array_column($plan['changes'],'component'),'id');
    foreach($expected['components'] as &$c)if(in_array((int)$c['id'],$ids,true))$c['kit']=0;unset($c);
    check(result(raw($pdo,$sql))==='SUCCESS','Initial failed');
    check(state($pdo)===$expected,'Unexpected change beyond four kit flags');
    foreach(raw($pdo,$verify) as $r)check($r['result']==='OK','SELECT failed');
    check(result(raw($pdo,$sql))==='SUCCESS'&&state($pdo)===$expected,'Repeat failed');
    check(result(raw($pdo,$undo))==='SUCCESS'&&state($pdo)===$before,'Undo failed');
    $report['tests']['combined_raw_import_exact_preservation_verify_repeat_undo']='PASS';
    foreach($plan['packages'] as $pkg){
        check(result(raw($pdo,file_get_contents(__DIR__.'/'.$pkg['prefix'].'_import.sql')))==='SUCCESS','Single manual failed');
        foreach(raw($pdo,file_get_contents(__DIR__.'/'.$pkg['prefix'].'_verify.sql')) as $r)check($r['result']==='OK','Single verify failed');
    }
    check(state($pdo)===$expected,'Separate files differ from combined');
    $report['tests']['separate_manual_files']='PASS';
    $mutations=[
        'changed_pn'=>"UPDATE components SET part_number='CHANGED' WHERE id=5283",
        'changed_ipl'=>"UPDATE components SET ipl_num='1-999' WHERE id=5284",
        'changed_manual'=>"UPDATE manuals SET number='WRONG' WHERE id=91",
        'deleted_part'=>"UPDATE components SET deleted_at='2026-09-21 00:00:00' WHERE id=2556",
        'missing_part'=>'DELETE FROM components WHERE id=5285',
        'unexpected_kit'=>'UPDATE components SET kit=2 WHERE id=5283',
    ];
    foreach($mutations as $name=>$mutation){
        fixture($pdo,$snapshot);$pdo->exec($mutation);$initial=state($pdo);
        check(result(raw($pdo,$sql))==='BLOCKED / ROLLED BACK'&&state($pdo)===$initial,$name.' did not block unchanged');
        $report['tests'][$name]='PASS';
    }
    fixture($pdo,$snapshot);$initial=state($pdo);
    $broken=preg_replace('/^UPDATE components SET kit=0.*;\n/m','',$sql);
    check(result(raw($pdo,$broken))==='BLOCKED / ROLLED BACK'&&state($pdo)===$initial,'Omitted update not detected');
    $report['tests']['omitted_update']='PASS';
    fixture($pdo,$snapshot);$pdo->exec('UPDATE components SET kit=0 WHERE id=5283');
    check(result(raw($pdo,$sql))==='SUCCESS'&&state($pdo)===$expected,'Partially applied retry failed');
    $report['tests']['partial_retry']='PASS';
    $report['result']='PASS';
} finally {
    $pdo=null;
    if(!preg_match('/^avia_kit_audit_20260921_[a-f0-9]{8}$/',$scratch))throw new RuntimeException('Invalid scratch name');
    $server->exec('DROP DATABASE `'.$scratch.'`');
}
file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
