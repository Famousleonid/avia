<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderStdListProcessesServiceTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_service_prefers_legacy_std_process_with_data_over_blank_new_carrier_row(): void
    {
        $instruction = $this->createOverhaulInstruction();
        $workorder = $this->createWorkorder([
            'instruction_id' => $instruction->id,
        ]);

        $processName = ProcessName::query()->create([
            'name' => 'STD Paint List',
            'process_sheet_name' => 'STD LIST',
            'form_number' => 'STD',
            'show_in_process_picker' => false,
        ]);

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LEGACY-STD-' . uniqid(),
            'name' => 'Legacy STD Component',
            'ipl_num' => '1-10',
            'eff_code' => 'ALL',
        ]);

        $legacyTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'LEGACY-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $legacyProcess = TdrProcess::query()->create([
            'tdrs_id' => $legacyTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-04-01',
            'date_finish' => '2026-04-05',
        ]);

        $carrierTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'serial_number' => 'NSN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $processName->id,
        ]);

        $resolved = app(WorkorderStdListProcessesService::class)->resolveForWorkorder($workorder);

        $this->assertNotNull($resolved);
        $this->assertSame($legacyProcess->id, $resolved->get('paint')?->id);
        $this->assertSame('2026-04-01', $resolved->get('paint')?->date_start?->format('Y-m-d'));
        $this->assertSame('2026-04-05', $resolved->get('paint')?->date_finish?->format('Y-m-d'));
    }
}
