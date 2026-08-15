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

    public function test_new_workorder_snapshots_unit_additional_manuals_and_builds_combined_std_and_kit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-PRIMARY']);
        $additionalManual = $this->createManual(['number' => 'MM-ADDITIONAL']);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$additionalManual->id],
        ]);
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
        $editPage->assertSee('MM-PRIMARY');
        $editPage->assertSee('MM-ADDITIONAL');
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
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$additionalManual->id],
        ]);
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
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$usedManual->id, $secondUsedManual->id],
        ]);
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
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$additionalManual->id],
        ]);
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
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$additionalManual->id],
        ]);
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

    public function test_existing_workorder_keeps_its_manual_snapshot_until_explicit_sync(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'MM-SNAPSHOT-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-SNAPSHOT-EXTRA']);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $unit->update(['additional_manual_ids' => [$additionalManual->id]]);

        $this->assertSame([], $workorder->fresh('unit')->additionalManualIds());
        $this->assertSame([$primaryManual->id], $workorder->fresh('unit')->usedManualIds());

        $response = $this->actingAs($admin)->postJson(
            route('workorders.manuals.sync', $workorder)
        );

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(
            [$additionalManual->id],
            $workorder->fresh('unit')->additionalManualIds()
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

        $unit->update(['additional_manual_ids' => [$additionalManual->id]]);

        foreach ([$admin, $manager] as $user) {
            $workorder->forceFill([
                'additional_manual_ids' => [],
                'not_used_manual_ids' => [],
            ])->save();

            $this->actingAs($user)
                ->get(route('workorders.edit', $workorder))
                ->assertOk()
                ->assertSee('syncWorkorderManualsBtn', false)
                ->assertSee('Load manuals from Unit');

            $this->actingAs($user)
                ->postJson(route('workorders.manuals.sync', $workorder))
                ->assertOk()
                ->assertJsonPath('success', true);

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
                ->postJson(route('workorders.manuals.sync', $workorder))
                ->assertForbidden();

            $this->actingAs($user)
                ->patchJson(route('workorders.manuals.usage', $workorder), [
                    'manual_id' => $additionalManual->id,
                    'used' => false,
                ])
                ->assertForbidden();
        }
    }

    public function test_manager_can_exclude_unit_additional_manual_during_workorder_creation(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'MM Create ' . uniqid()]);
        $customer = $this->createCustomer();
        $primaryManual = $this->createManual(['number' => 'MM-CREATE-MAIN']);
        $usedManual = $this->createManual(['number' => 'MM-CREATE-USED']);
        $excludedManual = $this->createManual(['number' => 'MM-CREATE-EXCLUDED']);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [$usedManual->id, $excludedManual->id],
        ]);

        $this->actingAs($manager)
            ->get(route('workorders.create'))
            ->assertOk()
            ->assertSee('WO Manuals')
            ->assertSee('createWorkorderManualsModal', false)
            ->assertSee('MM-CREATE-USED')
            ->assertSee('MM-CREATE-EXCLUDED');

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

    public function test_unit_editor_persists_additional_manuals_and_excludes_primary_manual(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $primaryManual = $this->createManual(['number' => 'MM-UNIT-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-UNIT-EXTRA', 'lib' => '243']);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'part_number' => 'MM-UNIT-PN',
        ]);

        $response = $this->actingAs($manager)->postJson(route('units.update', $primaryManual->id), [
            'part_numbers' => [[
                'part_number' => $unit->part_number,
                'name' => $unit->name,
                'eff_code' => $unit->eff_code,
                'verified' => true,
                'additional_manual_ids' => [$primaryManual->id, $additionalManual->id],
            ]],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame([$additionalManual->id], $unit->fresh()->additionalManualIds());

        $show = $this->actingAs($manager)->getJson(route('units.show', $primaryManual->id));
        $show->assertOk()
            ->assertJsonPath('units.0.additional_manual_ids.0', $additionalManual->id)
            ->assertJsonPath('units.0.additional_manual_numbers.0', 'MM-UNIT-EXTRA')
            ->assertJsonPath('units.0.additional_manuals.0.lib', '243');
        $this->assertContains(
            $additionalManual->id,
            collect($show->json('manual_options'))->pluck('id')->all()
        );
        $this->assertNotContains(
            $primaryManual->id,
            collect($show->json('manual_options'))->pluck('id')->all()
        );

        $admin = $this->createUserWithRole('Admin');
        $manualPage = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $primaryManual->id,
            'tab' => 'components',
        ]));
        $manualPage->assertOk();
        $manualPage->assertSee('Additional Manuals');
        $manualPage->assertSee('MM-UNIT-EXTRA');
        $manualPage->assertSee('<span class="text-secondary">(243)</span>', false);
    }

    public function test_technician_cannot_change_unit_additional_manuals(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $primaryManual = $this->createManual(['number' => 'MM-UNIT-ROLE-MAIN']);
        $additionalManual = $this->createManual(['number' => 'MM-UNIT-ROLE-EXTRA']);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);

        $this->actingAs($technician)->postJson(route('units.update', $primaryManual->id), [
            'part_numbers' => [[
                'part_number' => $unit->part_number,
                'verified' => true,
                'additional_manual_ids' => [$additionalManual->id],
            ]],
        ])->assertForbidden();

        $this->assertSame([], $unit->fresh()->additionalManualIds());
    }
}
