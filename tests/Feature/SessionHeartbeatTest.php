<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class SessionHeartbeatTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_guest_json_request_to_session_heartbeat_returns_unauthorized(): void
    {
        $response = $this->getJson(route('session.heartbeat'));

        $response->assertStatus(401);
    }

    public function test_guest_html_request_to_session_heartbeat_redirects_to_login(): void
    {
        $response = $this->get(route('session.heartbeat'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_ping_session_heartbeat(): void
    {
        $user = $this->createUserWithRole('Admin');

        $response = $this->actingAs($user)->getJson(route('session.heartbeat'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('user_id', $user->id);
        $response->assertJsonStructure(['server_time']);
    }
}
