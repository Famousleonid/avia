<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\StdProcess;
use App\Models\WorkorderPartGroupSelection;
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
