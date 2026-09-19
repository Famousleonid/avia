<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true)){throw new RuntimeException('Local DB only');}
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$scratch='avia_m40_review_20260918_'.bin2hex(random_bytes(4));
$exists=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$exists->execute([$scratch]);
if((int)$exists->fetchColumn()){throw new RuntimeException('Schema exists');}
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$snap=json_decode(file_get_contents(dirname(__DIR__,2).'/32-11-10/20260918-full/production-preflight.json'),true,512,JSON_THROW_ON_ERROR);
$target=json_decode(file_get_contents(__DIR__.'/target-fresh.json'),true,512,JSON_THROW_ON_ERROR);
function seed(PDO $pdo,string $table,array $rows):void{
 foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}
}
function fixture(PDO $pdo,array $snap,array $target,bool $checkSheet=false):void{
 if(!preg_match('/^avia_m40_review_20260918_[a-f0-9]{8}$/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn())){throw new RuntimeException('Not scratch');}
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 $pdo->exec('DROP TABLE IF EXISTS manual_in_process_check_sheets');
 foreach($snap['schema'] as $table=>$rows){
  $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
  if($table==='manuals'){$pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);
  $ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);
 }
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 seed($pdo,'manuals',[['id'=>40,'number'=>'32-11-10','deleted_at'=>null],['id'=>59,'number'=>'UNRELATED','deleted_at'=>null]]);
 seed($pdo,'components',$target['components']);seed($pdo,'manual_service_bulletins',$target['service_bulletins']);seed($pdo,'component_assemblies',$target['legacy']);
 seed($pdo,'components',[['id'=>99999,'manual_id'=>59,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
 if($checkSheet){$pdo->exec(file_get_contents(dirname(__DIR__,6).'/output/in-process-check-sheet-20260917/sql/in_process_check_sheet_20260917_v01_schema.sql'));}
}
function splitSql(string $sql):array{
 $sql=preg_replace('/^--[^\r\n]*\R?/m','',$sql);$out=[];$buf='';$quote=false;
 for($i=0;$i<strlen($sql);$i++){
  $ch=$sql[$i];if($ch==='\\'&&$quote){$buf.=$ch.($sql[++$i]??'');continue;}
  if($ch==="'"){if($quote&&($sql[$i+1]??'')==="'"){$buf.="''";$i++;continue;}$quote=!$quote;}
  if($ch===';'&&!$quote){if(trim($buf)!==''){$out[]=trim($buf);}$buf='';}else{$buf.=$ch;}
 }if(trim($buf)!==''){$out[]=trim($buf);}return $out;
}
function runSql(PDO $pdo,string $sql,bool $continue=false):array{
 $out=[];$errors=0;
 foreach(splitSql($sql) as $n=>$stmt){try{$s=$pdo->query($stmt);if($s){$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));$s->closeCursor();}}
 catch(Throwable $e){if(!$continue){throw new RuntimeException('Statement '.$n.': '.$e->getMessage());}$errors++;}}
 return ['rows'=>$out,'errors'=>$errors];
}
function digest(PDO $pdo):string{
 $out=[];foreach(['components','manual_part_groups','manual_part_group_options','manual_part_group_coverages','component_assemblies','manual_service_bulletins'] as $t){$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);}
 if($pdo->query("SHOW TABLES LIKE 'manual_in_process_check_sheets'")->fetchColumn()){$out['check_sheet']=$pdo->query('SELECT * FROM manual_in_process_check_sheets ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);}
 return hash('sha256',json_encode($out));
}
function result(array $r):string{$f=array_values(array_filter($r['rows'],fn($x)=>isset($x['final_status'])));return $f[0]['final_status']??'NONE';}
$sql=file_get_contents(__DIR__.'/32-11-10_20260918_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-11-10_20260918_v01_verify.sql');$report=[];
try{
 fixture($pdo,$snap,$target);
 $first=runSql($pdo,$sql);if(result($first)!=='SUCCESS'){throw new RuntimeException('Import failed '.json_encode(array_filter($first['rows'],fn($x)=>($x['result']??'')==='BLOCKED')));}
 $audit=runSql($pdo,$verify);foreach($audit['rows'] as $a){if($a['result']!=='OK'){throw new RuntimeException('Audit failure '.json_encode($a));}}
 $before=digest($pdo);$second=runSql($pdo,$sql);
 if(result($second)!=='SUCCESS'||$before!==digest($pdo)){throw new RuntimeException('Not idempotent');}
 $report['positive']=['first'=>'SUCCESS','repeat'=>'SUCCESS','byte_identical_repeat'=>true,'select_checks'=>count($audit['rows']),'missing_table_created'=>true];
 echo 'Import, SELECT audit and repeat passed.'.PHP_EOL;
 $dbGroups=App\Models\ManualPartGroup::where('manual_id',40)->with('options.coverages')->get()->keyBy('id');$opts=$dbGroups->flatMap(fn($g)=>$g->options)->keyBy('id');
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=40 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $expected=json_decode(file_get_contents(__DIR__.'/groups-audit.json'),true)['expanded'];
 $resolver=new App\Services\PartGroupCoverageResolver();$method=new ReflectionMethod($resolver,'expandBundleMember');$method->setAccessible(true);$cases=0;
 foreach($dbGroups as $g){if($g->behavior!=='bundle'){continue;}$opt=$g->options->firstWhere('is_default',true);
  foreach(['prl','ndt','cad','stress','paint'] as $scope){foreach([1,2] as $mult){$cov=[];
   foreach($opt->coverages as $member){$args=[&$cov,$member,$mult,$scope,'test',$g,$opt,$dbGroups,$opts,[(int)$opt->id=>true]];$method->invokeArgs($resolver,$args);}
   $actual=array_map(fn($x)=>$x['covered_qty'],$cov);$want=[];foreach($expected[$g->code] as $ipl=>$n){$want[$ids[$ipl]]=$n*$mult;}
   ksort($actual);ksort($want);if($actual!==$want){throw new RuntimeException('Resolver mismatch '.$g->code.' '.$scope);}$cases++;
  }}
 }
 $report['resolver']=['cases'=>$cases,'result'=>'PASS'];echo 'Resolver passed '.$cases.' assembly/scope/quantity cases.'.PHP_EOL;
 fixture($pdo,$snap,$target,true);
 $pdo->exec('UPDATE components SET log_card=1,paint_list=1,kit=1 WHERE id=5608');
 $retained=runSql($pdo,$sql);
 if(result($retained)!=='SUCCESS'||(int)$pdo->query('SELECT log_card+paint_list+kit FROM components WHERE id=5608')->fetchColumn()!==3){throw new RuntimeException('Existing true flags lost');}
 $report['additional_true_flags_preserved']=['result'=>'PASS','flags'=>['log_card','paint_list','kit']];
 echo 'Pre-existing LC/Paint/KIT preserved.'.PHP_EOL;
 foreach(['wrong_manual','changed_pn','missing_id','foreign_code','template_conflict','postflight_corruption','continue_after_dml_error'] as $case){
  fixture($pdo,$snap,$target,true);$candidate=$sql;
  if($case==='wrong_manual'){$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=40");}
  if($case==='changed_pn'){$pdo->exec("UPDATE components SET part_number='WRONG' WHERE id=5608");}
  if($case==='missing_id'){$pdo->exec('DELETE FROM components WHERE id=5608');}
  if($case==='foreign_code'){$pdo->exec("INSERT INTO manual_part_groups(manual_id,code,name,type,behavior) VALUES(59,'MPG-M40-F1-ASSY-1','foreign','assy','bundle')");}
  if($case==='template_conflict'){$pdo->exec("INSERT INTO manual_in_process_check_sheets(manual_id,source_file,source_sheet,source_sha256,content_sha256,content) VALUES(40,'different','different',REPEAT('a',64),REPEAT('b',64),'{}')");}
  if($case==='postflight_corruption'){$candidate=str_replace('-- POSTFLIGHT START',"UPDATE components SET units_assy='999' WHERE id=5608;\n-- POSTFLIGHT START",$candidate);}
  if($case==='continue_after_dml_error'){$candidate=str_replace('INSERT INTO components (','INSERT INTO deliberately_missing_table (',$candidate);}
  $before=digest($pdo);$res=runSql($pdo,$candidate,$case==='continue_after_dml_error');
  if(result($res)==='SUCCESS'||$before!==digest($pdo)){throw new RuntimeException('Safety failed '.$case);}
  $report[$case]=['unchanged'=>true,'result'=>result($res),'errors'=>$res['errors']];echo $case.' passed.'.PHP_EOL;
 }
 $report['sql_sha256']=hash('sha256',$sql);$report['verify_sha256']=hash('sha256',$verify);
 $report['local_server_version']=$pdo->query('SELECT VERSION()')->fetchColumn();
 file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo 'ALL PASS'.PHP_EOL;
}catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}fwrite(STDERR,$e->getMessage().PHP_EOL);$failed=true;}
finally{
 if($pdo->query('SELECT DATABASE()')->fetchColumn()!==$scratch){throw new RuntimeException('Unexpected schema during cleanup');}
 $pdo->exec('DROP DATABASE `'.$scratch.'`');echo 'Only generated scratch schema removed. Application DB unchanged.'.PHP_EOL;
}
if(isset($failed)){exit(1);}
