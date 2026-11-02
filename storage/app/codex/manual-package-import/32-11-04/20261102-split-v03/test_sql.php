<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$original=$pdo->query('SELECT DATABASE()')->fetchColumn();
$scratch='avia_m124_split_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function result($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
function check($b,$m){if(!$b)throw new RuntimeException($m);}
function digest($db,$tables){$all=[];foreach($tables as $t){$rows=$db->query('SELECT * FROM `'.$t.'`')->fetchAll(PDO::FETCH_ASSOC);$rows=array_map(fn($r)=>json_encode($r),$rows);sort($rows);$all[$t]=$rows;}return hash('sha256',json_encode($all));}

$snap=json_decode(file_get_contents(__DIR__.'/production-before.json'),true);$history=json_decode(file_get_contents(__DIR__.'/production-history.json'),true);$plan=json_decode(file_get_contents(__DIR__.'/split-plan.json'),true);
$report=['result'=>'FAIL','production_modified'=>false,'working_database_modified'=>false];
try {
 $schemas=$snap['schema']+$history['schema'];$seed=$snap['data']+$history['data'];$seed['manuals']=$snap['manuals'];
 foreach($schemas as $t=>$schema){$ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$schema[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);}
 foreach($seed as $table=>$rows){foreach($rows as $row){unset($row['active_ipl_num']);$cols=array_keys($row);$db->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($row));}}
 $db->exec("INSERT INTO manuals(id,number,title) VALUES(9999,'UNRELATED','Preserve')");
 $db->exec("INSERT INTO components(manual_id,part_number,name,ipl_num) VALUES(9999,'UNRELATED','Preserve','1-1')");
 $sql=file_get_contents(__DIR__.'/32-11-04_20261102_v03_split_import.sql');$verify=file_get_contents(__DIR__.'/32-11-04_20261102_v03_split_verify.sql');
 $first=result(raw($db,$sql));
 if($first!=='SUCCESS - MANUALS SPLIT'){
  $debug='';foreach(explode("\n",$sql) as $n=>$line){$debug.=$line."\n";if(str_starts_with($line,'SET @split_ok=COALESCE'))$debug.="SELECT ".($n+1)." debug_line,@split_ok ok;\n";}
  foreach(raw($db,$debug) as $row)if(isset($row['debug_line'])&&!(int)$row['ok']){$report['guard_line']=$row['debug_line'];$report['guard_sql']=explode("\n",$sql)[$row['debug_line']-1];break;}
 }
 check($first==='SUCCESS - MANUALS SPLIT','Initial raw split');
 $audit=raw($db,$verify);foreach($audit as $r)check($r['result']==='PASS','Postflight '.json_encode($r));
 $tables=array_keys($schemas);$after=digest($db,$tables);check(result(raw($db,$sql))==='SUCCESS - MANUALS SPLIT' && $after===digest($db,$tables),'Repeat not identical');
 check($db->query('SELECT deleted_at FROM units WHERE id=710')->fetchColumn()===null,'Unit710 restored');check($db->query('SELECT name FROM units WHERE id=711')->fetchColumn()===null,'Unit711 empty name preserved');$report['unit710_restored']=true;$report['checks']=count($audit);$report['repeat_identical']=true;
 $y=(int)$db->query("SELECT id FROM manuals WHERE number='32-11-04 Yoke'")->fetchColumn();$report['test_yoke_id']=$y;
 foreach($seed as $table=>$rows){if(in_array($table,['manuals','components','manual_part_groups','std_processes','manual_service_bulletins','manual_in_process_check_sheets','manual_processes','manual_plane','units','log_cards']))continue;
  foreach($rows as $r)if(isset($r['id']))check($r==$db->query('SELECT * FROM `'.$table.'` WHERE id='.(int)$r['id'])->fetch(PDO::FETCH_ASSOC),'History preserved '.$table.' '.$r['id']);}
 $report['unrelated_history_preserved']=true;
 $conn=Illuminate\Support\Facades\DB::connection('mysql');$conn->getPdo()->exec('USE `'.$scratch.'`');$conn->setDatabaseName($scratch);
 foreach(['yoke'=>$y,'stab'=>124] as $side=>$id){
  $manual=App\Models\Manual::findOrFail($id);$actual=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['parts'])->pluck('id')->map(fn($v)=>(int)$v)->all();
  check($actual===$plan['orders'][$side],'Manual relative Log Card order '.$side);
  foreach(['ndt','cad'] as $type){$before=$db->query("SELECT * FROM std_processes WHERE manual_id=$id AND std='$type' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);App\Models\StdProcess::syncFromComponentFlagsForManualStd($manual,$type);check($before===$db->query("SELECT * FROM std_processes WHERE manual_id=$id AND std='$type' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC),'STD sync '.$side.' '.$type);foreach($before as $r)check(App\Models\StdProcess::normalizeCsvProcessForManual($id,$type,$r['process'],1)===$r['process'],'STD picklist '.$side);}
 }
 foreach([603=>[$y],605=>[$y],604=>[124,151],606=>[124,151]] as $wo=>$want){
  $got=App\Models\Manual::manualIdsForWorkorder($wo);sort($got);sort($want);check($got===$want,'WO manuals '.$wo.' '.json_encode($got));
 }
 $report['runtime']='PASS: separate WO manual selection, retained Log Card order, STD sync and picklists';
 foreach([
 'wrong_part'=>["UPDATE components SET part_number='WRONG' WHERE id=15549","UPDATE components SET part_number='46360-105' WHERE id=15549"],
 'mixed_owner'=>["UPDATE components SET manual_id=124 WHERE id=15549","UPDATE components SET manual_id=$y WHERE id=15549"],
 'changed_qty'=>["UPDATE std_processes SET qty=qty+1 ORDER BY id LIMIT 1","UPDATE std_processes SET qty=qty-1 ORDER BY id LIMIT 1"],
 'changed_manual_title'=>["UPDATE manuals SET title='User changed' WHERE id=$y","UPDATE manuals SET title='Yoke Assy' WHERE id=$y"]
 ] as $case=>$pair){$db->exec($pair[0]);$before=digest($db,$tables);$res=result(raw($db,$sql));if($case==='mixed_owner'){check($res==='SUCCESS - MANUALS SPLIT','Repair interrupted owner');}else check($res==='ROLLED BACK - NOT SPLIT'&&$before===digest($db,$tables),'Guard '.$case);$report['negative'][$case]='PASS';$db->exec($pair[1]);}
 $report['result']='PASS';$report['sql_sha256']=hash('sha256',$sql);
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('USE `'.$original.'`');$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}
