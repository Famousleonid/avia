<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Manual;
use App\Models\ProcessName;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MultiManualWorkorderTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_draft_main_page_can_resolve_its_manual_package(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'DRAFT-MANUAL']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $draft = $this->createWorkorder([
            'number' => 40,
            'draft_number' => 40,
            'is_draft' => true,
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $this->assertSame([$manual->id], Manual::manualIdsForWorkorder($draft->id));

        $this->actingAs($admin)
            ->get(route('mains.show', $draft))
            ->assertOk()
            ->assertSee('DRAFT-MANUAL');
    }

    public function test_new_workorder_uses_manual_additional_manuals_and_builds_combined_std_and_kit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-PRIMARY', 'title' => 'Primary manual title']);
        $additionalManual = $this->createManual(['number' => 'MM-ADDITIONAL', 'title' => 'Additional manual title']);
        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $primaryComponent = Component::query()->create([
            'manual_id' => $primaryManual->id,
            'ipl_num' => '1-10',
            'part_number' => 'MM-PRIMARY-PN',
            'name' => 'Primary manual part',
            'units_assy' => 1,
            'ndt_list' => true,
            'cad_list' => true,
            'stress_relief_list' => true,
            'paint_list' => true,
            'kit' => true,
            'is_bush' => false,
        ]);
        $additionalComponent = Component::query()->create([
            'manual_id' => $additionalManual->id,
            'ipl_num' => '1-10',
            'part_number' => 'MM-ADDITIONAL-PN',
            'name' => 'Additional manual part',
            'units_assy' => 2,
            'ndt_list' => true,
            'cad_list' => true,
            'stress_relief_list' => true,
            'paint_list' => true,
            'kit' => true,
            'is_bush' => false,
        ]);

        $this->assertSame([$additionalManual->id], $workorder->fresh('unit')->additionalManualIds());
        $this->assertSame(
            [$primaryManual->id, $additionalManual->id],
            $workorder->fresh('unit')->usedManualIds()
        );

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh('unit'), StdProcess::STD_NDT);

        $this->assertEqualsCanonicalizing(
            [$primaryComponent->id, $additionalComponent->id],
            array_column($rows, 'component_id')
        );
        $this->assertEqualsCanonicalizing(
            [$primaryManual->id, $additionalManual->id],
            array_column($rows, 'manual_id')
        );

        $kit = $this->actingAs($admin)->get(route('tdrs.kitForm', $workorder->id));
        $kit->assertOk();
        $kit->assertSee('MM-PRIMARY-PN');
        $kit->assertSee('MM-ADDITIONAL-PN');

        $workorderPage = $this->actingAs($admin)->get(route('mains.show', $workorder->id));
        $workorderPage->assertOk();
        $workorderPage->assertDontSee('WO Manuals');
        $workorderPage->assertDontSee('workorderManualsModal', false);

        $editPage = $this->actingAs($admin)->get(route('workorders.edit', $workorder));
        $editPage->assertOk();
        $editPage->assertSee('WO Manuals');
        $editPage->assertSee('workorderManualsModal', false);
        $editPage->assertSee('<th class="workorder-manual-title-column">Title</th>', false);
        $editPage->assertSee('MM-PRIMARY');
        $editPage->assertSee('Primary manual title');
        $editPage->assertSee('MM-ADDITIONAL');
        $editPage->assertSee('Additional manual title');
        $editPage->assertSee('Set NOT USED');
        $editPage->assertSee("okText: nextUsed", false);
        $editPage->assertSee("danger: !nextUsed", false);

        $tdrShow = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $tdrShow->assertOk();
        $tdrShow->assertDontSee('workorderManualPackage', false);
        $tdrShow->assertDontSee('Load additional manuals from Unit');

        foreach (['tdrs.ndtStd', 'tdrs.cadStd', 'tdrs.paintStd', 'tdrs.stressStd'] as $routeName) {
            $form = $this->actingAs($admin)->get(route($routeName, $workorder->id));
            $form->assertOk();
            $form->assertSee('MM-PRIMARY-PN');
            $form->assertSee('MM-ADDITIONAL-PN');
            $form->assertSee('MM-PRIMARY');
            $form->assertSee('MM-ADDITIONAL');
        }
    }

    public function test_additional_manual_can_be_marked_not_used_without_deleting_unit_configuration(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-USED']);
        $additionalManual = $this->createManual(['number' => 'MM-NOT-USED']);
        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        Component::query()->create([
            'manual_id' => $primaryManual->id,
            'ipl_num' => '2-10',
            'part_number' => 'MM-USED-PN',
            'name' => 'Used manual part',
            'units_assy' => 1,
            'cad_list' => true,
            'kit' => true,
            'is_bush' => false,
        ]);
        $excludedComponent = Component::query()->create([
            'manual_id' => $additionalManual->id,
            'ipl_num' => '2-20',
            'part_number' => 'MM-NOT-USED-PN',
            'name' => 'Not used manual part',
            'units_assy' => 1,
            'cad_list' => true,
            'kit' => true,
            'is_bush' => false,
        ]);

        StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);

        $response = $this->actingAs($admin)->patchJson(
            route('workorders.manuals.usage', $workorder),
            ['manual_id' => $additionalManual->id, 'used' => false]
        );

        $response->assertOk()->assertJsonPath('success', true);
        $workorder = $workorder->fresh('unit');
        $this->assertSame([$additionalManual->id], $workorder->notUsedManualIds());
        $this->assertSame([$primaryManual->id], $workorder->usedManualIds());
        $this->assertSame([$additionalManual->id], $unit->fresh()->additionalManualIds());

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);
        $this->assertNotContains($excludedComponent->id, array_column($rows, 'component_id'));

        $kit = $this->actingAs($admin)->get(route('tdrs.kitForm', $workorder->id));
        $kit->assertOk();
        $kit->assertSee('MM-USED-PN');
        $kit->assertDontSee('MM-NOT-USED-PN');
    }

    public function test_mains_header_lists_only_used_additional_manual_libs(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => '32-21-04 Goodrich', 'lib' => '111']);
        $usedManual = $this->createManual(['number' => '32-50-01', 'lib' => '240']);
        $secondUsedManual = $this->createManual(['number' => '32-51-02', 'lib' => '243']);
        $primaryManual->update(['additional_manual_ids' => [$usedManual->id, $secondUsedManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSee('32-21-04 Goodrich | Lib:111 (240,243)');

        $workorder->update(['not_used_manual_ids' => [$secondUsedManual->id]]);

        $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSee('32-21-04 Goodrich | Lib:111 (240)')
            ->assertDontSee('32-21-04 Goodrich | Lib:111 (240,243)');
    }

    public function test_mains_parts_and_repair_processes_include_additional_manual_components(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-MAIN-PROCESSES']);
        $additionalManual = $this->createManual(['number' => 'MM-EHSV-PROCESSES']);
        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $component = Component::query()->create([
            'manual_id' => $additionalManual->id,
            'ipl_num' => '1-210A',
            'part_number' => '48107-103',
            'name' => 'Electro-Hydraulic Servo Valve (EHSV)',
            'units_assy' => 1,
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'serial_number' => 'EHSV-SN',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'EHSV Repair '.uniqid(),
            'process_sheet_name' => 'EHSV',
            'form_number' => 'EHSV',
            'show_in_process_picker' => true,
        ]);
        $process = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => 'R9205',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('mains.show', $workorder));

        $response
            ->assertOk()
            ->assertViewHas('components', fn ($components): bool => $components->contains('id', $component->id))
            ->assertSee('Electro-Hydraulic Servo Valve (EHSV)')
            ->assertSee('R9205')
            ->assertSee('data-qa-process-id="'.$process->id.'"', false);
    }

    public function test_manual_with_existing_tdr_part_cannot_be_marked_not_used(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual();
        $additionalManual = $this->createManual(['number' => 'MM-TDR']);
        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $component = Component::query()->create([
            'manual_id' => $additionalManual->id,
            'ipl_num' => '3-10',
            'part_number' => 'MM-TDR-PN',
            'name' => 'TDR additional part',
            'units_assy' => 1,
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('workorders.manuals.usage', $workorder),
            ['manual_id' => $additionalManual->id, 'used' => false]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This manual is already used by TDR parts: MM-TDR-PN');
        $this->assertSame([], $workorder->fresh()->notUsedManualIds());
    }

    public function test_existing_workorder_immediately_uses_current_manual_configuration(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-SNAPSHOT-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-SNAPSHOT-EXTRA']);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);

        $this->assertSame(
            [$additionalManual->id],
            $workorder->fresh('unit')->additionalManualIds()
        );
        $this->assertSame(
            [$primaryManual->id, $additionalManual->id],
            $workorder->fresh('unit')->usedManualIds()
        );
    }

    public function test_only_admin_and_manager_can_manage_workorder_manuals(): void
    {
        $primaryManual = $this->createManual(['number' => 'MM-ROLE-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-ROLE-EXTRA']);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $teamLeader = $this->createUserWithRole('Team Leader');
        $technician = $this->createUserWithRole('Technician');
        $shipping = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'user_id' => $technician->id,
            'unit_id' => $unit->id,
        ]);

        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);

        foreach ([$admin, $manager] as $user) {
            $workorder->forceFill(['not_used_manual_ids' => []])->save();

            $this->actingAs($user)
                ->get(route('workorders.edit', $workorder))
                ->assertOk()
                ->assertDontSee('syncWorkorderManualsBtn', false)
                ->assertSee('Additional manuals come from the primary Manual/CMM');

            $this->actingAs($user)
                ->patchJson(route('workorders.manuals.usage', $workorder), [
                    'manual_id' => $additionalManual->id,
                    'used' => false,
                ])
                ->assertOk()
                ->assertJsonPath('success', true);

            $this->assertSame([$additionalManual->id], $workorder->fresh()->notUsedManualIds());
        }

        foreach ([$teamLeader, $technician, $shipping] as $user) {
            $this->actingAs($user)
                ->patchJson(route('workorders.manuals.usage', $workorder), [
                    'manual_id' => $additionalManual->id,
                    'used' => false,
                ])
                ->assertForbidden();
        }
    }

    public function test_manager_can_exclude_manual_additional_manual_during_workorder_creation(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'MM Create ' . uniqid()]);
        $customer = $this->createCustomer();
        $primaryManual = $this->createManual(['number' => 'MM-CREATE-MAIN', 'title' => 'Create main title']);
        $usedManual = $this->createManual(['number' => 'MM-CREATE-USED', 'title' => 'Create used title']);
        $excludedManual = $this->createManual(['number' => 'MM-CREATE-EXCLUDED', 'title' => 'Create excluded title']);
        $primaryManual->update(['additional_manual_ids' => [$usedManual->id, $excludedManual->id]]);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);

        $this->actingAs($manager)
            ->get(route('workorders.create'))
            ->assertOk()
            ->assertSee('WO Manuals')
            ->assertSee('createWorkorderManualsModal', false)
            ->assertSee('<th class="workorder-manual-title-column">Title</th>', false)
            ->assertSee("change.workorderManuals", false)
            ->assertSee('MM-CREATE-USED')
            ->assertSee('Create used title')
            ->assertSee('MM-CREATE-EXCLUDED')
            ->assertSee('Create excluded title');

        $response = $this->actingAs($manager)->post(route('workorders.store'), [
            'number' => 790001,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $manager->id,
            'manual_selection_present' => 1,
            'used_additional_manual_ids' => [$usedManual->id],
        ]);

        $response->assertRedirect(route('workorders.index'))->assertSessionHasNoErrors();
        $workorder = \App\Models\Workorder::query()->withoutGlobalScope('exclude_drafts')->where('number', 790001)->firstOrFail();
        $this->assertSame([$usedManual->id, $excludedManual->id], $workorder->additionalManualIds());
        $this->assertSame([$excludedManual->id], $workorder->notUsedManualIds());
        $this->assertSame([$primaryManual->id, $usedManual->id], $workorder->usedManualIds());
    }

    public function test_workorder_creation_rejects_manual_not_assigned_to_selected_unit(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'MM Invalid ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $unassignedManual = $this->createManual(['number' => 'MM-UNASSIGNED']);

        $this->from(route('workorders.create'))
            ->actingAs($manager)
            ->post(route('workorders.store'), [
                'number' => 790002,
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $instruction->id,
                'user_id' => $manager->id,
                'manual_selection_present' => 1,
                'used_additional_manual_ids' => [$unassignedManual->id],
            ])
            ->assertRedirect(route('workorders.create'))
            ->assertSessionHasErrors('used_additional_manual_ids');

        $this->assertDatabaseMissing('workorders', ['number' => 790002]);
    }

    public function test_manuals_index_persists_additional_manuals_once_for_all_units(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $primaryManual = $this->createManual(['number' => 'MM-UNIT-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-UNIT-EXTRA', 'lib' => '243']);
        $manager->permittedManuals()->attach([$primaryManual->id, $additionalManual->id]);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'part_number' => 'MM-UNIT-PN',
        ]);
        $secondUnit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'part_number' => 'MM-UNIT-PN-2',
        ]);

        $response = $this->actingAs($manager)->patchJson(route('manuals.additional-manuals.update', $primaryManual), [
            'additional_manual_ids' => [$primaryManual->id, $additionalManual->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('additional_manual_ids.0', $additionalManual->id)
            ->assertJsonPath('additional_manuals.0.lib', '243');
        $this->assertSame([$additionalManual->id], $primaryManual->fresh()->additionalManualIds());
        $this->assertSame([$additionalManual->id], $unit->fresh()->additionalManualIds());
        $this->assertSame([$additionalManual->id], $secondUnit->fresh()->additionalManualIds());

        $show = $this->actingAs($manager)->getJson(route('units.show', $primaryManual->id));
        $show->assertOk()
            ->assertJsonMissingPath('additional_manual_ids')
            ->assertJsonMissingPath('manual_options')
            ->assertJsonMissingPath('units.0.additional_manual_ids');

        $manualsIndex = $this->actingAs($manager)->get(route('manuals.index'));
        $manualsIndex->assertOk();
        $manualsIndex->assertSee('Additional Manuals');
        $manualsIndex->assertSee('MM-UNIT-EXTRA');
        $manualsIndex->assertSee('manual-additional-cell-button', false);
        $manualsIndex->assertSee('data-manual-number="MM-UNIT-MAIN"', false);
        $manualsIndex->assertSee('(243)');

        $componentsPage = $this->actingAs($manager)->get(route('manuals.show', $primaryManual));
        $componentsPage->assertOk();
        $componentsPage->assertDontSee('manualAdditionalManualIds', false);
    }

    public function test_technician_cannot_change_manual_additional_manuals(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $primaryManual = $this->createManual(['number' => 'MM-UNIT-ROLE-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-UNIT-ROLE-EXTRA']);
        $this->actingAs($technician)->patchJson(route('manuals.additional-manuals.update', $primaryManual), [
            'additional_manual_ids' => [$additionalManual->id],
        ])->assertForbidden();

        $this->assertSame([], $primaryManual->fresh()->additionalManualIds());
    }
}
