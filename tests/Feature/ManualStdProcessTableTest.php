<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\StdProcess;
use App\Models\WorkorderStdProcessItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualStdProcessTableTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /** @dataProvider stdQuantityTypes */
    public function test_std_quantity_comes_from_parts_while_process_remains_editable(string $std, string $processName): void
    {
        $manual = $this->createManual();
        $component = Component::create(['manual_id' => $manual->id, 'ipl_num' => '1-10',
            'part_number' => 'QTY-FROM-PARTS', 'name' => 'Quantity Part', 'units_assy' => 3]);
        $this->attachManualProcess($manual->id, $processName, '', '1');
        $this->attachManualProcess($manual->id, $std === 'ndt' ? 'NDT-2' : $processName, '', '2');
        $this->actingAs($this->createUserWithRole('Admin'));
        $process = fn (string $value) => $std === 'ndt' ? [$value] : $value;
        $this->post(route('manuals.std-processes.store', $manual), [
            'std' => $std, 'component_id' => $component->id, 'process' => $process('1'), 'qty' => 99,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $row = StdProcess::where('manual_id', $manual->id)->where('std', $std)->firstOrFail();
        $this->assertSame(3, $row->qty);
        $url = route('manuals.std-processes.update', [$manual, $row]);
        $this->putJson($url, ['process' => $process('2')])->assertOk()
            ->assertJsonPath('row.qty', 3)->assertJsonPath('row.process', '2');
        $this->putJson($url, ['process' => $process('2'), 'qty' => 99])->assertOk()->assertJsonPath('row.qty', 3);
        $this->get(route('manuals.show', ['manual' => $manual->id, 'tab' => 'std', 'std_inner' => $std]))->assertOk();
        $this->assertSame(3, $row->refresh()->qty);
        $this->assertSame('2', $row->process);
        $component->updateQuietly(['units_assy' => 4]);
        $this->get(route('manuals.show', ['manual' => $manual->id, 'tab' => 'std']))->assertOk();
        $this->assertSame(4, $row->refresh()->qty);
        $this->assertSame('2', $row->process);
    }

    public static function stdQuantityTypes(): array
    {
        return [['ndt', 'NDT-1'], ['cad', 'Cad plate'], ['stress', 'Stress Relief'], ['paint', 'Paint']];
    }

    public function test_std_quantity_tracks_parts_from_another_source_manual(): void
    {
        $manual = $this->createManual();
        $source = $this->createManual();
        $component = Component::create(['manual_id' => $source->id, 'ipl_num' => '1-20',
            'part_number' => 'SOURCE-QTY', 'name' => 'Source Quantity Part', 'units_assy' => 5]);
        $row = StdProcess::create(['manual_id' => $manual->id, 'component_id' => $component->id,
            'std' => 'cad', 'process' => 'KEEP THIS PROCESS', 'qty' => 9]);
        StdProcess::syncFromComponentFlagsForManual($manual);
        $this->assertSame(5, $row->refresh()->qty);
        $this->assertSame('KEEP THIS PROCESS', $row->process);
        $component->updateQuietly(['units_assy' => 2]);
        StdProcess::syncFromComponentFlagsForManual($manual);
        $this->assertSame(2, $row->refresh()->qty);
    }

    public function test_manual_std_tab_syncs_rows_from_part_flags(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-10',
            'part_number' => 'PN-NDT-FLAG',
            'name' => 'Flagged NDT Part',
            'units_assy' => 3,
            'ndt_list' => false,
        ]);
        $component->updateQuietly(['ndt_list' => true]);

        $this->assertDatabaseMissing('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
        ]));

        $response->assertOk();
        $response->assertSee('PN-NDT-FLAG');
        $response->assertDontSee('Parts flag');
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'qty' => 3,
        ]);
    }

    public function test_manual_std_add_sets_part_flag(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-20',
            'part_number' => 'PN-ADD-FLAG',
            'name' => 'Add Flag Part',
            'units_assy' => 1,
            'ndt_list' => false,
        ]);
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', '1');

        $response = $this->actingAs($admin)->post(route('manuals.std-processes.store', $manual), [
            'std' => StdProcess::STD_NDT,
            'component_id' => $component->id,
            'qty' => 1,
            'process' => ['1'],
            'eff_code' => 'SHOULD-NOT-SAVE',
        ]);

        $response->assertRedirect(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_NDT,
        ]));
        $this->assertTrue((bool) $component->refresh()->ndt_list);
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'eff_code' => null,
        ]);
    }

    public function test_manual_std_process_picklist_includes_ndt_manual_processes(): void
    {
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'ASTM E1444 and MIL-STD-1907');

        $this->assertSame(
            ['1'],
            StdProcess::processPicklistValuesForManual($manual->id, StdProcess::STD_NDT)
        );
        $this->assertSame(
            [['value' => '1', 'label' => 'NDT-1 ASTM E1444 and MIL-STD-1907']],
            StdProcess::processPicklistOptionsForManual($manual->id, StdProcess::STD_NDT)
        );
    }

    public function test_manual_std_process_picklist_includes_labeled_ndt_manual_processes(): void
    {
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-6 (Eddy Current)', 'NDT', 'MIL-HDBK-728/2');

        $this->assertSame(
            ['6'],
            StdProcess::processPicklistValuesForManual($manual->id, StdProcess::STD_NDT)
        );
        $this->assertSame(
            [['value' => '6', 'label' => 'NDT-6 (Eddy Current) MIL-HDBK-728/2']],
            StdProcess::processPicklistOptionsForManual($manual->id, StdProcess::STD_NDT)
        );
    }

    public function test_manual_std_ndt_add_accepts_multiple_processes_as_numbers(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-25',
            'part_number' => 'PN-NDT-MULTI',
            'name' => 'Multi NDT Part',
            'units_assy' => 1,
            'ndt_list' => false,
        ]);
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'ASTM E1444 and MIL-STD-1907');
        $this->attachManualProcess($manual->id, 'NDT-4', 'NDT', 'ASTM E1417 Type I Method D');
        $this->attachManualProcess($manual->id, 'NDT-7', 'NDT', 'Other NDT procedure');

        $response = $this->actingAs($admin)->post(route('manuals.std-processes.store', $manual), [
            'std' => StdProcess::STD_NDT,
            'component_id' => $component->id,
            'qty' => 1,
            'process' => ['1', '4', '7'],
        ]);

        $response->assertRedirect(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_NDT,
        ]));
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'process' => '1 / 4 / 7',
        ]);
    }

    public function test_ndt_process_options_are_sorted_by_ndt_number(): void
    {
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-7', 'NDT', 'NDT seven');
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'NDT one');
        $this->attachManualProcess($manual->id, 'NDT-4', 'NDT', 'NDT four');

        $this->assertSame(
            ['1', '4', '7'],
            StdProcess::processPicklistValuesForManual($manual->id, StdProcess::STD_NDT)
        );
    }

    public function test_paint_flag_sync_uses_process_text_not_process_id(): void
    {
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-90',
            'part_number' => 'PN-PAINT-TASK',
            'name' => 'Paint Flag Part',
            'units_assy' => 1,
            'paint_list' => false,
        ]);
        $this->attachManualProcess($manual->id, 'Paint', 'PAINT APPLICATION', 'Task');

        $component->updateQuietly(['paint_list' => true]);
        StdProcess::syncFromComponentFlagsForManual($manual);

        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_PAINT,
            'process' => 'Task',
        ]);
    }

    public function test_flag_sync_refreshes_existing_std_qty_and_eff_without_overwriting_process(): void
    {
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => 'PN-SYNC-EFF',
            'name' => 'Sync Eff Part',
            'units_assy' => 2,
            'eff_code' => null,
            'cad_list' => false,
        ]);

        StdProcess::query()->create([
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_CAD,
            'process' => 'CUSTOM CAD PROCESS',
            'qty' => 9,
            'eff_code' => 'STALE',
        ]);

        $component->updateQuietly(['cad_list' => true]);
        StdProcess::syncFromComponentFlagsForManual($manual);

        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_CAD,
            'process' => 'CUSTOM CAD PROCESS',
            'qty' => 2,
            'eff_code' => null,
        ]);
    }

    public function test_flag_sync_removes_std_rows_for_deleted_parts(): void
    {
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-300A',
            'part_number' => '1840-0084',
            'name' => 'Roller, Uplock',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::syncFromComponentFlagsForManual($manual);
        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('std', StdProcess::STD_NDT)
            ->where('component_id', $component->id)
            ->firstOrFail();

        $component->delete();
        StdProcess::syncFromComponentFlagsForManual($manual);

        $this->assertDatabaseMissing('std_processes', [
            'id' => $stdRow->id,
        ]);
    }

    public function test_manual_std_delete_clears_part_flag(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-30',
            'part_number' => 'PN-DELETE-FLAG',
            'name' => 'Delete Flag Part',
            'units_assy' => 1,
            'paint_list' => true,
        ]);

        StdProcess::syncFromComponentFlagsForManual($manual);
        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('std', StdProcess::STD_PAINT)
            ->where('component_id', $component->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('manuals.std-processes.destroy', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]));

        $response->assertRedirect(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
        ]));
        $this->assertFalse((bool) $component->refresh()->paint_list);
        $this->assertDatabaseMissing('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_PAINT,
        ]);
    }

    public function test_manual_std_table_uses_action_column_for_editing_and_deleting_without_eff_code(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-35',
            'part_number' => 'PN-CELL-EDIT',
            'name' => 'Cell Edit Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        StdProcess::syncFromComponentFlagsForManual($manual);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_CAD,
        ]));

        $response->assertOk();
        $response->assertSee('data-std-process-edit', false);
        $response->assertSee('data-std-process-delete-form', false);
        $response->assertSee('class="text-center small std-process-edit-cell"', false);
        $response->assertDontSee('class="text-center std-process-edit-cell"', false);
        $response->assertSee('Qty from Parts');
        $response->assertSee('id="add_std_qty" class="form-control form-control-sm" value="1" readonly', false);
        $response->assertSee('id="edit_std_qty" class="form-control" readonly', false);
        $response->assertSee('data-ipl="7-35"', false);
        $response->assertSee('data-description="Cell Edit Part"', false);
        $response->assertSee('editStdProcessModalTitle', false);
        $response->assertSee('<form id="editStdProcessForm" method="POST" data-no-spinner>', false);
        $response->assertSee("iplEl.className = 'text-info'", false);
        $response->assertSee('<th class="text-center d-none" style="width: 96px;" aria-label="Action">', false);
        $response->assertSee('std-process-action-cell d-none', false);
        $response->assertSee('<span class="visually-hidden">Action</span>', false);
        $response->assertDontSee('<th class="text-center" style="width: 96px;">Action</th>', false);
        $response->assertSee('Delete STD row?', false);
        $response->assertDontSee('id="add_std_eff_code"', false);
        $response->assertDontSee('id="edit_std_eff_code"', false);
        $response->assertDontSee('<th class="text-center">EFF</th>', false);
        $response->assertSee('PN-CELL-EDIT', false);
    }

    public function test_manual_std_ajax_update_returns_row_without_redirect(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-37',
            'part_number' => 'PN-AJAX-STD',
            'name' => 'Ajax Std Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $this->attachManualProcess($manual->id, 'Cad plate', '', '2');
        StdProcess::syncFromComponentFlagsForManual($manual);
        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('component_id', $component->id)
            ->where('std', StdProcess::STD_CAD)
            ->firstOrFail();
        $stdRow->forceFill(['eff_code' => 'A, B'])->save();

        $response = $this->actingAs($admin)->putJson(route('manuals.std-processes.update', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]), [
            'qty' => 5,
            'process' => '2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('row.qty', 1);
        $response->assertJsonPath('row.process', '2');
        $response->assertJsonMissingPath('row.eff_code');
        $this->assertDatabaseHas('std_processes', [
            'id' => $stdRow->id,
            'eff_code' => null,
        ]);
    }

    public function test_manual_std_ajax_update_allows_full_manuals_access_user(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'notification_prefs' => ['manuals_full_access' => true],
        ]);
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-38',
            'part_number' => 'PN-FULL-ACCESS-STD',
            'name' => 'Full Access Std Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $this->attachManualProcess($manual->id, 'Cad plate', '', '2');
        StdProcess::syncFromComponentFlagsForManual($manual);
        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('component_id', $component->id)
            ->where('std', StdProcess::STD_CAD)
            ->firstOrFail();

        $response = $this->actingAs($user)->putJson(route('manuals.std-processes.update', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]), [
            'qty' => 2,
            'process' => '2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('row.process', '2');
        $this->assertDatabaseHas('std_processes', [
            'id' => $stdRow->id,
            'process' => '2',
            'qty' => 1,
        ]);
    }

    public function test_manual_std_ajax_update_still_denies_user_without_manual_access(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-39',
            'part_number' => 'PN-NO-ACCESS-STD',
            'name' => 'No Access Std Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $this->attachManualProcess($manual->id, 'Cad plate', '', '2');
        StdProcess::syncFromComponentFlagsForManual($manual);
        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('component_id', $component->id)
            ->where('std', StdProcess::STD_CAD)
            ->firstOrFail();

        $response = $this->actingAs($user)->putJson(route('manuals.std-processes.update', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]), [
            'qty' => 2,
            'process' => '2',
        ]);

        $response->assertForbidden();
    }

    public function test_manual_std_update_invalidates_existing_workorder_snapshot(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '7-40',
            'part_number' => 'PN-UPDATE-SNAPSHOT',
            'name' => 'Update Snapshot Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $this->attachManualProcess($manual->id, 'Cad plate', '', '2');

        StdProcess::syncFromComponentFlagsForManual($manual);
        StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);
        $this->assertTrue(WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('component_id', $component->id)
            ->exists());

        $stdRow = StdProcess::query()
            ->where('manual_id', $manual->id)
            ->where('std', StdProcess::STD_CAD)
            ->where('component_id', $component->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)->put(route('manuals.std-processes.update', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]), [
            'qty' => 4,
            'process' => '2',
        ]);

        $response->assertRedirect(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_CAD,
        ]));

        $this->assertFalse(WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('component_id', $component->id)
            ->exists());

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_CAD);

        $this->assertSame(1, $rows[0]['qty']);
        $this->assertSame('2', $rows[0]['process']);
    }

    public function test_multiline_cad_process_can_be_added_and_updated_from_multipart_form(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $process = "AMS 2401, Thickness 0.0003-0.0005 in.\nCMM Fig. 6004 (Sheet 2 of 4).                                                   Bake as per AMS 2759/9.";
        $this->attachManualProcess($manual->id, 'Cad plate', 'CAD', $process);
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-150',
            'part_number' => '190-70158-001',
            'name' => 'Bolt',
            'units_assy' => 1,
            'cad_list' => false,
        ]);
        $submitted = str_replace("\n", "\r\n", $process);
        $this->actingAs($admin)->post(route('manuals.std-processes.store', $manual), [
            'std' => 'cad', 'component_id' => $component->id, 'qty' => 1, 'process' => $submitted,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $row = StdProcess::where('manual_id', $manual->id)->where('component_id', $component->id)->where('std', 'cad')->firstOrFail();
        $this->assertSame($process, $row->process);
        $url = route('manuals.std-processes.update', ['manual' => $manual->id, 'stdProcess' => $row->id]);
        $this->putJson($url, ['qty' => 2, 'process' => $submitted])
            ->assertOk()->assertJsonPath('row.process', $process)->assertJsonPath('row.qty', 1);
        $this->assertSame($process, $row->refresh()->process);
        $this->putJson($url, ['qty' => 3, 'process' => $submitted.' Unlinked process'])
            ->assertUnprocessable()->assertJsonValidationErrors('process');
        $this->assertSame(1, (int) $row->refresh()->qty);
    }

    public function test_long_cad_specification_survives_manual_edit_and_workorder_snapshot(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $process = str_repeat('Source CAD instruction. ', 15)."\nBake for 24 hours.";
        $this->assertGreaterThan(255, strlen($process));
        $this->attachManualProcess($manual->id, 'Cad plate', 'CAD', $process);
        $part = Component::query()->create([
            'manual_id' => $manual->id, 'ipl_num' => '3-57', 'part_number' => 'LONG-CAD-PIN',
            'name' => 'Pin', 'units_assy' => 1, 'cad_list' => false,
        ]);
        $this->actingAs($admin)->post(route('manuals.std-processes.store', $manual), [
            'std' => 'cad', 'component_id' => $part->id, 'qty' => 1, 'process' => $process,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $row = StdProcess::where('component_id', $part->id)->where('std', 'cad')->firstOrFail();
        $this->assertSame($process, $row->process);
        $this->putJson(route('manuals.std-processes.update', ['manual' => $manual->id, 'stdProcess' => $row->id]), [
            'qty' => 1, 'process' => str_replace("\n", "\r\n", $process),
        ])->assertOk()->assertJsonPath('row.process', $process);
        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), 'cad');
        $this->assertSame($process, $rows[0]['process']);
        $this->assertSame($process, WorkorderStdProcessItem::where('workorder_id', $workorder->id)->where('component_id', $part->id)->value('process'));
    }

    private function attachManualProcess(int $manualId, string $name, string $sheet, string $processValue): void
    {
        $processName = ProcessName::query()->create([
            'name' => $name,
            'process_sheet_name' => $sheet,
            'form_number' => '016',
            'show_in_process_picker' => true,
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => $processValue,
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);
    }
}
