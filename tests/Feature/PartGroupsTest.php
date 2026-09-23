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

    public function test_saving_bushing_group_does_not_set_member_flags(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $parts = collect(['1-10', '1-10A', '1-10B'])->map(fn ($ipl) =>
            $this->createPartGroupComponent($manual->id, $ipl, 'AUTO-'.$ipl));
        $parts->each(fn ($part) => $part->update(['is_bush' => false, 'units_assy' => 2]));
        $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ]);
        $payload = ['name' => 'Auto flag', 'type' => 'alternative_pn', 'applies_to' => ['prl'],
            'component_ids' => [$parts[0]->id, $parts[1]->id]];
        $this->postJson(route('manuals.part-groups.store', $manual), $payload)->assertOk();
        $group = ManualPartGroup::where('manual_id', $manual->id)->firstOrFail();
        $this->assertFalse($parts[0]->fresh()->is_bush);
        $this->assertFalse($parts[1]->fresh()->is_bush);

        $payload['type'] = 'oversize';
        $this->putJson(route('manuals.part-groups.update', [$manual, $group]), $payload)->assertOk();
        $this->assertFalse($parts[0]->fresh()->is_bush);
        $this->assertFalse($parts[1]->fresh()->is_bush);
        $this->assertFalse($parts[2]->fresh()->is_bush);

        $payload['component_ids'] = [$parts[0]->id, $parts[2]->id];
        $this->putJson(route('manuals.part-groups.update', [$manual, $group]), $payload)->assertOk();
        $this->deleteJson(route('manuals.part-groups.destroy', [$manual, $group]))->assertOk();
        foreach ($parts as $part) {
            $this->assertFalse($part->fresh()->is_bush);
            $this->assertSame('2', (string) $part->fresh()->units_assy);
        }
    }

    public function test_admin_can_create_assy_group_with_scoped_composition(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $memberA = $this->createPartGroupComponent($manual->id, '1-10', 'MEMBER-A');
        $memberB = $this->createPartGroupComponent($manual->id, '1-20', 'MEMBER-B');

        $response = $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ])->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'Main ASSY',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt', 'cad'],
            'component_ids' => [$memberA->id, $memberB->id],
            'default_component_id' => $memberA->id,
            'order_part_number' => 'SHOULD-BE-IGNORED',
            'order_ipl_num' => '9-999',
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
        $this->assertDatabaseHas('manual_part_group_options', [
            'id' => $optionId,
            'component_id' => $memberA->id,
            'part_number' => 'MEMBER-A',
            'ipl_num' => '1-10',
        ]);
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
            ->assertDontSee('Delete a group to ungroup its parts.')
            ->assertDontSee('New ASSY / KIT P/N');
    }

    public function test_manual_badges_show_assy_only_on_its_head_not_on_members(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $head = $this->createPartGroupComponent($manual->id, '1-270', 'LOWER-ASSY');
        $member = $this->createPartGroupComponent($manual->id, '1-320', 'BEARING');
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'BADGES-'.uniqid(),
            'name' => 'Lower ASSY', 'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl'],
        ]);
        $option = $group->options()->create([
            'component_id' => $head->id, 'part_number' => $head->part_number,
            'ipl_num' => $head->ipl_num, 'is_default' => true,
        ]);
        $option->coverages()->create(['component_id' => $member->id, 'qty' => 1, 'applies_to' => ['prl']]);
        $page = $this->actingAs($admin)->get(route('manuals.show', $manual))->assertOk();
        $badges = $page->viewData('partGroupsByComponent');
        $this->assertSame([$group->id], $badges->get($head->id)->pluck('id')->all());
        $this->assertFalse($badges->has($member->id));
        $page->assertSee('manual-part-assy-groups-container', false)
            ->assertSee('id="manual-part-group-sidebar"', false)
            ->assertSee('id="manual-part-group-included-details"', false)
            ->assertDontSee('class="form-check-input manual-part-group-assy-check"', false);
    }

    public function test_new_group_name_defaults_to_assy_part_number_or_default(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $assy = $this->createPartGroupComponent($manual->id, '1-10', 'ASSY-100');
        $member = $this->createPartGroupComponent($manual->id, '1-20', 'MEMBER-100');

        $assyResponse = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'Default',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl'],
            'component_ids' => [$assy->id, $member->id],
            'default_component_id' => $assy->id,
            'member_qty' => [$assy->id => 1, $member->id => 1],
        ]);

        $assyResponse->assertOk()->assertJsonPath('group.name', 'ASSY-100');

        $alternativeResponse = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => null,
            'type' => ManualPartGroup::TYPE_ALTERNATIVE,
            'applies_to' => ['prl'],
            'component_ids' => [$assy->id, $member->id],
            'default_component_id' => $assy->id,
        ]);

        $alternativeResponse->assertOk()->assertJsonPath('group.name', 'Default');
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
        $addedMember = $this->createPartGroupComponent($group->manual_id, '2-40', 'ADDED-ASSY-MEMBER');
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
                'component_ids' => [$member->id, $addedMember->id],
                'default_component_id' => $member->id,
                'member_qty' => [$member->id => 3, $addedMember->id => 2],
                'member_applies_to' => [$member->id => ['prl', 'ndt']],
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $option->id,
            'component_id' => $addedMember->id,
            'qty' => 2,
        ]);
        $addedCoverage = collect($response->json('group.options.0.coverages'))->firstWhere('component_id', $addedMember->id);
        $this->assertSame($addedMember->name, $addedCoverage['name']);
        $this->assertDatabaseHas('manual_part_group_options', [
            'id' => $option->id,
            'component_id' => $member->id,
            'part_number' => 'MEMBER',
            'ipl_num' => '1-10',
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

    public function test_ordering_assy_part_automatically_covers_all_group_members(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id, 'user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $base = $this->createPartGroupComponent($manual->id, '1-10', '47170-103');
        $bushing = $this->createPartGroupComponent($manual->id, '1-20', 'BUSH-100');
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => '47170 ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $option = $group->options()->create([
            'component_id' => $base->id, 'part_number' => $base->part_number, 'ipl_num' => '1-10',
            'option_kind' => 'assy', 'is_default' => true,
        ]);
        $option->coverages()->createMany([
            ['component_id' => $base->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
            ['component_id' => $bushing->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
        ]);

        $orderNew = \App\Models\Necessary::query()->firstOrCreate(['name' => 'Order New']);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $base->id,
            'order_component_id' => $base->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $bushing->id,
            'order_component_id' => $bushing->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
        ]);

        $resolver = app(PartGroupCoverageResolver::class);
        $coverage = $resolver->coverageForWorkorder($workorder, 'ndt');
        $this->assertSame(1, $coverage[$base->id]['covered_qty']);
        $this->assertSame(1, $coverage[$bushing->id]['covered_qty']);
        $this->assertSame('Included in ASSY 47170-103', $coverage[$base->id]['reason']);

        $this->actingAs($admin)
            ->get(route('tdrs.prlForm', ['id' => $workorder->id]))
            ->assertOk()
            ->assertViewHas('ordersParts', function ($rows) use ($base, $bushing): bool {
                $assyRow = collect($rows)->first(fn ($row): bool => (int) ($row->order_component_id ?? 0) === $base->id);
                $bushingRow = collect($rows)->first(fn ($row): bool => (int) ($row->order_component_id ?? 0) === $bushing->id);

                return $assyRow
                    && $bushingRow
                    && empty($assyRow->prl_crossed_out)
                    && ! empty($bushingRow->prl_crossed_out);
            });
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

    public function test_assy_rejects_an_individual_member_of_a_bushing_group(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $assy = $this->createPartGroupComponent($manual->id, '1-10', 'ASSY-100');
        $original = $this->createPartGroupComponent($manual->id, '1-20', 'BUSH-STD');
        $oversize = $this->createPartGroupComponent($manual->id, '1-21', 'BUSH-OS');
        $original->update(['is_bush' => true, 'bush_ipl_num' => '1-20']);
        $oversize->update(['is_bush' => true, 'bush_ipl_num' => '1-20']);

        $bushingGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'MPG-'.uniqid(),
            'name' => 'Bushing 1-20',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE,
            'type' => ManualPartGroup::TYPE_OVERSIZE,
            'applies_to' => ['prl'],
        ]);
        $bushingGroup->options()->create([
            'component_id' => $original->id,
            'part_number' => $original->part_number,
            'ipl_num' => $original->ipl_num,
            'option_kind' => 'original',
            'is_default' => true,
        ]);
        $bushingGroup->options()->create([
            'component_id' => $oversize->id,
            'part_number' => $oversize->part_number,
            'ipl_num' => $oversize->ipl_num,
            'option_kind' => 'oversize',
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'ASSY-100',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl'],
            'component_ids' => [$assy->id, $original->id],
            'default_component_id' => $assy->id,
            'member_qty' => [$assy->id => 1, $original->id => 1],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['component_ids'])
            ->assertJsonPath('errors.component_ids.0', 'Add the complete Bushing Original/Oversize group.');
        $this->assertDatabaseMissing('manual_part_groups', [
            'manual_id' => $manual->id,
            'name' => 'ASSY-100',
            'type' => ManualPartGroup::TYPE_ASSY,
        ]);
    }

    public function test_assy_can_include_an_assy_and_a_complete_bushing_group_recursively(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $mainPart = $this->createPartGroupComponent($manual->id, '1-10', 'MAIN-ASSY');
        $subAssyPart = $this->createPartGroupComponent($manual->id, '1-20', 'SUB-ASSY');
        $original = $this->createPartGroupComponent($manual->id, '1-30', 'BUSH-STD');
        $oversize = $this->createPartGroupComponent($manual->id, '1-31', 'BUSH-OS');
        $original->update(['is_bush' => true, 'bush_ipl_num' => '1-30', 'units_assy' => 3]);
        $oversize->update(['is_bush' => true, 'bush_ipl_num' => '1-30', 'units_assy' => 3]);

        $subAssy = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Sub ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $subAssyOption = $subAssy->options()->create([
            'component_id' => $subAssyPart->id, 'part_number' => $subAssyPart->part_number,
            'ipl_num' => $subAssyPart->ipl_num, 'option_kind' => 'assy', 'is_default' => true,
        ]);
        $subAssyOption->coverages()->create([
            'component_id' => $subAssyPart->id, 'qty' => 2, 'applies_to' => ['prl', 'ndt'],
        ]);

        $bushingGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'Bushing 1-30',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE, 'type' => ManualPartGroup::TYPE_OVERSIZE,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $originalOption = $bushingGroup->options()->create([
            'component_id' => $original->id, 'part_number' => $original->part_number,
            'ipl_num' => $original->ipl_num, 'option_kind' => 'original', 'is_default' => true,
        ]);
        $bushingGroup->options()->create([
            'component_id' => $oversize->id, 'part_number' => $oversize->part_number,
            'ipl_num' => $oversize->ipl_num, 'option_kind' => 'oversize',
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'Main nested ASSY',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt'],
            'component_ids' => [$mainPart->id],
            'default_component_id' => $mainPart->id,
            'included_group_option_ids' => [$subAssyOption->id, $originalOption->id],
            'included_group_qty' => [$subAssyOption->id => 2, $originalOption->id => 3],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $mainGroupId = (int) $response->json('group.id');
        $mainOptionId = (int) $response->json('group.options.0.id');
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $mainOptionId,
            'covered_manual_part_group_option_id' => $subAssyOption->id,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $mainOptionId,
            'covered_manual_part_group_option_id' => $originalOption->id,
            'qty' => 3,
        ]);

        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $mainGroupId,
            'manual_part_group_option_id' => $mainOptionId,
            'qty' => 2,
            'selected_by_user_id' => $admin->id,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertSame(2, $coverage[$mainPart->id]['covered_qty']);
        $this->assertSame(8, $coverage[$subAssyPart->id]['covered_qty']);
        $this->assertSame(6, $coverage[$original->id]['covered_qty']);
        $this->assertSame(6, $coverage[$oversize->id]['covered_qty']);
        $this->assertSame('Included in ASSY MAIN-ASSY', $coverage[$oversize->id]['reason']);

        $this->actingAs($admin)
            ->get(route('manuals.show', $manual))
            ->assertOk()
            ->assertViewHas('partGroupsByComponent', function ($groupsByComponent) use ($oversize, $mainGroupId): bool {
                return ! collect($groupsByComponent->get($oversize->id, []))
                    ->contains(fn (ManualPartGroup $group): bool => (int) $group->id === $mainGroupId);
            });
    }

    public function test_assy_can_include_a_complete_alternative_part_number_group(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $assy = $this->createPartGroupComponent($manual->id, '1-1', 'ASSY-100');
        $alternateA = $this->createPartGroupComponent($manual->id, '1-70', 'AXLE-A');
        $alternateB = $this->createPartGroupComponent($manual->id, '1-71', 'AXLE-B');

        $alternativeGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'MPG-'.uniqid(),
            'name' => 'Axle Wheel',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE,
            'type' => ManualPartGroup::TYPE_ALTERNATIVE,
            'applies_to' => ['prl', 'ndt'],
        ]);
        $defaultOption = $alternativeGroup->options()->create([
            'component_id' => $alternateA->id,
            'part_number' => $alternateA->part_number,
            'ipl_num' => $alternateA->ipl_num,
            'option_kind' => 'alternate',
            'is_default' => true,
            'sort_order' => 0,
        ]);
        $alternativeGroup->options()->create([
            'component_id' => $alternateB->id,
            'part_number' => $alternateB->part_number,
            'ipl_num' => $alternateB->ipl_num,
            'option_kind' => 'alternate',
            'is_default' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), [
            'name' => 'ASSY-100',
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl', 'ndt'],
            'component_ids' => [$assy->id],
            'default_component_id' => $assy->id,
            'included_group_option_ids' => [$defaultOption->id],
            'included_group_qty' => [$defaultOption->id => 1],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $assyGroupId = (int) $response->json('group.id');
        $assyOptionId = (int) $response->json('group.options.0.id');
        $this->assertDatabaseHas('manual_part_group_coverages', [
            'manual_part_group_option_id' => $assyOptionId,
            'covered_manual_part_group_option_id' => $defaultOption->id,
            'qty' => 1,
        ]);

        WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $workorder->id,
            'manual_part_group_id' => $assyGroupId,
            'manual_part_group_option_id' => $assyOptionId,
            'qty' => 1,
            'selected_by_user_id' => $admin->id,
        ]);

        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($workorder, 'ndt');

        $this->assertSame(1, $coverage[$alternateA->id]['covered_qty']);
        $this->assertSame(1, $coverage[$alternateB->id]['covered_qty']);
        $this->assertSame('Included in ASSY ASSY-100', $coverage[$alternateA->id]['reason']);
    }

    public function test_assy_group_rejects_an_indirect_nesting_cycle(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $partA = $this->createPartGroupComponent($manual->id, '2-10', 'ASSY-A');
        $partB = $this->createPartGroupComponent($manual->id, '2-20', 'ASSY-B');

        $groupA = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'ASSY A',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl'],
        ]);
        $optionA = $groupA->options()->create([
            'component_id' => $partA->id, 'part_number' => $partA->part_number,
            'ipl_num' => $partA->ipl_num, 'option_kind' => 'assy', 'is_default' => true,
        ]);
        $optionA->coverages()->create(['component_id' => $partA->id, 'qty' => 1, 'applies_to' => ['prl']]);

        $groupB = ManualPartGroup::query()->create([
            'manual_id' => $manual->id, 'code' => 'MPG-'.uniqid(), 'name' => 'ASSY B',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE, 'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ['prl'],
        ]);
        $optionB = $groupB->options()->create([
            'component_id' => $partB->id, 'part_number' => $partB->part_number,
            'ipl_num' => $partB->ipl_num, 'option_kind' => 'assy', 'is_default' => true,
        ]);
        $optionB->coverages()->createMany([
            ['component_id' => $partB->id, 'qty' => 1, 'applies_to' => ['prl']],
            ['covered_manual_part_group_option_id' => $optionA->id, 'qty' => 1, 'applies_to' => ['prl']],
        ]);

        $this->actingAs($admin)->putJson(
            route('manuals.part-groups.update', ['manual' => $manual, 'partGroup' => $groupA]),
            [
                'name' => 'ASSY A',
                'type' => ManualPartGroup::TYPE_ASSY,
                'applies_to' => ['prl'],
                'component_ids' => [$partA->id],
                'default_component_id' => $partA->id,
                'included_group_option_ids' => [$optionB->id],
                'included_group_qty' => [$optionB->id => 1],
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors('included_group_option_ids');
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
        $workorder->update(['instruction_id' => $this->createOverhaulInstruction()->id]);
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

    public function test_tdr_page_does_not_show_a_separate_assy_kit_selector(): void
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
            ->assertDontSee('ASSY / KIT')
            ->assertDontSee('Select a complete ASSY or KIT only when its new P/N is being ordered.');
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

    public function test_assy_direct_letter_family_is_covered_without_an_alternative_group(): void
    {
        $scopes = ManualPartGroup::validScopes();
        [$admin, $wo, $member, $group, $option] = $this->bundleFixture($scopes, 1);
        $member->update(['ipl_num' => '5-70A']);
        $family = collect([$member]);
        foreach (['5-70', '5-70B', '5-70C'] as $ipl) {
            $family->push($this->createPartGroupComponent($member->manual_id, $ipl, 'PN-'.$ipl));
        }
        $outsiders = [
            $this->createPartGroupComponent($member->manual_id, '5-71', $member->part_number),
            $this->createPartGroupComponent($member->manual_id, '6-70', 'OTHER-FIG'),
            $this->createPartGroupComponent($this->createManual()->id, '5-70B', 'OTHER-MANUAL'),
            $this->createPartGroupComponent($member->manual_id, '5-70D', 'BUSH'),
        ];
        $outsiders[3]->update(['is_bush' => true]);
        $flags = ['ndt' => 'ndt_list', 'cad' => 'cad_list', 'stress' => 'stress_relief_list', 'paint' => 'paint_list'];
        foreach ($family as $part) {
            $part->update(array_merge(array_fill_keys(array_values($flags), true), ['kit' => true, 'units_assy' => 2]));
            foreach ($flags as $std => $flag) {
                StdProcess::query()->updateOrCreate(
                    ['manual_id' => $part->manual_id, 'component_id' => $part->id, 'std' => $std],
                    ['process' => '1', 'qty' => 2]
                );
            }
        }
        $wo->update(['instruction_id' => $this->createOverhaulInstruction()->id]);
        $selection = WorkorderPartGroupSelection::query()->create([
            'workorder_id' => $wo->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id, 'qty' => 1, 'selected_by_user_id' => $admin->id,
        ]);
        foreach ([1, 2] as $orderedQty) {
            $selection->update(['qty' => $orderedQty]);
            foreach ($scopes as $scope) {
                $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
                foreach ($family as $part) {
                    $this->assertSame($orderedQty, $coverage[$part->id]['covered_qty']);
                }
                foreach ($outsiders as $part) {
                    $this->assertArrayNotHasKey($part->id, $coverage);
                }
            }
            foreach ($flags as $std => $flag) {
                $rows = collect(app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($wo, $std));
                foreach ($family as $part) {
                    $row = $rows->firstWhere('component_id', $part->id);
                    $this->assertNotNull($row);
                    $this->assertSame($orderedQty === 2, $row['group_crossed_out']);
                    $this->assertSame(2 - $orderedQty, $row['qty']);
                }
            }
            $html = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $wo->id]))->assertOk()->getContent();
            foreach ($family as $part) {
                $this->assertMatchesRegularExpression('/data-kit-prl-component-id="'.$part->id.'".*?data-kit-prl-controller-crossed-out="'.($orderedQty === 2 ? '1' : '0').'"/s', $html);
            }
        }
        $this->assertSame(1, ManualPartGroup::where('manual_id', $member->manual_id)->count());
    }

    public function test_nested_assy_letter_family_counts_once_per_position_and_respects_scopes(): void
    {
        [$admin, $wo, $member, $child, $childOption] = $this->bundleFixture(['prl', 'ndt'], 2);
        $variant = $this->createPartGroupComponent($member->manual_id, '1-10A', 'VARIANT');
        // Existing imports may explicitly list both variants. They are not two parts.
        $childOption->coverages()->create(['component_id' => $variant->id, 'qty' => 2, 'applies_to' => ['prl', 'ndt']]);
        $parent = ManualPartGroup::create([
            'manual_id' => $member->manual_id, 'code' => 'NEST-'.uniqid(), 'name' => 'Parent',
            'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl', 'ndt', 'paint'],
        ]);
        $parentOption = $parent->options()->create(['part_number' => 'PARENT', 'is_default' => true]);
        $parentOption->coverages()->create(['covered_manual_part_group_option_id' => $childOption->id, 'qty' => 3, 'applies_to' => ['prl', 'ndt']]);
        // A distinct, direct occurrence remains additive to the nested assembly.
        $parentOption->coverages()->create(['component_id' => $member->id, 'qty' => 1, 'applies_to' => ['prl']]);
        WorkorderPartGroupSelection::create([
            'workorder_id' => $wo->id, 'manual_part_group_id' => $parent->id,
            'manual_part_group_option_id' => $parentOption->id, 'qty' => 2, 'selected_by_user_id' => $admin->id,
        ]);
        foreach (['prl' => 14, 'ndt' => 12, 'paint' => 0] as $scope => $qty) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
            foreach ([$member, $variant] as $part) {
                $this->assertSame($qty, $coverage[$part->id]['covered_qty'] ?? 0);
            }
        }
    }

    public function test_ordering_assy_from_tdr_covers_a_legacy_nested_letter_group_once(): void
    {
        [$admin, $wo, $head, $group, $option] = $this->bundleFixture(['prl', 'ndt'], 1);
        $option->update(['component_id' => $head->id]);
        $family = collect(['5-70', '5-70A', '5-70B', '5-70C'])->map(
            fn ($ipl) => $this->createPartGroupComponent($head->manual_id, $ipl, 'LETTER-'.$ipl)
        );
        $alternative = ManualPartGroup::create([
            'manual_id' => $head->manual_id, 'code' => 'LEGACY-'.uniqid(), 'name' => 'Legacy letters',
            'type' => 'alternative_pn', 'behavior' => 'choose_one', 'applies_to' => ['prl', 'ndt'],
        ]);
        foreach ($family as $part) {
            $alternative->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number]);
        }
        $option->coverages()->create([
            'covered_manual_part_group_option_id' => $alternative->options()->first()->id,
            'qty' => 2, 'applies_to' => ['prl', 'ndt'],
        ]);
        Tdr::create([
            'workorder_id' => $wo->id, 'component_id' => $head->id, 'order_component_id' => $head->id,
            'necessaries_id' => \App\Models\Necessary::firstOrCreate(['name' => 'Order New'])->id, 'qty' => 3,
        ]);
        foreach (['prl', 'ndt'] as $scope) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
            foreach ($family as $part) {
                $this->assertSame(6, $coverage[$part->id]['covered_qty']);
            }
        }
        $this->assertSame(4, $alternative->options()->count());
    }

    public function test_assy_does_not_expand_a_restricted_explicit_letter_subset(): void
    {
        [$admin, $wo, $head, $group, $option] = $this->bundleFixture(['prl'], 1);
        $parts = collect(['5-70A', '5-70B', '5-70C', '5-70D'])->map(
            fn ($ipl) => $this->createPartGroupComponent($head->manual_id, $ipl, 'CONFIG-'.$ipl)
        );
        $restricted = ManualPartGroup::create([
            'manual_id' => $head->manual_id, 'code' => 'RESTRICTED-'.uniqid(), 'name' => 'A/B only',
            'type' => 'alternative_pn', 'behavior' => 'choose_one', 'applies_to' => ['prl'],
        ]);
        foreach ($parts->take(2) as $part) {
            $restricted->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number]);
        }
        $edge = $option->coverages()->create([
            'covered_manual_part_group_option_id' => $restricted->options()->first()->id,
            'qty' => 2, 'applies_to' => ['prl'],
        ]);
        WorkorderPartGroupSelection::create([
            'workorder_id' => $wo->id, 'manual_part_group_id' => $group->id,
            'manual_part_group_option_id' => $option->id, 'qty' => 1, 'selected_by_user_id' => $admin->id,
        ]);
        $resolver = app(PartGroupCoverageResolver::class);
        $coverage = $resolver->coverageForWorkorder($wo, 'prl');
        foreach ($parts->take(2) as $part) $this->assertSame(2, $coverage[$part->id]['covered_qty']);
        foreach ($parts->skip(2) as $part) $this->assertArrayNotHasKey($part->id, $coverage);
        // Even a directly listed representative must not escape the restriction.
        $edge->update(['covered_manual_part_group_option_id' => null, 'component_id' => $parts[0]->id]);
        $coverage = $resolver->coverageForWorkorder($wo, 'prl');
        foreach ($parts->skip(1) as $part) $this->assertArrayNotHasKey($part->id, $coverage);
    }

    public function test_log_card_composition_keeps_letter_variants_after_group_retirement(): void
    {
        [$admin, $wo, $head, $group, $option] = $this->bundleFixture(['prl'], 1);
        $parts = collect(['5-70', '5-70A', '5-70B'])->map(
            fn ($ipl) => $this->createPartGroupComponent($head->manual_id, $ipl, 'LC-'.$ipl)
        );
        $other = $this->createPartGroupComponent($head->manual_id, '5-71', 'OTHER');
        $bush = $this->createPartGroupComponent($head->manual_id, '5-70C', 'BUSH');
        $bush->update(['is_bush' => true]);
        $alternative = ManualPartGroup::create([
            'manual_id' => $head->manual_id, 'code' => 'LC-'.uniqid(), 'name' => 'Letter family',
            'type' => 'alternative_pn', 'behavior' => 'choose_one', 'applies_to' => ['prl'],
        ]);
        foreach ($parts as $part) {
            $alternative->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number]);
        }
        $edge = $option->coverages()->create([
            'covered_manual_part_group_option_id' => $alternative->options()->first()->id,
            'qty' => 2, 'applies_to' => ['prl'],
        ]);
        $composition = fn () => app(\App\Services\ManualPartGroupCompositionResolver::class)
            ->componentIdsByGroup(ManualPartGroup::where('manual_id', $head->manual_id)->with('options.coverages')->get());
        $before = $composition()[$group->id]->sort()->values()->all();
        $edge->update(['covered_manual_part_group_option_id' => null, 'component_id' => $parts[0]->id]);
        $alternative->delete();
        $after = $composition()[$group->id]->sort()->values()->all();
        $this->assertSame($before, $after);
        foreach ($parts as $part) $this->assertContains($part->id, $after);
        $this->assertNotContains($other->id, $after);
        $this->assertNotContains($bush->id, $after);
    }

    public function test_exact_members_do_not_expand_or_collapse_letters_in_nested_assy(): void
    {
        $scopes = ManualPartGroup::validScopes();
        [$admin, $wo, $member, $child, $childOption] = $this->bundleFixture($scopes, 2);
        $a = $this->createPartGroupComponent($member->manual_id, '1-10A', 'VARIANT-A');
        $b = $this->createPartGroupComponent($member->manual_id, '1-10B', 'VARIANT-B');
        $childOption->coverages()->first()->update(['expand_ipl_family' => false]);
        $childOption->coverages()->create(['component_id' => $a->id, 'qty' => 1, 'applies_to' => $scopes, 'expand_ipl_family' => false]);
        $parent = ManualPartGroup::create([
            'manual_id' => $member->manual_id, 'code' => 'EXACT-'.uniqid(), 'name' => 'Parent',
            'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => $scopes,
        ]);
        $parentOption = $parent->options()->create(['part_number' => 'PARENT', 'is_default' => true]);
        $parentOption->coverages()->create(['covered_manual_part_group_option_id' => $childOption->id, 'qty' => 3, 'applies_to' => $scopes]);
        WorkorderPartGroupSelection::create([
            'workorder_id' => $wo->id, 'manual_part_group_id' => $parent->id,
            'manual_part_group_option_id' => $parentOption->id, 'qty' => 2, 'selected_by_user_id' => $admin->id,
        ]);
        foreach ($scopes as $scope) {
            $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, $scope);
            $this->assertSame(12, $coverage[$member->id]['covered_qty']);
            $this->assertSame(6, $coverage[$a->id]['covered_qty']);
            $this->assertArrayNotHasKey($b->id, $coverage);
        }
        $groups = ManualPartGroup::where('manual_id', $member->manual_id)->with('options.coverages')->get();
        $composition = app(\App\Services\ManualPartGroupCompositionResolver::class)->componentIdsByGroup($groups);
        foreach ([$child, $parent] as $group) {
            $this->assertEqualsCanonicalizing([$member->id, $a->id], $composition[$group->id]->all());
        }
        $wo->update(['scope_type' => \App\Models\Unit::SCOPE_PART_GROUP_OPTION, 'scope_part_group_option_id' => $parentOption->id]);
        $scope = app(\App\Services\WorkorderPartScopeResolver::class)->componentQuantities($wo, 'ndt');
        $this->assertSame(6, $scope[$member->id]);
        $this->assertSame(3, $scope[$a->id]);
        $this->assertArrayNotHasKey($b->id, $scope);
    }

    public function test_editor_does_not_offer_or_override_imported_ipl_restrictions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $head = $this->createPartGroupComponent($manual->id, '10-30', 'ASSY');
        $member = $this->createPartGroupComponent($manual->id, '10-35', 'BARE');
        $payload = ['type' => 'assy', 'applies_to' => ['prl'], 'component_ids' => [$head->id, $member->id],
            'default_component_id' => $head->id, 'member_expand_ipl_family' => [$member->id => false]];
        $r = $this->actingAs($admin)->postJson(route('manuals.part-groups.store', $manual), $payload)->assertOk();
        $group = ManualPartGroup::findOrFail($r->json('group.id'));
        $edge = $group->options()->first()->coverages()->where('component_id', $member->id)->firstOrFail();
        // An old UI or a crafted request cannot configure import-only restrictions.
        $this->assertTrue($edge->expandsIplFamily());
        $this->assertArrayNotHasKey('expand_ipl_family', collect($r->json('group.options.0.coverages'))->firstWhere('component_id', $member->id));
        $edge->update(['expand_ipl_family' => false]);
        unset($payload['member_expand_ipl_family']);
        $this->putJson(route('manuals.part-groups.update', [$manual, $group]), $payload)->assertOk();
        $this->assertFalse($edge->fresh()->expandsIplFamily());
        $payload['member_expand_ipl_family'] = [$member->id => true];
        $this->putJson(route('manuals.part-groups.update', [$manual, $group]), $payload)->assertOk();
        $this->assertFalse($edge->fresh()->expandsIplFamily());
        $template = file_get_contents(resource_path('views/admin/manuals/partials/part-groups-modal.blade.php'));
        $this->assertStringNotContainsString('part-group-member-family', $template);
        $this->assertStringNotContainsString('member_expand_ipl_family', $template);
        $this->assertTrue((new \App\Models\ManualPartGroupCoverage())->expandsIplFamily());
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
