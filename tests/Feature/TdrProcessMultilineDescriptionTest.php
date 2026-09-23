<?php

namespace Tests\Feature;

use App\Models\{Component, ManualProcess, Process, ProcessName, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrProcessMultilineDescriptionTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_description_round_trips_through_edit_and_print_forms(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::create([
            'manual_id' => $wo->unit->manual_id, 'part_number' => 'QA-ROD',
            'name' => 'ROD', 'ipl_num' => '1-375B',
        ]);
        $tdr = Tdr::create([
            'workorder_id' => $wo->id, 'component_id' => $component->id,
            'qty' => 1, 'use_tdr' => true, 'use_process_forms' => true,
        ]);
        $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ]);
        $description = "fig.6003\npg.6039\n<Zone 2>";

        foreach (['Chrome stripping', 'NDT-6 Eddy Current', 'Stress Relief'] as $name) {
            $processName = ProcessName::create([
                'name' => $name, 'process_sheet_name' => $name,
                'form_number' => 'QA', 'print_form' => true, 'show_in_process_picker' => true,
            ]);
            $process = Process::create([
                'process_names_id' => $processName->id, 'process' => "QA specification\nSecond line <literal>",
            ]);
            ManualProcess::create(['manual_id' => $wo->unit->manual_id, 'processes_id' => $process->id]);
            $row = TdrProcess::create([
                'tdrs_id' => $tdr->id, 'process_names_id' => $processName->id,
                'processes' => [$process->id], 'sort_order' => 1, 'ignore_row' => false,
            ]);
            $this->putJson(route('tdr-processes.update', $row->id), [
                'tdrs_id' => $tdr->id,
                'processes' => [['process_names_id' => $processName->id, 'process' => [$process->id]]],
                'description' => $description,
            ])->assertOk()->assertJson(['success' => true]);
            $this->assertSame($description, $row->fresh()->description);

            $this->get(route('tdr-processes.processesBody', ['tdrId' => $tdr->id]))
                ->assertOk()
                ->assertSee('<span class="process-cell-text">'.e($process->process), false)
                ->assertDontSee('<span class="process-cell-text">' . "\n", false)
                ->assertDontSee('<literal>', false);

            $this->get(route('tdr-processes.editForm', ['id' => $row->id, 'modal' => 1]))
                ->assertOk()->assertSee('<textarea', false)
                ->assertSee('rows="6" maxlength="255"', false)->assertSee($description);
            $this->get(route('tdr-processes.show', [
                'tdr_process' => $row->id, 'process_id' => $process->id, 'omit_form_header_date' => 1,
            ]))->assertOk()->assertSee('class="process-description">'.e($description), false)
                ->assertSee('white-space: pre-line', false)->assertDontSee('<Zone 2>', false);
            $this->get(route('tdr-processes.travelForm', $tdr->id))->assertOk()
                ->assertSee('white-space: pre-line; overflow-wrap: anywhere;">'.e($description), false)
                ->assertDontSee('<Zone 2>', false);
        }
    }
}
