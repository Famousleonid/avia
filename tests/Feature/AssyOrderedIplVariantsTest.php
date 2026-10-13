<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Component, ManualPartGroup, Necessary, Tdr, Workorder};
use App\Services\{ManualPartGroupCompositionResolver, PartGroupCoverageResolver, WorkorderStdProcessItemsService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AssyOrderedIplVariantsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider assemblyPaths */
    public function test_exact_assy_member_excludes_unordered_ipl_variants_from_all_work_lists(bool $nested): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id]);
        $head = $this->part($wo, '9A-430B', '2801A1200-01');
        $family = collect(['9A-510', '9A-510A', '9A-510B', '9A-510X'])->map(
            fn ($ipl) => $this->part($wo, $ipl, 'PN-'.$ipl, true)
        );
        $member = $family->firstWhere('ipl_num', '9A-510B');
        $member->update(['part_number' => '2505-1401']);
        $family->firstWhere('ipl_num', '9A-510A')->update(['units_assy' => 3]);
        $outsiders = collect([
            $this->part($wo, '9-510A', $member->part_number, true),
            $this->part($wo, '9A-511', $member->part_number, true),
            $this->part($wo, '9A-510C', 'BUSHING', true),
            $this->part($wo, '9A-510Z', 'OTHER-MANUAL', true),
        ]);
        $outsiders[2]->update(['is_bush' => true]);
        $outsiders[3]->update(['manual_id' => $this->createManual()->id]);
        $option = $this->assembly($head);
        $edge = $option->coverages()->create(['component_id' => $member->id, 'qty' => 1,
            'expand_ipl_family' => false, 'applies_to' => ManualPartGroup::validScopes()]);
        $orderHead = $head;
        if ($nested) {
            $orderHead = $this->part($wo, '9A-400', 'PARENT');
            $parent = $this->assembly($orderHead);
            $parent->coverages()->create(['covered_manual_part_group_option_id' => $option->id,
                'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()]);
        }
        $order = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $orderHead->id,
            'order_component_id' => $orderHead->id, 'qty' => 1,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id]);
        foreach (ManualPartGroup::validScopes() as $scope) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
            $this->assertSame(1, $coverage[$member->id]['covered_qty']);
            foreach ($family->where('id', '!=', $member->id) as $part) {
                $this->assertSame(PHP_INT_MAX, $coverage[$part->id]['covered_qty']);
            }
            foreach ($outsiders as $part) $this->assertArrayNotHasKey($part->id, $coverage);
        }
        foreach (['ndt', 'cad', 'stress', 'paint'] as $scope) {
            $rows = collect(app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($wo, $scope));
            $this->assertCount(0, $rows->whereIn('component_id', $family->pluck('id')));
        }
        $this->actingAs($admin)->get(route('tdrs.ndtStd', $wo))->assertOk()->assertDontSee('9A-510A');
        $options = app(TdrPrintFormController::class)->kitRows($wo)
            ->flatMap(fn ($row) => $row['kit_component_options'] ?? $row['prl_part_numbers'])->keyBy('component_id');
        foreach ($family as $part) $this->assertTrue($options[$part->id]['controller_crossed_out']);
        $composition = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup(
            ManualPartGroup::where('manual_id', $head->manual_id)->with('options.coverages')->get()
        );
        $this->assertEqualsCanonicalizing([$head->id, $member->id], $composition[$option->manual_part_group_id]->all());
        $this->assertFalse($edge->fresh()->expand_ipl_family);
        $order->delete();
        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'ndt');
        foreach ($family as $part) $this->assertArrayNotHasKey($part->id, $coverage);
    }

    private function part(Workorder $wo, string $ipl, string $pn, bool $processes = false): Component
    {
        return Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
            'part_number' => $pn, 'name' => 'SLEEVE', 'units_assy' => 1, 'kit' => $processes,
            'ndt_list' => $processes, 'cad_list' => $processes,
            'stress_relief_list' => $processes, 'paint_list' => $processes]);
    }

    private function assembly(Component $head)
    {
        $group = ManualPartGroup::create(['manual_id' => $head->manual_id, 'code' => 'ASSY-'.$head->id,
            'name' => $head->part_number, 'type' => 'assy', 'behavior' => 'bundle',
            'applies_to' => ManualPartGroup::validScopes()]);
        $option = $group->options()->create(['component_id' => $head->id,
            'part_number' => $head->part_number, 'ipl_num' => $head->ipl_num, 'option_kind' => 'assy']);
        $option->coverages()->create(['component_id' => $head->id, 'qty' => 1,
            'expand_ipl_family' => false, 'applies_to' => ManualPartGroup::validScopes()]);
        return $option;
    }

    public static function assemblyPaths(): array
    {
        return ['direct ASSY' => [false], 'nested ASSY' => [true]];
    }
}
