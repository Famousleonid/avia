<?php
// Isolated, randomly named local scratch schema, created here and removed in finally.
// The application schema and production are never modified.
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true)){throw new RuntimeException('Not a loopback database');}
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
$scratch='avia_m78_review_20260917_'.bin2hex(random_bytes(4));
if(!preg_match('/^avia_m78_review_20260917_[a-f0-9]{8}$/',$scratch)){throw new RuntimeException('Invalid scratch name');}
$stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$stmt->execute([$scratch]);
if((int)$stmt->fetchColumn()!==0){throw new RuntimeException('Scratch schema already exists');}
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$r=__DIR__;
function readJson(string $name):array{return json_decode(file_get_contents(__DIR__.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
$metadata=readJson('production-final-metadata.json');$snapshot=readJson('production-groups.json');$target=readJson('production-target.json')['manuals'][0];
function seed(PDO $pdo,string $table,array $rows):void{
 foreach($rows as $row){
  foreach($row as $k=>&$v){if(is_array($v)){$v=json_encode($v,JSON_THROW_ON_ERROR);}elseif(is_bool($v)){$v=(int)$v;}elseif(is_string($v)&&preg_match('/^\d{4}-\d{2}-\d{2}T/',$v)){$v=str_replace(['T','Z'],[' ',''],$v);}}unset($v);
  $cols=array_keys($row);$sql='INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
  $pdo->prepare($sql)->execute(array_values($row));
 }
}
function fixture(PDO $pdo,array $metadata,array $snapshot,array $target):void{
 foreach($metadata['schema'] as $table=>$ddlRows){
  $active=$pdo->query('SELECT DATABASE()')->fetchColumn();
  if(!preg_match('/^avia_m78_review_20260917_[a-f0-9]{8}$/',(string)$active)){throw new RuntimeException('Fixture is not in the scratch schema');}
  $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
  if($table==='manuals'){$pdo->exec('CREATE TABLE manuals(id BIGINT PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=$ddlRows[0]['Create Table'];
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$ddl);
  $ddl=preg_replace('/,\n\)/',"\n)",$ddl);
  $pdo->exec($ddl);
 }
 seed($pdo,'manuals',[['id'=>78,'number'=>'32-11-15RM','deleted_at'=>null]]);
 seed($pdo,'components',array_map(fn($p)=>$p+['manual_id'=>78],$metadata['components']));
 seed($pdo,'manual_service_bulletins',$target['service_bulletins']);
 seed($pdo,'component_assemblies',$snapshot['legacy']);
 foreach($snapshot['groups'] as $g){$opts=$g['options'];unset($g['options']);seed($pdo,'manual_part_groups',[$g]);foreach($opts as $o){$covers=$o['coverages'];unset($o['coverages']);seed($pdo,'manual_part_group_options',[$o]);seed($pdo,'manual_part_group_coverages',$covers);}}
}
function splitSql(string $sql):array{
 $sql=preg_replace('/^--[^\r\n]*\R?/m','',$sql);
 $out=[];$buf='';$quoted=false;$len=strlen($sql);
 for($i=0;$i<$len;$i++){
  $ch=$sql[$i];
  if($ch==='\\'&&$quoted){$buf.=$ch.($sql[++$i]??'');continue;}
  if($ch==="'"){
   if($quoted&&($sql[$i+1]??'')==="'"){$buf.="''";$i++;continue;}
   $quoted=!$quoted;
  }
  if($ch===';'&&!$quoted){if(trim($buf)!==''){$out[]=trim($buf);}$buf='';}else{$buf.=$ch;}
 }
 if(trim($buf)!==''){$out[]=trim($buf);}return $out;
}
function digest(PDO $pdo):string{
 $all=[];foreach(['components','manual_part_groups','manual_part_group_options','manual_part_group_coverages','manual_service_bulletins','component_assemblies'] as $t){$all[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);}return hash('sha256',json_encode($all));
}
function runSql(PDO $pdo,string $sql,bool $continueErrors=false):array{
 $results=[];$errors=[];
 foreach(splitSql($sql) as $n=>$s){
  try{$stmt=$pdo->query($s);if($stmt){$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);if($rows){$results=array_merge($results,$rows);}$stmt->closeCursor();}}
  catch(Throwable $e){$errors[]=['statement'=>$n,'error'=>$e->getMessage(),'sql'=>substr($s,0,200)];if(!$continueErrors){throw new RuntimeException(json_encode($errors,JSON_THROW_ON_ERROR));}}
 }
 return ['results'=>$results,'errors'=>$errors];
}
$sql=file_get_contents($r.'/32-11-15RM_20260917_v01_import.sql');$verify=file_get_contents($r.'/32-11-15RM_20260917_v01_verify.sql');$report=[];
try{
 fixture($pdo,$metadata,$snapshot,$target);
 $first=runSql($pdo,$sql);$final=array_values(array_filter($first['results'],fn($x)=>isset($x['final_status'])));
 if(($final[0]['final_status']??null)!=='SUCCESS'){throw new RuntimeException('First import failed: '.json_encode($first));}
 $verifyResult=runSql($pdo,$verify);
 $failedChecks=array_filter($verifyResult['results'],fn($x)=>isset($x['audit_check'])&&$x['result']!=='OK');
 if($failedChecks){throw new RuntimeException('Verification failed: '.json_encode($failedChecks));}
 echo "First import and standalone SELECT verification passed.\n";
 $before=digest($pdo);$second=runSql($pdo,$sql);$after=digest($pdo);
 if($before!==$after){throw new RuntimeException('Second import changed data');}
 $report['positive']=['first'=>'SUCCESS','second'=>'SUCCESS','idempotent'=>true,'verify'=>$verifyResult['results']];
 // Exercise the application resolver on the imported nested graph for all five forms.
 $dbGroups=App\Models\ManualPartGroup::where('manual_id',78)->with('options.coverages')->get()->keyBy('id');
 $options=$dbGroups->flatMap(fn($g)=>$g->options)->keyBy('id');
 $expected=readJson('groups-final.json');$graph=[];foreach($expected as $g){$graph[$g['code']]=$g;}
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=78 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $flatten=function(array $g,int $qty=1)use(&$flatten,$graph,$ids):array{
  $out=[];$opt=current(array_filter($g['options'],fn($o)=>$o['default']));
  foreach($opt['coverages'] as $c){
   $n=$qty*$c['qty'];
   if(isset($c['ipl'])){$id=$ids[$c['ipl']];$out[$id]=($out[$id]??0)+$n;}
   else{$child=$graph[$c['child']];if(in_array($child['type'],['oversize','alternative_pn'],true)){foreach($child['options'] as $o){$id=$ids[$o['ipl']];$out[$id]=($out[$id]??0)+$n;}}else{foreach($flatten($child,$n) as $id=>$amount){$out[$id]=($out[$id]??0)+$amount;}}}
  }return $out;
 };
 $resolver=new App\Services\PartGroupCoverageResolver();$method=new ReflectionMethod($resolver,'expandBundleMember');$method->setAccessible(true);$tested=0;
 foreach($dbGroups as $g){if($g->behavior!=='bundle'){continue;}$opt=$g->options->firstWhere('is_default',true);
  foreach(['prl','ndt','cad','stress','paint'] as $scope){$coverage=[];
   foreach($opt->coverages as $member){$args=[&$coverage,$member,1,$scope,'test',$g,$opt,$dbGroups,$options,[(int)$opt->id=>true]];$method->invokeArgs($resolver,$args);}
   $actual=array_map(fn($c)=>$c['covered_qty'],$coverage);$wanted=$flatten($graph[$g->code]);ksort($actual);ksort($wanted);
   if($actual!==$wanted){throw new RuntimeException('Resolver mismatch '.$g->code.' '.$scope);}$tested++;
  }
 }
 $report['resolver']=['bundle_scope_cases'=>$tested,'all'=>'PASS'];echo "Resolver passed {$tested} bundle/form cases.\n";
 foreach(['wrong_pn','wrong_manual','unexpected_coverage','missing_old_id','postflight_corruption','continue_after_dml_error'] as $case){
  fixture($pdo,$metadata,$snapshot,$target);$testSql=$sql;
  if($case==='wrong_pn'){$pdo->exec("UPDATE components SET part_number='TEST_WRONG_PN' WHERE id=8839");}
  if($case==='wrong_manual'){$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=78");}
  if($case==='unexpected_coverage'){$pdo->exec('UPDATE manual_part_group_coverages SET qty=123 WHERE id=(SELECT n FROM (SELECT MIN(id) n FROM manual_part_group_coverages) v)');}
  if($case==='missing_old_id'){$pdo->exec('DELETE FROM components WHERE id=3027');}
  if($case==='postflight_corruption'){$testSql=str_replace('-- POSTFLIGHT START',"UPDATE components SET units_assy='999' WHERE id=3027;\n-- POSTFLIGHT START",$testSql);}
  if($case==='continue_after_dml_error'){$testSql=str_replace('INSERT INTO components (manual_id','INSERT INTO deliberately_missing_table (manual_id',$testSql);}
  $before=digest($pdo);$res=runSql($pdo,$testSql,$case==='continue_after_dml_error');$after=digest($pdo);
  $final=array_values(array_filter($res['results'],fn($x)=>isset($x['final_status'])));
  if($before!==$after||str_starts_with($final[0]['final_status']??'','SUCCESS')){throw new RuntimeException('Safety case failed '.$case);}
  $report[$case]=['unchanged'=>true,'result'=>$final[0]['final_status']??null,'simulated_errors'=>count($res['errors'])];
  echo $case." passed.\n";
 }
 file_put_contents($r.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
 echo json_encode(['tests'=>'PASS','cases'=>array_keys($report),'isolated_scratch_schema'=>true],JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}fwrite(STDERR,$e->getMessage().PHP_EOL);$failed=true;}
finally{
 if($pdo->query('SELECT DATABASE()')->fetchColumn()!==$scratch){throw new RuntimeException('Unexpected database before scratch cleanup');}
 $pdo->exec('DROP DATABASE `'.$scratch.'`');
 echo "Removed only the generated scratch schema; application DB unchanged.\n";
}
if(isset($failed)){exit(1);}
