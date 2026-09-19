<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AccountSwitchLoginTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withMiddleware(VerifyCsrfToken::class);
        $this->app->bind(VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
    }

    public function test_switching_accounts_succeeds_on_first_login_after_logout(): void
    {
        $first = $this->createUserWithRole('Admin', ['must_change_password' => false]);
        $second = $this->createUserWithRole('Admin', ['must_change_password' => false]);
        $this->actingAs($first)->withSession([
            '_token' => 'logout-token',
            'auth.version' => (int) $first->auth_version,
            'password_hash_web' => $first->getAuthPassword(),
            'old_account_marker' => 'must disappear',
        ]);

        $this->post(route('logout'), ['_token' => 'logout-token'])
            ->assertRedirect(route('login'))
            ->assertSessionMissing('password_hash_web')
            ->assertSessionMissing('old_account_marker');
        $this->assertGuest();
        $newToken = $this->app['session.store']->token();
        $this->assertNotSame('logout-token', $newToken);

        $this->post(route('login'), [
            '_token' => $newToken,
            'email' => $second->email,
            'password' => 'password',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($second);
        $this->getJson(route('session.heartbeat'))->assertOk()->assertJsonPath('user_id', $second->id);
        $this->assertAuthenticatedAs($second);
    }

    public function test_logout_requires_valid_csrf_token(): void
    {
        $user = $this->createUserWithRole('Admin', ['must_change_password' => false]);
        $this->actingAs($user)->withSession(['_token' => 'valid-token']);
        $this->post(route('logout'), ['_token' => 'wrong-token'])->assertStatus(419);
        $this->assertAuthenticatedAs($user);
    }
}
