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
        ]);

        $response->assertRedirect(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_NDT,
        ]));
        $this->assertTrue((bool) $component->refresh()->ndt_list);
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

    public function test_manual_std_table_uses_cells_for_editing_without_action_column(): void
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
        $response->assertSee('data-ipl="7-35"', false);
        $response->assertSee('data-description="Cell Edit Part"', false);
        $response->assertSee('editStdProcessModalTitle', false);
        $response->assertSee('<form id="editStdProcessForm" method="POST" data-no-spinner>', false);
        $response->assertSee("iplEl.className = 'text-info'", false);
        $response->assertDontSee('btn-std-process-edit', false);
        $response->assertDontSee('Delete row?', false);
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

        $response = $this->actingAs($admin)->putJson(route('manuals.std-processes.update', [
            'manual' => $manual->id,
            'stdProcess' => $stdRow->id,
        ]), [
            'qty' => 5,
            'process' => '2',
            'eff_code' => 'A,B',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('row.qty', 5);
        $response->assertJsonPath('row.process', '2');
        $response->assertJsonPath('row.eff_code', 'A, B');
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

        $this->assertSame(4, $rows[0]['qty']);
        $this->assertSame('2', $rows[0]['process']);
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
