<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\WoBushingBatch;
use App\Models\WorkorderStdProcess;
use App\Services\QuantumRoBufferApplyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\BuildsDomainData;
use Tests\TestCase;

class QuantumRoBufferApplyServiceTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_apply_command_writes_log_to_storage_logs(): void
    {
        $logPath = storage_path('logs/quantum_ro_apply.log');
        $before = File::exists($logPath) ? File::get($logPath) : '';

        $exitCode = Artisan::call('quantum-ro:apply', [
            '--limit' => 1,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($logPath);

        $after = File::get($logPath);
        $this->assertNotSame($before, $after);
        $this->assertStringContainsString('status=ok', $after);
    }

    public function test_ndt_pn_writes_to_std_process_without_ref(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum STD Vendor']);
        $stdProcessName = ProcessName::query()->create([
            'name' => 'STD NDT List',
            'process_sheet_name' => 'STD',
            'form_number' => 'STD',
        ]);
        $target = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $stdProcessName->id,
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => 'NDT',
            'class' => 'STD_LIST_NDT',
            'bom_ref' => null,
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['applied']);
        $this->assertSame(0, $stats['unresolved']);

        $line->refresh();
        $target->refresh();

        $this->assertSame('applied', $line->apply_status);
        $this->assertSame('workorder_std_processes', $line->applied_target_table);
        $this->assertSame($target->id, $line->applied_target_id);
        $this->assertSame($line->ro_number, $target->repair_order);
        $this->assertSame($vendor->id, $target->vendor_id);
        $this->assertSame('2026-06-01', $target->date_start?->format('Y-m-d'));
        $this->assertSame('2026-06-03', $target->date_finish?->format('Y-m-d'));
        $this->assertSame('Quantum', $target->date_start_user);
        $this->assertSame('Quantum', $target->date_finish_user);
    }

    public function test_cad_plate_pn_writes_to_cad_std_process_without_ref(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum CAD Plate Vendor']);
        $stdProcessName = ProcessName::query()->create([
            'name' => 'STD CAD List',
            'process_sheet_name' => 'STD',
            'form_number' => 'STD',
        ]);
        $target = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $stdProcessName->id,
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => 'CAD Plate',
            'class' => 'STD_LIST_CAD',
            'bom_ref' => null,
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['applied']);
        $this->assertSame(0, $stats['unresolved']);

        $line->refresh();
        $target->refresh();

        $this->assertSame('applied', $line->apply_status);
        $this->assertSame('workorder_std_processes', $line->applied_target_table);
        $this->assertSame($target->id, $line->applied_target_id);
        $this->assertSame($line->ro_number, $target->repair_order);
        $this->assertSame($vendor->id, $target->vendor_id);
    }

    public function test_detail_part_pn_uses_ref_code_and_component_match_to_tdr_process(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum TDR Vendor']);
        $processName = ProcessName::query()->create([
            'name' => 'Cad plate',
            'code' => 'CP',
            'process_sheet_name' => 'CADMIUM PLATING',
            'form_number' => '014',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => '170-70170-001',
            'name' => 'Quantum TDR Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'Q-TDR-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $target = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => '170-70170-001',
            'class' => 'DETAIL_PART',
            'bom_ref' => 'C P',
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['applied']);

        $line->refresh();
        $target->refresh();

        $this->assertSame('applied', $line->apply_status);
        $this->assertSame('tdr_processes', $line->applied_target_table);
        $this->assertSame($target->id, $line->applied_target_id);
        $this->assertSame($line->ro_number, $target->repair_order);
        $this->assertSame($vendor->id, $target->vendor_id);
        $this->assertSame('Quantum', $target->date_start_user);
        $this->assertSame('Quantum', $target->date_finish_user);
    }

    public function test_detail_part_ref_code_is_case_insensitive_and_open_duplicate_target_is_selected(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum Shot Peen Vendor']);
        $processName = ProcessName::query()->create([
            'name' => 'Shot peening',
            'code' => 'shp',
            'process_sheet_name' => 'SHOT PEEN',
            'form_number' => 'SHP',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => '170-70930-001',
            'name' => 'Quantum Shot Peen Component',
            'ipl_num' => '1-2',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'Q-SHP-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $oldTarget = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-04-30',
            'date_finish' => '2026-05-08',
        ]);
        $openTarget = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-05-26',
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => '170-70930-001',
            'class' => 'DETAIL_PART',
            'bom_ref' => 'sHp',
            'out_date' => '2026-05-27',
            'returned_date' => '2026-06-02',
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['applied']);
        $this->assertSame(0, $stats['unresolved']);

        $line->refresh();
        $oldTarget->refresh();
        $openTarget->refresh();

        $this->assertSame('applied', $line->apply_status);
        $this->assertSame($openTarget->id, $line->applied_target_id);
        $this->assertNull($oldTarget->repair_order);
        $this->assertSame('2026-04-30', $oldTarget->date_start?->format('Y-m-d'));
        $this->assertSame('2026-05-08', $oldTarget->date_finish?->format('Y-m-d'));
        $this->assertSame($line->ro_number, $openTarget->repair_order);
        $this->assertSame($vendor->id, $openTarget->vendor_id);
        $this->assertSame('2026-05-27', $openTarget->date_start?->format('Y-m-d'));
        $this->assertSame('2026-06-02', $openTarget->date_finish?->format('Y-m-d'));
    }

    public function test_bushing_pn_writes_to_batch_selected_by_ref(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum Bushing Vendor']);
        $processName = ProcessName::query()->create([
            'name' => 'Bushing NDT',
            'process_sheet_name' => 'NDT',
            'form_number' => 'BUSH',
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'NDT',
        ]);
        $firstBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'ndt',
        ]);
        $secondBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'ndt',
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => 'NDTB',
            'class' => 'DETAIL_PROCESS',
            'bom_ref' => 'B2',
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['applied']);

        $line->refresh();
        $firstBatch->refresh();
        $secondBatch->refresh();

        $this->assertSame('applied', $line->apply_status);
        $this->assertSame('wo_bushing_batches', $line->applied_target_table);
        $this->assertSame($secondBatch->id, $line->applied_target_id);
        $this->assertNull($firstBatch->repair_order);
        $this->assertSame($line->ro_number, $secondBatch->repair_order);
        $this->assertSame($vendor->id, $secondBatch->vendor_id);
        $this->assertSame('2026-06-01', $secondBatch->date_start?->format('Y-m-d'));
        $this->assertSame('2026-06-03', $secondBatch->date_finish?->format('Y-m-d'));
    }

    public function test_bushing_missing_batch_stays_unresolved(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Quantum Missing Batch Vendor']);
        WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_column_key' => 'cad',
        ]);
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => 'CADB',
            'class' => 'DETAIL_PROCESS',
            'bom_ref' => 'B2',
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['unresolved']);

        $line->refresh();

        $this->assertSame('unresolved', $line->apply_status);
        $this->assertStringContainsString('Bushing batch not found: B2', (string) $line->apply_message);
    }

    public function test_missing_workorder_is_marked_not_applicable_and_not_reprocessed(): void
    {
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W99999999',
            'vendor_name' => 'Any Quantum Vendor',
            'pn' => 'NDT',
            'class' => 'STD_LIST_NDT',
            'bom_ref' => null,
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(0, $stats['unresolved']);
        $this->assertSame(1, $stats['not_applicable']);

        $line->refresh();

        $this->assertSame('N/A', $line->apply_status);
        $this->assertStringContainsString('Workorder not found', (string) $line->apply_message);
        $this->assertSame($line->source_hash, $line->applied_source_hash);

        $secondStats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(0, $secondStats['scanned']);
    }

    public function test_old_missing_workorder_gets_old_not_found_status(): void
    {
        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W106999',
            'vendor_name' => 'Any Quantum Vendor',
            'pn' => 'NDT',
            'class' => 'STD_LIST_NDT',
            'bom_ref' => null,
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(0, $stats['unresolved']);
        $this->assertSame(1, $stats['not_applicable']);

        $line->refresh();

        $this->assertSame('WO not found: old', $line->apply_status);
        $this->assertStringContainsString('WO not found: old', (string) $line->apply_message);
        $this->assertSame($line->source_hash, $line->applied_source_hash);
    }

    public function test_dismissed_line_is_processed_again_when_quantum_hash_changes(): void
    {
        $oldHash = hash('sha256', 'dismissed-old');
        $newHash = hash('sha256', 'dismissed-new');

        $line = $this->createQuantumLine([
            'ro_number' => 'R'.random_int(1000, 8999),
            'wo_number' => 'W99999888',
            'vendor_name' => 'Any Quantum Vendor',
            'pn' => 'NDT',
            'class' => 'STD_LIST_NDT',
            'bom_ref' => null,
            'apply_status' => 'dismissed',
            'apply_message' => 'Dismissed by user: old Quantum row, no action needed',
            'source_hash' => $newHash,
            'applied_source_hash' => $oldHash,
            'applied_at' => now()->subDay(),
        ]);

        $stats = app(QuantumRoBufferApplyService::class)->apply(1);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['not_applicable']);

        $line->refresh();

        $this->assertSame('N/A', $line->apply_status);
        $this->assertStringContainsString('Workorder not found', (string) $line->apply_message);
        $this->assertSame($newHash, $line->applied_source_hash);
    }

    private function createQuantumLine(array $attributes = []): QuantumRoLine
    {
        $sourceUid = 'rod:test:'.uniqid('', true);

        return QuantumRoLine::query()->create(array_merge([
            'source_uid' => $sourceUid,
            'rod_auto_key' => random_int(100000, 999999),
            'out_date' => '2026-06-01',
            'returned_date' => '2026-06-03',
            'source_last_modified' => '2000-01-01 00:00:00',
            'source_hash' => hash('sha256', $sourceUid),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }
}
