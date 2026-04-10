<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AdminTestDashboardTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_open_test_dashboard(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.tests.index'));

        $response->assertOk();
        $response->assertSee('QA Test Dashboard');
        $response->assertSee('Smoke Suite');
        $response->assertSee('Feature Suite');
    }

    public function test_non_admin_cannot_open_test_dashboard(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->get(route('admin.tests.index'));

        $response->assertForbidden();
    }
}
