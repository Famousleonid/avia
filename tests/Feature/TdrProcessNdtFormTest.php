<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrProcessNdtFormTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_ndt_form_prints_specification_for_process_name_with_suffix(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $suffix = uniqid();

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'NDT-7-' . $suffix,
            'name' => 'NDT ultrasound component',
            'ipl_num' => '11-240A',
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'NDT-7 Ultra Sound ' . $suffix,
            'process_sheet_name' => 'NDT',
            'form_number' => 'NDT',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Refer to SB NDT-7-' . $suffix,
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$process->id],
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('tdr-processes.show', [
                'tdr_process' => $tdrProcess->id,
                'process_id' => $process->id,
                'omit_form_header_date' => 1,
            ]))
            ->assertOk()
            ->assertSee('ULTRASOUND AS PER:')
            ->assertSee($process->process);
    }
}
