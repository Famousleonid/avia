<?php

namespace Tests\Feature;

use App\Models\UserUiSetting;
use App\Support\UiSettingsDevice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\{BuildsDomainData, TestCase};

class SharedAdminUiSettingsTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    private function sharedAdmin()
    {
        return $this->createUserWithRole('Admin', ['email' => 'admin@admin.ca']);
    }

    private function browser(string $id): self
    {
        return $this->withCredentials()->withCookie(UiSettingsDevice::COOKIE, str_repeat($id, 64));
    }

    private function savePreference(string $scope, string $key, $value)
    {
        return $this->postJson(route('user-ui-settings.store'), compact('scope', 'key', 'value'));
    }

    private function readPreference(string $scope)
    {
        return $this->getJson(route('user-ui-settings.index', ['scope' => $scope]));
    }

    public function test_two_browsers_keep_independent_values_and_deletes_without_changing_the_original(): void
    {
        $admin = $this->sharedAdmin();
        foreach (['manuals.show', 'browser-storage', 'browser-session-storage'] as $scope) {
            UserUiSetting::create(['user_id' => $admin->id, 'scope' => $scope, 'key' => 'state', 'value' => 'original']);
        }
        $this->actingAs($admin);
        foreach (['manuals.show', 'browser-storage', 'browser-session-storage'] as $scope) {
            $this->browser('a')->readPreference($scope)->assertOk()->assertJsonPath('settings.state', 'original');
            $this->savePreference($scope, 'state', 'browser A')->assertOk();
            $this->browser('b')->readPreference($scope)->assertOk()->assertJsonPath('settings.state', 'original');
            $this->savePreference($scope, 'state', 'browser B')->assertOk();
            $this->browser('a')->readPreference($scope)->assertOk()->assertJsonPath('settings.state', 'browser A');
            $this->savePreference($scope, 'state', null)->assertOk();
            $this->readPreference($scope)->assertJsonPath('settings', []); // Never re-seed a deleted key.
            $this->browser('b')->readPreference($scope)->assertJsonPath('settings.state', 'browser B');
            $this->assertSame('original', json_decode(DB::table('user_ui_settings')
                ->where(['user_id' => $admin->id, 'scope' => $scope, 'key' => 'state', 'device_key' => ''])
                ->value('value') ?? 'null', true));
        }
        $this->browser('c')->readPreference('manuals.show')->assertJsonPath('settings.state', 'original');
        $this->assertSame(3, DB::table('user_ui_setting_devices')->where('user_id', $admin->id)->count());
        $this->assertNull(UiSettingsDevice::context());
        $this->assertSame('original', UserUiSetting::where('user_id', $admin->id)->where('scope', 'manuals.show')->value('value'));
    }

    public function test_other_users_including_personal_admins_keep_account_wide_settings(): void
    {
        foreach (['Technician', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role, ['name' => 'Ad ministr']);
            session()->invalidate();
            $this->actingAs($user);
            $this->browser('a')->savePreference('browser-storage', 'theme', 'dark')->assertOk()
                ->assertCookieMissing(UiSettingsDevice::COOKIE);
            $this->browser('b')->readPreference('browser-storage')->assertJsonPath('settings.theme', 'dark');
            $this->savePreference('browser-storage', 'theme', 'light')->assertOk();
            $this->browser('a')->readPreference('browser-storage')->assertJsonPath('settings.theme', 'light');
            $this->assertDatabaseHas('user_ui_settings', ['user_id' => $user->id, 'device_key' => '', 'key' => 'theme']);
        }
        $this->assertSame(0, DB::table('user_ui_setting_devices')->count());
    }

    public function test_direct_controller_and_blade_reads_follow_the_same_browser_as_the_api(): void
    {
        $this->actingAs($this->sharedAdmin());
        foreach (['a', 'b'] as $device) {
            $this->browser($device)->savePreference('workorders.index', 'filters', ['q' => 'filter-'.$device])->assertOk();
            $this->savePreference('browser-storage', 'qa_marker', 'local-'.$device)->assertOk();
            $this->savePreference('browser-session-storage', 'qa_marker', 'session-'.$device)->assertOk();
        }
        foreach (['a', 'b'] as $device) {
            $other = $device === 'a' ? 'b' : 'a';
            $this->browser($device)->get(route('workorders.index'))->assertOk()
                ->assertSee('filter-'.$device)->assertDontSee('filter-'.$other)
                ->assertSee('local-'.$device)->assertDontSee('local-'.$other)
                ->assertSee('session-'.$device)->assertDontSee('session-'.$other);
        }
    }

    public function test_cookie_survives_a_new_session_and_is_encrypted_http_only(): void
    {
        $admin = $this->sharedAdmin();
        $this->actingAs($admin)->withCredentials();
        $response = $this->readPreference('manuals.show')->assertOk();
        $cookie = $response->getCookie(UiSettingsDevice::COOKIE);
        $token = $cookie->getValue();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertGreaterThan(time() + 300 * 86400, $cookie->getExpiresTime());
        $this->assertNotSame($token, $response->getCookie(UiSettingsDevice::COOKIE, false)->getValue());
        $this->withCookie(UiSettingsDevice::COOKIE, $token)->savePreference('manuals.show', 'tab', 'parts')->assertOk();
        session()->invalidate();
        $this->actingAs($admin)->readPreference('manuals.show')->assertJsonPath('settings.tab', 'parts');
        $this->assertSame(1, DB::table('user_ui_setting_devices')->count());
    }

    public function test_invalid_cookie_or_client_device_fields_cannot_select_another_profile(): void
    {
        $admin = $this->sharedAdmin();
        $this->actingAs($admin)->browser('a')->savePreference('browser-storage', 'state', 'private A')->assertOk();
        $this->browser('b')->postJson(route('user-ui-settings.store'), [
            'scope' => 'browser-storage', 'key' => 'state', 'value' => 'B',
            'device_key' => hash('sha256', str_repeat('a', 64)), 'user_id' => 999999,
        ])->assertOk();
        $this->browser('a')->readPreference('browser-storage')->assertJsonPath('settings.state', 'private A');
        session()->invalidate();
        $this->withCookie(UiSettingsDevice::COOKIE, 'invalid')->readPreference('browser-storage')->assertJsonPath('settings', []);
        $this->assertSame(3, DB::table('user_ui_setting_devices')->count());
        $this->assertDatabaseMissing('user_ui_settings', ['user_id' => 999999]);
    }

    public function test_background_preferences_are_independent_but_editing_another_user_stays_account_scoped(): void
    {
        $admin = $this->sharedAdmin();
        $other = $this->createUserWithRole('Technician');
        $this->actingAs($admin);
        foreach (['a' => 'off', 'b' => 'manual'] as $device => $mode) {
            $this->browser($device)->post(route('admin.project-settings.internet-background.store'), [
                'user_id' => $admin->id, 'mode' => $mode,
            ])->assertRedirect();
        }
        foreach (['a' => 'off', 'b' => 'manual'] as $device => $mode) {
            $this->browser($device)->readPreference(UserUiSetting::PROJECT_APPEARANCE_SCOPE)
                ->assertJsonPath('settings.background_source.mode', $mode);
        }
        $this->post(route('admin.project-settings.internet-background.store'), [
            'user_id' => $other->id, 'mode' => 'off',
        ])->assertRedirect();
        $this->assertDatabaseHas('user_ui_settings', ['user_id' => $other->id,
            'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE, 'key' => 'background_source', 'device_key' => '']);
        session()->invalidate();
        $this->actingAs($other)->browser('a')->readPreference(UserUiSetting::PROJECT_APPEARANCE_SCOPE)
            ->assertJsonPath('settings.background_source.mode', 'off');
    }

    public function test_password_reminder_state_stays_shared_and_other_accounts_remain_private(): void
    {
        $admin = $this->sharedAdmin();
        $other = $this->createUserWithRole('Technician');
        UserUiSetting::create(['user_id' => $other->id, 'scope' => 'browser-storage', 'key' => 'state', 'value' => 'other private']);
        $this->actingAs($admin)->browser('a')
            ->savePreference(UserUiSetting::PASSWORD_SECURITY_SCOPE, 'reminder', 'today')->assertOk();
        $this->browser('b')->readPreference(UserUiSetting::PASSWORD_SECURITY_SCOPE)->assertJsonPath('settings.reminder', 'today');
        $this->readPreference('browser-storage')->assertJsonPath('settings', []);
        $this->assertSame(1, DB::table('user_ui_settings')->where('user_id', $admin->id)->where('scope', UserUiSetting::PASSWORD_SECURITY_SCOPE)->count());
        $this->assertDatabaseHas('user_ui_settings', ['user_id' => $admin->id,
            'scope' => UserUiSetting::PASSWORD_SECURITY_SCOPE, 'device_key' => '']);
    }
}
