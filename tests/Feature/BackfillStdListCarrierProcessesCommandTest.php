<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class BackfillStdListCarrierProcessesCommandTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_command_copies_legacy_std_dates_to_workorder_level_carrier(): void
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
            'part_number' => 'CMD-LEGACY-' . uniqid(),
            'name' => 'Command Legacy Component',
            'ipl_num' => '1-20',
            'eff_code' => 'ALL',
        ]);

        $legacyTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'CMD-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $legacyTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-04-02',
            'date_finish' => '2026-04-06',
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

        $carrierProcess = TdrProcess::query()->create([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $processName->id,
        ]);

        $this->artisan('std-list:backfill-carriers', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])->assertExitCode(0);

        $carrierProcess->refresh();

        $this->assertSame('2026-04-02', $carrierProcess->date_start?->format('Y-m-d'));
        $this->assertSame('2026-04-06', $carrierProcess->date_finish?->format('Y-m-d'));
    }
}
