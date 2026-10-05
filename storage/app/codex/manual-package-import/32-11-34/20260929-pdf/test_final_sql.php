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
$scratch='avia_m76_review_20260929_'.bin2hex(random_bytes(4));
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
 must((bool)preg_match('/^avia_m76_review_20260929_[a-f0-9]{8}$/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach(data('production.json')['schema'] as $table=>$rows){
  $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
  if($table==='manuals')continue;
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);
 }
 $pdo->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec('DROP TABLE IF EXISTS units');$pdo->exec('CREATE TABLE units(id BIGINT UNSIGNED PRIMARY KEY,part_number VARCHAR(255),default_scope_type VARCHAR(50),default_scope_component_id BIGINT NULL,default_scope_part_group_option_id BIGINT NULL) ENGINE=InnoDB');
 $pdo->exec('DROP TABLE IF EXISTS workorders');$pdo->exec('CREATE TABLE workorders(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),unit_id BIGINT,scope_type VARCHAR(50),scope_component_id BIGINT NULL,scope_part_group_option_id BIGINT NULL,deleted_at TIMESTAMP NULL,done_at DATE NULL) ENGINE=InnoDB');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 $snap=data('production.json');$m=$snap['manual'];
 seed($pdo,'manuals',[['id'=>76,'number'=>'32-11-34','deleted_at'=>null],['id'=>999,'number'=>'UNRELATED','deleted_at'=>null]]);
 foreach(array_keys($snap['schema']) as $table)if($table!=='manuals')seed($pdo,$table,$m[$table]);
 seed($pdo,'units',$snap['units']);seed($pdo,'workorders',$snap['workorders']);foreach(data('scope-diagnostic.json')['wos'] as $w)$pdo->prepare('UPDATE workorders SET done_at=? WHERE id=?')->execute([$w['done_at'],$w['id']]);
 seed($pdo,'components',[['id'=>99999,'manual_id'=>999,'ipl_num'=>'1-1','part_number'=>'UNCHANGED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
}
function digest(PDO $pdo):string{$out=[];foreach(tables() as $t)$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($out));}
function runRaw(PDO $pdo,string $sql):array{$s=$pdo->query($sql);$out=[];do{if($s->columnCount())$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $out;}
function status(array $rows):string{foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
$sql=file_get_contents(__DIR__.'/32-11-34_20260929_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-11-34_20260929_v01_verify.sql');
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
 $gs=App\Models\ManualPartGroup::where('manual_id',76)->with('options.coverages')->get()->keyBy('id');$opts=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=76 AND deleted_at IS NULL')->fetchAll(PDO::FETCH_KEY_PAIR);
 $expected=data('groups-audit.json');$resolver=new App\Services\PartGroupCoverageResolver();$families=$resolver->bundleIplFamiliesForManuals([76],$gs);
 $expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);$members=new ReflectionMethod($resolver,'bundleMembers');$members->setAccessible(true);
 $composition=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($gs);$cases=0;$compositions=0;
 $modeSelections=[];
 foreach(['none'=>null,'old'=>'2-1370','new'=>'2-1370A'] as $mode=>$chosen){$modeSelections[$mode]=[];
  foreach($opts as $op)foreach($op->coverages as $c)if($c->choice_slot&&$opts->get($c->covered_manual_part_group_option_id)?->ipl_num===$chosen)$modeSelections[$mode][App\Services\WorkorderAssyConfiguration::key($op->id,$c->choice_slot)]=$c->id;
 }
 foreach($gs as $g){
  foreach($g->options as $opt)foreach($modeSelections as $mode=>$selected){
   $key=$g->code.'|'.$opt->ipl_num.'|'.$mode;$want=[];foreach($expected['expanded'][$key] as $ipl=>$n)$want[(int)$ids[$ipl]]=$n;
   foreach($expected['alternative_head_crossouts'][$key] as $ipl)$want[(int)$ids[$ipl]]=PHP_INT_MAX;ksort($want);
   foreach(['prl','ndt','cad','stress','paint'] as $scope)foreach([1,2] as $mult){$cov=[];
    foreach($members->invoke($resolver,$opt,$scope,$families,$selected) as $member){$args=[&$cov,$member,$mult,$scope,'scratch audit',$g,$opt,$gs,$opts,[(int)$opt->id=>true],$families,$selected];$expand->invokeArgs($resolver,$args);}
    $actual=array_map(fn($x)=>$x['covered_qty'],$cov);ksort($actual);$scaled=array_map(fn($n)=>$n===PHP_INT_MAX?$n:$n*$mult,$want);
    must($actual===$scaled,'Resolver mismatch '.$key.' '.$scope.' actual='.json_encode(array_diff_assoc($actual,$scaled)).' missing='.json_encode(array_diff_assoc($scaled,$actual)));$cases++;
   }
  }
  $actual=$composition[$g->id]->sort()->values()->all();$groupWant=array_map(fn($ipl)=>(int)$ids[$ipl],$expected['catalog'][$g->code]);sort($groupWant);must($actual===$groupWant,'Log Card composition mismatch '.$g->code);$compositions++;
 }
 $report['resolver']=['quantity_scope_cases'=>$cases,'composition_cases'=>$compositions,'result'=>'PASS'];echo $cases.' resolver and '.$compositions.' composition cases PASS'.PHP_EOL;
 // New flags must be exactly the reviewed source; old flags cannot be cleared.
 $final=data('expected-final.json');$actual=$pdo->query('SELECT * FROM components WHERE manual_id=76')->fetchAll(PDO::FETCH_ASSOC);$map=[];foreach($actual as $a)$map[$a['id']]=$a;$byIpl=[];foreach($actual as $a)if($a['deleted_at']===null)$byIpl[$a['ipl_num']]=$a;
 foreach($final['parts'] as $p)foreach(['log_card','ndt_list','cad_list','paint_list','kit','is_bush','kit_e','np','stress_relief_list'] as $flag)must((int)(isset($p['id'])?$map[$p['id']]:$byIpl[$p['ipl_num']])[$flag]===(int)($p[$flag]??0),'Unexpected flag '.$p['ipl_num'].' '.$flag);
 $report['exact_flags']='PASS';
 foreach([355,356,243] as $id)must($pdo->query('SELECT scope_type FROM workorders WHERE id='.$id)->fetchColumn()==='full_unit','Scope preservation');
 foreach([71,207,214] as $id)must($pdo->query('SELECT scope_type FROM workorders WHERE id='.$id)->fetchColumn()===null,'Completed WO changed');
 must($pdo->query('SELECT deleted_at FROM components WHERE id=2990')->fetchColumn()==='2026-05-05 07:36:42','Deleted duplicate restored');

 foreach(['changed_composition','changed_choice_slot','wrong_manual','changed_pn','changed_legacy','conflicting_sb','conflicting_check_sheet','unrelated_group','changed_scope','wo_completed'] as $scenario){
  if($scenario==='changed_composition'){$pdo->exec('UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1');}
  elseif($scenario==='changed_choice_slot'){fixture($pdo);must(status(runRaw($raw,$sql))==='SUCCESS','fixture reimport');$pdo->exec("UPDATE manual_part_group_coverages SET choice_slot='unexpected' ORDER BY id LIMIT 1");}
  else{fixture($pdo);
   if($scenario==='wrong_manual')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=76");
   elseif($scenario==='changed_pn')$pdo->exec("UPDATE components SET part_number='UNREVIEWED' WHERE id=2982");
   elseif($scenario==='changed_legacy')$pdo->exec("UPDATE component_assemblies SET assy_part_number='UNREVIEWED' WHERE id=114");
   elseif($scenario==='conflicting_sb')$pdo->exec("INSERT INTO manual_service_bulletins(manual_id,oem_service_bulletin_no) VALUES(76,'UNREVIEWED')");
   elseif($scenario==='conflicting_check_sheet')$pdo->exec("INSERT INTO manual_in_process_check_sheets(manual_id,source_file,source_sheet,source_sha256,content_sha256,content,schema_version) VALUES(76,'different.xlsx','IN PROCESS CHECK SHEET',REPEAT('a',64),REPEAT('b',64),'{}',1)");
   elseif($scenario==='wo_completed')$pdo->exec("UPDATE workorders SET done_at='2026-09-29' WHERE id=355");
   elseif($scenario==='changed_scope')$pdo->exec("UPDATE workorders SET scope_type='component' WHERE id=355");
   else$pdo->exec("INSERT INTO manual_part_groups(manual_id,code,name,type,behavior) VALUES(76,'UNREVIEWED','UNREVIEWED','assy','bundle')");
  }
  $before=digest($pdo);$bad=runRaw($raw,$sql);must(str_starts_with(status($bad),'ROLLED BACK')&&digest($pdo)===$before,'Unsafe guard '.$scenario);
  $report['negative'][$scenario]='ROLLED BACK, unchanged';echo $scenario.' guard PASS'.PHP_EOL;
 }
 fixture($pdo);$before=digest($pdo);
 $fault=str_replace('-- Exact postflight;',"UPDATE components SET units_assy='999' WHERE manual_id=76 AND ipl_num='1-1';\n-- Exact postflight;",$sql);
 must($fault!==$sql,'Injection marker missing');$bad=runRaw($raw,$fault);must(str_starts_with(status($bad),'ROLLED BACK')&&digest($pdo)===$before,'Postflight rollback failed');
 $report['negative']['postflight_injected_failure']='All transaction writes rolled back';echo 'Postflight rollback PASS'.PHP_EOL;
 fixture($pdo);$pdo->exec('UPDATE components SET kit=1,cad_list=1,paint_list=1,log_card=1 WHERE id=2982');
 must(status(runRaw($raw,$sql))==='SUCCESS','Existing flags rejected');
 must((int)$pdo->query('SELECT kit+cad_list+paint_list+log_card FROM components WHERE id=2982')->fetchColumn()===4,'Existing flags cleared');
 must($pdo->query('SELECT part_number FROM components WHERE id=99999')->fetchColumn()==='UNCHANGED','Other manual changed');
 $report['preservation']='PASS: all55 existing IDs, deleted duplicate2990,28legacy,6SB,exact flags,unrelated manual,9units,3approved activeWO scopes; completedWO unchanged';$report['result']='PASS';
}catch(Throwable $e){$report['result']='FAIL';$report['error']=$e->getMessage();throw $e;}
finally{
 if($raw->inTransaction())$raw->rollBack();if($pdo->inTransaction())$pdo->rollBack();$raw=null;
 if(preg_match('/^avia_m76_review_20260929_[a-f0-9]{8}$/',$scratch)&&(string)$pdo->query('SELECT DATABASE()')->fetchColumn()===$scratch){$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;}
 file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
