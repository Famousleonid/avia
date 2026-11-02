<?php

namespace Tests\Feature;

use App\Models\{Component, StdProcess, Unit, WorkorderStdProcess};
use App\Services\WorkorderStdApplicability;
use App\Services\Events\ProcessReadyForNextReminderEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class WorkorderStdApplicabilityTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_empty_lists_do_not_block_qa_or_send_reminders(): void
    {
        $user = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id]);
        $row = WorkorderStdProcess::create(['workorder_id' => $wo->id, 'std_type' => 'ndt', 'process_name_id' => \App\Models\ProcessName::firstOrCreate(['name' => 'STD NDT List'], ['process_sheet_name' => 'NDT', 'form_number' => 'NDT'])->id]);
        $this->assertSame('Empty STD list', app(WorkorderStdApplicability::class)->exclusionReason($wo, 'ndt', $row));
        $this->assertFalse(app(ProcessReadyForNextReminderEvent::class)->shouldRun($row));
        $response = $this->actingAs($user)->getJson(route('quality.workorder', ['q' => $wo->number]));
        $response->assertOk();
        $this->assertTrue(collect($response->json('workorder.checks'))->firstWhere('label', 'STD processes complete')['ok']);
        $this->assertFalse(collect($response->json('workorder.std_processes'))->firstWhere('type', 'ndt')['required']);
    }

    public function test_partial_quantity_stays_required_but_fully_repaired_list_is_empty(): void
    {
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id, 'scope_type' => Unit::SCOPE_FULL_UNIT]);
        $component = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'QTY-PIN', 'name' => 'PIN', 'ipl_num' => '1-10', 'units_assy' => 2, 'ndt_list' => true]);
        $tdr = \App\Models\Tdr::create(['workorder_id' => $wo->id, 'component_id' => $component->id, 'qty' => 1, 'necessaries_id' => \App\Models\Necessary::firstOrCreate(['name' => 'Repair'])->id]);
        $service = app(WorkorderStdApplicability::class);
        $this->assertNull($service->exclusionReason($wo, 'ndt'));
        $tdr->update(['qty' => 2]);
        $this->assertSame('Empty STD list', $service->exclusionReason($wo, 'ndt'));
    }

    public function test_nonempty_part_list_can_be_ignored_and_restored(): void
    {
        $wo = $this->createWorkorder(['instruction_id' => $this->createOverhaulInstruction()->id, 'scope_type' => Unit::SCOPE_FULL_UNIT]);
        $component = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'TEST-PIN', 'name' => 'PIN', 'ipl_num' => '1-10', 'units_assy' => 2, 'ndt_list' => true]);
        StdProcess::updateOrCreate(['manual_id' => $wo->unit->manual_id, 'component_id' => $component->id, 'std' => 'ndt'], ['manual_id' => $wo->unit->manual_id, 'component_id' => $component->id, 'std' => 'ndt', 'process' => '1', 'qty' => 2]);
        $row = WorkorderStdProcess::create(['workorder_id' => $wo->id, 'std_type' => 'ndt', 'process_name_id' => \App\Models\ProcessName::firstOrCreate(['name' => 'STD NDT List'], ['process_sheet_name' => 'NDT', 'form_number' => 'NDT'])->id, 'ignore_row' => true]);
        $service = app(WorkorderStdApplicability::class);
        $this->assertSame('Ignored', $service->exclusionReason($wo, 'ndt', $row));
        $wo->update(['scope_type' => Unit::SCOPE_COMPONENT, 'scope_component_id' => $component->id]);
        $this->assertSame('Ignored for Part / Assembly', $service->exclusionReason($wo, 'ndt', $row));
        $this->assertFalse($service->appliesTo($row->fresh()));
        $row->update(['ignore_row' => false]);
        $this->assertNull($service->exclusionReason($wo, 'ndt', $row));
        $response = $this->actingAs($this->createUserWithRole('Admin'))->getJson(route('quality.workorder', ['q' => $wo->number]));
        $response->assertOk();
        $this->assertFalse(collect($response->json('workorder.checks'))->firstWhere('label', 'STD processes complete')['ok']);
        $row->update(['ignore_row' => true]);
        $response = $this->getJson(route('quality.workorder', ['q' => $wo->number]));
        $this->assertTrue(collect($response->json('workorder.checks'))->firstWhere('label', 'STD processes complete')['ok']);
    }
}


