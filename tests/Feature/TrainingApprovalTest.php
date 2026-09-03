<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Приёмка тренингов: апрувят SCA-квалифицированные Manager/Admin и назначенные;
 * принятая запись заморожена (delete/update) для всех, кроме назначенных
 * (can_manage_approved_trainings).
 */
class TrainingApprovalTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession([\App\Http\Middleware\EnsureAuthSessionVersion::SESSION_KEY => 1]);
    }

    /** Смена юзера в том же тесте: AuthenticateSession требует свежую сессию. */
    private function actAsFresh($user): static
    {
        $this->flushSession();
        $this->withSession([\App\Http\Middleware\EnsureAuthSessionVersion::SESSION_KEY => 1]);

        return $this->actingAs($user);
    }

    private function makeTraining(): Training
    {
        $technician = $this->createUserWithRole('Technician', [
            'stamp' => 'TA',
            'is_admin' => false,
            'show_in_training_matrix' => true,
        ]);
        $manual = $this->createManual(['unit_name_training' => 'APPR-PN-' . uniqid()]);

        return Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => '2026-08-21',
            'form_type' => '112',
        ]);
    }

    public function test_sca_manager_can_approve_and_plain_manager_cannot(): void
    {
        $training = $this->makeTraining();

        // Manager без SCA-флага и без is_admin — апрув запрещён
        $plainManager = $this->createUserWithRole('Manager', [
            'stamp' => 'PM',
            'is_admin' => false,
            'can_sign_certificates' => false,
        ]);
        $this->actingAs($plainManager)
            ->postJson(route('trainings.approve', ['id' => $training->id]))
            ->assertForbidden();

        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $this->actAsFresh($scaManager)
            ->postJson(route('trainings.approve', ['id' => $training->id]))
            ->assertOk();

        $training->refresh();
        $this->assertSame($scaManager->id, (int) $training->approved_by);
        $this->assertNotNull($training->approved_at);
    }

    public function test_is_admin_checkbox_grants_full_training_rights(): void
    {
        $training = $this->makeTraining();

        // Любая роль с чекбоксом is_admin — без ограничений
        $superuser = $this->createUserWithRole('Manager', [
            'stamp' => 'SU',
            'is_admin' => true,
            'can_sign_certificates' => false,
        ]);

        $this->actingAs($superuser)
            ->postJson(route('trainings.approve', ['id' => $training->id]))
            ->assertOk();

        // Принятую запись может удалить (is_admin = как назначенный)
        $this->actingAs($superuser)
            ->deleteJson(route('trainings.destroy', ['training' => $training->id]))
            ->assertOk();
        $this->assertNull($training->fresh());
    }

    public function test_approved_training_is_locked_except_for_designated(): void
    {
        $training = $this->makeTraining();
        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $training->update(['approved_by' => $scaManager->id, 'approved_at' => now()]);

        // Даже сам принявший (не назначенный) не может ни удалить, ни сменить дату
        $this->actingAs($scaManager)
            ->deleteJson(route('trainings.destroy', ['training' => $training->id]))
            ->assertStatus(422);
        $this->actingAs($scaManager)
            ->putJson(route('trainings.update', ['training' => $training->id]), ['date_training' => '2026-08-14'])
            ->assertStatus(422);
        $this->assertNotNull($training->fresh());

        // Назначенный — может
        $designated = $this->createUserWithRole('Manager', [
            'stamp' => 'DS',
            'is_admin' => false,
            'can_manage_approved_trainings' => true,
        ]);
        $this->actAsFresh($designated)
            ->deleteJson(route('trainings.destroy', ['training' => $training->id]))
            ->assertOk();
        $this->assertNull($training->fresh());
    }

    public function test_unapprove_is_designated_only(): void
    {
        $training = $this->makeTraining();
        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $training->update(['approved_by' => $scaManager->id, 'approved_at' => now()]);

        $this->actingAs($scaManager)
            ->postJson(route('trainings.unapprove', ['id' => $training->id]))
            ->assertForbidden();

        $designated = $this->createUserWithRole('Manager', [
            'stamp' => 'DS',
            'is_admin' => false,
            'can_manage_approved_trainings' => true,
        ]);
        $this->actAsFresh($designated)
            ->postJson(route('trainings.unapprove', ['id' => $training->id]))
            ->assertOk();

        $this->assertNull($training->fresh()->approved_by);
    }

    public function test_form_132_date_editable_by_admin_only(): void
    {
        $training = $this->makeTraining();
        $form132 = Training::query()->create([
            'user_id' => $training->user_id,
            'manuals_id' => $training->manuals_id,
            'date_training' => '2026-08-21',
            'form_type' => '132',
        ]);

        $manager = $this->createUserWithRole('Manager', ['stamp' => 'MG', 'is_admin' => false]);
        $this->actingAs($manager)
            ->putJson(route('trainings.update', ['training' => $form132->id]), ['date_training' => '2026-08-14'])
            ->assertStatus(422);

        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $this->actAsFresh($admin)
            ->putJson(route('trainings.update', ['training' => $form132->id]), ['date_training' => '2026-08-14'])
            ->assertOk();

        // Дата нормализуется к пятнице своей недели (14.08.2026 — пятница)
        $this->assertSame('2026-08-14', $form132->fresh()->date_training);
    }

    public function test_matrix_legacy_endpoint_marks_pair_as_old_training(): void
    {
        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $technician = $this->createUserWithRole('Technician', ['stamp' => 'LG', 'is_admin' => false]);
        $manual = $this->createManual(['unit_name_training' => 'MXLEG-PN-' . uniqid()]);

        $this->actingAs($scaManager)->postJson(route('trainings.matrixLegacy.store'), [
            'user_id' => $technician->id,
            'manual_id' => $manual->id,
        ])->assertOk();

        $this->assertTrue(Training::query()
            ->where('user_id', $technician->id)
            ->where('manuals_id', $manual->id)
            ->where('is_legacy', true)
            ->whereNull('date_training')
            ->exists());

        // Обычный manager без прав приёмки — 403
        $plainManager = $this->createUserWithRole('Manager', [
            'stamp' => 'PL',
            'is_admin' => false,
            'can_sign_certificates' => false,
        ]);
        $this->actAsFresh($plainManager)->postJson(route('trainings.matrixLegacy.store'), [
            'user_id' => $technician->id,
            'manual_id' => $manual->id,
        ])->assertForbidden();
    }

    public function test_matrix_legacy_remove_clears_x_mark(): void
    {
        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $technician = $this->createUserWithRole('Technician', ['stamp' => 'LR', 'is_admin' => false]);
        $manual = $this->createManual(['unit_name_training' => 'XREM-PN-' . uniqid()]);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => null,
            'form_type' => null,
            'is_legacy' => true,
        ]);

        $this->actingAs($scaManager)->postJson(route('trainings.matrixLegacy.remove'), [
            'user_id' => $technician->id,
            'manual_id' => $manual->id,
        ])->assertOk();

        $this->assertFalse(Training::query()
            ->where('user_id', $technician->id)
            ->where('manuals_id', $manual->id)
            ->where('is_legacy', true)
            ->exists());
    }

    public function test_matrix_pair_history_returns_records_with_approval_state(): void
    {
        $training = $this->makeTraining();
        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $training->update(['approved_by' => $scaManager->id, 'approved_at' => now()]);

        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $response = $this->actAsFresh($admin)->getJson(route('trainings.matrixPairHistory', [
            'user_id' => $training->user_id,
            'manual_id' => $training->manuals_id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        // is_admin — суперправа, в т.ч. приёмка
        $response->assertJsonPath('can_approve', true);
        $response->assertJsonPath('records.0.approved', true);
        $this->assertSame('21/Aug/2026', $response->json('records.0.date'));

        // Техник видит историю своей пары
        $technician = \App\Models\User::findOrFail($training->user_id);
        $this->actAsFresh($technician)->getJson(route('trainings.matrixPairHistory', [
            'user_id' => $training->user_id,
            'manual_id' => $training->manuals_id,
        ]))->assertOk();
    }

    public function test_training_actions_are_written_to_activity_log(): void
    {
        $training = $this->makeTraining();

        // Создание пишется в лог (activity log, log_name=training)
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'training',
            'subject_type' => Training::class,
            'subject_id' => $training->id,
            'event' => 'created',
        ]);

        // Изменение даты — updated-лог с автором
        $admin = $this->createUserWithRole('Admin', ['stamp' => 'AD']);
        $this->actingAs($admin)
            ->putJson(route('trainings.update', ['training' => $training->id]), ['date_training' => '2026-08-14'])
            ->assertOk();
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'training',
            'subject_id' => $training->id,
            'event' => 'updated',
            'causer_id' => $admin->id,
        ]);

        // Массовое удаление юнита — deleted-лог на каждую запись (не bulk-query)
        $this->actingAs($admin)->postJson(route('trainings.deleteAll'), [
            'user_id' => $training->user_id,
            'manual_id' => $training->manuals_id,
        ])->assertOk();
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'training',
            'subject_id' => $training->id,
            'event' => 'deleted',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_approve_unit_approves_all_pair_records_and_blocks_delete_all(): void
    {
        $training = $this->makeTraining();
        Training::query()->create([
            'user_id' => $training->user_id,
            'manuals_id' => $training->manuals_id,
            'date_training' => '2026-08-21',
            'form_type' => '132',
        ]);

        $scaManager = $this->createUserWithRole('Manager', [
            'stamp' => 'SM',
            'is_admin' => false,
            'can_sign_certificates' => true,
        ]);
        $this->actingAs($scaManager)
            ->postJson(route('trainings.approveUnit'), [
                'user_id' => $training->user_id,
                'manual_id' => $training->manuals_id,
            ])
            ->assertOk();

        $this->assertSame(0, Training::query()
            ->where('user_id', $training->user_id)
            ->where('manuals_id', $training->manuals_id)
            ->whereNull('approved_by')
            ->count());

        // Delete All юнита с принятыми записями — только назначенный
        $response = $this->actingAs($scaManager)->postJson(route('trainings.deleteAll'), [
            'user_id' => $training->user_id,
            'manual_id' => $training->manuals_id,
        ]);
        $response->assertStatus(422);
        $this->assertNotNull($training->fresh());
    }
}
