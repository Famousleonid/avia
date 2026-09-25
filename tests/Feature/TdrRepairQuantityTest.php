<?php

namespace Tests\Feature;

use App\Models\{Code, Component, ManualProcess, Necessary, Process, ProcessName, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class TdrRepairQuantityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_repair_quantity_is_bounded_saved_and_printed(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'QA-DOWEL', 'ipl_num' => '1-230', 'name' => 'DOWEL', 'units_assy' => 10]);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $code = Code::firstOrCreate(['name' => 'Repairable'], ['code' => 'R']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $payload = ['workorder_id' => $wo->id, 'component_id' => $part->id, 'necessaries_id' => $repair->id, 'codes_id' => $code->id, 'use_tdr' => 1, 'use_process_forms' => 1, 'assy_serial_number' => '', 'description' => 'Repair dowels'];
        $payload['order_component_id'] = null;
        foreach ([0, 11, 1.5] as $invalid) {
            $this->postJson(route('tdrs.store'), $payload + ['qty' => $invalid])->assertUnprocessable()->assertJsonValidationErrors('qty');
        }
        $this->post(route('tdrs.store'), $payload)->assertSessionHasNoErrors();
        $tdr = Tdr::where('workorder_id', $wo->id)->where('component_id', $part->id)->firstOrFail();
        $this->assertSame(1, (int) $tdr->qty);
        $this->putJson(route('tdrs.update', $tdr->id), ['qty' => 10, 'workorder_id' => $wo->id])->assertOk();
        $this->assertSame(10, (int) $tdr->fresh()->qty);
        $this->putJson(route('tdrs.update', $tdr->id), ['qty' => 11])->assertUnprocessable()->assertJsonValidationErrors('qty');
        $this->assertSame(10, (int) $tdr->fresh()->qty);
        $this->get(route('tdrs.editForm', $tdr->id))->assertOk()->assertSee('id="edit_repair_qty"', false)->assertSee('data-manual-max="10"', false);
        $name = ProcessName::firstOrCreate(['name' => 'Xylan'], ['process_sheet_name' => 'Xylan', 'form_number' => 'QA', 'print_form' => true, 'show_in_process_picker' => true]);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'QA Xylan']);
        ManualProcess::create(['manual_id' => $wo->unit->manual_id, 'processes_id' => $process->id]);
        $row = TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id, 'processes' => [$process->id], 'sort_order' => 1]);
        foreach (['tdrs.specProcessForm', 'tdrs.specProcessFormEmp'] as $route) {
            $this->get(route($route, $wo->id))->assertOk()->assertSee('QTY: 10', false);
        }
        $this->get(route('tdr-processes.show', ['tdr_process' => $row->id, 'process_id' => $process->id]))
            ->assertOk()->assertSee('>10</div>', false)->assertSee('QA-DOWEL');
        $this->get(route('tdr-processes.travelForm', $tdr->id))->assertOk()->assertSee('QTY: 10', false);
    }
}
