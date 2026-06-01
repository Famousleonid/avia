<?php

namespace Tests\Feature;

use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\Vendor;
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

    public function test_cad_process_code_falls_back_to_std_cad_list_target(): void
    {
        $workorder = $this->createWorkorder();
        $vendor = Vendor::query()->create([
            'name' => 'Quantum Test Vendor',
        ]);

        $code = 'QCP'.random_int(10000, 99999);
        $cadPlate = ProcessName::query()->create([
            'name' => 'Cad plate',
            'code' => $code,
            'process_sheet_name' => 'CADMIUM PLATING',
            'form_number' => '014',
            'show_in_process_picker' => true,
        ]);
        $stdCadList = ProcessName::query()->create([
            'name' => 'STD CAD List',
            'process_sheet_name' => 'STD LIST',
            'form_number' => 'STD',
            'show_in_process_picker' => false,
        ]);

        $target = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $stdCadList->id,
        ]);

        $sourceUid = 'rod:test:'.uniqid('', true);
        $line = QuantumRoLine::query()->create([
            'source_uid' => $sourceUid,
            'rod_auto_key' => random_int(100000, 999999),
            'ro_number' => 'R8934',
            'wo_number' => 'W'.$workorder->number,
            'vendor_name' => $vendor->name,
            'pn' => '170-70170-001',
            'class' => 'DETAIL_PART',
            'bom_ref' => substr($code, 0, 3).' '.substr($code, 3),
            'out_date' => '2026-06-01',
            'source_last_modified' => '2000-01-01 00:00:00',
            'source_hash' => hash('sha256', $sourceUid),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
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
        $this->assertSame('R8934', $target->repair_order);
        $this->assertSame($vendor->id, $target->vendor_id);
        $this->assertSame('2026-06-01', $target->date_start?->format('Y-m-d'));

        $this->assertDatabaseMissing('workorder_std_processes', [
            'workorder_id' => $workorder->id,
            'process_name_id' => $cadPlate->id,
        ]);
    }
}
