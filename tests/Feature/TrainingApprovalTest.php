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

    public function test_sca_manager_can_approve_and_plain_admin_cannot(): void
    {
        $training = $this->makeTraining();

        $plainAdmin = $this->createUserWithRole('Admin', ['stamp' => 'PA', 'can_sign_certificates' => false]);
        $this->actingAs($plainAdmin)
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
        // Обычный админ без SCA историю видит, но апрувить не может
        $response->assertJsonPath('can_approve', false);
        $response->assertJsonPath('records.0.approved', true);
        $this->assertSame('Aug-21-2026', $response->json('records.0.date'));

        // Техник видит историю своей пары
        $technician = \App\Models\User::findOrFail($training->user_id);
        $this->actAsFresh($technician)->getJson(route('trainings.matrixPairHistory', [
            'user_id' => $training->user_id,
            'manual_id' => $training->manuals_id,
        ]))->assertOk();
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
