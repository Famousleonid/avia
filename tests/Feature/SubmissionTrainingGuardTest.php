<?php

namespace Tests\Feature;

use App\Models\{GeneralTask, Main, Task, Training};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class SubmissionTrainingGuardTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function context(string $role = 'Technician'): array
    {
        $user = $this->createUserWithRole($role);
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $wo->unit->manual->update(['unit_name_training' => '2802A0000-ser']);
        $group = GeneralTask::where('technician_stage', 'disassembly')->first()
            ?? GeneralTask::create(['name' => 'Disassembly']);
        $group->forceFill(['technician_stage' => 'disassembly'])->save();
        $task = Task::firstOrCreate(['name' => 'WO Submitted to inspection', 'general_task_id' => $group->id], ['task_has_start_date' => false]);
        return [$user, $wo, $task];
    }

    public function test_missing_or_invalid_training_blocks_both_save_routes_without_changes(): void
    {
        [$user, $wo, $task] = $this->context();
        $this->actingAs($user);
        $payload = ['workorder_id' => $wo->id, 'task_id' => $task->id, 'date_finish' => today()->toDateString()];
        $response = $this->postJson(route('mains.store'), $payload)->assertUnprocessable()->assertJsonPath('training_required', true);
        $this->assertDatabaseMissing('mains', ['workorder_id' => $wo->id, 'task_id' => $task->id]);
        $this->assertStringContainsString('manual_id='.$wo->unit->manual_id, $response->json('training_url'));
        $this->assertStringContainsString('open_training='.$wo->unit->manual_id, $response->json('training_url'));
        $this->get($response->json('training_url'))->assertOk()->assertViewIs('admin.trainings.index');
        $record = Main::create(['workorder_id' => $wo->id, 'general_task_id' => $task->general_task_id, 'task_id' => $task->id, 'user_id' => $user->id, 'date_finish' => '2026-01-01']);
        $base = ['user_id' => $user->id, 'manuals_id' => $wo->unit->manual_id, 'form_type' => 112];
        foreach ([['date_training' => today()->subDays(351)->toDateString()],
            ['date_training' => today()->addDay()->toDateString()],
            ['date_training' => today()->toDateString(), 'form_type' => 132],
            ['date_training' => today()->toDateString(), 'is_legacy' => true],
            ['date_training' => today()->toDateString(), 'manuals_id' => $this->createManual()->id],
            ['date_training' => today()->toDateString(), 'user_id' => $this->createUserWithRole('Technician')->id]] as $extra) {
            Training::create(array_merge($base, $extra));
        }
        $this->patchJson(route('mains.update', $record), ['date_finish' => today()->toDateString()])->assertUnprocessable();
        $this->assertSame('2026-01-01', $record->fresh()->date_finish->toDateString());
        $this->patchJson(route('mains.update', $record), ['date_finish' => null])->assertOk();
    }

    public function test_valid_boundary_training_allows_both_shop_roles_and_prefills_existing_unit(): void
    {
        foreach (['Technician', 'Team Leader'] as $role) {
            [$user, $wo, $task] = $this->context($role);
            Training::create(['user_id' => $user->id, 'manuals_id' => $wo->unit->manual_id, 'form_type' => 112,
                'date_training' => today()->subDays(350)->toDateString()]);
            $this->actingAs($user)->postJson(route('mains.store'), ['workorder_id' => $wo->id,
                'task_id' => $task->id, 'date_finish' => today()->toDateString()])->assertOk();
            $this->get(route('trainings.create', ['manual_id' => $wo->unit->manual_id]))->assertOk()
                ->assertViewHas('manuals', fn ($manuals) => $manuals->contains('id', $wo->unit->manual_id));
            $this->flushSession();
        }
    }

    public function test_admin_is_not_blocked_and_final_submission_remains_available(): void
    {
        [$admin, $wo, $task] = $this->context('Admin');
        $this->actingAs($admin)->postJson(route('mains.store'), ['workorder_id' => $wo->id, 'task_id' => $task->id,
            'date_finish' => today()->toDateString()])->assertOk();
        $this->flushSession();
        [$tech, $wo, $task] = $this->context();
        $task->generalTask->forceFill(['technician_stage' => 'assembly'])->save();
        $task->update(['name' => 'WO Submitted for Final Inspection']);
        $this->actingAs($tech)->postJson(route('mains.store'), ['workorder_id' => $wo->id, 'task_id' => $task->id,
            'date_finish' => today()->toDateString()])->assertOk();
    }

    public function test_mobile_date_endpoint_cannot_bypass_training_check(): void
    {
        [$user, $wo, $task] = $this->context();
        $login = $this->postJson(route('api.mobile.auth.login'), ['email' => $user->email,
            'password' => 'password', 'device_name' => 'Training guard test'])->assertOk();
        $token = $login->json('data.token');
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$wo->id, $task->id]), ['date_finish' => today()->toDateString()])
            ->assertUnprocessable()->assertJsonPath('training_required', true);
        $this->assertDatabaseMissing('mains', ['workorder_id' => $wo->id, 'task_id' => $task->id]);
    }

    public function test_cutover_preserves_legacy_rows_and_is_idempotent(): void
    {
        [$user, $wo, $task] = $this->context();
        $task->update(['name' => '3. WO Submitted to inspection']);
        $post = Task::create(['name' => 'Post Disassembly inspection', 'general_task_id' => $task->general_task_id]);
        $old = Main::create(['workorder_id' => $wo->id, 'general_task_id' => $task->general_task_id, 'task_id' => $task->id,
            'user_id' => $user->id, 'ignore_row' => true, 'date_finish' => '2025-09-08']);
        $before = $old->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_21_120000_retire_reused_legacy_disassembly_tasks.php');
        try {
            $migration->up();
            $this->fail('Pre-cutover catalog must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cutover SQL', $exception->getMessage());
        }
        $this->assertNull($task->fresh()->archived_at);
        foreach (['Wait records', 'In Quotation'] as $name) {
            Task::create(['name' => $name, 'general_task_id' => $task->general_task_id]);
        }
        $migration->up();
        $this->assertNotNull($task->fresh()->archived_at);
        $this->assertNotNull($post->fresh()->archived_at);
        $this->assertSame($before, $old->fresh()->getAttributes());
        $current = Task::active()->where('general_task_id', $task->general_task_id)->get();
        $this->assertCount(4, $current);
        $this->assertTrue($current->contains('name', 'WO Submitted to inspection'));
        $migration->up();
        $this->assertCount(4, Task::active()->where('general_task_id', $task->general_task_id)->get());
        $this->actingAs($user)->patchJson(route('mains.update', $old), ['date_finish' => today()->toDateString()])->assertForbidden();
        $this->assertSame($before, $old->fresh()->getAttributes());
    }
}
