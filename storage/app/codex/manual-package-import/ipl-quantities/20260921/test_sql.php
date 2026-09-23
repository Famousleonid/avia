<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['127.0.0.1','localhost','::1'],true))throw new RuntimeException('Local DB only');
$server=Illuminate\Support\Facades\DB::connection()->getPdo();
$scratch='avia_qty_audit_20260921_'.bin2hex(random_bytes(4));
$server->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function loadData(string $name):array{return json_decode(file_get_contents(__DIR__.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
function check(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
function raw(PDO $pdo,string $sql):array{
    $stmt=$pdo->query($sql);$rows=[];
    do{if($stmt->columnCount())$rows=array_merge($rows,$stmt->fetchAll(PDO::FETCH_ASSOC));}while($stmt->nextRowset());
    $stmt->closeCursor();return $rows;
}
function result(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function state(PDO $pdo):array{return ['manuals'=>$pdo->query('SELECT * FROM manuals ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),'components'=>$pdo->query('SELECT * FROM components ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];}
function fixture(PDO $pdo,array $source):void{
    check((bool)preg_match('/^avia_qty_audit_20260921_[a-f0-9]{8}$/',$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
    foreach(['components','manuals'] as $t){
        $pdo->exec('DROP TABLE IF EXISTS '.$t);
        $defs=[];
        foreach(array_keys($source[$t][0]) as $c){
            check((bool)preg_match('/^[a-z_]+$/',$c),'Bad column');
            $defs[]='`'.$c.'` '.($c==='id'?'BIGINT PRIMARY KEY':($c==='manual_id'?'BIGINT':'LONGTEXT NULL'));
        }
        $pdo->exec('CREATE TABLE '.$t.'('.implode(',',$defs).') ENGINE=InnoDB');
    }
    $pdo->beginTransaction();
    foreach(['manuals','components'] as $t){
        $cols=array_keys($source[$t][0]);
        $stmt=$pdo->prepare('INSERT INTO '.$t.' (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')');
        foreach($source[$t] as $row)$stmt->execute(array_values($row));
    }
    $pdo->commit();
}
$snapshot=loadData('production.json');$plan=loadData('final-rows.json');$packages=loadData('packages.json');
$out=dirname(__DIR__,6).'/output/sql/ipl-quantities-20260921/';
$sql=file_get_contents($out.$packages[0]['prefix'].'_import.sql');
$verify=file_get_contents($out.$packages[0]['prefix'].'_verify.sql');
$report=['production_modified'=>false,'working_database_modified'=>false,'raw_sql_sha256'=>hash('sha256',$sql),'tests'=>[]];
try{
    fixture($pdo,$snapshot);$baseline=state($pdo);$expected=$baseline;
    $byId=array_column($plan,null,'id');
    foreach($expected['components'] as &$c)if(isset($byId[$c['id']]))$c['units_assy']=$byId[$c['id']]['after'];unset($c);
    check(result(raw($pdo,$sql))==='SUCCESS','Initial import failed');
    check(state($pdo)===$expected,'Unexpected changes to other fields');
    foreach(raw($pdo,$verify) as $r)check($r['result']==='OK','FIG verify failed');
    check(result(raw($pdo,$sql))==='SUCCESS'&&state($pdo)===$expected,'Idempotency failed');
    $report['tests']['raw_import_3130_rows_only_138_quantities_changed_36_figs_verify_repeat']='PASS';
    fixture($pdo,$snapshot);
    foreach(array_slice($packages,1) as $pkg){
        check(result(raw($pdo,file_get_contents($out.$pkg['prefix'].'_import.sql')))==='SUCCESS','Per-manual failed');
        foreach(raw($pdo,file_get_contents($out.$pkg['prefix'].'_verify.sql')) as $r)check($r['result']==='OK','Per-manual verification failed');
    }
    check(state($pdo)===$expected,'Individual result differs');
    $report['tests']['six_individual_manual_files']='PASS';
    $changed=array_values(array_filter($plan,fn($r)=>$r['changed']));$id=$changed[0]['id'];
    $mutations=[
        'changed_pn'=>"UPDATE components SET part_number='CHANGED' WHERE id=$id",
        'changed_ipl'=>"UPDATE components SET ipl_num='999-999' WHERE id=$id",
        'changed_manual'=>"UPDATE manuals SET number='WRONG' WHERE id=95",
        'deleted_part'=>"UPDATE components SET deleted_at='2026-09-21 00:00:00' WHERE id=$id",
        'missing_part'=>"DELETE FROM components WHERE id=$id",
        'unexpected_quantity'=>"UPDATE components SET units_assy='777' WHERE id=$id",
    ];
    foreach($mutations as $name=>$mutation){
        fixture($pdo,$snapshot);$pdo->exec($mutation);$initial=state($pdo);
        check(result(raw($pdo,$sql))==='BLOCKED / ROLLED BACK'&&state($pdo)===$initial,$name.' failed');
        $report['tests'][$name]='PASS';
    }
    fixture($pdo,$snapshot);$initial=state($pdo);
    $broken=preg_replace('/^UPDATE components SET units_assy=.*;\n/m','',$sql,1);
    check(result(raw($pdo,$broken))==='BLOCKED / ROLLED BACK'&&state($pdo)===$initial,'Postflight rollback failed');
    $report['tests']['omitted_update_rolls_back_all']='PASS';
    fixture($pdo,$snapshot);$pdo->prepare('UPDATE components SET units_assy=? WHERE id=?')->execute([$changed[0]['after'],$id]);
    check(result(raw($pdo,$sql))==='SUCCESS'&&state($pdo)===$expected,'Partial retry failed');
    $report['tests']['partial_retry']='PASS';$report['result']='PASS';
}finally{
    $pdo=null;
    if(!preg_match('/^avia_qty_audit_20260921_[a-f0-9]{8}$/',$scratch))throw new RuntimeException('Invalid scratch name');
    $server->exec('DROP DATABASE `'.$scratch.'`');
}
file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
