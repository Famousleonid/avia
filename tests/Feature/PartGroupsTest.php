<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\WorkorderPartGroupSelection;
use App\Models\WoBushing;
use App\Models\WoBushingLine;
use App\Services\PartGroupCoverageResolver;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PartGroupsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_admin_can_create_assy_group_with_scoped_composition(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $memberA = $this->createPartGroupComponent($manual->id, '1-10', 'MEMBER-A');
        $memberB = $this->createPartGroupComponent($manual->id, '1-20', 'MEMBER-B');

        $response = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'Main ASSY',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt', 'cad'],
            'component_ids' => [$memberA->id, $memberB->id],
            'default_component_id' => $memberA->id,
            'order_part_number' => 'ASSY-100',
            'order_ipl_num' => '1-5',
            'member_qty' => [$memberA->id => 1, $memberB->id => 2],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertFalse(Schema::hasColumn('manual_part_groups', 'status'));
        $groupId = (int) $response->json('group.id');
        $this->assertDatabaseHas('manual_part_groups', [
            'id' => $groupId,
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
        ]);
        $optionId = ManualPartGroupOption::query()->where('manual_part_group_id', $groupId)->value('id');
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $optionId,
            'component_id' => $memberB->id,
            'qty' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('manuals.show', $manual))
            ->assertOk()
            ->assertSee('Main ASSY')
            ->assertSee('data-part-group-id="'.$groupId.'"', false)
            ->assertDontSee('manual-part-group-status')
            ->assertDontSee('manual-part-group-badge-meta')
            ->assertDontSee('manual-part-group-new')
            ->assertDontSee('Existing groups')
            ->assertDontSee('Delete a group to ungroup its parts.');
    }

    public function test_bundle_selection_covers_quantities_only_in_enabled_forms(): void
    {
        [$admin, $workorder, $member, $group, $option] = $this->bundleFixture(['prl', 'ndt'], 2);
        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id,
            'qty' => 3,
            'selected_by_user_id' => $admin->id,
        ]);

        $resolver = app(PartGroupCoverageResolver::class);
        $this->assertSame(6, $resolver->coverageForWorkorder($workorder, 'prl')[$member->id]['covered_qty']);
        $this->assertSame(6, $resolver->coverageForWorkorder($workorder, 'ndt')[$member->id]['covered_qty']);
        $this->assertArrayNotHasKey($member->id, $resolver->coverageForWorkorder($workorder, 'paint'));
    }

    public function test_admin_can_create_kit_from_an_existing_assy_group_without_loose_parts(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $base = $this->createPartGroupComponent($manual->id, '1-10', 'BASE-100');
        $assy = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Existing ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $assyOption = $assy->options()->create([
            'component_id' => $base->id, 'part_number' => 'ASSY-100',
            'option_kind' => 'assy', 'is_default' => true,
        ]);
        $assyOption->coverages()->create([
            'component_id' => $base->id, 'qty' => 1, 'applies_to' => ['prl', 'ndt'],
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'KIT with ASSY',
            'type' => ManualPartGroup::TYPE_KIT,
            'applies_to' => ['prl', 'ndt'],
            'component_ids' => [],
            'included_group_option_ids' => [$assyOption->id],
            'included_group_qty' => [$assyOption->id => 2],
            'order_part_number' => 'KIT-900',
        ]);

        $response->assertOk()->assertJsonPath('group.type', ManualPartGroup::TYPE_KIT);
        $kitOptionId = (int) $response->json('group.options.0.id');
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $kitOptionId,
            'component_id' => null,
            'covered_manual_part_group_option_id' => $assyOption->id,
            'qty' => 2,
        ]);
    }

    public function test_editing_bundle_preserves_existing_workorder_selection_and_option_id(): void
    {
        [$admin, $workorder, $member, $group, $option] = $this->bundleFixture(['prl', 'ndt'], 1);
        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id,
            'qty' => 2,
            'selected_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->putJson(
            route('manuals.part-groups.update', ['manual' => $group->manual_id, 'partGroup' => $group->id]),
            [
                'name' => 'Updated ASSY Group',
                'type' => ManualPartGroup::TYPE_ASSY,
                'applies_to' => ['prl', 'ndt'],
                'component_ids' => [$member->id],
                'default_component_id' => $member->id,
                'order_part_number' => 'ASSY-200',
                'order_ipl_num' => '1-6',
                'member_qty' => [$member->id => 3],
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('manual_part_group_options', [
            'id' => $option->id,
            'part_number' => 'ASSY-200',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('workorder_part_group_selections', [
            'workorder_id' => $workorder->id,
            'manual_part_group_option_id' => $option->id,
            'qty' => 2,
        ]);
        $this->assertSame(
            6,
            app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder->fresh(), 'ndt')[$member->id]['covered_qty']
        );
    }

    public function test_choose_one_selection_crosses_out_other_options_but_not_selected_option(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $first = $this->createPartGroupComponent($manual->id, '1-280', 'PN-STD');
        $second = $this->createPartGroupComponent($manual->id, '1-280A', 'PN-ALT');
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Alternatives',
            'behavior' => 'choose_one', 'type' => 'alternative_pn', 'applies_to' => ['prl', 'ndt'],
        ]);
        $firstOption = $group->options()->create(['component_id' => $first->id, 'part_number' => $first->part_number, 'ipl_num' => $first->ipl_num, 'is_default' => true]);
        $group->options()->create(['component_id' => $second->id, 'part_number' => $second->part_number, 'ipl_num' => $second->ipl_num]);
        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $firstOption->id, 'qty' => 1, 'selected_by_user_id' => $admin->id,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertArrayNotHasKey($first->id, $coverage);
        $this->assertSame(PHP_INT_MAX, $coverage[$second->id]['covered_qty']);
    }

    public function test_assy_base_part_order_covers_only_itself_but_complete_assy_covers_all_members(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $base = $this->createPartGroupComponent($manual->id, '1-10', '47170-103');
        $bushing = $this->createPartGroupComponent($manual->id, '1-20', 'BUSH-100');
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => '47170 ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $option = $group->options()->create([
            'component_id' => $base->id, 'part_number' => '47170-3', 'ipl_num' => '1-10',
            'option_kind' => 'assy', 'is_default' => true,
        ]);
        $option->coverages()->createMany([
            ['component_id' => $base->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
            ['component_id' => $bushing->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $base->id,
            'order_component_id' => $base->id,
            'qty' => 1,
        ]);

        $resolver = app(PartGroupCoverageResolver::class);
        $this->assertSame([], $resolver->coverageForWorkorder($workorder, 'ndt'));

        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id,
            'qty' => 1,
            'selected_by_user_id' => $admin->id,
        ]);

        $coverage = $resolver->coverageForWorkorder($workorder, 'ndt');
        $this->assertSame(1, $coverage[$base->id]['covered_qty']);
        $this->assertSame(1, $coverage[$bushing->id]['covered_qty']);
        $this->assertSame('Included in ASSY 47170-3', $coverage[$base->id]['reason']);
    }

    public function test_alternative_group_is_selected_automatically_from_tdr_order_part(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $parts = collect(['42107-33', '42-107-33A', '42107-34', '42107-34A', '42107-35'])
            ->map(fn (string $partNumber, int $index): Component => $this->createPartGroupComponent($manual->id, '2-'.(10 + $index), $partNumber));
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => '42107 alternatives',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE, 'type' => ManualPartGroup::TYPE_ALTERNATIVE,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $options = $parts->map(fn (Component $part, int $index) => $group->options()->create([
            'component_id' => $part->id, 'part_number' => $part->part_number, 'ipl_num' => $part->ipl_num,
            'option_kind' => 'alternate', 'is_default' => $index === 0, 'sort_order' => $index,
        ]));
        $selected = $parts->get(3);
        Tdr::query()->create([
            'workorder_id' => $workorder->id, 'component_id' => $selected->id,
            'order_component_id' => $selected->id, 'qty' => 1,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertArrayNotHasKey($selected->id, $coverage);
        foreach ($parts->where('id', '!=', $selected->id) as $part) {
            $this->assertSame(PHP_INT_MAX, $coverage[$part->id]['covered_qty']);
        }
        $this->assertSame($options->get(3)->id, $coverage[$parts->first()->id]['option_id']);
    }

    public function test_bushing_group_allows_original_and_oversize_together_and_covers_only_unselected_sizes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $original = $this->createPartGroupComponent($manual->id, '3-100', 'BUSH-ORIGINAL');
        $oversize = $this->createPartGroupComponent($manual->id, '3-101', 'BUSH-010');
        $largerOversize = $this->createPartGroupComponent($manual->id, '3-102', 'BUSH-020');
        $original->update(['is_bush' => true, 'bush_ipl_num' => '3-100', 'units_assy' => 2]);
        $oversize->update(['is_bush' => true, 'bush_ipl_num' => '3-100', 'units_assy' => 2]);
        $largerOversize->update(['is_bush' => true, 'bush_ipl_num' => '3-100', 'units_assy' => 2]);
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Bushing 3-100',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE, 'type' => ManualPartGroup::TYPE_OVERSIZE,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $group->options()->create([
            'component_id' => $original->id, 'part_number' => $original->part_number,
            'ipl_num' => $original->ipl_num, 'option_kind' => 'original', 'is_default' => true,
        ]);
        $oversizeOption = $group->options()->create([
            'component_id' => $oversize->id, 'part_number' => $oversize->part_number,
            'ipl_num' => $oversize->ipl_num, 'option_kind' => 'oversize',
        ]);
        $group->options()->create([
            'component_id' => $largerOversize->id, 'part_number' => $largerOversize->part_number,
            'ipl_num' => $largerOversize->ipl_num, 'option_kind' => 'oversize',
        ]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id, 'workorder_id' => $workorder->id,
            'component_id' => $original->id, 'qty' => 1, 'qty_remaining' => 1,
            'do_not_order' => false,
        ]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id, 'workorder_id' => $workorder->id,
            'component_id' => $oversize->id, 'qty' => 1, 'qty_remaining' => 1,
            'do_not_order' => false,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertArrayNotHasKey($original->id, $coverage);
        $this->assertArrayNotHasKey($oversize->id, $coverage);
        $this->assertSame(PHP_INT_MAX, $coverage[$largerOversize->id]['covered_qty']);
        $this->assertStringContainsString($original->part_number, $coverage[$largerOversize->id]['reason']);
        $this->assertStringContainsString($oversizeOption->part_number, $coverage[$largerOversize->id]['reason']);
    }

    public function test_kit_can_include_complete_assy_and_expands_its_composition(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $base = $this->createPartGroupComponent($manual->id, '4-10', 'BASE');
        $bushing = $this->createPartGroupComponent($manual->id, '4-20', 'BUSH');
        $loosePart = $this->createPartGroupComponent($manual->id, '4-30', 'LOOSE');
        $assy = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Nested ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $assyOption = $assy->options()->create([
            'component_id' => $base->id, 'part_number' => 'ASSY-NEW', 'option_kind' => 'assy', 'is_default' => true,
        ]);
        $assyOption->coverages()->createMany([
            ['component_id' => $base->id, 'qty' => 1, 'applies_to' => ['prl', 'ndt']],
            ['component_id' => $bushing->id, 'qty' => 2, 'applies_to' => ['prl', 'ndt']],
        ]);
        $kit = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Complete KIT',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_KIT,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $kitOption = $kit->options()->create(['part_number' => 'KIT-500', 'option_kind' => 'kit', 'is_default' => true]);
        $kitOption->coverages()->createMany([
            ['component_id' => $loosePart->id, 'qty' => 3, 'applies_to' => ['prl', 'ndt']],
            ['covered_manual_part_group_option_id' => $assyOption->id, 'qty' => 2, 'applies_to' => ['prl', 'ndt']],
        ]);
        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id, 'manual_part_group_id' => $kit->id,
            'manual_part_group_option_id' => $kitOption->id, 'qty' => 1,
            'selected_by_user_id' => $admin->id,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertSame(2, $coverage[$base->id]['covered_qty']);
        $this->assertSame(4, $coverage[$bushing->id]['covered_qty']);
        $this->assertSame(3, $coverage[$loosePart->id]['covered_qty']);
        $this->assertSame('Included in KIT KIT-500', $coverage[$base->id]['reason']);
    }

    public function test_all_std_snapshots_keep_fully_covered_row_for_visible_crossout_and_exclude_it_from_qty(): void
    {
        $stdFlags = [
            StdProcess::STD_NDT => 'ndt_list',
            StdProcess::STD_CAD => 'cad_list',
            StdProcess::STD_STRESS => 'stress_relief_list',
            StdProcess::STD_PAINT => 'paint_list',
        ];
        [$admin, $workorder, $member, $group, $option] = $this->bundleFixture(array_keys($stdFlags), 1);
        $member->update(array_merge(array_fill_keys(array_values($stdFlags), true), ['units_assy' => 2]));
        foreach (array_keys($stdFlags) as $std) {
            StdProcess::query()->updateOrCreate(
                ['manual_id' => $member->manual_id, 'component_id' => $member->id, 'std' => $std],
                ['process' => '1', 'qty' => 2]
            );
        }
        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id, 'qty' => 2, 'selected_by_user_id' => $admin->id,
        ]);

        foreach (array_keys($stdFlags) as $std) {
            $rows = app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($workorder, $std);
            $row = collect($rows)->firstWhere('component_id', $member->id);

            $this->assertNotNull($row, "Missing {$std} group row");
            $this->assertTrue($row['group_crossed_out'], "{$std} row was not crossed out");
            $this->assertSame(0, $row['qty']);
            $this->assertSame(2, $row['group_covered_qty']);
            $this->assertSame('Included in ASSY ASSY-100', $row['group_crossout_reason']);
        }
    }

    public function test_kit_prl_crosses_out_group_member_only_when_selected_bundle_covers_required_quantity(): void
    {
        [$admin, $workorder, $member, $group, $option] = $this->bundleFixture(['prl'], 1);
        $member->update(['kit' => true, 'units_assy' => 2]);
        $selection = WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id,
            'qty' => 1,
            'selected_by_user_id' => $admin->id,
        ]);

        $partialResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));
        $partialResponse->assertOk();
        $this->assertMatchesRegularExpression(
            '/data-kit-prl-component-id="'.$member->id.'".*?data-kit-prl-controller-crossed-out="0"/s',
            $partialResponse->getContent()
        );

        $selection->update(['qty' => 2]);
        $coveredResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));
        $coveredResponse->assertOk();
        $this->assertMatchesRegularExpression(
            '/data-kit-prl-component-id="'.$member->id.'".*?data-kit-prl-controller-crossed-out="1"/s',
            $coveredResponse->getContent()
        );
    }

    public function test_tdr_page_uses_clear_assy_kit_button_and_does_not_show_it_for_alternatives_only(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $first = $this->createPartGroupComponent($manual->id, '5-10', 'ALT-A');
        $second = $this->createPartGroupComponent($manual->id, '5-20', 'ALT-B');
        $alternative = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Alternatives only',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE, 'type' => ManualPartGroup::TYPE_ALTERNATIVE,
            'applies_to' => ['prl'],
        ]);
        foreach ([$first, $second] as $index => $component) {
            $alternative->options()->create([
                'component_id' => $component->id, 'part_number' => $component->part_number,
                'ipl_num' => $component->ipl_num, 'is_default' => $index === 0,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('tdrs.show', ['id' => $workorder->id]))
            ->assertOk()
            ->assertDontSee('ASSY / KIT');

        $assy = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Selectable ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl'],
        ]);
        $assyOption = $assy->options()->create([
            'component_id' => $first->id, 'part_number' => 'ASSY-500',
            'option_kind' => 'assy', 'is_default' => true,
        ]);
        $assyOption->coverages()->create(['component_id' => $first->id, 'qty' => 1, 'applies_to' => ['prl']]);

        $this->actingAs($admin)
            ->get(route('tdrs.show', ['id' => $workorder->id]))
            ->assertOk()
            ->assertSee('ASSY / KIT')
            ->assertSee('Select a complete ASSY or KIT only when its new P/N is being ordered.');
    }

    public function test_legacy_import_creates_group_and_preserves_assembly_link(): void
    {
        $manual = $this->createManual();
        $component = $this->createPartGroupComponent($manual->id, '2-20', 'LEGACY-MEMBER');
        $assembly = ComponentAssembly::query()->create([
            'component_id' => $component->id,
            'assy_part_number' => 'LEGACY-ASSY',
            'assy_ipl_num' => '2-10',
            'units_assy' => '2',
        ]);

        $exit = Artisan::call('parts:import-legacy-groups', ['--manual' => $manual->id, '--apply' => true]);

        $this->assertSame(0, $exit);
        $group = ManualPartGroup::query()->where('manual_id', $manual->id)->firstOrFail();
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'legacy_component_assembly_id' => $assembly->id,
            'component_id' => $component->id,
            'qty' => 2,
        ]);
    }

    private function bundleFixture(array $scopes, int $memberQty): array
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $member = $this->createPartGroupComponent($manual->id, '1-10', 'MEMBER');
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'ASSY Group',
            'behavior' => 'bundle', 'type' => 'assy', 'applies_to' => $scopes,
        ]);
        $option = $group->options()->create(['part_number' => 'ASSY-100', 'ipl_num' => '1-5', 'option_kind' => 'assy', 'is_default' => true]);
        $option->coverages()->create(['component_id' => $member->id, 'qty' => $memberQty, 'applies_to' => $scopes]);

        return [$admin, $workorder, $member, $group, $option];
    }

    private function createPartGroupComponent(int $manualId, string $ipl, string $partNumber): Component
    {
        return Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => $ipl,
            'part_number' => $partNumber,
            'name' => 'Part '.$partNumber,
            'units_assy' => 1,
        ]);
    }
}
