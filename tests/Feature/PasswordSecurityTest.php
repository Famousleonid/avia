<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAuthSessionVersion;
use App\Models\MobileApiToken;
use App\Models\UserUiSetting;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_password_policy_requires_eight_characters_and_at_symbol(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)->post(route('profile.password'), [
            'old_pass' => 'old-password',
            'password' => 'Short@',
            'password_confirmation' => 'Short@',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->post(route('profile.password'), [
            'old_pass' => 'old-password',
            'password' => 'LongEnough',
            'password_confirmation' => 'LongEnough',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_existing_user_is_forced_to_change_password_before_app_access(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('cabinet.index'))
            ->assertRedirect(route('password.required'));
    }

    public function test_login_redirects_a_flagged_user_directly_to_required_change(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('123'),
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => '123',
        ])->assertRedirect(route('password.required'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_temporary_password_cannot_be_used(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('123'),
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => '123',
        ])->assertRedirect(route('password.required'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_temporary_password_allows_access_and_warns_once_per_day(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('Temporary@2026'),
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Temporary@2026',
        ])->assertRedirect('/cabinet');
        $this->assertAuthenticatedAs($user);

        $first = $this->get(route('profile.edit'));
        $first->assertOk()
            ->assertSee('id="temporary-password-reminder"', false)
            ->assertSee(route('profile.edit'), false);

        $setting = UserUiSetting::query()
            ->where('user_id', $user->id)
            ->where('scope', UserUiSetting::PASSWORD_SECURITY_SCOPE)
            ->where('key', UserUiSetting::TEMPORARY_PASSWORD_REMINDER_KEY)
            ->firstOrFail();
        $this->assertSame(today()->toDateString(), $setting->value);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('id="temporary-password-reminder"', false);
    }

    public function test_required_password_change_revokes_tokens_and_signs_user_out(): void
    {
        Notification::fake();

        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('oldpass'),
            'must_change_password' => true,
            'temporary_password_expires_at' => null,
            'auth_version' => 1,
        ]);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Stale device',
            'token_hash' => hash('sha256', 'stale-token'),
        ]);

        $this->actingAs($user)
            ->post(route('password.required.update'), [
                'old_pass' => 'oldpass',
                'password' => 'Secure8@',
                'password_confirmation' => 'Secure8@',
            ])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->temporary_password_expires_at);
        $this->assertSame(2, $user->auth_version);
        $this->assertTrue(Hash::check('Secure8@', $user->password));
        $this->assertSame('argon2id', Hash::info($user->password)['algoName']);
        $this->assertDatabaseMissing('mobile_api_tokens', ['user_id' => $user->id]);
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_old_password_is_not_flashed_after_validation_error(): void
    {
        $user = $this->createUserWithRole('Technician');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.password'), [
                'old_pass' => 'sensitive-current-password',
                'password' => 'short@',
                'password_confirmation' => 'short@',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('password');

        $this->assertNull(session()->getOldInput('old_pass'));
    }

    public function test_auth_version_invalidates_an_older_web_session(): void
    {
        $user = $this->createUserWithRole('Technician', ['auth_version' => 2]);

        $this->withSession([EnsureAuthSessionVersion::SESSION_KEY => 1])
            ->actingAs($user)
            ->get(route('cabinet.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_forgot_password_response_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $user = $this->createUserWithRole('Technician');

        $known = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);
        $knownMessage = $known->getSession()->get('status');

        $unknown = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'unknown-' . uniqid() . '@example.test',
        ]);

        $known->assertRedirect(route('password.request'));
        $unknown->assertRedirect(route('password.request'));
        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($knownMessage, $unknown->getSession()->get('status'));
    }

    public function test_mobile_login_is_limited_per_account_across_different_ips(): void
    {
        $user = $this->createUserWithRole('Technician');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.' . $attempt])
                ->postJson(route('api.mobile.auth.login'), [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.25'])
            ->postJson(route('api.mobile.auth.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);
    }

    public function test_password_reset_revokes_access_and_requires_a_normal_login(): void
    {
        Notification::fake();
        $user = $this->createUserWithRole('Technician', [
            'password' => Hash::make('old-password'),
            'auth_version' => 3,
        ]);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Reset device',
            'token_hash' => hash('sha256', 'reset-device-token'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Resetpass@',
            'password_confirmation' => 'Resetpass@',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $user->refresh();
        $this->assertTrue(Hash::check('Resetpass@', $user->password));
        $this->assertSame(4, $user->auth_version);
        $this->assertDatabaseMissing('mobile_api_tokens', ['user_id' => $user->id]);
    }
}
