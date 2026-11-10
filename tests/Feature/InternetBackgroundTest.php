<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserUiSetting;
use App\Services\InternetProjectBackground;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\BuildsDomainData;
use Tests\TestCase;

class InternetBackgroundTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-07 12:00:00', 'America/Toronto'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enable(User $user): void
    {
        UserUiSetting::updateOrCreate(['user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => InternetProjectBackground::KEY], ['value' => ['mode' => 'internet', 'topic' => 'Красноярск']]);
    }

    private function page(int $id, array $overrides = []): array
    {
        return ['pageid' => $id, 'title' => 'File:City '.$id.'.jpg', 'imageinfo' => [array_replace([
            'width' => 4000, 'height' => 2500, 'mime' => 'image/jpeg',
            'thumburl' => 'https://thumb.wikimedia.org/wikipedia/commons/'.$id.'.jpg',
            'descriptionurl' => 'https://commons.wikimedia.org/wiki/File:City'.$id.'.jpg',
            'extmetadata' => ['Artist' => ['value' => '<a href="x">Photographer</a>'],
                'LicenseShortName' => ['value' => 'CC BY 4.0'],
                'LicenseUrl' => ['value' => 'https://creativecommons.org/licenses/by/4.0/']],
        ], $overrides)]];
    }

    private function signIn(User $user): self
    {
        return $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword()]);
    }

    public function test_photo_changes_only_on_request_and_survives_cache_clear(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1), $this->page(2), $this->page(3)]]])]);
        $service = app(InternetProjectBackground::class); $seen = [];
        $photo = $service->current($user);
        $seen[] = $photo['id'];
        for ($day = 8; $day <= 9; $day++) {
            Carbon::setTestNow(Carbon::parse('2026-11-'.$day.' 12:00', 'America/Toronto'));
            $this->assertEquals($photo, $service->current($user));
            $photo = $service->current($user, true);
            $this->assertNotContains($photo['id'], $seen);
            $seen[] = $photo['id'];
            $this->assertEquals($photo, $service->current($user));
            $this->assertSame('Photographer', $photo['author']);
        }
        Cache::flush();
        $this->assertEquals($photo, $service->current($user));
        Http::assertSentCount(1);
    }

    public function test_owner_can_change_photo_with_post_and_get_preserves_selection(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1), $this->page(2)]]])]);
        $url = route('admin.project-settings.internet-background.show', $user);
        $first = $this->signIn($user)->getJson($url)->assertOk()->json('photo.id');
        $next = $this->postJson($url)->assertOk()->json('photo.id');
        $this->assertNotSame($first, $next);
        $this->getJson($url)->assertOk()->assertJsonPath('photo.id', $next);
    }

    public function test_network_failure_keeps_previous_photo_and_throttles_retries(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1)]]])]);
        $service = app(InternetProjectBackground::class); $photo = $service->current($user);
        Carbon::setTestNow(Carbon::parse('2026-11-16 12:00', 'America/Toronto'));
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertEquals($photo, $service->current($user, true));
        $this->assertEquals($photo, $service->current($user));
        Http::assertSentCount(1);
    }

    public function test_new_photo_retries_immediately_after_initial_failure(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::sequence()->push([], 503)
            ->push(['query' => ['pages' => [$this->page(1)]]])]);
        $url = route('admin.project-settings.internet-background.show', $user);
        $this->signIn($user)->getJson($url)->assertOk()->assertJsonPath('photo', null)
            ->assertJsonPath('issue.code', 'search_unavailable');
        $this->getJson($url)->assertOk()->assertJsonPath('issue.code', 'retry_later');
        Http::assertSentCount(1);
        $this->postJson($url)->assertOk()->assertJsonPath('photo.id', 1)->assertJsonPath('issue', null);
        $this->getJson($url)->assertOk()->assertJsonPath('photo.id', 1)->assertJsonPath('issue', null);
        Http::assertSentCount(2);
        $key = 'internet-background:v1:'.$user->id.':'.hash('sha256', 'Красноярск').':retry';
        $this->assertFalse(Cache::has($key));
    }

    public function test_empty_search_is_distinguished_from_network_failure(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::response(['query' => ['pages' => []]])]);
        $this->signIn($user)->getJson(route('admin.project-settings.internet-background.show', $user))
            ->assertOk()->assertJsonPath('photo', null)->assertJsonPath('issue.code', 'no_photos');
    }

    public function test_unsuitable_images_and_untrusted_hosts_are_rejected(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        Http::fake(['*' => Http::response(['query' => ['pages' => [
            $this->page(1, ['thumburl' => 'https://example.org/tracker.jpg']),
            $this->page(2, ['width' => 100]), $this->page(3, ['height' => 5000]),
            $this->page(4, ['extmetadata' => ['LicenseShortName' => ['value' => 'CC BY-NC 4.0']]]),
            $this->page(5),
        ]]])]);
        $this->assertSame(5, app(InternetProjectBackground::class)->current($user)['id']);
    }

    public function test_settings_are_admin_only_and_photo_access_is_owner_or_admin(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $owner = $this->createUserWithRole('Technician'); $other = $this->createUserWithRole('Technician');
        $url = route('admin.project-settings.internet-background.store');
        $this->signIn($owner)->post($url, ['user_id' => $owner->id, 'mode' => 'internet', 'topic' => 'City'])->assertForbidden();
        $this->signIn($admin)->post($url, ['user_id' => $owner->id, 'mode' => 'internet', 'topic' => 'Красноярск'])->assertRedirect();
        $this->assertSame('Красноярск', app(InternetProjectBackground::class)->settings($owner)['topic']);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1)]]])]);
        $photoUrl = route('admin.project-settings.internet-background.show', $owner);
        $this->signIn($other)->getJson($photoUrl)->assertForbidden();
        $this->postJson($photoUrl)->assertForbidden();
        $this->signIn($owner)->getJson($photoUrl)->assertOk()->assertJsonPath('photo.id', 1);
        $this->signIn($admin)->getJson($photoUrl)->assertOk()->assertJsonPath('photo.id', 1);
        $this->get(route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $owner->id]))
            ->assertOk()->assertSee('Internet photos')->assertSee('Красноярск');
    }

    public function test_manual_mode_does_not_request_external_images(): void
    {
        $user = $this->createUserWithRole('Technician'); Http::fake();
        $this->assertNull(app(InternetProjectBackground::class)->current($user));
        Http::assertNothingSent();
    }

    public function test_off_preserves_photos_and_owner_can_restore_without_new_search(): void
    {
        $user = $this->createUserWithRole('Admin', ['is_admin' => true]); $this->enable($user);
        Storage::fake('public');
        $path = 'user-backgrounds/'.$user->id.'/saved.jpg';
        Storage::disk('public')->put($path, 'existing photo');
        UserUiSetting::create(['user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => UserUiSetting::PROJECT_BACKGROUND_KEY, 'value' => ['path' => $path]]);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1)]]])]);
        $url = route('admin.project-settings.internet-background.show', $user);
        $this->signIn($user)->getJson($url)->assertOk()->assertJsonPath('photo.id', 1);
        $photo = UserUiSetting::where('user_id', $user->id)->where('key', InternetProjectBackground::PHOTO_KEY)->first()->value;
        $this->patchJson($url, ['mode' => 'off', 'cadence' => 'button'])->assertOk()->assertJsonPath('photo', null)
            ->assertJsonPath('settings.mode', 'off')->assertJsonPath('settings.topic', 'Красноярск');
        $this->postJson($url)->assertOk()->assertJsonPath('photo', null);
        $this->get(route('cabinet.index'))->assertOk()->assertDontSee('<body class="p-0 m-0 g-0 has-user-project-background">', false)
            ->assertDontSee('id="internetBackgroundCredit"', false);
        Storage::disk('public')->assertExists($path);
        $this->assertEquals($photo, UserUiSetting::where('user_id', $user->id)->where('key', InternetProjectBackground::PHOTO_KEY)->first()->value);
        $this->patchJson($url, ['mode' => 'internet', 'topic' => 'Красноярск', 'cadence' => 'button'])
            ->assertOk()->assertJsonPath('photo.id', 1);
        Http::assertSentCount(1);
    }

    public function test_admin_can_disable_with_empty_topic_but_internet_requires_readable_validation(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $user = $this->createUserWithRole('Technician');
        $url = route('admin.project-settings.internet-background.store');
        foreach (['off', 'manual'] as $mode) {
            $this->signIn($admin)->post($url, ['user_id' => $user->id, 'mode' => $mode, 'topic' => ''])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($mode, app(InternetProjectBackground::class)->settings($user)['mode']);
        }
        $this->postJson($url, ['user_id' => $user->id, 'mode' => 'internet', 'topic' => ''])
            ->assertUnprocessable()->assertJsonPath('errors.topic.0', 'Enter a photo topic for Internet photos.');
    }

    public function test_owner_controls_topic_and_daily_rotation_without_changing_other_users(): void
    {
        $user = $this->createUserWithRole('Technician'); $this->enable($user);
        $other = $this->createUserWithRole('Technician'); $this->enable($other);
        Http::fake(['*' => Http::response(['query' => ['pages' => [$this->page(1), $this->page(2), $this->page(3)]]])]);
        $url = route('admin.project-settings.internet-background.show', $user);
        $this->signIn($other)->patchJson($url, ['topic' => 'Forest', 'cadence' => 'daily'])->assertForbidden();
        $this->signIn($user)->patchJson($url, ['topic' => '', 'cadence' => 'hourly'])->assertUnprocessable();
        $first = $this->patchJson($url, ['topic' => 'Forest', 'cadence' => 'daily'])->assertOk()
            ->assertJsonPath('settings.topic', 'Forest')->assertJsonPath('settings.cadence', 'daily')->json('photo.id');
        $this->getJson($url)->assertOk()->assertJsonPath('photo.id', $first);
        Carbon::setTestNow(Carbon::parse('2026-11-08 12:00', 'America/Toronto'));
        $second = $this->getJson($url)->assertOk()->json('photo.id');
        $this->assertNotSame($first, $second);
        $this->getJson($url)->assertOk()->assertJsonPath('photo.id', $second);
        $this->patchJson($url, ['topic' => 'Forest', 'cadence' => 'button'])->assertOk();
        Carbon::setTestNow(Carbon::parse('2026-11-09 12:00', 'America/Toronto'));
        $this->getJson($url)->assertOk()->assertJsonPath('photo.id', $second);
        $this->assertSame('Красноярск', app(InternetProjectBackground::class)->settings($other)['topic']);
        Http::assertSent(fn ($request) => str_contains($request['gsrsearch'], 'Forest'));
    }
}
