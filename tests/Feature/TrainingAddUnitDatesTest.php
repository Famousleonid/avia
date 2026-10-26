<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsActiveTrainingData;
use Tests\TestCase;

class TrainingAddUnitDatesTest extends TestCase
{
    use BuildsActiveTrainingData, DatabaseTransactions;

    public function test_modal_returns_form_without_page_layout_and_saves_with_json(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->get(route('trainings.create', ['modal' => 1]))->assertOk()
            ->assertSee('training_create_form', false)->assertSee('addUnitPaper132', false)
            ->assertSee('aria-disabled="true"', false)->assertDontSee('<html', false);
        $this->postJson(route('trainings.store'), ['manuals_id' => $manual->id,
            'date_training' => '17/Oct/2023', 'training_dates' => ['18/Oct/2024']])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(3, Training::where('user_id', $user->id)->where('manuals_id', $manual->id)->count());
        $manual->update(['unit_name_training' => 'EXISTING-COMPONENT']);
        $html = $this->get(route('trainings.create', ['modal' => 1]))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath = new \DOMXPath($dom);
        $option = '//select[@id="manuals_id"]/option[@value="'.$manual->id.'" and @data-already-added="1"]';
        $this->assertSame(1, $xpath->query($option)->length);
        $this->assertSame(0, $xpath->query($option.'[@disabled]')->length);
    }

    public function test_old_first_date_only_creates_initial_forms_and_explicit_dates(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->post(route('trainings.store'), [
            'manuals_id' => $manual->id,
            'date_training' => '17/Oct/2023',
            'training_dates' => ['18/Oct/2024'],
            // Obsolete field from an already-open old page must not add a current training.
            'additional_training_date' => '25/Sep/2026',
        ])->assertRedirect();
        $rows = Training::where('user_id', $user->id)->where('manuals_id', $manual->id)->get();
        $this->assertCount(3, $rows);
        $this->assertCount(1, $rows->where('form_type', '132'));
        $this->assertEquals(['2023-10-20', '2024-10-18'], $rows->where('form_type', '112')->pluck('date_training')->map(fn ($d) => substr((string) $d, 0, 10))->sort()->values()->all());
    }

    public function test_add_unit_has_no_additional_training_prompt(): void
    {
        $user = $this->createUserWithRole('Technician');
        $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ])->get(route('trainings.create'))->assertOk()
            ->assertDontSee('additionalTrainingModal', false)
            ->assertDontSee('name="additional_training_date"', false)
            ->assertSee('add_training_date_btn', false);
    }
}
