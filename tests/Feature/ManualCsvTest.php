<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\StdProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualCsvTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_csv_store_imports_std_processes_without_saving_file(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-5', 'NDT', 'NDT procedure five');
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PN-100',
            'name' => 'Sample Row',
            'units_assy' => 2,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty,manual,eff code',
            '1-10,PN-100,Sample Row,5,2,CMM-TEST,ALL',
        ]);

        $storeResponse = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $storeResponse->assertOk();
        $storeResponse->assertJsonPath('success', true);

        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'process' => '5',
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('components', [
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PN-100',
            'ndt_list' => true,
        ]);

        $this->assertDatabaseMissing('media', [
            'model_type' => Manual::class,
            'model_id' => $manual->id,
            'collection_name' => 'csv_files',
        ]);
    }

    public function test_manual_csv_store_requires_review_for_missing_ipl(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'NDT procedure one');

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '8-10,PN-MISSING,Missing Part,1,1',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('needs_review', true);
        $response->assertJsonPath('conflicts.0.type', 'missing_ipl');
        $this->assertDatabaseMissing('components', [
            'manual_id' => $manual->id,
            'ipl_num' => '8-10',
        ]);
    }

    public function test_manual_csv_store_can_add_missing_ipl_after_review(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'NDT procedure one');

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '8-20,PN-NEW,New Part,1,2',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
            'csv_resolutions' => json_encode(['0' => 'add_component']),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('components', [
            'manual_id' => $manual->id,
            'ipl_num' => '8-20',
            'part_number' => 'PN-NEW',
            'name' => 'New Part',
            'ndt_list' => true,
        ]);
        $component = Component::query()
            ->where('manual_id', $manual->id)
            ->where('ipl_num', '8-20')
            ->firstOrFail();
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'qty' => 2,
        ]);
    }

    public function test_manual_csv_store_can_skip_name_mismatch_after_review(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-30',
            'part_number' => 'PN-PARTS',
            'name' => 'Parts Name',
            'units_assy' => 1,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '8-30,PN-CSV,CSV Name,1,1',
        ]);

        $review = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_CAD,
            'csv_file' => $this->makeUploadedFile('cad.csv', $csv, 'text/csv'),
        ]);
        $review->assertOk();
        $review->assertJsonPath('needs_review', true);
        $review->assertJsonPath('conflicts.0.type', 'name_mismatch');

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_CAD,
            'csv_file' => $this->makeUploadedFile('cad.csv', $csv, 'text/csv'),
            'csv_resolutions' => json_encode(['0' => 'skip']),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('std_processes', [
            'manual_id' => $manual->id,
            'std' => StdProcess::STD_CAD,
        ]);
        $this->assertDatabaseHas('components', [
            'manual_id' => $manual->id,
            'ipl_num' => '8-30',
            'cad_list' => false,
        ]);
    }

    public function test_manual_csv_store_uses_component_data_for_name_mismatch_after_review(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-40',
            'part_number' => 'PN-PARTS',
            'name' => 'Parts Name',
            'units_assy' => 1,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '8-40,PN-CSV,CSV Name,1,1',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_CAD,
            'csv_file' => $this->makeUploadedFile('cad.csv', $csv, 'text/csv'),
            'csv_resolutions' => json_encode(['0' => 'use_component']),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_CAD,
        ]);
        $this->assertDatabaseMissing('components', [
            'manual_id' => $manual->id,
            'part_number' => 'PN-CSV',
            'name' => 'CSV Name',
        ]);
    }

    public function test_manual_csv_store_can_overwrite_parts_data_after_review(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'Cad plate', '', '1');
        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-45',
            'part_number' => 'PN-PARTS',
            'name' => 'Parts Name',
            'units_assy' => 1,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '8-45,PN-CSV,CSV Name,1,3',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_CAD,
            'csv_file' => $this->makeUploadedFile('cad.csv', $csv, 'text/csv'),
            'csv_resolutions' => json_encode(['0' => 'overwrite_component']),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('components', [
            'manual_id' => $manual->id,
            'ipl_num' => '8-45',
            'part_number' => 'PN-CSV',
            'name' => 'CSV Name',
            'cad_list' => true,
        ]);
        $component = Component::query()
            ->where('manual_id', $manual->id)
            ->where('ipl_num', '8-45')
            ->firstOrFail();
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_CAD,
            'qty' => 3,
        ]);
    }

    public function test_manual_csv_import_rebuilds_existing_workorder_std_items(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'NDT procedure one');
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9-10',
            'part_number' => 'PN-OLD',
            'name' => 'Old STD Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        $this->assertSame(['PN-OLD'], array_column(
            StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT),
            'part_number'
        ));

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9-20',
            'part_number' => 'PN-NEW',
            'name' => 'New STD Part',
            'units_assy' => 2,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '9-20,PN-NEW,New STD Part,1,2',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'part_number' => 'PN-OLD',
        ]);
        $this->assertDatabaseHas('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'std_type' => StdProcess::STD_NDT,
            'part_number' => 'PN-NEW',
            'remaining_qty' => 2,
        ]);
    }

    public function test_manual_csv_store_rejects_invalid_process_type(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $response = $this->actingAs($admin)->postJson(route('manuals.csv.store', $manual), [
            'process_type' => 'unknown',
            'csv_file' => $this->makeUploadedFile('bad.csv', "item no.\n1-10", 'text/csv'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_manual_csv_store_rejects_process_not_linked_to_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'ASTM E1444 and MIL-STD-1907');
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PN-100',
            'name' => 'Sample Row',
            'units_assy' => 1,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '1-10,PN-100,Sample Row,4,1',
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('not linked to this manual', (string) $response->json('error'));
        $this->assertDatabaseMissing('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
        ]);
    }

    public function test_manual_csv_store_normalizes_multiple_ndt_processes_against_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->attachManualProcess($manual->id, 'NDT-1', 'NDT', 'ASTM E1444 and MIL-STD-1907');
        $this->attachManualProcess($manual->id, 'NDT-4', 'NDT', 'ASTM E1417 Type I Method D');
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-20',
            'part_number' => 'PN-200',
            'name' => 'Multi NDT Row',
            'units_assy' => 1,
        ]);

        $csv = implode("\n", [
            'item no.,part no.,description,process no.,qty',
            '1-20,PN-200,Multi NDT Row,NDT-4 / NDT-1,1',
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('ndt.csv', $csv, 'text/csv'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('std_processes', [
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
            'process' => '1 / 4',
        ]);
    }

    public function test_manual_csv_store_reports_format_errors(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $csv = implode("\n", [
            'item no.,part no.,description,qty',
            '1-10,PN-100,Sample Row,1',
        ]);

        $response = $this->actingAs($admin)->postJson(route('manuals.csv.store', $manual), [
            'process_type' => StdProcess::STD_NDT,
            'csv_file' => $this->makeUploadedFile('bad-format.csv', $csv, 'text/csv'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('CSV format error', (string) $response->json('error'));
        $this->assertStringContainsString('process no.', (string) $response->json('error'));
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
