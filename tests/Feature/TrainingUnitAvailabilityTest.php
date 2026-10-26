<?php
namespace Tests\Feature;
use App\Models\Training;
use App\Services\TrainingUnitAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;
class TrainingUnitAvailabilityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;
    public function test_arrival_window_ignores_drafts_deleted_future_and_undated_workorders(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay());
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $service = app(TrainingUnitAvailability::class);
        $this->assertFalse($service->forManuals([$manual->id])[$manual->id]['available']);
        $old = $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => '2025-10-18']);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => now(), 'is_draft' => true]);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => now()->addDay()]);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => null]);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => now()])->delete();
        $state = $service->forManuals([$manual->id])[$manual->id];
        $this->assertFalse($state['available']);
        $this->assertStringContainsString('18/Oct/2025', $state['message']);
        $old->update(['open_at' => '2025-10-19']);
        $this->assertTrue($service->forManuals([$manual->id])[$manual->id]['available']);
    }
    public function test_idle_unit_keeps_dates_grey_but_allows_history_and_matrix_clicks(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay());
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual(['unit_name_training' => 'IDLE-TEST']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => '2024-09-04']);
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $html = $this->get(route('trainings.create', ['manual_id' => $manual->id]))->assertOk()->getContent();
        $dom = new \DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xp = new \DOMXPath($dom);
        $this->assertSame(0, $xp->query('//select[@id="manuals_id"]/option[@value="'.$manual->id.'"][@disabled]')->length);
        $this->assertSame(1, $xp->query('//select[@id="manuals_id"]/option[@value="'.$manual->id.'"][@selected]')->length);
        $this->postJson(route('trainings.store'), ['manuals_id' => $manual->id, 'date_training' => '2025-04-18'])->assertOk();
        $this->assertDatabaseHas('trainings', ['user_id' => $user->id, 'manuals_id' => $manual->id, 'date_training' => '2025-04-18', 'form_type' => '112']);
        $html = $this->get(route('trainings.index'))->assertOk()->getContent();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xp = new \DOMXPath($dom);
        $this->assertSame(1, $xp->query('//span[@class="training-date-idle" and contains(@title,"04/Sep/2024")]')->length);
        $this->assertSame(0, $xp->query('//*[contains(@class,"add-training-date-input") or contains(@class,"add-training-in-edit-btn")][@disabled]')->length);
        $category = \App\Models\TrainingCategory::create(['name' => 'Idle QA', 'sort_order' => 1, 'is_sca' => false]);
        $matrixRow = \App\Models\TrainingMatrixRow::create(['training_category_id' => $category->id,
            'manual_id' => $manual->id, 'description' => 'Idle unit', 'part_number' => 'IDLE-TEST', 'sort_order' => 1, 'is_active' => true]);
        $user->update(['stamp' => 'IDLE', 'show_in_training_matrix' => true, 'can_sign_certificates' => false]);
        $html = $this->get(route('trainings.showAll'))->assertOk()->getContent();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xp = new \DOMXPath($dom);
        $this->assertGreaterThan(0, $xp->query('//td[contains(@title,"04/Sep/2024")]/span[@class="training-date-idle"]')->length);
        $this->assertGreaterThan(0, $xp->query('//td[@data-train-manual="'.$manual->id.'"]')->length);
        $payload = ['manuals_id' => [$manual->id], 'date_training' => ['2025-05-16'], 'form_type' => ['112']];
        $this->postJson(route('trainings.createTraining'), $payload)->assertOk();
        $payload['date_training'] = ['2025-06-20'];
        $this->postJson(route('trainings.updateToToday'), $payload)->assertOk();
        foreach (['2025-05-16', '2025-06-20'] as $date) {
            $this->assertDatabaseHas('trainings', ['user_id' => $user->id, 'manuals_id' => $manual->id, 'date_training' => $date]);
        }
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => now()]);
        $html = $this->get(route('trainings.index'))->assertOk()->getContent();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xp = new \DOMXPath($dom);
        $this->assertSame(0, $xp->query('//span[@class="training-date-idle"]')->length);
        $payload['date_training'] = ['2026-10-16'];
        $this->postJson(route('trainings.createTraining'), $payload)->assertOk();
        $this->assertDatabaseHas('trainings', ['user_id' => $user->id, 'manuals_id' => $manual->id, 'date_training' => '2026-10-16']);
    }

    public function test_unit_without_any_workorders_can_be_selected_and_saved_from_modal(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual(['unit_name_training' => 'NO-WO-HISTORY']);
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $html = $this->get(route('trainings.create', ['modal' => 1, 'manual_id' => $manual->id]))->assertOk()->getContent();
        $dom = new \DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); $xp = new \DOMXPath($dom);
        $this->assertSame(1, $xp->query('//option[@value="'.$manual->id.'"][@selected][not(@disabled)]')->length);
        $this->postJson(route('trainings.store'), ['manuals_id' => $manual->id, 'date_training' => now()->addMonth()->format('Y-m-d')])
            ->assertUnprocessable()->assertJsonValidationErrors('date_training');
        $this->postJson(route('trainings.store'), ['manuals_id' => $manual->id, 'date_training' => '2023-04-14', 'training_dates' => ['2024-04-12']])->assertOk();
        $this->assertSame(3, Training::where('user_id', $user->id)->where('manuals_id', $manual->id)->count());
    }
}
