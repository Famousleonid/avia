<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\ProcessName;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
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

    public function test_mains_process_dates_must_follow_previous_process_dates(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Sequence Test'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '000',
                'show_in_process_picker' => true,
            ]
        );

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-10',
            'date_finish' => '2026-05-12',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 2,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), ['date_start' => '2026-05-11'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');

        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $second), ['date_start' => '2026-05-11'])
            ->assertOk()
            ->assertJsonPath('date_start', '2026-05-11');

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), ['date_start' => '2026-05-12'])
            ->assertOk()
            ->assertJsonPath('date_start', '2026-05-12');

        $second->forceFill(['date_finish' => '2026-05-14'])->save();

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), ['date_start' => '2026-05-13'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');
    }

    public function test_mains_process_sequence_ignores_sequence_exempt_ec_rows(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Sequence Normal Test'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '000',
                'show_in_process_picker' => true,
            ]
        );
        $ecProcessName = ProcessName::query()->firstOrCreate(
            ['name' => 'EC'],
            [
                'process_sheet_name' => 'EC',
                'form_number' => 'EC',
                'show_in_process_picker' => true,
            ]
        );
        $ecProcessName->forceFill(['sequence_exempt' => true])->save();

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
            'date_start' => '2026-04-08',
            'date_finish' => '2026-04-08',
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ecProcessName->id,
            'sort_order' => 2,
            'date_start' => '2026-04-17',
            'date_finish' => null,
        ]);
        $next = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 3,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $next), ['date_start' => '2026-04-09'])
            ->assertOk()
            ->assertJsonPath('date_start', '2026-04-09');
    }

    public function test_mains_std_process_returned_date_requires_sent_date_and_same_or_later_date(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Sequence Test'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '000',
                'show_in_process_picker' => true,
            ]
        );

        $std = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $processName->id,
            'date_start' => null,
            'date_finish' => null,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateDate', $std), ['date_finish' => '2026-05-09'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_finish');

        $std->forceFill(['date_start' => '2026-05-10'])->save();

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateDate', $std), ['date_finish' => '2026-05-09'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_finish');

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateDate', $std), ['date_finish' => '2026-05-10'])
            ->assertOk()
            ->assertJsonPath('date_finish', '2026-05-10');
    }

    public function test_is_admin_bypasses_std_process_sequence_guard(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder();
        WorkorderStdProcess::query()->where('workorder_id', $workorder->id)->delete();
        $firstProcessName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Admin Sequence Test'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '000',
                'show_in_process_picker' => true,
            ]
        );
        $secondProcessName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Admin Sequence Test 2'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '001',
                'show_in_process_picker' => true,
            ]
        );

        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $firstProcessName->id,
            'date_start' => '2026-05-10',
            'date_finish' => '2026-05-12',
        ]);
        $second = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $secondProcessName->id,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateDate', $second), ['date_start' => '2026-05-11'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');

        $this->actingAs($admin)
            ->patchJson(route('workorder_std_processes.updateDate', $second), ['date_start' => '2026-05-11'])
            ->assertOk()
            ->assertJsonPath('date_start', '2026-05-11');
    }
}
