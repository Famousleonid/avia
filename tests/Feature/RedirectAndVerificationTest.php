<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class RedirectAndVerificationTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_authenticated_desktop_guest_route_redirects_to_cabinet(): void
    {
        $user = $this->createUserWithRole('Technician');

        $response = $this
            ->withUnencryptedCookie('viewport_mobile', '0')
            ->actingAs($user)
            ->get(route('login'));

        $response->assertRedirect('/cabinet');
    }

    public function test_authenticated_mobile_guest_route_redirects_to_mobile(): void
    {
        $user = $this->createUserWithRole('Technician');

        $response = $this
            ->withUnencryptedCookie('viewport_mobile', '1')
            ->actingAs($user)
            ->get(route('login'));

        $response->assertRedirect('/mobile');
    }

    public function test_mobile_device_is_redirected_away_from_desktop_cabinet(): void
    {
        $user = $this->createUserWithRole('Technician');

        $response = $this
            ->withUnencryptedCookie('viewport_mobile', '1')
            ->actingAs($user)
            ->get(route('cabinet.index'));

        $response->assertRedirect('/mobile');
    }

    public function test_desktop_device_can_open_mobile_branch(): void
    {
        $user = $this->createUserWithRole('Admin');

        $response = $this
            ->withUnencryptedCookie('viewport_mobile', '0')
            ->actingAs($user)
            ->get(route('mobile.index'));

        $response->assertOk();
    }

    public function test_unverified_mobile_user_is_sent_to_verification_notice(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'email_verified_at' => null,
        ]);

        $response = $this
            ->withUnencryptedCookie('viewport_mobile', '1')
            ->actingAs($user)
            ->get(route('mobile.index'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verification_notice_page_renders(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertSee('Verify Your Email Address');
    }
}
