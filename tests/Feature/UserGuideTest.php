<?php

namespace Tests\Feature;

use Tests\BuildsDomainData;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    use BuildsDomainData;

    public function test_admin_can_open_the_user_guide(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.user-guide'));

        $response->assertOk();
        $response->assertSee('data-language="en"', false);
        $response->assertSee('data-language="ru"', false);
        $response->assertSee('data-language="uk"', false);
        $response->assertSee('data-language="he"', false);
        $response->assertSee("window.UserUiSettings.get(scope, 'language', 'en')", false);
        $response->assertSee('Move photos between groups');
        $response->assertSee('img/user-guide/mobile-workorder-add-photo.png', false);
        $response->assertSee('img/user-guide/mobile-workorder-with-photos.png', false);
        $response->assertSee('img/user-guide/desktop-main-menu.png', false);
        $response->assertSee('class="guide-toc-accordion"', false);
        $response->assertSee("document.querySelectorAll('.guide-toc details')", false);
    }

    public function test_non_admin_cannot_open_the_user_guide(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->get(route('admin.user-guide'))
            ->assertForbidden();
    }
}
