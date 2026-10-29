<?php

namespace Tests\Feature;

use App\Models\{ManualPartGroup, WorkorderPartGroupSelection, WoBushing, WoBushingLine};
use App\Services\PartGroupCoverageResolver;
use App\Services\PartVariantGrouping;
use App\Support\BushingPrlGrouping;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\{BuildsDomainData, TestCase};

class PartsCalculationSnapshotTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_snapshot_preserves_scope_flags_and_next_calculation_reads_changes(): void
    {
        $wo = $this->createWorkorder();
        $manual = $wo->unit->manual;
        $head = $this->createComponent($manual, ['ipl_num' => '1-10']);
        $child = $this->createComponent($manual, ['ipl_num' => '1-20']);
        $group = ManualPartGroup::create(['manual_id' => $manual->id, 'type' => 'assy',
            'behavior' => 'bundle', 'code' => 'SNAPSHOT', 'name' => 'Snapshot ASSY', 'applies_to' => ['prl', 'ndt']]);
        $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $head->part_number]);
        $option->coverages()->create(['component_id' => $head->id, 'qty' => 1, 'applies_to' => ['prl']]);
        $coverage = $option->coverages()->create(['component_id' => $child->id, 'qty' => 2, 'applies_to' => ['prl', 'ndt']]);
        WorkorderPartGroupSelection::create(['workorder_id' => $wo->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id, 'qty' => 1]);
        $bush = $this->createComponent($manual, ['ipl_num' => '2-10', 'is_bush' => true]);
        $oversize = $this->createComponent($manual, ['ipl_num' => '2-10A', 'is_bush' => true]);
        $sizes = ManualPartGroup::create(['manual_id' => $manual->id, 'type' => 'oversize',
            'behavior' => 'choose_one', 'code' => 'SIZES', 'name' => 'Sizes', 'applies_to' => ['prl']]);
        foreach ([$bush, $oversize] as $part) $sizes->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number]);
        $header = WoBushing::create(['workorder_id' => $wo->id]);
        WoBushingLine::create(['wo_bushing_id' => $header->id, 'workorder_id' => $wo->id,
            'component_id' => $bush->id, 'qty' => 1, 'qty_remaining' => 1]);

        $resolver = app(PartGroupCoverageResolver::class);
        $snapshot = $resolver->forSnapshot($wo);
        foreach (ManualPartGroup::validScopes() as $scope) {
            foreach ([false, true] as $excludeHead) {
                foreach ([false, true] as $bushingChoices) {
                    $expected = $resolver->coverageForWorkorder($wo, $scope, $excludeHead, $bushingChoices);
                    $this->assertSame($expected, $snapshot->coverageForWorkorder($wo, $scope, $excludeHead, $bushingChoices));
                }
            }
        }
        $this->assertArrayHasKey($head->id, $snapshot->coverageForWorkorder($wo, 'prl'));
        $this->assertArrayNotHasKey($head->id, $snapshot->coverageForWorkorder($wo, 'prl', true));
        $this->assertArrayHasKey($oversize->id, $snapshot->coverageForWorkorder($wo, 'prl'));
        $this->assertArrayNotHasKey($oversize->id, $snapshot->coverageForWorkorder($wo, 'prl', false, false));
        $coverage->update(['qty' => 4]);
        $this->assertSame(4, $resolver->forSnapshot($wo)->coverageForWorkorder($wo, 'prl')[$child->id]['covered_qty']);
        $other = $this->createWorkorder(['unit_id' => $wo->unit_id]);
        $this->assertSame([], $snapshot->coverageForWorkorder($other, 'prl'));

        $grouping = app(PartVariantGrouping::class);
        $catalogue = $grouping->forSnapshot();
        foreach (['prl', 'ndt', 'bushing'] as $scope) {
            $this->assertSame($grouping->explicitKeys([$manual->id], $scope),
                $catalogue->explicitKeys([$manual->id], $scope));
        }
        $this->assertArrayHasKey($oversize->id, $catalogue->explicitKeys([$manual->id], 'bushing'));
        $this->assertArrayNotHasKey($oversize->id, $catalogue->explicitKeys([$manual->id], 'ndt'));
        $sizes->delete();
        $this->assertArrayNotHasKey($oversize->id,
            $grouping->forSnapshot()->explicitKeys([$manual->id], 'bushing'));
    }

    public function test_capacity_options_are_batched_without_changing_original_or_legacy_limits(): void
    {
        $manual = $this->createManual();
        $families = collect();
        foreach (range(1, 12) as $i) {
            $original = $this->createComponent($manual, ['ipl_num' => "{$i}-10", 'units_assy' => $i]);
            $oversize = $this->createComponent($manual, ['ipl_num' => "{$i}-10A", 'units_assy' => 'AR']);
            $group = ManualPartGroup::create(['manual_id' => $manual->id, 'type' => 'oversize',
                'behavior' => 'choose_one', 'code' => 'SIZE-'.$i, 'name' => 'Size '.$i]);
            $group->options()->create(['component_id' => $oversize->id, 'part_number' => $oversize->part_number, 'sort_order' => 1]);
            $group->options()->create(['component_id' => $original->id, 'part_number' => $original->part_number, 'sort_order' => 2,
                'option_kind' => $i % 2 ? 'original' : 'standard']);
            $families->push(collect([$oversize, $original]));
        }
        $expected = $families->map(fn ($family) => BushingPrlGrouping::capacity($family))->all();
        DB::enableQueryLog(); DB::flushQueryLog();
        try {
            $options = BushingPrlGrouping::capacityOptions($families->flatten(1));
            $actual = $families->map(fn ($family) => BushingPrlGrouping::capacity($family, $options))->all();
            $this->assertCount(1, DB::getQueryLog());
        } finally { DB::disableQueryLog(); }
        $this->assertSame(range(1, 12), $expected);
        $this->assertSame($expected, $actual);
    }
}
