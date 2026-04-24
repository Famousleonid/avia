<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class DatabaseBackupAccessTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_system_admin_can_create_database_backup(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $service = \Mockery::mock(DatabaseBackupService::class);
        $service->shouldReceive('createBackup')->once()->andReturn(storage_path('app/backups/db_test.sql.gz'));
        $this->app->instance(DatabaseBackupService::class, $service);

        $response = $this->actingAs($admin)->post(route('admin.database.backup'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_without_is_admin_cannot_create_database_backup(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);

        $response = $this->actingAs($roleOnlyAdmin)->post(route('admin.database.backup'));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_create_database_backup(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->post(route('admin.database.backup'));

        $response->assertForbidden();
    }
}
