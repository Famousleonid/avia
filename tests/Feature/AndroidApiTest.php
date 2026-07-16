<?php

namespace Tests\Feature;

use App\Models\MobileApiToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Android contour of the mobile API (/api/android/*).
 * AndroidApiController inherits the iOS controller; these tests pin down the
 * platform-specific overrides AND that the iOS contour stays untouched.
 */
class AndroidApiTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_public_app_config_reports_android_platform(): void
    {
        $this->getJson(route('api.android.public.app-config'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.app.platform', 'android')
            ->assertJsonPath('data.app.android.min_sdk', 26)
            // shared branding still comes from the parent payload
            ->assertJsonPath('data.launch.initial_route', 'login');
    }

    public function test_login_issues_platform_tagged_token_and_bootstrap_works(): void
    {
        $user = $this->createUserWithRole('Technician');

        $res = $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('ok', true);

        $token = $res->json('data.token');
        $this->assertNotEmpty($token);

        $row = MobileApiToken::where('token_hash', MobileApiToken::hashPlainTextToken($token))->first();
        $this->assertNotNull($row);
        $this->assertSame('android', $row->platform);
        $this->assertSame('Android device', $row->name);

        // the shared token middleware accepts the android token on the android contour
        $this->getJson(route('api.android.bootstrap'), ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure(['data' => ['navigation', 'screens', 'menu_mode']]);
    }

    public function test_android_login_rejects_bad_credentials(): void
    {
        $user = $this->createUserWithRole('Technician');

        $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_ios_contour_is_unchanged(): void
    {
        $user = $this->createUserWithRole('Technician');

        $res = $this->postJson(route('api.mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('ok', true);

        $row = MobileApiToken::where('token_hash', MobileApiToken::hashPlainTextToken($res->json('data.token')))->first();
        $this->assertSame('iOS device', $row->name);
        $this->assertNull($row->platform); // iOS login does not set platform

        // iOS app-config has no android block
        $config = $this->getJson(route('api.mobile.public.app-config'))->assertOk()->json('data.app');
        $this->assertArrayNotHasKey('platform', $config);
        $this->assertArrayNotHasKey('android', $config);
    }
}
