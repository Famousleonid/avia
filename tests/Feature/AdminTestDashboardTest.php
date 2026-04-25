<?php

namespace Tests\Feature;

use App\Services\TestSuiteRunnerService;
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
        $runner = \Mockery::mock(TestSuiteRunnerService::class);
        $runner->shouldReceive('allResults')->once()->andReturn([
            'smoke' => ['label' => 'Smoke Suite'],
            'feature' => ['label' => 'Feature Suite'],
        ]);
        $this->app->instance(TestSuiteRunnerService::class, $runner);

        $response = $this->actingAs($admin)->get(route('admin.tests.index'));

        $response->assertOk();
        $response->assertSee('QA Test Dashboard');
        $response->assertSee('Smoke Suite');
        $response->assertSee('Feature Suite');
    }

    public function test_admin_without_is_admin_cannot_open_test_dashboard(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);

        $response = $this->actingAs($roleOnlyAdmin)->get(route('admin.tests.index'));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_open_test_dashboard(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->get(route('admin.tests.index'));

        $response->assertForbidden();
    }
}
