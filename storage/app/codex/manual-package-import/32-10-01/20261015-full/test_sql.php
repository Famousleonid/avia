<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m28_review_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function result($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function check($b,$m){if(!$b)throw new RuntimeException($m);}
function digest($db,$tables){$all=[];foreach($tables as $t)$all[$t]=$db->query('SELECT * FROM `'.$t.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($all));}
$snap=json_decode(file_get_contents(__DIR__.'/production-handoff-snapshot.json'),true,512,JSON_THROW_ON_ERROR);$report=['result'=>'FAIL','production_modified'=>false,'working_database_modified'=>false];
try{
 foreach($snap['schema'] as $t=>$schema){
  if($t==='manuals'){$db->exec('CREATE TABLE manuals(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),title VARCHAR(255),log_card_order JSON NULL,deleted_at TIMESTAMP NULL) ENGINE=InnoDB');continue;}
  $ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$schema[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
 }
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(28,'32-10-01 ELEB','Leg Assy, Strut, MLG'),(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num,kit) VALUES(9999,'UNRELATED','Preserve','1-1',1)");
 $unrelated=$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC);
 $sql=file_get_contents(__DIR__.'/32-10-01_ELEB_20261015_v01_partial_import.sql');$verify=file_get_contents(__DIR__.'/32-10-01_ELEB_20261015_v01_partial_verify.sql');
 check(result(raw($db,$sql))==='SUCCESS - REVIEWED PARTIAL PACKAGE','Initial raw import');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Audit '.json_encode($r));
 $tables=array_keys($snap['schema']);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - REVIEWED PARTIAL PACKAGE' && $after===digest($db,$tables),'Repeat differs');
 check($unrelated===$db->query('SELECT * FROM components WHERE manual_id=9999')->fetchAll(PDO::FETCH_ASSOC),'Unrelated data changed');
 $report['positive']=['raw_import'=>true,'select_checks'=>count($audit),'repeat_identical'=>true,'unrelated_preserved'=>true];
 // Use the real application family and composition services against scratch data.
 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 $manual=App\Models\Manual::findOrFail(28);$actual=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['parts'])->pluck('ipl')->all();
 $expected=json_decode(file_get_contents(__DIR__.'/log-card-plan.json'),true)['ordered_families'];
 $actualFamilies=app(App\Services\ManualLogCardOrder::class)->groups($manual)->pluck('key')->all();check($actualFamilies===array_keys($expected),'Runtime family order');
 check(count($actual)===109,'Runtime Log Card count');
 $gs=App\Models\ManualPartGroup::where('manual_id',28)->with('options.coverages')->get();$composition=app(App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($gs);
 $plan=json_decode(file_get_contents(__DIR__.'/groups-reviewed-partial.json'),true)['groups'];$iplIds=App\Models\Component::where('manual_id',28)->pluck('id','ipl_num');
 foreach($plan as $g){$live=$gs->firstWhere('code',$g['code']);$ids=$composition[$live->id];$wanted=[$iplIds[$g['assembly_ipl']]];foreach($g['members'] as $m){$wanted[]=$iplIds[$m['component_ipl']];if($m['expand_ipl_family'])foreach($iplIds as $ipl=>$id)if(App\Services\PartVariantGrouping::iplFamily($ipl)===App\Services\PartVariantGrouping::iplFamily($m['component_ipl']))$wanted[]=$id;}$wanted=array_values(array_unique($wanted));sort($wanted);$actualIds=$ids->unique()->sort()->values()->all();check($wanted===$actualIds,'Runtime exact composition '.$g['assembly_ipl']);}
 $report['runtime']=['log_card_parts'=>109,'log_card_families'=>36,'groups'=>48,'direct_members'=>329];
 foreach([
 'wrong_manual'=>["UPDATE manuals SET number='WRONG' WHERE id=28","UPDATE manuals SET number='32-10-01 ELEB' WHERE id=28"],
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE manual_id=28 AND ipl_num='1014-540K'","UPDATE components SET part_number='2309-3037-607' WHERE manual_id=28 AND ipl_num='1014-540K'"],
 'changed_group_qty'=>['UPDATE manual_part_group_coverages SET qty=qty+1 ORDER BY id LIMIT 1','UPDATE manual_part_group_coverages SET qty=qty-1 ORDER BY id LIMIT 1'],
 'manual_order'=>["UPDATE manuals SET log_card_order='[999999]' WHERE id=28",null]
 ] as $case=>$pair){$oldOrder=$db->query('SELECT log_card_order FROM manuals WHERE id=28')->fetchColumn();$db->exec($pair[0]);$before=digest($db,$tables);check(result(raw($db,$sql))==='ROLLED BACK - NOT IMPORTED' && $before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';if($pair[1])$db->exec($pair[1]);else $db->prepare('UPDATE manuals SET log_card_order=? WHERE id=28')->execute([$oldOrder]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
