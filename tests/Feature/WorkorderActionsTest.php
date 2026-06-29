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

    public function test_mains_show_hides_tdr_processes_without_process_name(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $component = $this->createComponent($manual, [
            'name' => 'Main Null Guard Component',
            'part_number' => 'NULL-GUARD-PN',
            'ipl_num' => '1-1',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'serial_number' => 'NULL-GUARD-SN',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Visible Null Guard Process ' . uniqid(),
            'process_sheet_name' => 'TEST',
            'form_number' => '000',
            'show_in_process_picker' => true,
        ]);
        $visible = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
            'repair_order' => 'VISIBLE-NULL-GUARD-RO',
        ]);
        $orphan = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => null,
            'sort_order' => 2,
            'repair_order' => 'ORPHAN-NULL-GUARD-RO',
        ]);

        $response = $this->actingAs($admin)->get(route('mains.show', $workorder));

        $response
            ->assertOk()
            ->assertSee('VISIBLE-NULL-GUARD-RO')
            ->assertSee('data-qa-process-id="' . $visible->id . '"', false)
            ->assertDontSee('ORPHAN-NULL-GUARD-RO')
            ->assertDontSee('data-qa-process-id="' . $orphan->id . '"', false);
    }

    public function test_tdr_process_date_clear_is_written_to_dedicated_activity_log(): void
    {
        $manager = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder(['number' => 880611]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Date Audit Sequence Test'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => 'AUDIT',
                'show_in_process_picker' => true,
            ]
        );
        $process = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-06-03',
            'date_finish' => '2026-06-04',
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $process), [
                'date_start' => null,
            ])
            ->assertOk()
            ->assertJsonPath('date_start', null)
            ->assertJsonPath('date_finish', null);

        $activity = Activity::query()
            ->where('log_name', 'tdr_process_date_change')
            ->where('subject_type', TdrProcess::class)
            ->where('subject_id', $process->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $props = $activity->properties->toArray();

        $this->assertSame('tdr_process_update_date', $props['source']);
        $this->assertSame($workorder->number, $props['workorder_number']);
        $this->assertSame($process->id, $props['tdr_process_id']);
        $this->assertSame(['date_start'], $props['received_fields']);
        $this->assertSame(['date_start'], $props['empty_fields']);
        $this->assertTrue($props['auto_cleared_finish']);
        $this->assertSame('2026-06-03', $props['old']['date_start']);
        $this->assertSame('2026-06-04', $props['old']['date_finish']);
        $this->assertNull($props['new']['date_start']);
        $this->assertNull($props['new']['date_finish']);
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

    public function test_std_ignore_toggle_returns_existing_dates_for_live_restore(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Stress relief List'],
            [
                'process_sheet_name' => 'STD',
                'form_number' => 'STRESS',
                'show_in_process_picker' => false,
            ]
        );

        $std = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'stress',
            'process_name_id' => $processName->id,
            'date_start' => '2026-05-10',
            'date_finish' => '2026-05-12',
            'date_start_user' => 'Quantum',
            'date_finish_user' => 'Quantum',
            'ignore_row' => true,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateIgnoreRow', $std), ['ignore_row' => false])
            ->assertOk()
            ->assertJsonPath('ignore_row', false)
            ->assertJsonPath('date_start', '2026-05-10')
            ->assertJsonPath('date_finish', '2026-05-12')
            ->assertJsonPath('repair_order', 'AT')
            ->assertJsonPath('date_start_user', 'Quantum')
            ->assertJsonPath('date_finish_user', 'Quantum');
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

    public function test_allowed_tdr_process_dates_can_be_edited_by_technician_and_write_text_user(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $firstProcessName = ProcessName::query()->firstOrCreate(
            ['name' => 'Manual Date Guard First'],
            [
                'process_sheet_name' => 'TEST',
                'form_number' => '000',
                'show_in_process_picker' => true,
            ]
        );
        $machiningEcName = ProcessName::query()->firstOrCreate(
            ['name' => 'Machining (EC)'],
            [
                'process_sheet_name' => 'MACHINING',
                'form_number' => '018',
                'show_in_process_picker' => true,
            ]
        );

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstProcessName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-10',
            'date_finish' => '2026-05-12',
        ]);
        $editable = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $machiningEcName->id,
            'sort_order' => 2,
        ]);

        $this->actingAs($technician)
            ->patchJson(route('tdrprocesses.updateDate', $editable), [
                'date_start' => '2026-05-09',
                'date_finish' => '2026-05-08',
            ])
            ->assertOk()
            ->assertJsonPath('date_start', '2026-05-09')
            ->assertJsonPath('date_finish', '2026-05-08')
            ->assertJsonPath('repair_order', 'AT')
            ->assertJsonPath('date_start_user', $technician->name)
            ->assertJsonPath('date_finish_user', $technician->name);

        $editable->refresh();

        $this->assertSame($technician->id, $editable->date_start_user_id);
        $this->assertSame($technician->id, $editable->date_finish_user_id);
        $this->assertSame($technician->name, $editable->date_start_user);
        $this->assertSame($technician->name, $editable->date_finish_user);
        $this->assertSame('AT', $editable->repair_order);

        $this->actingAs($technician)
            ->patchJson(route('tdrprocesses.updateDate', $editable), [
                'date_start' => null,
                'date_finish' => null,
            ])
            ->assertOk()
            ->assertJsonPath('date_start', null)
            ->assertJsonPath('date_finish', null)
            ->assertJsonPath('repair_order', null);

        $editable->refresh();

        $this->assertNull($editable->date_start);
        $this->assertNull($editable->date_finish);
        $this->assertNull($editable->repair_order);
    }

    public function test_allowed_std_process_dates_can_be_edited_by_technician_and_write_text_user(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        WorkorderStdProcess::query()->where('workorder_id', $workorder->id)->delete();
        $stressName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Stress relief List'],
            [
                'process_sheet_name' => 'STD',
                'form_number' => 'STRESS',
                'show_in_process_picker' => false,
            ]
        );
        $std = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'stress',
            'process_name_id' => $stressName->id,
            'date_start' => null,
            'date_finish' => null,
        ]);

        $this->actingAs($technician)
            ->patchJson(route('workorder_std_processes.updateDate', $std), ['date_finish' => '2026-05-09'])
            ->assertOk()
            ->assertJsonPath('date_start', null)
            ->assertJsonPath('date_finish', '2026-05-09')
            ->assertJsonPath('repair_order', 'AT')
            ->assertJsonPath('date_finish_user', $technician->name);

        $std->refresh();

        $this->assertNull($std->date_start);
        $this->assertSame($technician->id, $std->date_finish_user_id);
        $this->assertSame($technician->name, $std->date_finish_user);
        $this->assertSame('AT', $std->repair_order);

        $this->actingAs($technician)
            ->patchJson(route('workorder_std_processes.updateDate', $std), [
                'date_start' => null,
                'date_finish' => null,
            ])
            ->assertOk()
            ->assertJsonPath('date_start', null)
            ->assertJsonPath('date_finish', null)
            ->assertJsonPath('repair_order', null);

        $std->refresh();

        $this->assertNull($std->date_start);
        $this->assertNull($std->date_finish);
        $this->assertNull($std->repair_order);
    }
}
