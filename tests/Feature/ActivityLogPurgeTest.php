<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ActivityLogPurgeTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_system_admin_can_purge_old_activity_logs(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');

        Activity::query()->create([
            'log_name' => 'test_logs',
            'description' => 'old_log',
            'event' => 'created',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        Activity::query()->create([
            'log_name' => 'test_logs',
            'description' => 'fresh_log',
            'event' => 'created',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($systemAdmin)->post(route('admin.activity.purge'), [
            'days' => 30,
        ]);

        $response->assertRedirect(route('admin.activity.index'));
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'old_log',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'fresh_log',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'activity_log',
            'description' => 'activity_log_purged',
            'event' => 'purged',
            'causer_id' => $systemAdmin->id,
        ]);
    }

    public function test_admin_without_is_admin_cannot_open_or_purge_activity_logs(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);

        $this->actingAs($roleOnlyAdmin)
            ->get(route('admin.activity.index'))
            ->assertForbidden();

        $this->actingAs($roleOnlyAdmin)
            ->post(route('admin.activity.purge'), ['days' => 30])
            ->assertForbidden();
    }
}
