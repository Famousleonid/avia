<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsActiveTrainingData;
use Tests\TestCase;

class TrainingDatesModalTest extends TestCase
{
    use BuildsActiveTrainingData, DatabaseTransactions;

    public function test_technician_base_date_is_locked_and_uses_plain_style(): void
    {
        $user = $this->createUserWithRole('Technician', ['is_admin' => false]);
        $manual = $this->createManual();
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '132', 'date_training' => '2024-04-12', 'is_legacy' => false]);
        $response = $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->get(route('trainings.index'));
        $response->assertOk()->assertSee('id="trainingTotalHours" class="text-info"', false);
        $this->assertMatchesRegularExpression('/<input[^>]*class="[^"]*training-locked-base-date[^>]*disabled>/s', $response->getContent());
        $response->assertDontSee('background: #b3423a', false);
    }

    public function test_save_returns_actual_hours_and_normalized_dates_for_animation(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual(['training_hours' => 8]);
        $response = $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->postJson(route('trainings.createTraining'), [
            'manuals_id' => [$manual->id, $manual->id],
            'date_training' => ['2025-04-16', '2025-06-18'],
            'form_type' => ['112', '112'],
        ]);
        $response->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('submitted_dates', ['2025-04-18', '2025-06-20'])
            ->assertJsonPath('saved_trainings.0.hours', 40)
            ->assertJsonPath('saved_trainings.1.hours', 10);
    }

    public function test_modal_hours_match_total_and_keep_base_form_uncredited(): void
    {
        $user = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['training_hours' => 8]);
        $records = [];
        foreach (['132' => ['2024-04-12'], '112' => ['2025-04-18', '2026-03-06', '2027-04-09']] as $form => $dates) {
            foreach ($dates as $date) {
                $records[] = Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
                    'form_type' => (string) $form, 'date_training' => $date, 'is_legacy' => false]);
            }
        }
        $response = $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->get(route('trainings.index'));
        $response->assertOk()->assertSee('training-date-grid')->assertSee('training-hours');
        $list = collect($response->viewData('formattedTrainingLists'))->firstWhere('manuals_id', $manual->id);
        $this->assertEquals(90, $list['total_hours']);
        $this->assertEquals([$records[1]->id => 40, $records[2]->id => 10, $records[3]->id => 40], $list['hours_by_training']);
        $this->assertArrayNotHasKey($records[0]->id, $list['hours_by_training']);
    }
}
