<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true)){throw new RuntimeException('Local DB only');}
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$scratch='avia_m42_review_20260921_'.bin2hex(random_bytes(4));
$s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$s->execute([$scratch]);
if((int)$s->fetchColumn()){throw new RuntimeException('Scratch already exists');}
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$pdo->exec('USE `'.$scratch.'`');
$rawPdo=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function loadJson(string $n):array{return json_decode(file_get_contents(__DIR__.'/'.$n),true,512,JSON_THROW_ON_ERROR);}
function seed(PDO $db,string $table,array $rows):void{
 foreach($rows as $r){unset($r['active_ipl_num']);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_fill(0,count($r),'?')).')')->execute(array_values($r));}
}
function fixture(PDO $db,array $target):void{
 if(!preg_match('/^avia_m42_review_20260921_[a-f0-9]{8}$/',(string)$db->query('SELECT DATABASE()')->fetchColumn()))throw new RuntimeException('Not scratch');
 $db->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach(loadJson('production-schema.json')['schema'] as $table=>$rows){
  $db->exec('DROP TABLE IF EXISTS `'.$table.'`');
  if($table==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
 }
 $db->exec('SET FOREIGN_KEY_CHECKS=1');
 seed($db,'manuals',[['id'=>42,'number'=>'32-21-02','deleted_at'=>null],['id'=>999,'number'=>'UNRELATED','deleted_at'=>null]]);
 foreach(['components'=>'components','groups'=>'manual_part_groups','options'=>'manual_part_group_options','coverages'=>'manual_part_group_coverages','legacy'=>'component_assemblies','service_bulletins'=>'manual_service_bulletins','check_sheet'=>'manual_in_process_check_sheets'] as $k=>$table)seed($db,$table,$target[$k]);
 seed($db,'components',[['id'=>99999,'manual_id'=>999,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
}
function raw(PDO $db,string $sql):array{
 $s=$db->query($sql);$rows=[];do{if($s->columnCount()>0)$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $rows;
}
function status(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function digest(PDO $db):string{
 $a=[];foreach(array_keys(loadJson('production-schema.json')['schema']) as $t)$a[$t]=$db->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($a));
}
function must(bool $ok,string $m):void{if(!$ok)throw new RuntimeException($m);}
$sql=file_get_contents(__DIR__.'/32-21-02_20260921_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-21-02_20260921_v01_verify.sql');
$target=loadJson('production.json')['manual'];$report=['schema'=>$scratch,'production_modified'=>false,'working_database_modified'=>false,'sql_sha256'=>hash('sha256',$sql),'verify_sha256'=>hash('sha256',$verify),'result'=>'NOT PASSED'];
try{
 fixture($pdo,$target);$first=raw($rawPdo,$sql);
 must(status($first)==='SUCCESS','Initial import failed: '.json_encode(array_slice(array_values(array_filter($first,fn($x)=>($x['result']??'')==='BLOCKED')),0,4)));
 $audit=raw($rawPdo,$verify);foreach($audit as $a)must($a['result']==='OK','Audit failed '.json_encode($a));
 $before=digest($pdo);$again=raw($rawPdo,$sql);must(status($again)==='SUCCESS'&&$before===digest($pdo),'Not idempotent');
 $report['positive']=['first'=>'SUCCESS','repeat'=>'SUCCESS','byte_identical_repeat'=>true,'raw_file_and_comments'=>true,'select_checks'=>count($audit)];
 echo 'Raw import, '.count($audit).' SELECT checks and identical repeat PASS'.PHP_EOL;
 $gs=App\Models\ManualPartGroup::where('manual_id',42)->with('options.coverages')->get()->keyBy('id');$opts=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=42 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $expected=loadJson('groups-audit.json')['expanded'];$resolver=new App\Services\PartGroupCoverageResolver();
 $families=$resolver->bundleIplFamiliesForManuals([42],$gs);$method=new ReflectionMethod($resolver,'expandBundleMember');$method->setAccessible(true);$cases=0;
 foreach($gs as $g){if($g->behavior!=='bundle')continue;$opt=$g->options->firstWhere('is_default',true);
  foreach(['prl','ndt','cad','stress','paint'] as $scope)foreach([1,2] as $mult){$cov=[];
   foreach($opt->coverages as $member){$args=[&$cov,$member,$mult,$scope,'scratch audit',$g,$opt,$gs,$opts,[(int)$opt->id=>true],$families];$method->invokeArgs($resolver,$args);}
   $actual=array_map(fn($x)=>$x['covered_qty'],$cov);$want=[];foreach($expected[$g->code] as $ipl=>$qty)$want[$ids[$ipl]]=$qty*$mult;
   ksort($actual);ksort($want);must($actual===$want,'Coverage mismatch '.$g->code.' '.$scope.' '.json_encode(['actual_only'=>array_diff_assoc($actual,$want),'wanted_only'=>array_diff_assoc($want,$actual)]));$cases++;
  }
 }
 $report['coverage_runtime']=['cases'=>$cases,'result'=>'PASS'];echo $cases.' actual resolver/scope/quantity cases PASS'.PHP_EOL;
 $composition=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($gs);$compositionCases=0;
 foreach($gs as $g){if($g->behavior!=='bundle')continue;
  $actual=$composition[$g->id]->sort()->values()->all();$want=array_map(fn($ipl)=>(int)$ids[$ipl],array_keys($expected[$g->code]));sort($want);
  must($actual===$want,'Log Card composition mismatch '.$g->code);$compositionCases++;
 }
 $report['log_card_composition']=['cases'=>$compositionCases,'result'=>'PASS'];echo $compositionCases.' Log Card composition cases PASS'.PHP_EOL;
 $pdo->exec('UPDATE manual_part_group_coverages SET qty=qty+1 WHERE id=(SELECT x.id FROM (SELECT c.id FROM manual_part_group_coverages c JOIN manual_part_group_options o ON o.id=c.manual_part_group_option_id WHERE o.manual_part_group_id<>264 ORDER BY c.id LIMIT 1) x)');
 $before=digest($pdo);$bad=raw($rawPdo,$sql);must(str_starts_with(status($bad),'ROLLED BACK')&&$before===digest($pdo),'Changed composition not rejected');
 $report['negative']['changed_managed_composition']='ROLLED BACK, unchanged';
 foreach(['wrong_manual','changed_pn','changed_cam','changed_sb','conflicting_check_sheet'] as $case){
  fixture($pdo,$target);
  if($case==='wrong_manual')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=42");
  if($case==='changed_pn')$pdo->exec("UPDATE components SET part_number='UNREVIEWED' WHERE id=6490");
  if($case==='changed_cam')$pdo->exec("UPDATE manual_part_groups SET name='UNREVIEWED' WHERE id=264");
  if($case==='changed_sb')$pdo->exec("UPDATE manual_service_bulletins SET description='UNREVIEWED' WHERE manual_id=42 ORDER BY id LIMIT 1");
  if($case==='conflicting_check_sheet')$pdo->exec("INSERT INTO manual_in_process_check_sheets(manual_id,source_file,source_sheet,source_sha256,content_sha256,content,schema_version) VALUES(42,'different.xlsx','IN PROCESS CHECK SHEET',REPEAT('a',64),REPEAT('b',64),'{}',1)");
  $before=digest($pdo);$bad=raw($rawPdo,$sql);must(str_starts_with(status($bad),'ROLLED BACK')&&$before===digest($pdo),'Unsafe '.$case);$report['negative'][$case]='ROLLED BACK, unchanged';echo $case.' PASS'.PHP_EOL;
 }
 fixture($pdo,$target);$pdo->exec('UPDATE components SET kit=1,paint_list=1,log_card=1 WHERE id=6490');
 must(status(raw($rawPdo,$sql))==='SUCCESS','Additional true flags blocked');
 must((int)$pdo->query('SELECT kit+paint_list+log_card FROM components WHERE id=6490')->fetchColumn()===3,'Flags lost');
 must($pdo->query('SELECT part_number FROM components WHERE id=6490')->fetchColumn()==='170-73808-901','ID-preserving PN fix failed');
 must($pdo->query('SELECT part_number FROM components WHERE id=99999')->fetchColumn()==='UNCHANGED','Unrelated manual changed');
 foreach(['1-415A','1-415B','1-451B'] as $ipl)must((int)$pdo->query("SELECT kit FROM components WHERE manual_id=42 AND ipl_num='$ipl' AND deleted_at IS NULL")->fetchColumn()===1,'Tie Rap flag missing '.$ipl);
 $report['preservation']='PASS: IDs, true flags, legacy, Cam, extra1-451B, archive, SB, unrelated manual';$report['result']='PASS';
}finally{
 $rawPdo=null;
 if(preg_match('/^avia_m42_review_20260921_[a-f0-9]{8}$/',$scratch)&&(string)$pdo->query('SELECT DATABASE()')->fetchColumn()===$scratch){$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;}
 file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
