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

class TdrProcessCombinedFormTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_combined_form_prints_five_selected_rows_without_a_maximum_limit(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $selections = [];

        foreach (range(1, 5) as $number) {
            $process = Process::query()->create([
                'process_names_id' => $processName->id,
                'process' => "Combined selected process {$number}",
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);
            $tdrProcess = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'processes' => [$process->id],
                'sort_order' => $number,
            ]);
            $selections[] = [
                'tdr_process_id' => $tdrProcess->id,
                'process_id' => $process->id,
            ];
        }

        $response = $this->actingAs($admin)->post(route('tdr-processes.combinedForm', [
            'tdrId' => $tdr->id,
        ]), [
            'selections' => json_encode($selections),
            'omit_form_header_date' => 1,
        ]);

        $response->assertOk();
        foreach (range(1, 5) as $number) {
            $response->assertSee("Combined selected process {$number}");
        }
    }

    public function test_combined_form_keeps_only_the_selected_process_from_a_multi_process_row(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $processes = collect(['Selected Alpha', 'Not Selected', 'Selected Beta'])->map(function ($text) use ($tdr, $processName) {
            $process = Process::query()->create([
                'process_names_id' => $processName->id,
                'process' => $text,
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);

            return $process;
        });
        $multiRow = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => $processes->take(2)->pluck('id')->all(),
            'sort_order' => 1,
        ]);
        $secondRow = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$processes[2]->id],
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($admin)->post(route('tdr-processes.combinedForm', [
            'tdrId' => $tdr->id,
        ]), [
            'selections' => json_encode([
                ['tdr_process_id' => $multiRow->id, 'process_id' => $processes[0]->id],
                ['tdr_process_id' => $secondRow->id, 'process_id' => $processes[2]->id],
            ]),
        ]);

        $response->assertOk()
            ->assertSee('Selected Alpha')
            ->assertSee('Selected Beta')
            ->assertDontSee('Not Selected');
    }

    public function test_combined_form_rejects_different_process_names(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $otherProcessName = ProcessName::query()->create([
            'name' => 'Other combined name ' . uniqid(),
            'process_sheet_name' => 'OTHER',
            'form_number' => '018',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $rows = collect([$processName, $otherProcessName])->map(function (ProcessName $name, int $index) use ($tdr) {
            $process = Process::query()->create([
                'process_names_id' => $name->id,
                'process' => 'Mismatch process ' . $index,
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);
            $row = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $name->id,
                'processes' => [$process->id],
                'sort_order' => $index + 1,
            ]);

            return ['tdr_process_id' => $row->id, 'process_id' => $process->id];
        });

        $this->actingAs($admin)
            ->post(route('tdr-processes.combinedForm', [
                'tdrId' => $tdr->id,
            ]), [
                'selections' => json_encode($rows->all()),
            ])
            ->assertStatus(422);
    }

    private function createFormContext(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'COMBINED-' . uniqid(),
            'name' => 'Combined form component',
            'ipl_num' => '10-100',
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Combined form process ' . uniqid(),
            'process_sheet_name' => 'OTHER',
            'form_number' => '018',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        return [$admin, $tdr->load('workorder.unit'), $processName];
    }
}
