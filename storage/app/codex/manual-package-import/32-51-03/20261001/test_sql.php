<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local DB only');
Illuminate\Support\Facades\DB::setDefaultConnection('mysql');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$scratch='avia_m97_review_20261001_'.bin2hex(random_bytes(4));
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$raw=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function data(string $name):array{return json_decode(file_get_contents(__DIR__.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
function must(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
function tables():array{return array_merge(array_keys(data('production.json')['schema']),['units','workorders']);}
function seed(PDO $pdo,string $table,array $rows):void{
 foreach($rows as $row){unset($row['active_ipl_num']);$keys=array_keys($row);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$keys).'`) VALUES('.implode(',',array_fill(0,count($keys),'?')).')')->execute(array_values($row));}
}
function fixture(PDO $pdo):void{
 must((bool)preg_match('/^avia_m97_review_20261001_[a-f0-9]{8}$/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach(data('production.json')['schema'] as $table=>$rows){
  $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
  if($table==='manuals')continue;
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);
 }
 $pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec('DROP TABLE IF EXISTS units');$pdo->exec('CREATE TABLE units(id BIGINT UNSIGNED PRIMARY KEY,part_number VARCHAR(255),default_scope_type VARCHAR(50),default_scope_component_id BIGINT NULL,default_scope_part_group_option_id BIGINT NULL) ENGINE=InnoDB');
 $pdo->exec('DROP TABLE IF EXISTS workorders');$pdo->exec('CREATE TABLE workorders(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),unit_id BIGINT,scope_type VARCHAR(50),scope_component_id BIGINT NULL,scope_part_group_option_id BIGINT NULL,deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 $snap=data('production.json');$m=$snap['manual'];
 seed($pdo,'manuals',[['id'=>97,'number'=>'32-51-03','deleted_at'=>null],['id'=>999,'number'=>'UNRELATED','deleted_at'=>null]]);
 foreach(array_keys($snap['schema']) as $table)if($table!=='manuals')seed($pdo,$table,$m[$table]);
 seed($pdo,'units',$snap['units']);seed($pdo,'workorders',$snap['workorders']);
 seed($pdo,'components',[['id'=>99999,'manual_id'=>999,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
}
function digest(PDO $pdo):string{$out=[];foreach(tables() as $t)$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($out));}
function runRaw(PDO $pdo,string $sql):array{$s=$pdo->query($sql);$out=[];do{if($s->columnCount())$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $out;}
function status(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
$sql=file_get_contents(__DIR__.'/32-51-03_20261001_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-51-03_20261001_v01_verify.sql');
$report=[];
try{
 fixture($pdo);$result=runRaw($raw,$sql);must(status($result)==='SUCCESS','Initial blocked '.json_encode(array_filter($result,fn($r)=>($r['result']??'')==='BLOCKED')));
 $checks=runRaw($raw,$verify);foreach($checks as $c)must($c['result']==='OK',json_encode($c));$report['checks']=count($checks);
 $gs=App\Models\ManualPartGroup::where('manual_id',97)->with('options.coverages')->get();
 $actual=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($gs);
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=97')->fetchAll(PDO::FETCH_KEY_PAIR);
 foreach(data('reviewed-plan.json')['groups'] as [$head,$children]){
  $g=$gs->firstWhere('code','M97-A-'.$head);$want=array_map(fn($i)=>(int)$ids[$i],array_merge([$head],$children));sort($want);
  must($actual[$g->id]->sort()->values()->all()===$want,'Composition resolver '.$head);
 }
 $report['composition_resolver']='5 groups PASS; no inapplicable suffixes';
 $old=array_column(data('production.json')['manual']['components'],null,'ipl_num');$expected=data('reviewed-plan.json')['flags'];
 foreach($pdo->query('SELECT * FROM components WHERE manual_id=97')->fetchAll(PDO::FETCH_ASSOC) as $c){foreach(['log_card','ndt_list','cad_list','paint_list','kit','is_bush','np','kit_e','stress_relief_list'] as $f){must((int)$c[$f]===max((int)($old[$c['ipl_num']][$f]??0),(int)($expected[$c['ipl_num']][$f]??0)),'Flag '.$c['ipl_num'].' '.$f);}}
 $report['exact_flags']='PASS';
 $before=digest($pdo);must(status(runRaw($raw,$sql))==='SUCCESS'&&digest($pdo)===$before,'Repeat changed data');$report['repeat']='identical';
 foreach(['manual','pn','quantity','composition','postflight'] as $case){
  fixture($pdo);
  if($case==='manual')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=97");
  if($case==='pn')$pdo->exec("UPDATE components SET part_number='WRONG' WHERE id=5314");
  if($case==='quantity')$pdo->exec("UPDATE components SET units_assy='99' WHERE id=5314");
  if($case==='composition'){must(status(runRaw($raw,$sql))==='SUCCESS','Repeat setup');$pdo->exec('UPDATE manual_part_group_coverages SET qty=99 ORDER BY id LIMIT 1');}
  $before=digest($pdo);$test=$case==='postflight'?str_replace('-- Exact postflight',"UPDATE components SET units_assy='99' WHERE manual_id=97 AND ipl_num='1-10';\n-- Exact postflight",$sql):$sql;
  must(str_starts_with(status(runRaw($raw,$test)),'ROLLED BACK')&&digest($pdo)===$before,'Guard failed '.$case);$report[$case]='rollback unchanged';
 }
 $report['result']='PASS';
}finally{if($raw->inTransaction())$raw->rollBack();$raw=null;if(preg_match('/^avia_m97_review_20261001_[a-f0-9]{8}$/',$scratch))$pdo->exec('DROP DATABASE `'.$scratch.'`');file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT));}
echo json_encode($report,JSON_PRETTY_PRINT);

