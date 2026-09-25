<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\Unit;
use App\Services\LogCardAssemblyIdentity;
use App\Services\ManualPartGroupCompositionResolver;
use App\Services\PartGroupCoverageResolver;
use App\Services\WorkorderPartScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AssyConfigurationChoiceTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_selected_upper_torque_link_is_included_once_and_not_separately_ordered(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $head = $this->part($manual->id, '1-780', '190-70453-401');
        $parent = $this->assy($manual->id, $head);
        $children = [];
        foreach (['8-150' => '190-70468-401', '8-150A' => '190-70468-403', '8-150B' => '190-70468-405'] as $ipl => $pn) {
            $childHead = $this->part($manual->id, $ipl, $pn);
            $member = $this->part($manual->id, $ipl.'-MEMBER', 'MEMBER-'.$ipl);
            $child = $this->assy($manual->id, $childHead);
            $child->coverages()->create(['component_id' => $member->id, 'qty' => 2, 'applies_to' => ManualPartGroup::validScopes()]);
            $edge = $parent->coverages()->create([
                'covered_manual_part_group_option_id' => $child->id,
                'qty' => 1, 'choice_slot' => 'upper_torque_link',
                'applies_to' => ManualPartGroup::validScopes(),
            ]);
            $children[$ipl] = [$child, $member, $edge];
        }
        $wo->update(['scope_type' => Unit::SCOPE_PART_GROUP_OPTION, 'scope_part_group_option_id' => $parent->id]);
        Tdr::create([
            'workorder_id' => $wo->id, 'component_id' => $head->id, 'order_component_id' => $head->id,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id, 'qty' => 1,
        ]);

        $this->assertCount(3, app(\App\Services\WorkorderAssyConfiguration::class)->slots($wo)[0]['candidates']);

        $before = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'prl');
        foreach ($children as [$child, $member]) {
            $this->assertArrayNotHasKey($member->id, $before);
        }

        $this->actingAs($admin)->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $parent->id, 'choice_slot' => 'upper_torque_link',
            'selected_coverage_id' => $children['8-150B'][2]->id,
        ])->assertOk()->assertJsonPath('slots.0.selected_coverage_id', $children['8-150B'][2]->id);

        foreach (ManualPartGroup::validScopes() as $form) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $form);
            $this->assertSame(2, $coverage[$children['8-150B'][1]->id]['covered_qty']);
            $this->assertArrayHasKey($children['8-150B'][0]->component_id, $coverage);
            $this->assertSame(PHP_INT_MAX, $coverage[$children['8-150'][0]->component_id]['covered_qty']);
            $this->assertSame(PHP_INT_MAX, $coverage[$children['8-150A'][0]->component_id]['covered_qty']);
            $this->assertArrayNotHasKey($children['8-150'][1]->id, $coverage);
            $this->assertArrayNotHasKey($children['8-150A'][1]->id, $coverage);
        }
        $scope = app(WorkorderPartScopeResolver::class)->componentQuantities($wo);
        $this->assertSame(2, $scope[$children['8-150B'][1]->id]);
        $this->assertArrayNotHasKey($children['8-150A'][1]->id, $scope);

        $groups = ManualPartGroup::where('manual_id', $manual->id)->with('options.coverages')->get();
        $members = app(ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups, $wo);
        $this->assertContains($children['8-150B'][1]->id, $members[$parent->manual_part_group_id]->all());
        $this->assertNotContains($children['8-150A'][0]->component_id, $members[$parent->manual_part_group_id]->all());
        $this->assertNotContains($children['8-150A'][1]->id, $members[$parent->manual_part_group_id]->all());
        $allowed = app(LogCardAssemblyIdentity::class)->groupsForWorkorder($groups, $wo)->pluck('id');
        $this->assertContains($children['8-150B'][0]->manual_part_group_id, $allowed);
        $this->assertNotContains($children['8-150A'][0]->manual_part_group_id, $allowed);
        $this->assertSame(1, Tdr::where('workorder_id', $wo->id)->count());

        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $parent->id, 'choice_slot' => 'upper_torque_link',
            'selected_coverage_id' => $children['8-150A'][2]->id,
        ])->assertOk();
        $after = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'prl');
        $this->assertSame(2, $after[$children['8-150A'][1]->id]['covered_qty']);
        $this->assertArrayNotHasKey($children['8-150B'][1]->id, $after);

        $this->putJson(route('manuals.part-groups.update', [$manual, $parent->manual_part_group_id]), [
            'name' => 'Root ASSY edited', 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
            'component_ids' => [$head->id], 'default_component_id' => $head->id,
            'member_qty' => [$head->id => 1], 'included_group_option_ids' => [],
        ])->assertOk();
        $this->assertSame(3, $parent->coverages()->where('choice_slot', 'upper_torque_link')->count());
        $this->assertCount(3, app(\App\Services\WorkorderAssyConfiguration::class)->slots($wo)[0]['candidates']);
        $other = $this->part($manual->id, '8-170', 'UNRELATED');
        $unrelated = $parent->coverages()->create(['component_id' => $other->id,
            'choice_slot' => 'different_slot', 'applies_to' => ManualPartGroup::validScopes()]);
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $parent->id, 'choice_slot' => 'upper_torque_link',
            'selected_coverage_id' => $unrelated->id,
        ])->assertStatus(422);
        $this->assertSame($children['8-150A'][2]->id,
            app(\App\Services\WorkorderAssyConfiguration::class)->selectedCoverageIds($wo)[
                \App\Services\WorkorderAssyConfiguration::key($parent->id, 'upper_torque_link')
            ]);
        $wo->forceFill(['done_at' => now()])->save();
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $parent->id, 'choice_slot' => 'upper_torque_link',
            'selected_coverage_id' => $children['8-150B'][2]->id,
        ])->assertStatus(422);
    }

    public function test_sb07_axle_choice_keeps_exact_pn_and_spacer_without_letter_sibling(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $tube = $this->assy($manual->id, $this->part($manual->id, '9-390B', '190-70465-601'));
        $spacerOld = $this->part($manual->id, '9-140', '190-70491-001');
        $spacerNew = $this->part($manual->id, '9-140A', '190-70491-003');
        $axle303 = $this->part($manual->id, '9-542', '190-70460-303');
        $axle309 = $this->part($manual->id, '9-542A', '190-70460-309');
        $axle305 = $this->part($manual->id, '9-543', '190-70460-305');
        $tube->coverages()->create(['component_id' => $spacerNew->id, 'qty' => 1,
            'expand_ipl_family' => false, 'applies_to' => ManualPartGroup::validScopes()]);
        $first = $tube->coverages()->create(['component_id' => $axle303->id, 'qty' => 1,
            'choice_slot' => 'wheel_axle', 'expand_ipl_family' => false,
            'applies_to' => ManualPartGroup::validScopes()]);
        $second = $tube->coverages()->create(['component_id' => $axle305->id, 'qty' => 1,
            'choice_slot' => 'wheel_axle', 'expand_ipl_family' => false,
            'applies_to' => ManualPartGroup::validScopes()]);
        $wo->update(['scope_type' => Unit::SCOPE_PART_GROUP_OPTION, 'scope_part_group_option_id' => $tube->id]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $tube->component_id,
            'order_component_id' => $tube->component_id,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id, 'qty' => 1]);
        $this->actingAs($admin)->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $tube->id, 'choice_slot' => 'wheel_axle',
            'selected_coverage_id' => $first->id,
        ])->assertOk();

        foreach (ManualPartGroup::validScopes() as $scope) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
            $this->assertSame(1, $coverage[$axle303->id]['covered_qty']);
            $this->assertSame(1, $coverage[$spacerNew->id]['covered_qty']);
            foreach ([$axle305, $axle309, $spacerOld] as $excluded) {
                $this->assertArrayNotHasKey($excluded->id, $coverage);
            }
        }
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $tube->id, 'choice_slot' => 'wheel_axle',
            'selected_coverage_id' => $second->id,
        ])->assertOk();
        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'prl');
        $this->assertArrayHasKey($axle305->id, $coverage);
        $this->assertArrayNotHasKey($axle303->id, $coverage);
    }

    public function test_form_specific_candidate_never_falls_back_to_a_different_assy_choice(): void
    {
        $manual = $this->createManual();
        $head = $this->part($manual->id, '1-780', 'ROOT-1');
        $parent = $this->assy($manual->id, $head);
        $prlPart = $this->part($manual->id, '8-150', 'PRL-PART');
        $ndtPart = $this->part($manual->id, '8-151', 'NDT-PART');
        $prl = $parent->coverages()->create(['component_id' => $prlPart->id, 'choice_slot' => 'optional_fit',
            'applies_to' => ['prl']]);
        $parent->coverages()->create(['component_id' => $ndtPart->id, 'choice_slot' => 'optional_fit',
            'applies_to' => ['ndt']]);
        $members = app(\App\Services\WorkorderAssyConfiguration::class);
        $selected = [$members::key($parent->id, 'optional_fit') => $prl->id];
        $parent->load('coverages');

        $this->assertContains($prl->id, $members->members($parent, $selected, 'prl')->pluck('id')->all());
        $this->assertSame([$head->id],
            $members->members($parent, $selected, 'ndt')->pluck('component_id')->all());
    }

    public function test_changing_parent_assy_clears_unreachable_nested_choice(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $root = $this->assy($manual->id, $this->part($manual->id, '1-780', 'ROOT'));
        $old = $this->assy($manual->id, $this->part($manual->id, '9-390', 'TUBE-OLD'));
        $new = $this->assy($manual->id, $this->part($manual->id, '9-390B', 'TUBE-NEW'));
        $oldEdge = $root->coverages()->create(['covered_manual_part_group_option_id' => $old->id,
            'choice_slot' => 'sliding_tube', 'applies_to' => ManualPartGroup::validScopes()]);
        $newEdge = $root->coverages()->create(['covered_manual_part_group_option_id' => $new->id,
            'choice_slot' => 'sliding_tube', 'applies_to' => ManualPartGroup::validScopes()]);
        $axle1 = $new->coverages()->create(['component_id' => $this->part($manual->id, '9-542', 'AXLE-303')->id,
            'choice_slot' => 'wheel_axle', 'applies_to' => ManualPartGroup::validScopes()]);
        $new->coverages()->create(['component_id' => $this->part($manual->id, '9-543', 'AXLE-305')->id,
            'choice_slot' => 'wheel_axle', 'applies_to' => ManualPartGroup::validScopes()]);
        $wo->update(['scope_type' => Unit::SCOPE_PART_GROUP_OPTION, 'scope_part_group_option_id' => $root->id]);
        $this->actingAs($admin)->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $root->id, 'choice_slot' => 'sliding_tube',
            'selected_coverage_id' => $newEdge->id,
        ])->assertOk();
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $new->id, 'choice_slot' => 'wheel_axle',
            'selected_coverage_id' => $axle1->id,
        ])->assertOk();
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $root->id, 'choice_slot' => 'sliding_tube',
            'selected_coverage_id' => $oldEdge->id,
        ])->assertOk();
        $this->assertDatabaseMissing('workorder_assy_configuration_choices', [
            'workorder_id' => $wo->id, 'parent_option_id' => $new->id, 'choice_slot' => 'wheel_axle',
        ]);
        $this->patchJson(route('workorders.assy-configuration.update', $wo), [
            'parent_option_id' => $root->id, 'choice_slot' => 'sliding_tube',
            'selected_coverage_id' => $newEdge->id,
        ])->assertOk();
        $slot = collect(app(\App\Services\WorkorderAssyConfiguration::class)->slots($wo))
            ->firstWhere('slot', 'wheel_axle');
        $this->assertSame(0, $slot['selected_coverage_id']);
    }

    private function part(int $manualId, string $ipl, string $pn): Component
    {
        return Component::create(['manual_id' => $manualId, 'ipl_num' => $ipl,
            'part_number' => $pn, 'name' => $pn, 'units_assy' => 1]);
    }

    private function assy(int $manualId, Component $head): \App\Models\ManualPartGroupOption
    {
        $group = ManualPartGroup::create(['manual_id' => $manualId, 'code' => uniqid('ASSY-CFG-'),
            'name' => $head->part_number, 'type' => 'assy', 'behavior' => 'bundle',
            'applies_to' => ManualPartGroup::validScopes()]);
        $option = $group->options()->create(['component_id' => $head->id,
            'part_number' => $head->part_number, 'ipl_num' => $head->ipl_num,
            'option_kind' => 'assy', 'is_default' => true]);
        $option->coverages()->create(['component_id' => $head->id, 'qty' => 1,
            'applies_to' => ManualPartGroup::validScopes()]);

        return $option;
    }
}
