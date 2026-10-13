<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualPartsScrollTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_parts_pages_search_and_sort_cover_the_whole_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $other = $this->createManual();
        foreach (range(1, 125) as $number) {
            Component::create(['manual_id' => $manual->id, 'ipl_num' => '1-'.$number,
                'part_number' => 'PAGE-'.$number, 'name' => 'Part '.$number]);
        }
        Component::create(['manual_id' => $other->id, 'ipl_num' => '1-1', 'part_number' => 'FOREIGN', 'name' => 'Foreign']);
        $url = route('manuals.parts-rows', $manual);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);
        $first = $this->getJson($url)->assertOk()->assertJsonPath('next', 60)->assertJsonPath('total', 125);
        $this->assertSame(60, preg_match_all('/id="manual-part-row-\d+"/', $first->json('html')));
        $this->assertStringNotContainsString('FOREIGN', $first->json('html'));
        $last = $this->getJson($url.'?offset=120')->assertOk()->assertJsonPath('has_more', false)->assertJsonPath('next', 125);
        $this->assertSame(5, preg_match_all('/id="manual-part-row-\d+"/', $last->json('html')));
        $this->getJson($url.'?q=PAGE-125')->assertOk()->assertJsonPath('matched', 1);
        $descending = $this->getJson($url.'?direction=desc')->assertOk()->json('html');
        $this->assertLessThan(strpos($descending, 'PAGE-124'), strpos($descending, 'PAGE-125'));
        $this->getJson($url.'?q=missing')->assertOk()->assertJsonPath('matched', 0)->assertJsonPath('has_more', false);
        $this->getJson($url.'?sort=invalid')->assertUnprocessable();
        $html = $this->get(route('manuals.show', $manual))->assertOk()->getContent();
        $this->assertSame(60, preg_match_all('/id="manual-part-row-\d+"/', $html));
    }

    public function test_parts_endpoint_enforces_manual_access(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $this->actingAs($user)->getJson(route('manuals.parts-rows', $manual))->assertForbidden();
    }

    public function test_each_main_tab_is_restored_from_user_settings(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $this->actingAs($admin);
        foreach (['components', 'parts', 'processes', 'traveler', 'std', 'sb', 'revision', 'dimensions', 'fc'] as $tab) {
            $this->postJson(route('user-ui-settings.store'), ['scope' => 'manuals.show',
                'key' => 'activeTab:'.$manual->id, 'value' => $tab])->assertOk();
            $this->get(route('manuals.show', $manual))->assertOk()->assertViewHas('manualShowTab', $tab);
        }
    }
}
