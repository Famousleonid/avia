<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Task;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderActionsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manager_can_approve_workorder_and_main_record_is_created(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $generalTask = GeneralTask::query()->create([
            'name' => 'Approval',
            'sort_order' => 1,
        ]);
        $approvedTask = Task::query()->create([
            'name' => 'Approved',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => 0,
        ]);

        $response = $this->actingAs($manager)->postJson(route('workorders.approve.ajax', $workorder), [
            'approve_date' => '2026-04-10',
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('approved', true);
        $response->assertJsonPath('approve_name', $manager->name);

        $workorder->refresh();

        $this->assertNotNull($workorder->approve_at);
        $this->assertSame($manager->name, $workorder->approve_name);
        $this->assertDatabaseHas('mains', [
            'workorder_id' => $workorder->id,
            'task_id' => $approvedTask->id,
            'general_task_id' => $generalTask->id,
            'user_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('workorder_general_task_statuses', [
            'workorder_id' => $workorder->id,
            'general_task_id' => $generalTask->id,
            'is_done' => 1,
        ]);
    }

    public function test_authenticated_user_can_update_notes_and_manager_can_read_logs(): void
    {
        $author = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();

        $updateResponse = $this->actingAs($author)->patchJson(route('workorders.notes.update', $workorder), [
            'notes' => 'Initial note body',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('success', true);
        $updateResponse->assertJsonPath('notes', 'Initial note body');
        $updateResponse->assertJsonPath('user', $author->name);

        $workorder->refresh();
        $this->assertSame('Initial note body', $workorder->notes);

        $this->assertTrue(Activity::query()
            ->where('subject_type', Workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('log_name', 'workorders')
            ->where('description', 'workorder_notes_created')
            ->exists());

        $logsResponse = $this->actingAs($manager)->getJson(route('workorders.notes.logs', $workorder));

        $logsResponse->assertOk();
        $logsResponse->assertJsonPath('success', true);
        $logsResponse->assertJsonPath('data.0.user', $author->name);
        $logsResponse->assertJsonPath('data.0.new', 'Initial note body');
    }

    public function test_technician_cannot_read_notes_logs(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();

        $response = $this->actingAs($technician)->get(route('workorders.notes.logs', $workorder));

        $response->assertForbidden();
    }
}
