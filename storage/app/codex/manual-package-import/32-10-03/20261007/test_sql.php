<?php
declare(strict_types=1);
require dirname(__DIR__,6).'/vendor/autoload.php';
$app=require dirname(__DIR__,6).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cfg=config('database.connections.mysql');
if(!in_array($cfg['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local DB only');
Illuminate\Support\Facades\DB::setDefaultConnection('mysql');
$pdo=Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
$scratch='avia_m79_review_20261007_'.bin2hex(random_bytes(4));
$pdo->exec('CREATE DATABASE `'.$scratch.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `'.$scratch.'`');
$raw=new PDO('mysql:host='.$cfg['host'].';port='.($cfg['port']??3306).';dbname='.$scratch.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
function data($n){return json_decode(file_get_contents(__DIR__.'/'.$n),true,512,JSON_THROW_ON_ERROR);}
function must($ok,$why){if(!$ok)throw new RuntimeException($why);}
function seed($pdo,$table,$rows){foreach($rows as $row){unset($row['active_ipl_num']);$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($row)).'`) VALUES('.implode(',',array_fill(0,count($row),'?')).')')->execute(array_values($row));}}
function fixture($pdo){
 must(preg_match('/^avia_m79_review_20261007_[a-f0-9]{8}$/',$pdo->query('SELECT DATABASE()')->fetchColumn()),'Not scratch');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach(data('production-scope.json')['schema'] as $t=>$rows){$pdo->exec('DROP TABLE IF EXISTS `'.$t.'`');$ddl=preg_replace('/^  CONSTRAINT[^\n]+\n?/m','',$rows[0]['Create Table']);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$pdo->exec($ddl);}
 $pdo->exec('DROP TABLE IF EXISTS units');$pdo->exec('CREATE TABLE units(id BIGINT UNSIGNED PRIMARY KEY,part_number VARCHAR(255),default_scope_type VARCHAR(50),default_scope_component_id BIGINT NULL,default_scope_part_group_option_id BIGINT NULL) ENGINE=InnoDB');
 $pdo->exec('DROP TABLE IF EXISTS workorders');$pdo->exec('CREATE TABLE workorders(id BIGINT UNSIGNED PRIMARY KEY,number VARCHAR(255),unit_id BIGINT,scope_type VARCHAR(50),scope_component_id BIGINT NULL,scope_part_group_option_id BIGINT NULL,deleted_at TIMESTAMP NULL) ENGINE=InnoDB');
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 seed($pdo,'manuals',[['id'=>79,'number'=>'32-10-03','title'=>'Main Side Strut'],['id'=>120,'number'=>'32-10-03 Safran','title'=>'MLG Yoke Assembly']]);
 seed($pdo,'components',[['id'=>99999,'manual_id'=>120,'ipl_num'=>'1-1','part_number'=>'UNRELATED','name'=>'Unrelated','units_assy'=>'8','log_card'=>1]]);
 foreach(['units','workorders'] as $t)seed($pdo,$t,data('production-scope.json')[$t]);
}
function digest($pdo){$out=[];foreach(array_merge(array_keys(data('production-scope.json')['schema']),['units','workorders']) as $t)$out[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return hash('sha256',json_encode($out));}
function runRaw($pdo,$sql){$s=$pdo->query($sql);$out=[];do{if($s->columnCount())$out=array_merge($out,$s->fetchAll(PDO::FETCH_ASSOC));}while($s->nextRowset());$s->closeCursor();return $out;}
function status($rows){foreach($rows as $r)if(isset($r['final_status']))return $r['final_status'];return 'NONE';}
$sql=file_get_contents(__DIR__.'/32-10-03_20261007_v01_import.sql');$verify=file_get_contents(__DIR__.'/32-10-03_20261007_v01_verify.sql');$report=[];
try{
 fixture($pdo);$sentinel=$pdo->query('SELECT * FROM components WHERE manual_id=120')->fetchAll(PDO::FETCH_ASSOC);
 $result=runRaw($raw,$sql);must(status($result)==='SUCCESS','Initial blocked '.json_encode(array_filter($result,fn($r)=>($r['result']??'')==='BLOCKED')));
 foreach($checks=runRaw($raw,$verify) as $c)must($c['result']==='OK',json_encode($c));$report['exact_sql_checks']=count($checks);
 must($sentinel===$pdo->query('SELECT * FROM components WHERE manual_id=120')->fetchAll(PDO::FETCH_ASSOC),'Unrelated manual changed');
 $plan=data('reviewed-plan.json');$byHead=array_column($plan['groups'],null,'head');$parts=array_column($plan['parts'],null,'ipl_num');
 $ids=$pdo->query('SELECT ipl_num,id FROM components WHERE manual_id=79')->fetchAll(PDO::FETCH_KEY_PAIR);$ipls=array_flip($ids);
 foreach($pdo->query('SELECT * FROM components WHERE manual_id=79')->fetchAll(PDO::FETCH_ASSOC) as $c){foreach(['log_card','ndt_list','cad_list','paint_list','kit','is_bush','np','kit_e','stress_relief_list'] as $f)must((int)$c[$f]===(int)($plan['flags'][$c['ipl_num']][$f]??0),'Flag '.$c['ipl_num'].' '.$f);}
 $gs=App\Models\ManualPartGroup::where('manual_id',79)->with('options.coverages')->get()->keyBy('id');$os=$gs->flatMap(fn($g)=>$g->options)->keyBy('id');$byIpl=$os->keyBy('ipl_num');
 $resolver=new App\Services\PartGroupCoverageResolver();$families=$resolver->bundleIplFamiliesForManuals([79],$gs);
 $bm=new ReflectionMethod($resolver,'bundleMembers');$bm->setAccessible(true);$expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);
 $scope=new App\Services\WorkorderPartScopeResolver();$scopeMethod=new ReflectionMethod($scope,'optionComponentQuantities');$scopeMethod->setAccessible(true);
 // Independent plan walk: exact FIG, family policy, quantity and choice exclusion.
 $family=function($i)use($parts,$plan){if($plan['flags'][$i]['is_bush'])return [$i];$base=preg_replace('/[A-Z]+$/','',$i);return array_values(array_filter(array_keys($parts),fn($j)=>!$plan['flags'][$j]['is_bush']&&preg_replace('/[A-Z]+$/','',$j)===$base));};
 $expected=function($h,$selected,$qty=1,$mode='coverage')use(&$expected,$byHead,$family){$out=[];foreach($byHead[$h]['links'] as $l){if($l['choice_slot']&&($selected[$h.'|'.$l['choice_slot']]??null)!==$l['ipl'])continue;if($l['nested']){$sub=$expected($l['ipl'],$selected,$qty*$l['qty'],$mode);foreach($sub as $i=>$n)$out[$i]=($out[$i]??0)+$n;}else{foreach($mode==='scope'||!$l['expand_ipl_family']?[$l['ipl']]:$family($l['ipl']) as $i)$out[$i]=($out[$i]??0)+$qty*$l['qty'];if($l['ipl']===$h&&$mode==='coverage')foreach($family($h) as $i)if($i!==$h)$out[$i]=PHP_INT_MAX;}}return $out;};
 $cases=0;
 foreach($byHead as $head=>$g){
  $sets=[[]];$slots=[];foreach($g['links'] as $l)if($l['choice_slot'])$slots[$l['choice_slot']][]=$l['ipl'];
  foreach($slots as $slot=>$candidates){$next=$sets;foreach($sets as $set)foreach($candidates as $candidate)$next[]=array_merge($set,[$head.'|'.$slot=>$candidate]);$sets=$next;}
  foreach($sets as $selection){$configured=[];foreach($selection as $key=>$chosen){[$ph,$slot]=explode('|',$key);$o=$byIpl[$ph];$l=$byHead[$ph]['links'][array_search($chosen,array_column($byHead[$ph]['links'],'ipl'))];$cov=$o->coverages->first(fn($c)=>$l['nested']?(int)$c->covered_manual_part_group_option_id===(int)$byIpl[$chosen]->id:(int)$c->component_id===(int)$ids[$chosen]);$configured[$o->id.'|'.$slot]=$cov->id;}
   foreach(['prl','ndt','cad','stress','paint'] as $form){foreach([1,2] as $qty){$o=$byIpl[$head];$group=$gs[$o->manual_part_group_id];$got=[];foreach($bm->invoke($resolver,$o,$form,$families,$configured) as $member){$args=[&$got,$member,$qty,$form,'Test',$group,$o,$gs,$os,[$o->id=>true],$families,$configured];$expand->invokeArgs($resolver,$args);}$actual=[];foreach($got as $id=>$v)$actual[$ipls[$id]]=$v['covered_qty'];$want=$expected($head,$selection,$qty);ksort($actual);ksort($want);must($actual===$want,'Coverage '.$head.' '.$form.' '.json_encode([$selection,$actual,$want]));$cases++;}
    $actual=[];foreach($scopeMethod->invoke($scope,$byIpl[$head]->id,$form,$configured) as $id=>$n)$actual[$ipls[$id]]=$n;$want=$expected($head,$selection,1,'scope');ksort($actual);ksort($want);must($actual===$want,'Work scope '.$head);$cases++;
   }
  }
 }
 $report['runtime_quantity_choice_cases']=$cases;
 $composition=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($gs);
 foreach($byHead as $head=>$g){$expectedIds=[];$collect=function($h)use(&$collect,&$expectedIds,$byHead,$family){foreach($byHead[$h]['links'] as $l){if($l['nested'])$collect($l['ipl']);else foreach($l['expand_ipl_family']?$family($l['ipl']):[$l['ipl']] as $i)$expectedIds[$i]=true;}};$collect($head);$want=array_map(fn($i)=>(int)$ids[$i],array_keys($expectedIds));sort($want);$group=$gs[$byIpl[$head]->manual_part_group_id];must($composition[$group->id]->sort()->values()->all()===$want,'Log Card composition '.$head);}
 $report['composition_groups']=25;
 $manual=App\Models\Manual::find(79);$order=app(App\Services\ManualLogCardOrder::class)->groups($manual)->flatMap(fn($g)=>$g['ids'])->all();must($order===array_map(fn($i)=>(int)$ids[$i],$plan['log_card_ipls']),'LLP family order');$report['llp_order']='PASS 16 parts / 10 families';
 $before=digest($pdo);must(status(runRaw($raw,$sql))==='SUCCESS'&&digest($pdo)===$before,'Repeat not identical');$report['repeat']='identical';
 foreach(['manual','new_part','pn','quantity','composition','order','postflight'] as $case){fixture($pdo);if(in_array($case,['pn','quantity','composition','order']))must(status(runRaw($raw,$sql))==='SUCCESS','Guard setup');
  if($case==='manual')$pdo->exec("UPDATE manuals SET number='WRONG' WHERE id=79");
  if($case==='new_part')seed($pdo,'components',[['manual_id'=>79,'ipl_num'=>'BAD','part_number'=>'BAD','name'=>'BAD']]);
  if($case==='pn')$pdo->exec("UPDATE components SET part_number='WRONG' WHERE manual_id=79 AND ipl_num='1001-10'");
  if($case==='quantity')$pdo->exec("UPDATE components SET units_assy='99' WHERE manual_id=79 AND ipl_num='1001-10'");
  if($case==='composition')$pdo->exec('UPDATE manual_part_group_coverages SET qty=99 ORDER BY id LIMIT 1');
  if($case==='order')$pdo->exec("UPDATE manuals SET log_card_order='[99999]' WHERE id=79");
  $before=digest($pdo);$test=$case==='postflight'?str_replace('-- Exact postflight',"UPDATE components SET units_assy='99' WHERE manual_id=79 AND ipl_num='1001-10';\n-- Exact postflight",$sql):$sql;
  must(str_starts_with(status(runRaw($raw,$test)),'ROLLED BACK')&&digest($pdo)===$before,'Guard failed '.$case);$report['guard_'.$case]='rollback unchanged';
 }
 $report['result']='PASS';
}finally{if($raw->inTransaction())$raw->rollBack();$raw=null;if(preg_match('/^avia_m79_review_20261007_[a-f0-9]{8}$/',$scratch))$pdo->exec('DROP DATABASE `'.$scratch.'`');file_put_contents(__DIR__.'/sql-local-test.json',json_encode($report,JSON_PRETTY_PRINT));}
echo json_encode($report,JSON_PRETTY_PRINT);
