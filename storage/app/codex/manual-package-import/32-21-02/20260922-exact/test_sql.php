<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local DB only');
$db=Illuminate\Support\Facades\DB::connection()->getPdo();
$scratch='avia_m42_exact_20260922_'.bin2hex(random_bytes(4));
$db->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->exec('USE `'.$scratch.'`');
$raw=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function readJson(string $f):array{return json_decode(file_get_contents($f),true,512,JSON_THROW_ON_ERROR);}
function runRaw(PDO $db,string $sql):array{$s=$db->query($sql);$out=[];do{if($s->columnCount())$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $out;}
function must(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
function status(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function seed(PDO $db,string $table,array $rows):void{foreach($rows as $r){unset($r['active_ipl_num']);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($r)).'`) VALUES ('.implode(',',array_fill(0,count($r),'?')).')')->execute(array_values($r));}}
$old=dirname(__DIR__).'/20260921-full';
$schema=readJson($old.'/production-schema.json')['schema'];
$target=readJson(__DIR__.'/production-before.json')['manual'];
$tables=['components'=>'components','groups'=>'manual_part_groups','options'=>'manual_part_group_options','coverages'=>'manual_part_group_coverages','legacy'=>'component_assemblies','service_bulletins'=>'manual_service_bulletins','check_sheet'=>'manual_in_process_check_sheets'];
$prefix=__DIR__.'/32-21-02_20260922_v01_exact';
$sql=file_get_contents($prefix.'_import.sql');$schemaSql=file_get_contents($prefix.'_schema.sql');
$changes=readJson(__DIR__.'/changes.json');$report=['result'=>'NOT PASSED','production_modified'=>false,'working_data_modified'=>false];
function digest(PDO $db,array $tables):string{$rows=[];foreach($tables as $t)$rows[$t]=$db->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($rows));}
try{
 foreach($schema as $t=>$rows){
  if($t==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$db->exec(preg_replace('/,\n\)/',"\n)",$ddl));
 }
 seed($db,'manuals',[['id'=>42,'number'=>'32-21-02','deleted_at'=>null]]);
 foreach($tables as $key=>$table)seed($db,$table,$target[$key]);
 $untouched=array_values(array_diff($tables,['manual_part_group_coverages']));
 $unchanged=digest($db,$untouched);
 runRaw($raw,$schemaSql);runRaw($raw,$schemaSql);
 must(status(runRaw($raw,$sql))==='SUCCESS','First import');
 $after=digest($db,array_values($tables));
 must(status(runRaw($raw,$sql))==='SUCCESS'&&$after===digest($db,array_values($tables)),'Repeat not identical');
 $audit=runRaw($raw,file_get_contents($prefix.'_verify.sql'));
 foreach($audit as $r)must($r['result']==='OK','Audit '.$r['audit']);
 must($unchanged===digest($db,$untouched),'Unrelated data changed');
 $report['sql']=['checks'=>count($audit),'repeat_identical'=>true,'schema_repeat'=>true,'preserved_data'=>true];
 $groups=App\Models\ManualPartGroup::where('manual_id',42)->with('options.coverages')->get()->keyBy('id');
 $options=$groups->flatMap(fn($g)=>$g->options)->keyBy('id');
 $ids=$db->query('SELECT ipl_num,id FROM components WHERE manual_id=42 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $expected=readJson($old.'/groups-audit.json')['expanded'];
 foreach($changes as $c)$expected[$c['group_code']][$c['item']]=$c['qty'];
 $resolver=new App\Services\PartGroupCoverageResolver();$families=$resolver->bundleIplFamiliesForManuals([42],$groups);
 $members=new ReflectionMethod($resolver,'bundleMembers');$members->setAccessible(true);
 $expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);$cases=0;
 foreach($groups as $g){if($g->behavior!=='bundle')continue;$opt=$g->options->first();
  foreach(['prl','ndt','cad','stress','paint'] as $scope)foreach([1,2] as $mult){$cov=[];
   foreach($members->invoke($resolver,$opt,$scope,$families) as $member){$args=[&$cov,$member,$mult,$scope,'audit',$g,$opt,$groups,$options,[(int)$opt->id=>true],$families];$expand->invokeArgs($resolver,$args);}
   $actual=array_map(fn($c)=>$c['covered_qty'],$cov);$want=[];foreach($expected[$g->code] as $ipl=>$qty)$want[$ids[$ipl]]=$qty*$mult;
   ksort($want);ksort($actual);must($want===$actual,'Coverage '.$g->code.' '.$scope);$cases++;
  }
 }
 $comp=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($groups);$comps=0;
 foreach($groups as $g){if($g->behavior!=='bundle')continue;$want=array_map(fn($i)=>(int)$ids[$i],array_keys($expected[$g->code]));sort($want);must($want===$comp[$g->id]->sort()->values()->all(),'Composition '.$g->code);$comps++;}
 $report['runtime']=['coverage_cases'=>$cases,'composition_cases'=>$comps];
 foreach(['wrong_manual','wrong_quantity','unreviewed_edge'] as $case){
  if($case==='wrong_manual')$db->exec("UPDATE manuals SET number='WRONG' WHERE id=42");
  if($case==='wrong_quantity')$db->exec('UPDATE manual_part_group_coverages SET qty=9 WHERE manual_part_group_option_id='.$changes[0]['option_id'].' AND component_id='.$changes[0]['component_id']);
  if($case==='unreviewed_edge')$db->exec('INSERT INTO manual_part_group_coverages(manual_part_group_option_id,component_id,qty) VALUES('.$changes[0]['option_id'].','.$ids['1-451B'].',1)');
  $before=digest($db,array_merge(['manuals'],array_values($tables)));
  must(str_starts_with(status(runRaw($raw,$sql)),'ROLLED BACK')&&$before===digest($db,array_merge(['manuals'],array_values($tables))),'Unsafe '.$case);
  if($case==='wrong_manual')$db->exec("UPDATE manuals SET number='32-21-02' WHERE id=42");
  if($case==='wrong_quantity')$db->exec('UPDATE manual_part_group_coverages SET qty='.$changes[0]['qty'].' WHERE manual_part_group_option_id='.$changes[0]['option_id'].' AND component_id='.$changes[0]['component_id']);
  if($case==='unreviewed_edge')$db->exec('DELETE FROM manual_part_group_coverages WHERE id=LAST_INSERT_ID()');
  $report['rollback'][$case]='PASS';
 }
 $report['result']='PASS';
}finally{
 $raw=null;
 if(preg_match('/^avia_m42_exact_20260922_[a-f0-9]{8}$/',$scratch)&&(string)$db->query('SELECT DATABASE()')->fetchColumn()===$scratch){$db->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;}
 file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;
