<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Task;
use App\Models\WorkStage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class LibraryWorkStagesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_library_uses_feature_access_and_system_admin_bypass(): void
    {
        foreach ([['Admin', false, true], ['Technician', true, false], ['Admin', true, true], ['Manager', false, true]] as [$role, $isAdmin, $allowed]) {
            $user = $this->createUserWithRole($role, ['is_admin' => $isAdmin])->refresh();
            if ($role === 'Manager') $this->grantFeatureAccess($user, 'library.work_stages');
            $this->actingAs($user)->withSession([
                'auth.version' => $user->auth_version,
                'password_hash_web' => $user->getAuthPassword(),
            ]);
            $this->get(route('library.work-stages.index'))->assertStatus($allowed ? 200 : 403);
            $this->get(route('tasks.index'))->assertStatus($allowed ? 302 : 403);
            $this->postJson(route('general-tasks.store'), ['name' => 'Group for '.$role.' '.(int) $isAdmin, 'sort_order' => 5])
                ->assertStatus($allowed ? 204 : 403);
        }
    }

    public function test_catalog_tables_edit_and_preserve_workorder_history(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $this->get(route('general-tasks.index'))->assertRedirect(route('library.work-stages.index'));
        $this->get(route('library.work-stages.index'))->assertOk()->assertSeeInOrder([
            'Disassembly', 'Wait Records', 'In Quotation', 'Wait Approval',
            'OVH in Process', 'Final Assembly &amp; Test', 'Final Inspection',
        ], false);

        $this->postJson(route('general-tasks.store'), ['name' => 'Disassembly', 'sort_order' => 15])->assertNoContent();
        $group = GeneralTask::where('name', 'Disassembly')->firstOrFail();
        $other = GeneralTask::create(['name' => 'Other stage', 'sort_order' => 20]);
        $this->patchJson(route('general-tasks.update', $group), ['name' => 'Shop disassembly', 'sort_order' => 30])->assertNoContent();
        $this->assertSame('disassembly', $group->fresh()->technician_stage);
        $this->deleteJson(route('general-tasks.destroy', $group))->assertConflict();

        $newTask = Task::create(['name' => 'New task', 'general_task_id' => $group->id]);
        $this->patchJson(route('tasks.update', $newTask), ['name' => 'New task', 'general_task_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('general_task_id');
        $this->patchJson(route('tasks.update', $newTask), ['name' => 'New task', 'general_task_id' => $other->id])->assertNoContent();
        $this->assertSame($other->id, $newTask->fresh()->general_task_id);
        $this->deleteJson(route('tasks.destroy', $newTask))->assertNoContent();

        $this->postJson(route('tasks.store'), ['name' => 'Strip part', 'general_task_id' => $group->id, 'task_has_start_date' => true])->assertNoContent();
        $task = Task::where('name', 'Strip part')->firstOrFail();
        $wo = $this->createWorkorder();
        Main::create(['workorder_id' => $wo->id, 'general_task_id' => $group->id, 'task_id' => $task->id]);
        $this->patchJson(route('tasks.update', $task), ['name' => 'Strip component', 'task_has_start_date' => true])->assertNoContent();
        $this->patchJson(route('tasks.update', $task), ['name' => 'Moved task', 'general_task_id' => $other->id])->assertConflict();
        $this->deleteJson(route('tasks.destroy', $task))->assertConflict();
        $this->get(route('library.work-stages.index', ['section' => 'tasks']))->assertOk()
            ->assertSee('Shop disassembly')->assertSee('Strip component')->assertDontSee('Wait Records');

        $this->postJson(route('library.work-stages.store'), ['name' => 'New status', 'sort_order' => 1])->assertNoContent();
        $stage = WorkStage::where('name', 'New status')->firstOrFail();
        $this->patchJson(route('library.work-stages.update', $stage), ['name' => 'Wait Records', 'sort_order' => 5])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->patchJson(route('library.work-stages.update', $stage), ['name' => 'Ready', 'sort_order' => 5])->assertNoContent();
        $this->get(route('library.work-stages.index', ['section' => 'work-stages']))->assertOk()
            ->assertSeeInOrder(['Ready', 'Disassembly'])->assertDontSee('Strip component');
        $this->deleteJson(route('library.work-stages.destroy', $stage))->assertNoContent();
        $this->assertModelMissing($stage);

        $requiredStage = WorkStage::where('workflow_key', 'wait_records')->firstOrFail();
        $this->deleteJson(route('library.work-stages.destroy', $requiredStage))->assertConflict();
        $this->patchJson(route('library.work-stages.update', $requiredStage), ['name' => 'Records pending', 'sort_order' => 99])
            ->assertNoContent();
        $this->assertSame('wait_records', $requiredStage->fresh()->workflow_key);
    }

    public function test_stage_text_color_can_be_set_and_reset_without_changing_the_workflow(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $stage = WorkStage::where('workflow_key', 'ovh_in_process')->firstOrFail();
        $update = route('library.work-stages.update', $stage);
        $workorder = $this->createWorkorder(['approve_at' => '2026-11-04', 'user_id' => $admin->id]);
        $list = route('workorders.index', ['q' => $workorder->number, 'stages' => $stage->workflow_key]);
        $fragment = $list.'&fragment=1';

        $this->patchJson($update, ['text_color' => '#a8d5fe'])->assertNoContent();
        $this->patchJson($update, ['text_color' => '#fff; color: red'])->assertUnprocessable()->assertJsonValidationErrors('text_color');
        $this->assertEquals($stage->only('name', 'sort_order', 'workflow_key'), $stage->fresh()->only('name', 'sort_order', 'workflow_key'));
        $this->get(route('library.work-stages.index', ['section' => 'work-stages']))->assertOk()->assertSee('data-saved-color="#a8d5fe"', false);
        $this->get($list)->assertOk()->assertSee('style="--work-stage-color: #a8d5fe"', false);
        $this->assertStringContainsString('style="--work-stage-color: #a8d5fe"', $this->getJson($fragment)->assertOk()->json('html'));

        $this->patchJson($update, ['text_color' => null])->assertNoContent();
        $this->assertNull($stage->fresh()->text_color);
        $this->get($list)->assertOk()->assertDontSee('style="--work-stage-color:', false);
    }

    /** @dataProvider submissionDateRoles */
    public function test_shop_role_sees_and_edits_only_the_two_submission_dates(string $role): void
    {
        $user = $this->createUserWithRole($role);
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        \App\Models\Training::create(['user_id' => $user->id, 'manuals_id' => $wo->unit->manual_id,
            'form_type' => 112, 'date_training' => today()->toDateString()]);
        $disassembly = GeneralTask::create(['name' => 'Disassembly', 'sort_order' => 20]);
        $disassembly->forceFill(['technician_stage' => 'disassembly'])->save();
        $assembly = GeneralTask::create(['name' => 'Assembly & Test', 'sort_order' => 10]);
        $assembly->forceFill(['technician_stage' => 'assembly'])->save();
        $final = GeneralTask::create(['name' => 'Final Test', 'sort_order' => 0]);
        $submitted = Task::create(['name' => 'WO Submitted to inspection', 'general_task_id' => $disassembly->id, 'task_has_start_date' => false]);
        $finalSubmitted = Task::create(['name' => '2. WO Submitted for Final Inspection', 'general_task_id' => $assembly->id, 'task_has_start_date' => true]);
        $inspect = Task::create(['name' => 'Final inspection', 'general_task_id' => $final->id]);
        $locked = Task::create(['name' => 'Wait records', 'general_task_id' => $disassembly->id]);
        $hidden = Task::create(['name' => 'Waiting parts', 'general_task_id' => $assembly->id]);
        $wrongStage = Task::create(['name' => 'WO Submitted to inspection', 'general_task_id' => $assembly->id]);
        $duplicate = Task::create(['name' => $submitted->name, 'general_task_id' => $disassembly->id, 'sort_order' => 10]);
        config(['mains.restricted_date_task_ids' => [$locked->id]]);

        $this->actingAs($user)->get(route('mains.show', $wo))->assertOk()
            ->assertViewHas('general_tasks', fn ($stages) => $stages->modelKeys() === [$assembly->id, $disassembly->id])
            ->assertViewHas('tasksByGeneral', fn ($tasks) => $tasks[$disassembly->id]->modelKeys() === [$submitted->id]
                && $tasks[$assembly->id]->modelKeys() === [$finalSubmitted->id])
            ->assertSee('Date submitted')->assertSee($submitted->name)->assertSee($finalSubmitted->name)
            ->assertDontSee('Wait records')->assertDontSee('Waiting parts')->assertDontSee('woNotesLogModal')
            ->assertDontSee('name="date_start"', false)->assertDontSee('name="ignore_row"', false);
        foreach ([$inspect, $locked, $hidden, $wrongStage, $duplicate] as $task) {
            $this->postJson(route('mains.store'), ['workorder_id' => $wo->id, 'task_id' => $task->id, 'date_finish' => '2026-11-20'])->assertForbidden();
        }
        foreach ([$submitted, $finalSubmitted] as $task) {
            $this->postJson(route('mains.store'), [
                'workorder_id' => $wo->id, 'task_id' => $task->id,
                'date_finish' => '2026-11-20',
            ])->assertOk()->assertJsonPath('general_task_all_finished', true);
        }
        $outside = Main::create(['workorder_id' => $wo->id, 'general_task_id' => $final->id, 'task_id' => $inspect->id]);
        $this->patchJson(route('mains.update', $outside), ['date_finish' => '2026-11-21'])->assertForbidden();

        $saved = Main::where('workorder_id', $wo->id)->where('task_id', $finalSubmitted->id)->firstOrFail();
        $saved->forceFill(['date_start' => '2026-11-19'])->save();
        $editUrl = route('mains.update', $saved);
        $this->patchJson($editUrl, ['date_finish' => '2026-11-21', 'ignore_row' => true])->assertForbidden();
        $this->patchJson($editUrl, ['date_finish' => '2026-11-21', 'date_start' => '2026-11-20'])->assertForbidden();
        $this->patchJson($editUrl, ['date_finish' => '2026-11-21'])->assertOk()
            ->assertJsonPath('date_start', '2026-11-19')->assertJsonPath('date_finish', '2026-11-21');
        $this->patchJson($editUrl, ['date_finish' => null])->assertOk()->assertJsonPath('general_task_all_finished', false);
    }

    public static function submissionDateRoles(): array
    {
        return [['Technician'], ['Team Leader']];
    }

    public function test_task_order_and_archive_apply_to_active_work_without_removing_history(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $stage = GeneralTask::create(['name' => 'Ordered stage', 'sort_order' => 4]);
        $first = Task::create(['name' => 'Zulu task', 'general_task_id' => $stage->id, 'sort_order' => 10]);
        $second = Task::create(['name' => 'Alpha task', 'general_task_id' => $stage->id, 'sort_order' => 20]);
        $history = Main::create(['workorder_id' => $wo->id, 'general_task_id' => $stage->id, 'task_id' => $first->id, 'date_start' => '2026-11-19']);
        Main::create(['workorder_id' => $wo->id, 'general_task_id' => $stage->id, 'task_id' => $second->id, 'date_finish' => '2026-11-20']);

        $this->actingAs($admin);
        $library = route('library.work-stages.index', ['section' => 'tasks']);
        $main = route('mains.show', $wo);
        $this->get($library)->assertSeeInOrder(['Zulu task', 'Alpha task'])->assertSee('Archive Zulu task');
        $this->get($main)->assertViewHas('tasksByGeneral', fn ($tasks) => $tasks[$stage->id]->modelKeys() === [$first->id, $second->id]);
        $this->patchJson(route('tasks.update', $first), ['name' => $first->name, 'sort_order' => 30])->assertNoContent();
        $this->get($library)->assertSeeInOrder(['Alpha task', 'Zulu task']);
        $this->get($main)->assertViewHas('tasksByGeneral', fn ($tasks) => $tasks[$stage->id]->modelKeys() === [$second->id, $first->id]);
        $this->get($main)->assertViewHas('gtAllFinished', fn ($done) => !$done[$stage->id]);

        $this->deleteJson(route('tasks.destroy', $first))->assertConflict();
        $this->patchJson(route('tasks.archive', $first))->assertNoContent();
        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertSame('2026-11-19', $history->fresh()->date_start->toDateString());
        $this->get($library)->assertSee('Archived tasks (1)')->assertSee('Restore Zulu task');
        $this->get($main)
            ->assertDontSee('data-main-task-id="'.$first->id.'"', false)
            ->assertDontSee('data-history-task-id="'.$first->id.'"', false)
            ->assertViewHas('gtAllFinished', fn ($done) => $done[$stage->id]);
        $this->get(route('mains.task-history', [$wo, $first]))
            ->assertNotFound();
        $this->patchJson(route('mains.update', $history), ['date_finish' => '2026-11-21'])->assertConflict();

        $this->patchJson(route('tasks.restore', $first))->assertNoContent();
        $this->assertNull($first->fresh()->archived_at);
        $this->get($main)->assertSee('Zulu task')->assertViewHas('gtAllFinished', fn ($done) => !$done[$stage->id]);
    }

    public function test_group_with_archived_tasks_can_be_removed_without_losing_workorder_history(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $group = GeneralTask::create(['name' => 'Final Test', 'sort_order' => 4]);
        $task = Task::create(['name' => 'Final inspection', 'general_task_id' => $group->id]);
        $wo = $this->createWorkorder();
        $history = Main::create([
            'workorder_id' => $wo->id, 'general_task_id' => $group->id, 'task_id' => $task->id,
            'date_finish' => '2026-11-19',
        ]);
        $library = route('library.work-stages.index', ['section' => 'tasks']);
        $delete = route('general-tasks.destroy', $group);

        $this->get($library)->assertDontSee('Delete Final Test');
        $this->deleteJson($delete)->assertConflict();
        $this->patchJson(route('tasks.archive', $task))->assertNoContent();
        $this->get($library)->assertSee('Delete Final Test')->assertSee('Restore Final inspection');
        $this->deleteJson($delete)->assertNoContent();

        $this->assertNotNull($group->fresh()->catalog_removed_at);
        $this->get($library)->assertDontSee('Delete Final Test')->assertDontSee('Restore Final inspection');
        $this->get(route('mains.show', $wo))
            ->assertViewHas('general_tasks', fn ($groups) => !$groups->contains('id', $group->id))
            ->assertDontSee('data-main-task-id="'.$task->id.'"', false)
            ->assertDontSee('data-history-task-id="'.$task->id.'"', false);
        $this->get(route('mains.task-history', [$wo, $task]))->assertNotFound();
        $other = $this->createWorkorder();
        $this->get(route('mains.task-history', [$other, $task]))->assertNotFound();
        $this->assertSame($task->id, $history->fresh()->task->id);
        $this->assertSame('2026-11-19', $history->fresh()->date_finish->toDateString());
        $this->patchJson(route('tasks.restore', $task))->assertNotFound();
        $this->postJson(route('tasks.store'), ['name' => 'Another inspection', 'general_task_id' => $group->id])
            ->assertUnprocessable()->assertJsonValidationErrors('general_task_id');
        $technician = $this->createUserWithRole('Technician');
        $this->actingAs($technician)->withSession([
            'auth.version' => $technician->auth_version, 'password_hash_web' => $technician->getAuthPassword(),
        ])->get(route('mains.task-history', [$wo, $task]))->assertForbidden();
    }
}
