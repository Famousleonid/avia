<?php
declare(strict_types=1);
// In-memory models only: no Laravel bootstrap, DB connection or writes.
require dirname(__DIR__,6).'/vendor/autoload.php';
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\ManualPartGroupCoverage;
use Illuminate\Support\Collection;
function readJson(string $name):array{return json_decode(file_get_contents(__DIR__.'/'.$name),true,512,JSON_THROW_ON_ERROR);}
$source=readJson('groups-lower-draft.json');$expected=readJson('groups-lower-audit.json')['expanded'];
$parts=readJson('parts-reviewed-draft.json');$ids=[];
foreach($parts as $i=>$p){$ids[$p['ipl_num']]=$i+1;}
$groups=new Collection();$options=new Collection();$groupIds=[];$optionIds=[];$next=1;
foreach($source as $i=>$s){
    $g=new ManualPartGroup();$g->forceFill(['id'=>$i+1,'code'=>$s['code'],'type'=>$s['type'],'behavior'=>$s['behavior'],'name'=>$s['name'],'applies_to'=>$s['scopes']]);
    $groupIds[$s['code']]=$g->id;$os=new Collection();
    foreach($s['options'] as $o){
        $m=new ManualPartGroupOption();$m->forceFill(['id'=>$next++,'manual_part_group_id'=>$g->id,'component_id'=>$ids[$o['ipl']],'ipl_num'=>$o['ipl'],'is_default'=>$o['default']]);
        $m->setRelation('coverages',new Collection());$os->push($m);$options->put($m->id,$m);$optionIds[$s['code'].'/'.$o['ipl']]=$m->id;
    }
    $g->setRelation('options',$os);$groups->put($g->id,$g);
}
foreach($source as $s){foreach($s['coverages'] as $c){
    $owner=$options[$optionIds[$s['code'].'/'.$c['owner']]];
    $member=new ManualPartGroupCoverage();$member->forceFill(['manual_part_group_option_id'=>$owner->id,'component_id'=>$c['ipl']?$ids[$c['ipl']]:null,'covered_manual_part_group_option_id'=>!empty($c['child_group'])?$optionIds[$c['child_group'].'/'.$c['child_option']]:null,'qty'=>$c['qty'],'applies_to'=>$s['scopes']]);
    $owner->coverages->push($member);
}}
$resolver=new App\Services\PartGroupCoverageResolver();
$method=new ReflectionMethod($resolver,'expandBundleMember');$method->setAccessible(true);$cases=0;
foreach($groups as $g){if($g->behavior!=='bundle'){continue;}$opt=$g->options->firstWhere('is_default',true);
    foreach(['prl','ndt','cad','stress','paint'] as $scope){foreach([1,2] as $mult){$cov=[];
        foreach($opt->coverages as $member){$args=[&$cov,$member,$mult,$scope,'in-memory audit',$g,$opt,$groups,$options,[(int)$opt->id=>true]];$method->invokeArgs($resolver,$args);}
        $actual=array_map(fn($x)=>$x['covered_qty'],$cov);$want=[];
        foreach($expected[$g->code] as $ipl=>$qty){$want[$ids[$ipl]]=$qty*$mult;}
        ksort($actual);ksort($want);
        if($actual!==$want){throw new RuntimeException('Resolver mismatch '.$g->code.' '.$scope.' x'.$mult);}$cases++;
    }}
}
$report=['result'=>'PASS','assy_groups'=>count($expected),'cases'=>$cases,'scopes'=>['prl','ndt','cad','stress','paint'],'multipliers'=>[1,2],'database_connected'=>false,'sql_import_tested'=>false];
file_put_contents(__DIR__.'/lower-resolver-tests.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
