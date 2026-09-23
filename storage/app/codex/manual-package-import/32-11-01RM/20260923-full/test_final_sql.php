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
$scratch='avia_m68_review_20260923_'.bin2hex(random_bytes(4));
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$raw=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function readJson(string $name):array{return json_decode(file_get_contents(__DIR__.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
function must(bool $ok,string $why):void{if(!$ok)throw new RuntimeException($why);}
function seed(PDO $pdo,string $table,array $rows):void{
 foreach($rows as $row){unset($row['active_ipl_num']);$keys=array_keys($row);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$keys).'`) VALUES('.implode(',',array_fill(0,count($keys),'?')).')')->execute(array_values($row));}
}
function fixture(PDO $pdo):void{
 must((bool)preg_match('/^avia_m68_review_20260923_[a-f0-9]{8}$/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach(readJson('production-schema-references.json')['schema'] as $table=>$rows){
  $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);
 }
 $pdo->exec('DROP TABLE IF EXISTS manuals');$pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 $m=readJson('production.json')['manual'];
 seed($pdo,'manuals',[['id'=>68,'number'=>'32-11-01RM','deleted_at'=>null],['id'=>999,'number'=>'UNRELATED','deleted_at'=>null]]);
 foreach(['components'=>'components','component_assemblies'=>'legacy','manual_service_bulletins'=>'service_bulletins','manual_part_groups'=>'groups','manual_part_group_options'=>'options','manual_part_group_coverages'=>'coverages','manual_in_process_check_sheets'=>'check_sheet'] as $table=>$key)seed($pdo,$table,$m[$key]);
 seed($pdo,'components',[['id'=>99999,'manual_id'=>999,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
}
function digest(PDO $pdo):string{
 $out=[];foreach(array_merge(['manuals'],array_keys(readJson('production-schema-references.json')['schema'])) as $t)$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 return hash('sha256',json_encode($out));
}
function runRaw(PDO $pdo,string $sql):array{
 $s=$pdo->query($sql);$out=[];do{if($s->columnCount())$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $out;
}
function status(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
$sql=file_get_contents(__DIR__.'/32-11-01RM_20260923_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-11-01RM_20260923_v01_verify.sql');
$report=['schema'=>$scratch,'working_database_modified'=>false,'production_modified'=>false,'sql_sha256'=>hash('sha256',$sql),'verify_sha256'=>hash('sha256',$verify)];
try{
 fixture($pdo);
 $first=runRaw($raw,$sql);
 must(status($first)==='SUCCESS','Initial import blocked: '.json_encode(array_slice(array_values(array_filter($first,fn($x)=>($x['result']??'')==='BLOCKED')),0,4)));
 $audit=runRaw($raw,$verify);foreach($audit as $a)must($a['result']==='OK','Verify failed '.json_encode($a));
 echo count($audit).' raw SELECT checks PASS'.PHP_EOL;
 $before=digest($pdo);$second=runRaw($raw,$sql);must(status($second)==='SUCCESS'&&digest($pdo)===$before,'Repeat changed database');
 $report['positive']=['first'=>'SUCCESS','repeat'=>'SUCCESS','identical_repeat'=>true,'raw_file_including_comments'=>true,'select_checks'=>count($audit)];
 echo 'Identical repeat PASS'.PHP_EOL;
 $gs=App\Models\ManualPartGroup::where('manual_id',68)->with('options.coverages')->get()->keyBy('id');$opts=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=68 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $expected=readJson('groups-audit.json')['expanded'];$resolver=new App\Services\PartGroupCoverageResolver();$families=$resolver->bundleIplFamiliesForManuals([68],$gs);
 $expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);$members=new ReflectionMethod($resolver,'bundleMembers');$members->setAccessible(true);
 $composition=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($gs);$cases=0;$compositions=0;
 foreach($gs as $g){if($g->type!=='assy')continue;$opt=$g->options->first();
  $want=[];foreach($expected[$g->code] as $ipl=>$n)$want[(int)$ids[$ipl]]=$n;ksort($want);
  foreach(['prl','ndt','cad','stress','paint'] as $scope)foreach([1,2] as $mult){$cov=[];
   foreach($members->invoke($resolver,$opt,$scope,$families) as $member){$args=[&$cov,$member,$mult,$scope,'scratch audit',$g,$opt,$gs,$opts,[(int)$opt->id=>true],$families];$expand->invokeArgs($resolver,$args);}
   $actual=array_map(fn($x)=>$x['covered_qty'],$cov);ksort($actual);$scaled=array_map(fn($n)=>$n*$mult,$want);
   must($actual===$scaled,'Resolver mismatch '.$g->code.' '.$scope.' actual='.json_encode(array_diff_assoc($actual,$scaled)).' missing='.json_encode(array_diff_assoc($scaled,$actual)));$cases++;
  }
  $actual=$composition[$g->id]->sort()->values()->all();$wantIds=array_keys($want);sort($wantIds);must($actual===$wantIds,'Composition mismatch '.$g->code);$compositions++;
 }
 $report['resolver']=['quantity_scope_cases'=>$cases,'composition_cases'=>$compositions,'result'=>'PASS'];echo $cases.' resolver and '.$compositions.' composition cases PASS'.PHP_EOL;
 foreach(['changed_composition','wrong_manual','changed_pn','changed_archived','conflicting_sb','conflicting_check_sheet','unrelated_group'] as $scenario){
  if($scenario==='changed_composition'){$pdo->exec('UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1');}
  else{fixture($pdo);
   if($scenario==='wrong_manual')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=68");
   elseif($scenario==='changed_pn')$pdo->exec("UPDATE components SET part_number='UNREVIEWED' WHERE id=5020");
   elseif($scenario==='changed_archived')$pdo->exec("UPDATE components SET part_number='UNREVIEWED' WHERE id=3779");
   elseif($scenario==='conflicting_sb')$pdo->exec("UPDATE manual_service_bulletins SET description='UNREVIEWED' WHERE id=3");
   elseif($scenario==='conflicting_check_sheet')$pdo->exec("INSERT INTO manual_in_process_check_sheets(manual_id,source_file,source_sheet,source_sha256,content_sha256,content,schema_version) VALUES(68,'different.xlsx','IN PROCESS CHECK SHEET',REPEAT('a',64),REPEAT('b',64),'{}',1)");
   else$pdo->exec("INSERT INTO manual_part_groups(manual_id,code,name,type,behavior) VALUES(68,'UNREVIEWED','UNREVIEWED','assy','bundle')");
  }
  $before=digest($pdo);$bad=runRaw($raw,$sql);must(str_starts_with(status($bad),'ROLLED BACK')&&digest($pdo)===$before,'Unsafe guard '.$scenario);
  $report['negative'][$scenario]='ROLLED BACK, unchanged';echo $scenario.' guard PASS'.PHP_EOL;
 }
 fixture($pdo);$pdo->exec('UPDATE components SET kit=1,cad_list=1,paint_list=1,log_card=1 WHERE id=5020');
 must(status(runRaw($raw,$sql))==='SUCCESS','Existing flags rejected');
 must((int)$pdo->query('SELECT kit+cad_list+paint_list+log_card FROM components WHERE id=5020')->fetchColumn()===4,'Existing flags cleared');
 must($pdo->query('SELECT part_number FROM components WHERE id=5020')->fetchColumn()==='AS15001-1P','Approved PN not fixed');
 must($pdo->query('SELECT part_number FROM components WHERE id=99999')->fetchColumn()==='UNCHANGED','Other manual changed');
 must((int)$pdo->query('SELECT COUNT(*) FROM components WHERE id IN(3779,2268) AND deleted_at IS NULL')->fetchColumn()===2,'Referenced archived IDs not restored');
 $report['preservation']='PASS: IDs, archived history, SB, legacy, existing flags, unrelated manual';$report['result']='PASS';
}catch(Throwable $e){$report['result']='FAIL';$report['error']=$e->getMessage();throw $e;}
finally{
 if($raw->inTransaction())$raw->rollBack();if($pdo->inTransaction())$pdo->rollBack();$raw=null;
 if(preg_match('/^avia_m68_review_20260923_[a-f0-9]{8}$/',$scratch)&&(string)$pdo->query('SELECT DATABASE()')->fetchColumn()===$scratch){$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;}
 file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
