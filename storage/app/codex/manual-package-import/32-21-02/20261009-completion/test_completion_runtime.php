<?php
$groups=App\Models\ManualPartGroup::where('manual_id',42)->with('options.coverages')->get()->keyBy('id');
$options=$groups->flatMap(fn($g)=>$g->options)->keyBy('id');
$byIpl=App\Models\Component::where('manual_id',42)->get()->keyBy('ipl_num');
$resolver=new App\Services\PartGroupCoverageResolver();$cfgService=new App\Services\WorkorderAssyConfiguration();
$families=$resolver->bundleIplFamiliesForManuals([42],$groups);
$expand=new ReflectionMethod($resolver,'expandBundleMember');$expand->setAccessible(true);
$bundle=new ReflectionMethod($resolver,'bundleMembers');$bundle->setAccessible(true);
$scopeResolver=new App\Services\WorkorderPartScopeResolver();$scopeMethod=new ReflectionMethod($scopeResolver,'optionComponentQuantities');$scopeMethod->setAccessible(true);
$makeCoverage=function($option,$selected,$scope,$mult=1)use($resolver,$groups,$options,$families,$expand,$bundle){$out=[];$g=$groups[$option->manual_part_group_id];foreach($bundle->invoke($resolver,$option,$scope,$families,$selected) as $member){$args=[&$out,$member,$mult,$scope,'review',$g,$option,$groups,$options,[$option->id=>true],$families,$selected];$expand->invokeArgs($resolver,$args);}return $out;};
$defaults=function($option,array $selected)use(&$defaults,$cfgService,$options){
 foreach($cfgService->eligibleMembers($option,$selected)->whereNotNull('choice_slot')->groupBy('choice_slot') as $slot=>$edges){$key=$cfgService::key($option->id,$slot);if($edges->count()>1&&!$edges->contains('id',$selected[$key]??0))$selected[$key]=(int)$edges->first()->id;}
 // Conditions can become eligible after the tube slot was chosen.
 foreach($cfgService->eligibleMembers($option,$selected)->whereNotNull('choice_slot')->groupBy('choice_slot') as $slot=>$edges){$key=$cfgService::key($option->id,$slot);if($edges->count()>1&&!$edges->contains('id',$selected[$key]??0))$selected[$key]=(int)$edges->first()->id;}
 foreach($cfgService->members($option,$selected) as $edge)if($edge->covered_manual_part_group_option_id)$selected=$defaults($options[$edge->covered_manual_part_group_option_id],$selected);
 return $selected;
};
$plan=json_decode(file_get_contents(__DIR__.'/final-plan.json'),true);$camSets=[];foreach($plan['cam_companions'] as $e)$camSets[$e['cam_ipl']][$e['companion_ipl']]=$e['qty'];
$cases=0;$scopeCases=0;$compositionCases=0;
foreach(range(1683,1693) as $rootId){
 $root=$options[$rootId];$base=$defaults($root,[]);$scenarios=[$base];
 foreach($root->coverages->whereNotNull('choice_slot') as $edge){$s=$base;$s[$cfgService::key($rootId,$edge->choice_slot)]=(int)$edge->id;$scenarios[]=$defaults($root,$s);}
 foreach([1672,1673,1674,1675] as $tubeId){$rootEdge=$root->coverages->firstWhere('covered_manual_part_group_option_id',$tubeId);foreach($options[$tubeId]->coverages->where('choice_slot','wheel_axle') as $axle){$s=$base;$s[$cfgService::key($rootId,'sliding_tube')]=(int)$rootEdge->id;$s[$cfgService::key($tubeId,'wheel_axle')]=(int)$axle->id;$scenarios[]=$defaults($root,$s);}}
 foreach($scenarios as $selected){
  $active=$cfgService->members($root,$selected);$tubeId=(int)$active->firstWhere('choice_slot','sliding_tube')->covered_manual_part_group_option_id;
  $camId=(int)$active->firstWhere('choice_slot','lower_cam')->covered_manual_part_group_option_id;$cam=$options[$camId];
  $axle=$cfgService->members($options[$tubeId],$selected)->firstWhere('choice_slot','wheel_axle');
  $spacer=$active->firstWhere('choice_slot','wheel_axle_spacer');check($spacer!==null,'Missing selected spacer');
  $expect=[$byIpl['8-372']->id=>2,$byIpl['8-147']->id=>1,$cam->component_id=>1,$axle->component_id=>1,$spacer->component_id=>2];foreach($camSets[$cam->ipl_num] as $ipl=>$qty)$expect[$byIpl[$ipl]->id]=$qty;
  foreach(App\Models\ManualPartGroup::validScopes() as $scope){foreach([1,2] as $mult){$actual=$makeCoverage($root,$selected,$scope,$mult);foreach($expect as $id=>$qty)check(($actual[$id]['covered_qty']??0)===$qty*$mult,'Coverage qty '.$rootId.' '.$id.' '.$scope);$cases++;}}
  $scope=$scopeMethod->invoke($scopeResolver,$rootId,null,$selected);foreach($expect as $id=>$qty)check(($scope[$id]??0)===$qty,'Work Scope qty '.$rootId.' '.$id);$scopeCases++;
  if(in_array($tubeId,[1674,1675]))check(!isset($scope[$byIpl['9-140']->id]),'PostSB old spacer');
  foreach($camSets as $otherIpl=>$set)if($otherIpl!==$cam->ipl_num)check(!isset($scope[$byIpl[$otherIpl]->id]),'Wrong Cam in Work Scope');
  $db->exec('DELETE FROM workorder_assy_configuration_choices');
  foreach($selected as $key=>$coverageId){[$parent,$slot]=explode('|',$key);seed($db,'workorder_assy_configuration_choices',[['workorder_id'=>999,'parent_option_id'=>(int)$parent,'choice_slot'=>$slot,'selected_coverage_id'=>$coverageId]]);}
  $wo=new App\Models\Workorder();$wo->id=999;
  $members=(new App\Services\ManualPartGroupCompositionResolver())->componentIdsByGroup($groups,$wo)[$root->manual_part_group_id]->all();
  foreach(array_keys($expect) as $id)check(in_array($id,$members,true),'Log Card missing '.$id);
  foreach($camSets as $otherIpl=>$set)if($otherIpl!==$cam->ipl_num)check(!in_array($byIpl[$otherIpl]->id,$members,true),'Wrong Cam in Log Card');
  $compositionCases++;
 }
}
foreach([1672,1673,1674,1675] as $tubeId){$tube=$options[$tubeId];$selected=$defaults($tube,[]);foreach(App\Models\ManualPartGroup::validScopes() as $scope){$c=$makeCoverage($tube,$selected,$scope);check(!isset($c[$byIpl['9-140']->id])&&!isset($c[$byIpl['9-140A']->id]),'Standalone tube contains spacer');}}
foreach($plan['new_groups'] as $g){$opt=$options->first(fn($o)=>$groups[$o->manual_part_group_id]->code===$g['code']);foreach(App\Models\ManualPartGroup::validScopes() as $scope){$c=$makeCoverage($opt,[],$scope);$finite=array_filter($c,fn($x)=>$x['covered_qty']!==PHP_INT_MAX);check(count($finite)===1+count($camSets[$g['ipl']]),'Cam bundle extra parts');foreach($camSets[$g['ipl']] as $ipl=>$qty)check($finite[$byIpl[$ipl]->id]['covered_qty']===$qty,'Cam seal qty');}}
$db->exec('DELETE FROM workorder_assy_configuration_choices');
$report['runtime']=['procurement_scope_multiplier_cases'=>$cases,'work_scope_cases'=>$scopeCases,'log_card_compositions'=>$compositionCases,'standalone_tubes_no_spacers'=>4,'cam_bundles_exact'=>6,'result'=>'PASS'];
echo 'Runtime BOM, Cam seals and root-only spacers PASS'.PHP_EOL;
