<?php

namespace Tests\Feature;

use App\Models\ManualPartGroup;
use App\Models\Unit;
use App\Models\Workorder;
use App\Services\WorkorderScopeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderScopeEditingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_edit_page_exposes_workorder_scope_controls_and_options_from_the_unit_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $component = $this->createComponent($manual, [
            'part_number' => 'SCOPE-PART',
            'ipl_num' => '13-10',
            'name' => 'SLIDING TUBE',
        ]);
        $outsideComponent = $this->createComponent($otherManual, [
            'part_number' => 'OUTSIDE-PART',
            'ipl_num' => '1-10',
        ]);
        $group = $this->createAssyGroup($manual->id, $component->id, 'SCOPE-ASSY');
        $standalone = $this->createComponent($manual, [
            'part_number' => 'SCOPE-PART',
            'ipl_num' => '12-800A',
            'name' => 'STANDALONE POSITION',
        ]);
        $kitGroup = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'WO-SCOPE-KIT-'.uniqid(),
            'name' => 'SCOPE-KIT',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_KIT,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $kitOption = $kitGroup->options()->create([
            'component_id' => $component->id,
            'part_number' => 'SCOPE-KIT',
            'ipl_num' => '13-20',
            'option_kind' => ManualPartGroup::TYPE_KIT,
            'is_default' => true,
        ]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertSee('id="work_scope_type"', false)
            ->assertSee('id="work_scope_target"', false)
            ->assertSee('>Complete Unit</option>', false)
            ->assertSee('>Part / Assembly</option>', false)
            ->assertDontSee('Legacy scope (current)')
            ->assertDontSee('ASSY / KIT option');

        $response = $this->actingAs($admin)->getJson(route('workorders.scope-options', [
            'workorder' => $workorder,
            'unit_id' => $unit->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('unit.id', $unit->id)
            ->assertJsonMissing(['value' => 'component:'.$component->id])
            ->assertJsonFragment(['value' => 'component:'.$standalone->id, 'kind' => 'part'])
            ->assertJsonFragment(['value' => 'part_group_option:'.$group->options->first()->id, 'kind' => 'assembly'])
            ->assertJsonMissing(['value' => 'component:'.$outsideComponent->id])
            ->assertJsonMissing(['value' => 'part_group_option:'.$kitOption->id]);

        $targets = collect($response->json('scope_targets'))->keyBy('value');
        $this->assertSame('12-800A · SCOPE-PART · STANDALONE POSITION · Part', $targets['component:'.$standalone->id]['label']);
        $this->assertSame('13-1A · SCOPE-ASSY · SLIDING TUBE · ASSY', $targets['part_group_option:'.$group->options->first()->id]['label']);
    }

    public function test_existing_workorder_can_change_from_legacy_scope_to_assy_and_rebuild_std_snapshot(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $root = $this->createComponent($manual, [
            'part_number' => 'SLIDING-TUBE-ASSY',
            'ipl_num' => '13-1A',
            'ndt_list' => false,
        ]);
        $ndtMember = $this->createComponent($manual, [
            'part_number' => 'SCOPED-KIT-NDT-MEMBER',
            'ipl_num' => '13-180A',
            'ndt_list' => true,
            'kit' => true,
        ]);
        $outsideKitMember = $this->createComponent($manual, [
            'part_number' => 'UNSCOPED-KIT-MEMBER',
            'ipl_num' => '14-10',
            'kit' => true,
        ]);
        $group = $this->createAssyGroup($manual->id, $root->id, 'SLIDING-TUBE-ASSY');
        $option = $group->options->first();
        $option->coverages()->create([
            'component_id' => $ndtMember->id,
            'qty' => 1,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_COMPONENT,
            'default_scope_component_id' => $root->id,
        ]);
        $instruction = $this->createInstruction(['name' => 'Repair '.uniqid()]);
        $customer = $this->createCustomer();
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
        ]);
        $workorder->forceFill([
            'scope_type' => null,
            'scope_component_id' => null,
            'scope_part_group_option_id' => null,
        ])->save();

        $this->actingAs($admin)
            ->put(route('workorders.update', $workorder), $this->updatePayload(
                $workorder,
                $unit,
                $customer->id,
                $instruction->id,
                $admin->id,
                [
                    'scope_type' => 'part_assembly',
                    'scope_target_id' => 'part_group_option:'.$option->id,
                ]
            ))
            ->assertRedirect(route('workorders.index'))
            ->assertSessionHasNoErrors();

        $workorder->refresh();
        $this->assertSame(Unit::SCOPE_PART_GROUP_OPTION, $workorder->scope_type);
        $this->assertNull($workorder->scope_component_id);
        $this->assertSame($option->id, (int) $workorder->scope_part_group_option_id);
        $this->assertDatabaseHas('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $ndtMember->id,
            'std_type' => 'ndt',
            'remaining_qty' => 1,
        ]);
        $this->actingAs($admin)
            ->get(route('tdrs.kitForm', $workorder))
            ->assertRedirect(route('tdrs.prlForm', $workorder));
    }

    public function test_part_assembly_choice_can_store_an_individual_part(): void
    {
        $manual = $this->createManual();
        $component = $this->createComponent($manual, ['part_number' => 'RECEIVED-PART']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $selection = app(WorkorderScopeService::class)->normalizeSelection([
            'scope_type' => 'part_assembly',
            'scope_target_id' => 'component:'.$component->id,
        ], $unit, $workorder);

        $this->assertSame(Unit::SCOPE_COMPONENT, $selection['scope_type']);
        $this->assertSame($component->id, $selection['scope_component_id']);
        $this->assertNull($selection['scope_part_group_option_id']);
    }

    public function test_unchanged_two_choice_selection_preserves_a_legacy_workorder_scope(): void
    {
        $manual = $this->createManual();
        $component = $this->createComponent($manual, [
            'part_number' => 'LEGACY-RECEIVED-PART',
            'eff_code' => 'ALL',
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => $component->part_number,
            'eff_code' => 'ALL',
        ]);
        $instruction = $this->createInstruction(['name' => 'Overhaul']);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $instruction->id,
        ]);
        $workorder->forceFill([
            'scope_type' => null,
            'scope_component_id' => null,
            'scope_part_group_option_id' => null,
        ])->save();

        $service = app(WorkorderScopeService::class);
        $businessSelection = $service->businessSelectionForWorkorder($workorder->fresh());
        $selection = $service->normalizeSelection([
            'scope_type' => $businessSelection['mode'],
            'scope_target_id' => $businessSelection['target'],
        ], $unit, $workorder->fresh());

        $this->assertSame('part_assembly', $businessSelection['mode']);
        $this->assertSame('component:'.$component->id, $businessSelection['target']);
        $this->assertNull($selection['scope_type']);
        $this->assertNull($selection['scope_component_id']);
        $this->assertNull($selection['scope_part_group_option_id']);
    }

    public function test_part_assembly_choice_rejects_a_kit_group(): void
    {
        $manual = $this->createManual();
        $component = $this->createComponent($manual, ['part_number' => 'KIT-ROOT']);
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manual->id,
            'code' => 'WO-SCOPE-KIT-'.uniqid(),
            'name' => 'KIT-ROOT',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_KIT,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $option = $group->options()->create([
            'component_id' => $component->id,
            'part_number' => 'KIT-ROOT',
            'option_kind' => ManualPartGroup::TYPE_KIT,
            'is_default' => true,
        ]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $this->expectException(ValidationException::class);
        app(WorkorderScopeService::class)->normalizeSelection([
            'scope_type' => 'part_assembly',
            'scope_target_id' => 'part_group_option:'.$option->id,
        ], $unit, $workorder);
    }

    public function test_workorder_scope_rejects_an_assy_from_another_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $root = $this->createComponent($manual, ['part_number' => 'ROOT']);
        $outsideRoot = $this->createComponent($otherManual, ['part_number' => 'OUTSIDE-ROOT']);
        $outsideGroup = $this->createAssyGroup($otherManual->id, $outsideRoot->id, 'OUTSIDE-ASSY');
        $outsideOption = $outsideGroup->options->first();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'default_scope_type' => Unit::SCOPE_COMPONENT,
            'default_scope_component_id' => $root->id,
        ]);
        $instruction = $this->createInstruction(['name' => 'Repair '.uniqid()]);
        $customer = $this->createCustomer();
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
        ]);

        $this->from(route('workorders.edit', $workorder))
            ->actingAs($admin)
            ->put(route('workorders.update', $workorder), $this->updatePayload(
                $workorder,
                $unit,
                $customer->id,
                $instruction->id,
                $admin->id,
                [
                    'scope_type' => 'part_assembly',
                    'scope_target_id' => 'part_group_option:'.$outsideOption->id,
                ]
            ))
            ->assertRedirect(route('workorders.edit', $workorder))
            ->assertSessionHasErrors('scope_target_id');

        $workorder->refresh();
        $this->assertSame(Unit::SCOPE_COMPONENT, $workorder->scope_type);
        $this->assertSame($root->id, (int) $workorder->scope_component_id);
    }

    public function test_failed_unit_change_keeps_submitted_unit_manual_and_part_identity_in_edit_form(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $oldManual = $this->createManual(['number' => 'QA-15RM']);
        $newManual = $this->createManual(['number' => 'QA-14RM']);
        $oldUnit = $this->createUnit(['manual_id' => $oldManual->id, 'part_number' => 'SHARED-PN']);
        $newUnit = $this->createUnit(['manual_id' => $newManual->id, 'part_number' => 'SHARED-PN']);
        $oldPart = $this->createComponent($oldManual);
        $instruction = $this->createInstruction(['name' => 'Repair '.uniqid()]);
        $customer = $this->createCustomer();
        $workorder = $this->createWorkorder([
            'unit_id' => $oldUnit->id, 'customer_id' => $customer->id,
            'instruction_id' => $instruction->id, 'user_id' => $admin->id,
        ]);

        // Reproduce a stale Parts choice from the previous manual.
        $this->actingAs($admin)->from(route('workorders.edit', $workorder))
            ->put(route('workorders.update', $workorder), $this->updatePayload(
                $workorder, $newUnit, $customer->id, $instruction->id, $admin->id,
                ['scope_type' => 'part_assembly', 'scope_target_id' => 'component:'.$oldPart->id]
            ))->assertSessionHasErrors('scope_target_id');

        $response = $this->get(route('workorders.edit', $workorder))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $option = $xpath->query('//select[@id="unit_id"]/option[@selected]')->item(0);
        $this->assertSame((string) $newUnit->id, $option->getAttribute('value'));
        $this->assertSame((string) $newManual->id, $option->getAttribute('data-manual-id'));
        $this->assertSame('SHARED-PN', $option->getAttribute('data-part-number'));
        $this->assertStringContainsString('QA-14RM', $option->textContent);
        $this->assertStringNotContainsString('QA-15RM', $option->textContent);
        $this->assertSame(1, $xpath->query('//select[@id="unit_id"]/option[@value="'.$newUnit->id.'"]')->length);
        $this->assertSame($oldUnit->id, (int) $workorder->fresh()->unit_id);
    }

    public function test_switching_units_with_same_part_number_saves_the_selected_manual_and_its_assy(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $oldManual = $this->createManual();
        $newManual = $this->createManual();
        $oldUnit = $this->createUnit(['manual_id' => $oldManual->id, 'part_number' => 'SHARED-PN']);
        $root = $this->createComponent($newManual, ['part_number' => 'SHARED-PN']);
        $newUnit = $this->createUnit([
            'manual_id' => $newManual->id, 'part_number' => 'SHARED-PN',
            'default_scope_type' => Unit::SCOPE_COMPONENT, 'default_scope_component_id' => $root->id,
        ]);
        $assy = $this->createAssyGroup($newManual->id, $root->id, 'SHARED-PN')->options->first();
        $instruction = $this->createInstruction(['name' => 'Repair '.uniqid()]);
        $customer = $this->createCustomer();
        $workorder = $this->createWorkorder([
            'unit_id' => $oldUnit->id, 'customer_id' => $customer->id,
            'instruction_id' => $instruction->id, 'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->put(route('workorders.update', $workorder), $this->updatePayload(
            $workorder, $newUnit, $customer->id, $instruction->id, $admin->id,
            ['scope_type' => 'part_assembly', 'scope_target_id' => 'part_group_option:'.$assy->id]
        ))->assertSessionHasNoErrors()->assertRedirect(route('workorders.index'));

        $workorder->refresh();
        $this->assertSame($newUnit->id, (int) $workorder->unit_id);
        $this->assertSame($newManual->id, (int) $workorder->unit->manual_id);
        $this->assertSame(Unit::SCOPE_PART_GROUP_OPTION, $workorder->scope_type);
        $this->assertSame($assy->id, (int) $workorder->scope_part_group_option_id);
        $this->assertNull($workorder->scope_component_id);
        $this->assertSame($oldManual->id, (int) $oldUnit->fresh()->manual_id);
        $this->assertSame(Unit::SCOPE_COMPONENT, $newUnit->fresh()->default_scope_type);
    }

    private function createAssyGroup(int $manualId, int $rootComponentId, string $partNumber): ManualPartGroup
    {
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manualId,
            'code' => 'WO-SCOPE-'.uniqid(),
            'name' => $partNumber,
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $group->options()->create([
            'component_id' => $rootComponentId,
            'part_number' => $partNumber,
            'ipl_num' => '13-1A',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
            'is_default' => true,
        ]);

        return $group->load('options');
    }

    private function updatePayload(
        Workorder $workorder,
        Unit $unit,
        int $customerId,
        int $instructionId,
        int $userId,
        array $scope
    ): array {
        return [
            'number' => $workorder->number,
            'unit_id' => $unit->id,
            'customer_id' => $customerId,
            'instruction_id' => $instructionId,
            'user_id' => $userId,
            'open_at' => now()->format('d/M/Y'),
        ] + $scope;
    }
}
