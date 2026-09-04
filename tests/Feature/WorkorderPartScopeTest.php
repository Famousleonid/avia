<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\StdProcess;
use App\Models\Unit;
use App\Services\WorkorderPartScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderPartScopeTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_new_workorder_copies_unit_scope_as_an_unchanging_snapshot(): void
    {
        $manual = $this->createManual();
        $component = $this->createComponent($manual, [
            'part_number' => 'PIN-SNAPSHOT',
            'ipl_num' => '2-10',
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_COMPONENT,
            'default_scope_component_id' => $component->id,
        ]);

        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $this->assertSame(Unit::SCOPE_COMPONENT, $workorder->scope_type);
        $this->assertSame($component->id, (int) $workorder->scope_component_id);
        $this->assertNull($workorder->scope_part_group_option_id);

        $unit->update([
            'default_scope_type' => Unit::SCOPE_FULL_UNIT,
            'default_scope_component_id' => null,
        ]);

        $workorder->refresh();
        $this->assertSame(Unit::SCOPE_COMPONENT, $workorder->scope_type);
        $this->assertSame($component->id, (int) $workorder->scope_component_id);
    }

    public function test_unit_scope_can_be_configured_once_and_must_reference_the_same_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $component = $this->createComponent($manual, [
            'part_number' => 'PIN-CONFIGURED',
            'ipl_num' => '2-15',
        ]);
        $outsideComponent = $this->createComponent($otherManual, [
            'part_number' => 'PIN-OUTSIDE',
            'ipl_num' => '1-10',
        ]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $basePayload = [
            'part_number' => $unit->part_number,
            'name' => $unit->name,
            'verified' => true,
        ];

        $this->actingAs($admin)
            ->patchJson(route('units.updateSingle', $unit), $basePayload + [
                'default_scope_type' => Unit::SCOPE_COMPONENT,
                'default_scope_component_id' => $component->id,
            ])
            ->assertOk()
            ->assertJsonPath('default_scope_type', Unit::SCOPE_COMPONENT)
            ->assertJsonPath('default_scope_component_id', $component->id);

        $this->actingAs($admin)
            ->getJson(route('units.show', $manual->id))
            ->assertOk()
            ->assertJsonPath('units.0.scope_display', 'Part: PIN-CONFIGURED · IPL 2-15');

        $this->actingAs($admin)
            ->patchJson(route('units.updateSingle', $unit), $basePayload + [
                'default_scope_type' => Unit::SCOPE_COMPONENT,
                'default_scope_component_id' => $outsideComponent->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('default_scope_component_id');

        $this->assertSame($component->id, (int) $unit->fresh()->default_scope_component_id);
    }

    public function test_changing_workorder_unit_copies_the_new_unit_default_scope(): void
    {
        $manual = $this->createManual();
        $component = $this->createComponent($manual, [
            'part_number' => 'PIN-NEW-UNIT',
            'ipl_num' => '2-20',
        ]);
        $fullUnit = $this->createUnit(['manual_id' => $manual->id]);
        $partUnit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_COMPONENT,
            'default_scope_component_id' => $component->id,
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $fullUnit->id]);

        $this->assertSame(Unit::SCOPE_FULL_UNIT, $workorder->scope_type);

        $workorder->update(['unit_id' => $partUnit->id]);
        $workorder->refresh();

        $this->assertSame(Unit::SCOPE_COMPONENT, $workorder->scope_type);
        $this->assertSame($component->id, (int) $workorder->scope_component_id);
        $this->assertSame([], $workorder->not_used_manual_ids);
    }

    public function test_component_scope_restricts_std_lists_and_does_not_inherit_unit_kit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $receivedPin = $this->createComponent($manual, [
            'part_number' => 'PIN-ONLY',
            'ipl_num' => '3-10',
            'units_assy' => 7,
            'ndt_list' => true,
        ]);
        $manualKitPart = $this->createComponent($manual, [
            'part_number' => 'FULL-UNIT-KIT-PART',
            'ipl_num' => '3-20',
            'units_assy' => 2,
            'ndt_list' => true,
            'kit' => true,
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_COMPONENT,
            'default_scope_component_id' => $receivedPin->id,
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => $this->createInstruction(['name' => 'Repair'])->id,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame(['PIN-ONLY'], array_column($rows, 'part_number'));
        $this->assertSame(1, $rows[0]['qty']);
        $this->assertFalse(app(WorkorderPartScopeResolver::class)->allowsComponent(
            $workorder,
            $manualKitPart->id
        ));

        $componentResponse = $this->actingAs($admin)->getJson(route('tdrs.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));
        $componentResponse->assertOk();
        $this->assertSame(
            [$receivedPin->id],
            collect($componentResponse->json('components'))->pluck('id')->map(fn ($id): int => (int) $id)->all()
        );

        $this->actingAs($admin)
            ->get(route('tdrs.kitForm', $workorder->id))
            ->assertOk()
            ->assertSee('PARTS REPLACEMENT LIST - KIT')
            ->assertDontSee('FULL-UNIT-KIT-PART');
    }

    public function test_assy_scope_expands_nested_members_and_honors_form_coverage(): void
    {
        $manual = $this->createManual();
        $root = $this->createComponent($manual, ['part_number' => 'ASSY-ROOT', 'ipl_num' => '4-10']);
        $ndtMember = $this->createComponent($manual, ['part_number' => 'ASSY-NDT', 'ipl_num' => '4-20']);
        $cadMember = $this->createComponent($manual, ['part_number' => 'ASSY-CAD', 'ipl_num' => '4-30']);
        $nestedMember = $this->createComponent($manual, ['part_number' => 'ASSY-NESTED', 'ipl_num' => '4-40']);
        $originalBushing = $this->createComponent($manual, [
            'part_number' => 'BUSH-STD', 'ipl_num' => '4-50', 'is_bush' => true, 'bush_ipl_num' => '4-50',
        ]);
        $oversizeBushing = $this->createComponent($manual, [
            'part_number' => 'BUSH-OS', 'ipl_num' => '4-51', 'is_bush' => true, 'bush_ipl_num' => '4-50',
        ]);

        $nestedGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'SCOPE-NESTED-'.uniqid(),
            'name' => 'Nested ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $nestedOption = $nestedGroup->options()->create([
            'component_id' => $nestedMember->id,
            'part_number' => 'NESTED-OPTION',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
            'is_default' => true,
        ]);

        $bushingGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'SCOPE-BUSH-'.uniqid(),
            'name' => 'Bushing family',
            'behavior' => ManualPartGroup::BEHAVIOR_CHOOSE_ONE,
            'type' => ManualPartGroup::TYPE_OVERSIZE,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $bushingOption = $bushingGroup->options()->create([
            'component_id' => $originalBushing->id,
            'part_number' => $originalBushing->part_number,
            'option_kind' => 'original',
            'is_default' => true,
        ]);
        $bushingGroup->options()->create([
            'component_id' => $oversizeBushing->id,
            'part_number' => $oversizeBushing->part_number,
            'option_kind' => 'oversize',
        ]);

        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'SCOPE-ASSY-'.uniqid(),
            'name' => 'Scoped ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $option = $group->options()->create([
            'component_id' => $root->id,
            'part_number' => 'ASSY-OPTION',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
            'is_default' => true,
        ]);
        $option->coverages()->createMany([
            ['component_id' => $ndtMember->id, 'qty' => 2, 'applies_to' => [ManualPartGroup::SCOPE_NDT]],
            ['component_id' => $cadMember->id, 'qty' => 3, 'applies_to' => [ManualPartGroup::SCOPE_CAD]],
            [
                'covered_manual_part_group_option_id' => $nestedOption->id,
                'qty' => 4,
                'applies_to' => [ManualPartGroup::SCOPE_NDT],
            ],
            [
                'covered_manual_part_group_option_id' => $bushingOption->id,
                'qty' => 2,
                'applies_to' => [ManualPartGroup::SCOPE_NDT],
            ],
        ]);

        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_PART_GROUP_OPTION,
            'default_scope_part_group_option_id' => $option->id,
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $resolver = app(WorkorderPartScopeResolver::class);

        $this->assertSame([
            $root->id => 1,
            $ndtMember->id => 2,
            $nestedMember->id => 4,
            $originalBushing->id => 2,
            $oversizeBushing->id => 2,
        ], $resolver->componentQuantities($workorder, ManualPartGroup::SCOPE_NDT));
        $this->assertSame([
            $root->id => 1,
            $cadMember->id => 3,
        ], $resolver->componentQuantities($workorder, ManualPartGroup::SCOPE_CAD));
    }

    public function test_complete_unit_scope_keeps_the_whole_manual_available(): void
    {
        $workorder = $this->createWorkorder();

        $this->assertSame(Unit::SCOPE_FULL_UNIT, $workorder->scope_type);
        $this->assertNull(app(WorkorderPartScopeResolver::class)->componentQuantities($workorder));
    }
}
