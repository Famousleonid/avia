<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\ManualServiceBulletin;
use App\Models\Task;
use App\Models\WorkorderServiceBulletinLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ServiceBulletinLogTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_technician_cannot_update_service_bulletin_log_after_post_disassembly_inspection_is_finished(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $bulletin = $this->createManualServiceBulletinForWorkorder($workorder);
        $this->finishPostDisassemblyInspection($workorder);

        $response = $this->actingAs($technician)->post(route('tdrs.serviceBulletinLog.update', ['workorder' => $workorder->id]), [
            'rows' => [
                $bulletin->id => [
                    'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
                    'notes' => 'Technician edit should be blocked',
                ],
            ],
        ]);

        $response
            ->assertRedirect(route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]))
            ->assertSessionHasErrors('service_bulletin_log');

        $this->assertDatabaseMissing('workorder_service_bulletin_logs', [
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $bulletin->id,
        ]);
    }

    public function test_manager_can_update_service_bulletin_log_after_post_disassembly_inspection_is_finished(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $bulletin = $this->createManualServiceBulletinForWorkorder($workorder);
        $this->finishPostDisassemblyInspection($workorder);

        $response = $this->actingAs($manager)->post(route('tdrs.serviceBulletinLog.update', ['workorder' => $workorder->id]), [
            'rows' => [
                $bulletin->id => [
                    'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
                    'notes' => 'Manager edit is allowed',
                ],
            ],
        ]);

        $response->assertRedirect(route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]));

        $this->assertDatabaseHas('workorder_service_bulletin_logs', [
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $bulletin->id,
            'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
            'notes' => 'Manager edit is allowed',
        ]);
    }

    private function createManualServiceBulletinForWorkorder($workorder): ManualServiceBulletin
    {
        return ManualServiceBulletin::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'sort_order' => 1,
            'year_introduced' => '2026',
            'ac_mfg_service_bulletin_no' => 'AC-SB-1',
            'oem_service_bulletin_no' => 'OEM-SB-1',
            'awd_no' => 'AWD-1',
            'identification_method' => 'Visual',
            'description' => 'QA service bulletin',
            'default_requirement' => ManualServiceBulletin::REQUIREMENT_MANDATORY,
            'is_active' => true,
        ]);
    }

    private function finishPostDisassemblyInspection($workorder): void
    {
        $generalTask = GeneralTask::query()->create([
            'name' => 'QA',
            'sort_order' => 1,
        ]);
        $task = Task::query()->create([
            'name' => 'Post Disassembly inspection',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => 0,
        ]);

        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $generalTask->id,
            'task_id' => $task->id,
            'date_finish' => '2026-05-20',
        ]);
    }
}
