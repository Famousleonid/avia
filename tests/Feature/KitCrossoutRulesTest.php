<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Component, ManualPartGroup, Necessary, Tdr, Workorder, WorkorderKitPrlCrossout, WorkorderPartGroupSelection};
use App\Services\PartGroupCoverageResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class KitCrossoutRulesTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function workorder(): Workorder
    {
        return $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id]);
    }

    private function part(Workorder $wo, string $ipl, string $pn, int $qty = 1, bool $bushing = false): Component
    {
        return Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
            'part_number' => $pn, 'name' => $bushing ? 'BUSHING' : 'PART', 'units_assy' => $qty,
            'kit' => true, 'is_bush' => $bushing]);
    }

    private function bundle(Component $head, array $members, string $type = 'assy', array $scopes = ['prl'])
    {
        $group = ManualPartGroup::create(['manual_id' => $head->manual_id, 'code' => 'TEST-'.$head->id,
            'name' => $head->part_number, 'type' => $type, 'behavior' => 'bundle', 'applies_to' => $scopes]);
        $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $head->part_number,
            'ipl_num' => $head->ipl_num, 'option_kind' => $type]);
        foreach ([$head->id => 1] + $members as $id => $qty) {
            $option->coverages()->create(['component_id' => $id, 'qty' => $qty,
                'expand_ipl_family' => false, 'applies_to' => $scopes]);
        }
        return $option;
    }

    private function order(Workorder $wo, Component $head): Tdr
    {
        return Tdr::create(['workorder_id' => $wo->id, 'component_id' => $head->id,
            'order_component_id' => $head->id, 'qty' => 1,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id]);
    }

    private function kitOptions(Workorder $wo)
    {
        return app(TdrPrintFormController::class)->kitRows($wo)
            ->flatMap(fn ($row) => $row['kit_component_options'] ?? $row['prl_part_numbers'])
            ->keyBy('component_id');
    }

    /** @dataProvider bundleTypes */
    public function test_ordered_bundle_stays_in_kit_while_its_members_are_excluded(string $type): void
    {
        $wo = $this->workorder();
        $head = $this->part($wo, '6-950', '1840A0600-01');
        $bushA = $this->part($wo, '6-951', '1840-0510', 2, true);
        $bushB = $this->part($wo, '6-955', '1840-0503', 1, true);
        $bracket = $this->part($wo, '6-959', '1840-0601');
        $otherPosition = $this->part($wo, '7-100', '1840-0601');
        $this->bundle($head, [$bushA->id => 2, $bushB->id => 1, $bracket->id => 1], $type);
        $this->order($wo, $head);
        $options = $this->kitOptions($wo);
        $this->assertFalse($options[$head->id]['crossed_out']);
        $this->assertFalse($options[$otherPosition->id]['crossed_out']);
        foreach ([$bushA, $bushB, $bracket] as $part) {
            $this->assertTrue($options[$part->id]['controller_crossed_out']);
            $this->assertFalse($options[$part->id]['manual_crossed_out']);
        }
        $this->assertCount(0, app(TdrPrintFormController::class)->prlRows($wo));
        // Other consumers still need the complete composition, including its head.
        $this->assertSame(1, app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'prl')[$head->id]['covered_qty']);
    }

    public static function bundleTypes(): array { return ['ASSY' => ['assy'], 'KIT group' => ['kit']]; }

    public function test_nested_head_is_still_covered_by_its_parent_even_when_ordered_itself(): void
    {
        $wo = $this->workorder();
        $parent = $this->part($wo, '1-10', 'PARENT');
        $child = $this->part($wo, '1-20', 'CHILD');
        $member = $this->part($wo, '1-30', 'MEMBER');
        $childOption = $this->bundle($child, [$member->id => 1]);
        $parentOption = $this->bundle($parent, []);
        $parentOption->coverages()->create(['covered_manual_part_group_option_id' => $childOption->id,
            'qty' => 1, 'applies_to' => ['prl']]);
        $parentOrder = $this->order($wo, $parent);
        $this->order($wo, $child);
        $options = $this->kitOptions($wo);
        $this->assertFalse($options[$parent->id]['crossed_out']);
        $this->assertTrue($options[$child->id]['crossed_out']);
        $this->assertTrue($options[$member->id]['crossed_out']);
        $parentOrder->delete();
        $this->assertFalse($this->kitOptions($wo)[$child->id]['crossed_out']);
    }

    public function test_partial_coverage_does_not_cross_out_a_larger_kit_quantity(): void
    {
        $wo = $this->workorder();
        $head = $this->part($wo, '1-10', 'ASSY');
        $member = $this->part($wo, '1-20', 'WASHER', 2);
        $this->bundle($head, [$member->id => 1]);
        $order = $this->order($wo, $head);
        $this->assertFalse($this->kitOptions($wo)[$member->id]['crossed_out']);
        $order->update(['qty' => 2]);
        $this->assertTrue($this->kitOptions($wo)[$member->id]['crossed_out']);
        $this->assertFalse($this->kitOptions($wo)[$head->id]['crossed_out']);
    }

    public function test_alternatives_and_manual_marks_remain_positional(): void
    {
        $wo = $this->workorder();
        $a = $this->part($wo, '1-10', 'SELECTED');
        $b = $this->part($wo, '1-10A', 'SHARED');
        $other = $this->part($wo, '2-10', 'SHARED');
        $group = ManualPartGroup::create(['manual_id' => $a->manual_id, 'code' => 'ALT',
            'name' => 'Alternatives', 'type' => 'alternative_pn', 'behavior' => 'choose_one', 'applies_to' => ['prl']]);
        foreach ([$a, $b] as $part) {
            $option = $group->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number, 'ipl_num' => $part->ipl_num]);
            if ($part->id === $a->id) $chosen = $option;
        }
        WorkorderPartGroupSelection::create(['workorder_id' => $wo->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $chosen->id, 'qty' => 1]);
        $options = $this->kitOptions($wo);
        $this->assertFalse($options[$a->id]['crossed_out']);
        $this->assertTrue($options[$b->id]['crossed_out']);
        $this->assertFalse($options[$other->id]['crossed_out']);
        $mark = WorkorderKitPrlCrossout::create(['workorder_id' => $wo->id, 'component_id' => $a->id]);
        $this->assertTrue($this->kitOptions($wo)[$a->id]['manual_crossed_out']);
        $mark->delete();
        $this->assertFalse($this->kitOptions($wo)[$a->id]['crossed_out']);
        $this->assertTrue($this->kitOptions($wo)[$b->id]['controller_crossed_out']);
    }

    public function test_groups_without_prl_scope_do_not_cross_out_kit(): void
    {
        $wo = $this->workorder();
        $head = $this->part($wo, '1-10', 'ASSY');
        $member = $this->part($wo, '1-20', 'MEMBER');
        $this->bundle($head, [$member->id => 1], scopes: ['ndt']);
        $this->order($wo, $head);
        $this->assertFalse($this->kitOptions($wo)[$member->id]['crossed_out']);
    }

    public function test_partial_bushing_group_supply_is_shared_between_selected_sizes(): void
    {
        $wo = $this->workorder();
        $head = $this->part($wo, '1-10', 'ASSY');
        $original = $this->part($wo, '1-20', 'BUSH', 2, true);
        $oversize = $this->part($wo, '1-21', 'BUSH-OS', 1, true);
        $group = ManualPartGroup::create(['manual_id' => $head->manual_id, 'code' => 'BUSH',
            'name' => 'Bushing', 'type' => 'oversize', 'behavior' => 'choose_one', 'applies_to' => ['prl']]);
        foreach ([$original, $oversize] as $index => $part) {
            $option = $group->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number,
                'ipl_num' => $part->ipl_num, 'option_kind' => $index === 0 ? 'original' : 'oversize']);
            if ($index === 0) $originalOption = $option;
        }
        $bundle = $this->bundle($head, []);
        $bundle->coverages()->create(['covered_manual_part_group_option_id' => $originalOption->id,
            'qty' => 1, 'applies_to' => ['prl']]);
        $bushing = \App\Models\WoBushing::create(['workorder_id' => $wo->id]);
        foreach ([$original, $oversize] as $part) {
            \App\Models\WoBushingLine::create(['workorder_id' => $wo->id, 'wo_bushing_id' => $bushing->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'do_not_order' => false]);
        }
        $order = $this->order($wo, $head);
        $options = $this->kitOptions($wo);
        $this->assertTrue($options[$original->id]['crossed_out']);
        $this->assertFalse($options[$oversize->id]['crossed_out']);
        $order->update(['qty' => 2]);
        $this->assertTrue($this->kitOptions($wo)[$oversize->id]['crossed_out']);
    }
}
