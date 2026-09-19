<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true)){throw new RuntimeException('Local DB only');}
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$scratch='avia_m41_review_20260919_'.bin2hex(random_bytes(4));
$exists=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$exists->execute([$scratch]);
if((int)$exists->fetchColumn()){throw new RuntimeException('Schema already exists');}
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$rawPdo=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
$snap=json_decode(file_get_contents(__DIR__.'/production-preflight-current.json'),true,512,JSON_THROW_ON_ERROR);$target=$snap['manuals'][0];
function seed(PDO $pdo,string $table,array $rows):void{
    foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}
}
function fixture(PDO $pdo,array $snap,array $target):void{
    if(!preg_match('/^avia_m41_review_20260919_[a-f0-9]{8}$/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn())){throw new RuntimeException('Not scratch');}
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('DROP TABLE IF EXISTS manual_in_process_check_sheets');
    foreach($snap['schema'] as $table=>$rows){
        $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
        if($table==='manuals'){$pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
        // Outside-manual FK parents are deliberately not copied into scratch.
        $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    seed($pdo,'manuals',[['id'=>41,'number'=>'32-11-06','deleted_at'=>null],['id'=>999,'number'=>'UNRELATED','deleted_at'=>null]]);
    seed($pdo,'components',$target['components']);seed($pdo,'component_assemblies',$target['legacy']);
    seed($pdo,'components',[['id'=>99999,'manual_id'=>999,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
}
function splitSql(string $sql):array{
    // Only valid MySQL comments. Never hide invalid --text syntax from server.
    $sql=preg_replace('/^--(?=[\x00-\x20]|$)[^\r\n]*\R?/m','',$sql);$out=[];$buf='';$quote=false;
    for($i=0;$i<strlen($sql);$i++){
        $ch=$sql[$i];if($ch==='\\'&&$quote){$buf.=$ch.($sql[++$i]??'');continue;}
        if($ch==="'"){if($quote&&($sql[$i+1]??'')==="'"){$buf.="''";$i++;continue;}$quote=!$quote;}
        if($ch===';'&&!$quote){if(trim($buf)!==''){$out[]=trim($buf);}$buf='';}else{$buf.=$ch;}
    }if(trim($buf)!==''){$out[]=trim($buf);}return $out;
}
function runSql(PDO $pdo,array $stmts,bool $continue=false):array{
    $out=[];$errors=0;
    foreach($stmts as $n=>$stmt){try{$s=$pdo->query($stmt);if($s){$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));$s->closeCursor();}}
        catch(Throwable $e){if(!$continue){throw new RuntimeException('Statement '.$n.': '.$e->getMessage());}$errors++;}
    }return ['rows'=>$out,'errors'=>$errors];
}
function digest(PDO $pdo):string{
    $out=[];foreach(['manuals','components','manual_part_groups','manual_part_group_options','manual_part_group_coverages','component_assemblies','manual_service_bulletins'] as $t){$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);}
    if($pdo->query("SHOW TABLES LIKE 'manual_in_process_check_sheets'")->fetchColumn()){$out['check_sheet']=$pdo->query('SELECT * FROM manual_in_process_check_sheets ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);}
    return hash('sha256',json_encode($out));
}
function runRawSql(PDO $pdo,string $sql):array{
    // Exact delivered bytes including comments, sent to server without splitting.
    $statement=$pdo->query($sql);$rows=[];
    do{if($statement->columnCount()>0){$rows=array_merge($rows,$statement->fetchAll(PDO::FETCH_ASSOC));}}while($statement->nextRowset());
    $statement->closeCursor();return ['rows'=>$rows,'errors'=>0];
}
function status(array $r):string{$f=array_values(array_filter($r['rows'],fn($x)=>isset($x['final_status'])));return $f[0]['final_status']??'NONE';}
function must(bool $ok,string $message):void{if(!$ok){throw new RuntimeException($message);}}
$rawSql=file_get_contents(__DIR__.'/32-11-06_20260919_v02_import.sql');
$rawVerify=file_get_contents(__DIR__.'/32-11-06_20260919_v02_verify.sql');
$statements=splitSql($rawSql);$verify=splitSql($rawVerify);
$report=['schema'=>$scratch,'production_modified'=>false,'working_database_modified'=>false,'sql_sha256'=>hash('sha256',$rawSql),'verify_sha256'=>hash('sha256',$rawVerify)];
try{
    fixture($pdo,$snap,$target);
    $before=digest($pdo);$reproduced=false;
    try{runRawSql($rawPdo,file_get_contents(__DIR__.'/32-11-06_20260919_v01_import.sql'));}
    catch(PDOException $e){$reproduced=($e->errorInfo[1]??null)===1064;}
    must($reproduced&&$before===digest($pdo),'Original v01 syntax error not reproduced safely');
    $report['v01_regression']=['mysql_error'=>1064,'data_unchanged'=>true];
    must(count(splitSql("--invalid comment\nSELECT 1;"))===1&&str_starts_with(splitSql("--invalid comment\nSELECT 1;")[0],'--invalid'),'Splitter masks invalid comments');
    $first=runRawSql($rawPdo,$rawSql);
    must(status($first)==='SUCCESS','Initial import failed: '.json_encode(array_slice(array_values(array_filter($first['rows'],fn($x)=>($x['result']??'')==='BLOCKED')),0,5)));
    $audit=runRawSql($rawPdo,$rawVerify);foreach($audit['rows'] as $a){must($a['result']==='OK','SELECT audit failure '.json_encode($a));}
    $before=digest($pdo);$repeat=runRawSql($rawPdo,$rawSql);
    must(status($repeat)==='SUCCESS'&&$before===digest($pdo),'Repeat is not byte-identical');
    $report['positive']=['first'=>'SUCCESS','repeat'=>'SUCCESS','byte_identical_repeat'=>true,'select_checks'=>count($audit['rows']),'missing_check_sheet_table_created'=>true,'raw_file_including_comments'=>true,'raw_verify_including_comments'=>true];
    echo 'Import + '.count($audit['rows']).' SELECT checks + idempotent repeat PASS'.PHP_EOL;
    $gs=App\Models\ManualPartGroup::where('manual_id',41)->with('options.coverages')->get()->keyBy('id');$opts=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');
    $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=41')->fetchAll(PDO::FETCH_KEY_PAIR);
    $expected=json_decode(file_get_contents(__DIR__.'/groups-audit.json'),true)['expanded'];
    $resolver=new App\Services\PartGroupCoverageResolver();$method=new ReflectionMethod($resolver,'expandBundleMember');$method->setAccessible(true);$cases=0;
    foreach($gs as $g){if($g->behavior!=='bundle'){continue;}$opt=$g->options->firstWhere('is_default',true);
        foreach(['prl','ndt','cad','stress','paint'] as $scope){foreach([1,2] as $mult){$cov=[];
            foreach($opt->coverages as $member){$args=[&$cov,$member,$mult,$scope,'scratch audit',$g,$opt,$gs,$opts,[(int)$opt->id=>true]];$method->invokeArgs($resolver,$args);}
            $actual=array_map(fn($x)=>$x['covered_qty'],$cov);$want=[];foreach($expected[$g->code] as $ipl=>$qty){$want[$ids[$ipl]]=$qty*$mult;}
            ksort($actual);ksort($want);must($actual===$want,'Resolver mismatch '.$g->code.' '.$scope);$cases++;
        }}
    }
    $report['resolver']=['cases'=>$cases,'result'=>'PASS'];echo $cases.' resolver/scope/quantity cases PASS'.PHP_EOL;
    // Corrupt one existing managed edge. Repeat must neither overwrite nor commit.
    $pdo->exec('UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1');
    $before=digest($pdo);$bad=runSql($pdo,$statements);
    must(str_starts_with(status($bad),'ROLLED BACK')&&$before===digest($pdo),'Modified composition not rejected safely');
    $report['negative']['changed_composition']='ROLLED BACK, byte-identical';
    foreach(['wrong_manual','changed_pn','conflicting_sb','conflicting_check_sheet'] as $scenario){
        fixture($pdo,$snap,$target);
        // DDL is outside transaction; make table first for digest comparison.
        $pdo->exec(file_get_contents(dirname(__DIR__,6).'/output/in-process-check-sheet-20260917/sql/in_process_check_sheet_20260917_v01_schema.sql'));
        if($scenario==='wrong_manual'){$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=41");}
        elseif($scenario==='changed_pn'){$pdo->exec("UPDATE components SET part_number='UNREVIEWED' WHERE id=6230");}
        elseif($scenario==='conflicting_sb'){seed($pdo,'manual_service_bulletins',[['manual_id'=>41,'sort_order'=>0,'ac_mfg_service_bulletin_no'=>'UNREVIEWED','is_active'=>1]]);}
        else{$pdo->exec("INSERT INTO manual_in_process_check_sheets(manual_id,source_file,source_sheet,source_sha256,content_sha256,content,schema_version,created_at,updated_at) VALUES(41,'different.xlsx','IN PROCESS CHECK SHEET',REPEAT('a',64),REPEAT('b',64),'{}',1,NOW(),NOW())");}
        $before=digest($pdo);$bad=runSql($pdo,$statements);
        must(str_starts_with(status($bad),'ROLLED BACK')&&$before===digest($pdo),'Unsafe negative scenario '.$scenario);
        $report['negative'][$scenario]='ROLLED BACK, byte-identical';echo $scenario.' guard PASS'.PHP_EOL;
    }
    fixture($pdo,$snap,$target);
    $pdo->exec('UPDATE components SET kit=1,paint_list=1,log_card=1 WHERE id=6230');
    $flags=runSql($pdo,$statements);must(status($flags)==='SUCCESS','Existing true flags rejected');
    must((int)$pdo->query('SELECT kit+paint_list+log_card FROM components WHERE id=6230')->fetchColumn()===3,'Existing flags cleared');
    must($pdo->query('SELECT part_number FROM components WHERE id=6230')->fetchColumn()==='49120-111','Approved PN correction failed');
    must((int)$pdo->query("SELECT COUNT(*) FROM components WHERE manual_id=41 AND ipl_num IN('0-0ECBush','0-0ECSleeve')")->fetchColumn()===2,'Existing-only parts lost');
    must($pdo->query('SELECT part_number FROM components WHERE id=99999')->fetchColumn()==='UNCHANGED','Unrelated manual changed');
    $report['preservation']='PASS: IDs, true flags, EC-only Parts, legacy, unrelated manual';
    $report['result']='PASS';
}finally{
    $rawPdo=null;
    if(preg_match('/^avia_m41_review_20260919_[a-f0-9]{8}$/',$scratch)&&(string)$pdo->query('SELECT DATABASE()')->fetchColumn()===$scratch){$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;}
    file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
