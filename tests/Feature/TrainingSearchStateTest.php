<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\UserUiSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TrainingSearchStateTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_sort_is_saved_for_viewer_and_restored_when_viewing_another_employee(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $other = $this->createUserWithRole('Technician');
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword()]);
        foreach (['asc', 'desc'] as $direction) {
            $this->postJson(route('user-ui-settings.store'), ['scope' => 'trainings', 'key' => 'sort', 'value' => $direction])->assertOk();
            $this->get(route('trainings.index', ['user_id' => $other->id]))->assertOk()->assertViewHas('trainingSort', $direction);
        }
        $this->actingAs($other)->withSession(['auth.version' => (int) $other->auth_version, 'password_hash_web' => $other->getAuthPassword()]);
        $this->get(route('trainings.index'))->assertOk()->assertViewHas('trainingSort', null);
    }

    public function test_saved_search_is_rendered_before_javascript_and_is_user_scoped(): void
    {
        $user = $this->createUserWithRole('Technician');
        $other = $this->createUserWithRole('Technician');
        $manual = $this->createManual(['unit_name_training' => '190-70200-ser']);
        $second = $this->createManual(['unit_name_training' => 'OTHER-PN']);
        foreach ([$user, $other] as $owner) {
            foreach ([$manual, $second] as $item) {
                Training::create(['user_id' => $owner->id, 'manuals_id' => $item->id, 'date_training' => '2025-05-16', 'form_type' => '112']);
            }
        }
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $this->postJson(route('user-ui-settings.store'), ['scope' => 'trainings', 'key' => 'search', 'value' => ['text' => '190-70200', 'manual_id' => '']])->assertOk();
        $response = $this->get(route('trainings.index'))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame('190-70200', $xpath->query('//input[@id="trainingsSearch"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//table[@id="trainingsTable"]//tr[@data-manual-id="'.$manual->id.'"]')->item(0)->getAttribute('style'));
        $this->assertSame('display: none;', $xpath->query('//table[@id="trainingsTable"]//tr[@data-manual-id="'.$second->id.'"]')->item(0)->getAttribute('style'));
        $this->get(route('trainings.index', ['manual_id' => $second->id]))->assertOk()->assertViewHas('trainingSearch', 'OTHER-PN');
        $this->get(route('trainings.index', ['manual_id' => 999999]))->assertOk()->assertViewHas('trainingSearch', '');
        $this->postJson(route('user-ui-settings.store'), ['scope' => 'trainings', 'key' => 'search', 'value' => ['text' => '', 'manual_id' => '']])->assertOk();
        $this->get(route('trainings.index'))->assertOk()->assertViewHas('trainingSearch', '');
        UserUiSetting::where('user_id', $user->id)->where('scope', 'trainings')->update(['value' => json_encode(['text' => 'PRIVATE-SEARCH'])]);
        $this->actingAs($other)->withSession(['auth.version' => (int) $other->auth_version, 'password_hash_web' => $other->getAuthPassword()]);
        $this->get(route('trainings.index'))->assertOk()->assertViewHas('trainingSearch', '');
    }
}
