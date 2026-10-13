<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TrainingWeekAvailabilityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_overlap_marks_two_trainings_and_links_only_editable_records(): void
    {
        $user = $this->employee();
        $manual = $this->createManual();
        $one = Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '132', 'date_training' => '2025-04-18']);
        $weeks = collect($this->getJson(route('trainings.weekCalendar', ['month' => '2025-01']))->assertOk()->json('weeks'))->keyBy('friday');
        $this->assertSame('occupied', $weeks['2025-04-18']['status']);
        $two = Training::create(['user_id' => $user->id, 'manuals_id' => $this->createManual()->id,
            'form_type' => '112', 'date_training' => '2025-04-18', 'approved_by' => $user->id]);
        $weeks = collect($this->getJson(route('trainings.weekCalendar', ['month' => '2025-01']))->assertOk()->json('weeks'))->keyBy('friday');
        $this->assertSame('conflict', $weeks['2025-04-18']['status']);
        $records = collect($weeks['2025-04-18']['trainings'])->keyBy('id');
        $this->assertCount(2, $records);
        $this->assertSame(route('trainings.index', ['user_id' => $user->id, 'edit_training' => $one->id]), $records[$one->id]['edit_url']);
        $this->assertNull($records[$two->id]['edit_url']);
    }

    public function test_matrix_names_open_the_calendar_for_their_own_employee(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $category = \App\Models\TrainingCategory::create(['name' => 'Calendar QA', 'sort_order' => 1, 'is_sca' => false]);
        \App\Models\TrainingMatrixRow::create(['training_category_id' => $category->id,
            'description' => 'Calendar row', 'part_number' => 'CALENDAR-QA', 'sort_order' => 1, 'is_active' => true]);
        $technician = $this->createUserWithRole('Technician', [
            'is_admin' => false, 'stamp' => 'T-WEEK', 'show_in_training_matrix' => true, 'can_sign_certificates' => false,
        ]);
        $response = $this->actingAs($admin)->get(route('trainings.showAll'))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//thead//button[contains(@class,"training-week-calendar-btn") and @data-user-id="'.$technician->id.'"]')->length);
        $this->getJson(route('trainings.weekCalendar', ['user_id' => $technician->id, 'month' => '2026-01']))
            ->assertOk()->assertJsonPath('employee', $technician->selection_name)->assertJsonCount(54, 'weeks');
    }

    public function test_calendar_shows_all_training_types_and_distinguishes_free_and_future_weeks(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2025-04-18 12:00:00'));
        $user = $this->employee();
        $manual = $this->createManual(['unit_name_training' => 'CALENDAR-PN']);
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '112', 'date_training' => '2025-04-02']);
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '132', 'date_training' => '2025-04-18']);
        $course = \App\Models\TrainingMatrixRow::whereHas('category', fn ($q) => $q->where('is_sca', true))->firstOrFail();
        Training::create(['user_id' => $user->id, 'matrix_row_id' => $course->id, 'date_training' => '2025-04-11']);
        $response = $this->getJson(route('trainings.weekCalendar', ['month' => '2025-04']))->assertOk();
        $weeks = collect($response->json('weeks'))->keyBy('friday');
        $this->assertCount(54, $weeks);
        $fridays = $weeks->keys()->all();
        for ($i = 1; $i < count($fridays); $i++) {
            $this->assertSame(7, (int) \Carbon\Carbon::parse($fridays[$i - 1])->diffInDays($fridays[$i]));
        }
        $this->assertSame('occupied', $weeks['2025-04-04']['status']);
        $this->assertSame('CALENDAR-PN', $weeks['2025-04-04']['trainings'][0]['name']);
        $this->assertSame('occupied', $weeks['2025-04-11']['status']);
        $this->assertSame('free', $weeks['2025-04-18']['status']);
        $this->assertSame('future', $weeks['2025-04-25']['status']);
        $this->assertSame(7, count($weeks['2025-04-04']['days']));
        $this->assertSame('31/Mar/2025 – 04/Apr/2025', $weeks['2025-04-04']['label']);
    }

    public function test_calendar_cannot_disclose_another_employees_training_to_a_technician(): void
    {
        $this->employee();
        $other = $this->createUserWithRole('Technician');
        $this->getJson(route('trainings.weekCalendar', ['month' => '2025-04', 'user_id' => $other->id]))->assertForbidden();
        $this->getJson(route('trainings.weekCalendar', ['month' => '2025-99']))->assertUnprocessable();
    }

    public function test_calendar_button_is_next_to_sign_in_for_the_selected_employee(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $target = $this->createUserWithRole('Technician');
        Training::create(['user_id' => $target->id, 'manuals_id' => $this->createManual()->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $response = $this->actingAs($admin)->get(route('trainings.index', ['user_id' => $target->id]))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//div[contains(@class,"modal-footer")]/div[.//input[contains(@class,"sign-in-toggle")]]/button[contains(@class,"training-week-calendar-btn") and @data-user-id="'.$target->id.'"]')->length);
    }

    private function employee()
    {
        $user = $this->createUserWithRole('Technician');
        $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ]);
        return $user;
    }

    public function test_conflicting_new_component_rolls_back_certificate_and_all_dates(): void
    {
        $user = $this->employee();
        $old = $this->createManual();
        $new = $this->createManual();
        Training::create(['user_id' => $user->id, 'manuals_id' => $old->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $this->postJson(route('trainings.store'), ['manuals_id' => $new->id,
            'date_training' => '04/Apr/2025', 'training_dates' => ['16/Apr/2025']])
            ->assertUnprocessable()->assertJsonValidationErrors('date_training');
        $this->assertDatabaseMissing('trainings', ['user_id' => $user->id, 'manuals_id' => $new->id]);
        $preview = $this->postJson(route('trainings.weekAvailability'), ['date_training' => '2025-04-18'])
            ->assertOk()->json();
        $this->postJson(route('trainings.store'), ['manuals_id' => $new->id,
            'date_training' => $preview['suggested_date']])->assertOk();
        $this->assertSame(2, Training::where('user_id', $user->id)->where('manuals_id', $new->id)->count());
    }

    public function test_preview_skips_occupied_and_unsaved_weeks_across_year_boundary(): void
    {
        $user = $this->employee();
        $manual = $this->createManual();
        foreach (['2025-01-03', '2024-12-27'] as $date) {
            Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
                'form_type' => '112', 'date_training' => $date]);
        }
        $this->postJson(route('trainings.weekAvailability'), ['date_training' => '2024-12-30',
            'reserved_dates' => ['2024-12-20']])->assertOk()->assertJsonPath('available', false)
            ->assertJsonPath('suggested_date', '2024-12-13');
    }

    public function test_additional_dates_batch_and_edit_cannot_use_another_training_week(): void
    {
        $user = $this->employee();
        $one = $this->createManual();
        $two = $this->createManual();
        $record = Training::create(['user_id' => $user->id, 'manuals_id' => $one->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $edit = Training::create(['user_id' => $user->id, 'manuals_id' => $two->id,
            'form_type' => '112', 'date_training' => '2025-03-07']);
        $this->postJson(route('trainings.createTraining'), ['manuals_id' => [$two->id, $two->id],
            'date_training' => ['2025-04-04', '2025-04-18'], 'form_type' => ['112', '112']])
            ->assertUnprocessable();
        $this->assertDatabaseMissing('trainings', ['user_id' => $user->id, 'manuals_id' => $two->id, 'date_training' => '2025-04-04']);
        $this->patchJson(route('trainings.update', $edit), ['date_training' => '2025-04-16'])->assertUnprocessable();
        $this->assertSame('2025-03-07', $edit->fresh()->date_training);
        $this->postJson(route('trainings.weekAvailability'), ['date_training' => '2025-04-18', 'training_id' => $record->id])
            ->assertOk()->assertJsonPath('available', true);
    }

    public function test_certificate_legacy_and_other_employee_do_not_occupy_week(): void
    {
        $user = $this->employee();
        $manual = $this->createManual();
        $other = $this->createUserWithRole('Technician');
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '132', 'date_training' => '2025-04-18']);
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'is_legacy' => true, 'date_training' => null]);
        Training::create(['user_id' => $other->id, 'manuals_id' => $manual->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $this->postJson(route('trainings.store'), ['manuals_id' => $manual->id, 'date_training' => '2025-04-16'])
            ->assertOk();
        $this->assertSame(1, Training::where('user_id', $user->id)->where('form_type', '112')->count());
        $this->postJson(route('trainings.weekAvailability'), ['user_id' => $other->id, 'date_training' => '2025-04-18'])
            ->assertForbidden();
    }

    public function test_sca_course_and_component_share_the_same_week_limit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $user = $this->createUserWithRole('Manager', ['can_sign_certificates' => true]);
        $manual = $this->createManual();
        $course = \App\Models\TrainingMatrixRow::whereHas('category', fn ($q) => $q->where('is_sca', true))->firstOrFail();
        Training::create(['user_id' => $user->id, 'manuals_id' => $manual->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $this->actingAs($admin)->postJson(route('trainings.matrixCourseDate.store'), [
            'user_id' => $user->id, 'matrix_row_id' => $course->id, 'date_training' => '2025-04-16',
        ])->assertUnprocessable();
        Training::create(['user_id' => $user->id, 'matrix_row_id' => $course->id,
            'date_training' => '2025-04-11']);
        $this->postJson(route('trainings.createTraining'), ['user_id' => $user->id,
            'manuals_id' => [$manual->id], 'date_training' => ['2025-04-11'], 'form_type' => ['112'],
        ])->assertUnprocessable();
    }

    public function test_update_to_today_endpoint_also_rejects_a_conflicting_component(): void
    {
        $user = $this->employee();
        $one = $this->createManual();
        $two = $this->createManual();
        Training::create(['user_id' => $user->id, 'manuals_id' => $one->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $this->postJson(route('trainings.updateToToday'), [
            'manuals_id' => [$two->id], 'date_training' => ['2025-04-16'], 'form_type' => ['112'],
        ])->assertUnprocessable();
    }

    public function test_manager_keeps_existing_date_edit_permission_without_gaining_add_permission(): void
    {
        $manager = $this->createUserWithRole('Manager', ['is_admin' => false, 'can_sign_certificates' => false]);
        $target = $this->createUserWithRole('Technician');
        $record = Training::create(['user_id' => $target->id, 'manuals_id' => $this->createManual()->id,
            'form_type' => '112', 'date_training' => '2025-04-18']);
        $this->actingAs($manager)->postJson(route('trainings.weekAvailability'), [
            'user_id' => $target->id, 'date_training' => '2025-04-11', 'training_id' => $record->id,
        ])->assertOk()->assertJsonPath('available', true);
        $this->postJson(route('trainings.weekAvailability'), [
            'user_id' => $target->id, 'date_training' => '2025-04-11',
        ])->assertForbidden();
    }
}
