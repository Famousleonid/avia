<?php
require dirname(__DIR__,6).'/vendor/autoload.php';$app=require dirname(__DIR__,6).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local only');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();$scratch='avia_m28_eff_'.bin2hex(random_bytes(4));$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function raw($db,$sql){$s=$db->query($sql);$rows=[];do{if($s->columnCount())$rows=array_merge($rows,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());return $rows;}
function status($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return null;}
function ck($b,$m){if(!$b)throw new RuntimeException($m);}
function snapshot($db,$tables){$d=[];foreach($tables as $t)$d[$t]=$db->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return $d;}
$d=json_decode(file_get_contents(__DIR__.'/production-before.json'),true);$plan=json_decode(file_get_contents(__DIR__.'/eff-plan.json'),true);$tables=['manuals','components','units','std_processes','workorder_std_process_items'];$report=['status'=>'FAIL','production_modified'=>false,'working_database_modified'=>false];
try{
 foreach($tables as $t){$ddl=$d['schema'][$t][0]['Create Table'];$ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->exec($ddl);
  $rows=$t==='manuals'?[$d['manual']]:$d['data'][$t];foreach($rows as $r){unset($r['active_ipl_num']);$cols=array_keys($r);$s=$db->prepare('INSERT INTO '.$t.' (`'.implode('`,`',$cols).'`) VALUES('.implode(',',array_fill(0,count($cols),'?')).')');$s->execute(array_values($r));}
 }
 $db->exec("INSERT INTO components(id,manual_id,part_number,name,ipl_num,eff_code) VALUES(999999,9999,'UNRELATED','KEEP','1-1','X')");
 $before=snapshot($db,$tables);$sql=file_get_contents(__DIR__.'/32-10-01_ELEB_20261016_v01_eff_LR_update.sql');$verify=file_get_contents(__DIR__.'/32-10-01_ELEB_20261016_v01_eff_LR_verify.sql');
 ck(status(raw($db,$sql))==='SUCCESS - EFF L/R','Initial import');$checks=raw($db,$verify);foreach($checks as $r)ck($r['result']==='PASS','Check '.json_encode($r));
 $after=snapshot($db,$tables);ck(count($after['workorder_std_process_items'])===0,'Derived snapshots invalidated');
 foreach(['manuals','components','units','std_processes'] as $t){$b=$before[$t];$a=$after[$t];foreach($b as &$r){unset($r['eff_code'],$r['updated_at']);}unset($r);foreach($a as &$r){unset($r['eff_code'],$r['updated_at']);}unset($r);ck($a===$b,'Non-EFF data changed '.$t);}
 ck($db->query('SELECT eff_code FROM components WHERE id=999999')->fetchColumn()==='X','Unrelated changed');
 $extra=$db->query('SELECT * FROM components WHERE id=14330')->fetch(PDO::FETCH_ASSOC);ck($extra===array_values(array_filter($before['components'],fn($r)=>(int)$r['id']===14330))[0],'Extra user Part changed');
 ck(status(raw($db,$sql))==='SUCCESS - EFF L/R' && snapshot($db,$tables)===$after,'Repeat not identical');
 // Every source row obeys EFF intersection; common rows remain visible on both sides.
 $runtime=['L'=>0,'R'=>0];foreach($plan['expected_parts'] as $p){foreach(['L','R'] as $side){$match=App\Models\StdProcess::stdRowEffMatchesUnit($p['eff_code'],$side);ck($match===($p['eff_code']===null||$p['eff_code']===$side),'EFF intersection '.$p['ipl']);if($match)$runtime[$side]++;}}
 foreach(['manual'=>["UPDATE manuals SET number='WRONG' WHERE id=28","UPDATE manuals SET number='32-10-01 ELEB' WHERE id=28"],'PN'=>["UPDATE components SET part_number='WRONG' WHERE ipl_num='1003-110' AND manual_id=28","UPDATE components SET part_number='2309-2099-001' WHERE ipl_num='1003-110' AND manual_id=28"],'existing_eff'=>["UPDATE components SET eff_code='X' WHERE ipl_num='1003-110' AND manual_id=28","UPDATE components SET eff_code='R' WHERE ipl_num='1003-110' AND manual_id=28"]] as $name=>$pair){$db->exec($pair[0]);$old=snapshot($db,$tables);ck(status(raw($db,$sql))==='ROLLED BACK - NOT CHANGED'&&snapshot($db,$tables)===$old,'Guard '.$name);$db->exec($pair[1]);}
 $report+=['checks'=>count($checks),'repeat_identical'=>true,'non_eff_fields_preserved'=>true,'extra_part_preserved'=>true,'unrelated_preserved'=>true,'snapshot_invalidation'=>true,'runtime_visible_parts'=>$runtime,'negative_guards'=>['manual','PN','existing_eff'],'sql_sha256'=>hash('sha256',$sql)];$report['status']='PASS';
}catch(Throwable $e){$report['error']=$e->getMessage();throw $e;}
finally{try{if($db->inTransaction())$db->rollBack();}catch(Throwable $ignored){}$db=null;$pdo->exec('DROP DATABASE `'.$scratch.'`');$report['scratch_removed']=true;file_put_contents(__DIR__.'/sql-test.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo json_encode($report,JSON_PRETTY_PRINT).PHP_EOL;}
