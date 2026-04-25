<?php

namespace Tests\Feature;

use App\Models\DateNotification;
use App\Services\DateNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessageNotification;
use Tests\BuildsDomainData;
use Tests\TestCase;

class DateNotificationsTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    public function test_admin_can_create_date_notification(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manager = $this->createUserWithRole('Manager');

        $response = $this->actingAs($admin)->post(route('admin.date-notifications.store'), [
            'name' => 'Christmas',
            'run_on' => '2026-12-25',
            'repeat_mode' => 'yearly',
            'enabled' => 1,
            'title' => 'Merry Christmas',
            'message' => 'Merry Christmas to everyone!',
            'respect_user_preferences' => 1,
            'recipient_roles' => [$manager->role_id],
            'recipient_dynamic' => ['system_admins'],
        ]);

        $response->assertRedirect(route('admin.date-notifications.index'));

        $notification = DateNotification::query()->where('name', 'Christmas')->firstOrFail();
        $this->assertSame(12, $notification->run_month);
        $this->assertSame(25, $notification->run_day);
        $this->assertTrue($notification->repeats_yearly);
        $this->assertNull($notification->run_year);
        $this->assertDatabaseHas('date_notification_recipients', [
            'date_notification_id' => $notification->id,
            'recipient_type' => 'role',
            'recipient_value' => (string) $manager->role_id,
        ]);
    }

    public function test_due_date_notification_sends_once_for_today(): void
    {
        Notification::fake();

        $systemAdmin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');

        $notification = DateNotification::query()->create([
            'name' => 'Today notice',
            'run_month' => (int) now()->month,
            'run_day' => (int) now()->day,
            'repeats_yearly' => true,
            'run_year' => null,
            'enabled' => true,
            'title' => 'Today notice',
            'message' => 'This is a date notification.',
            'respect_user_preferences' => true,
        ]);

        $notification->recipients()->create([
            'recipient_type' => 'role',
            'recipient_value' => (string) $manager->role_id,
        ]);
        $notification->recipients()->create([
            'recipient_type' => 'dynamic',
            'recipient_value' => 'system_admins',
        ]);

        $service = app(DateNotificationService::class);
        $service->sendDueForToday();
        $service->sendDueForToday();

        Notification::assertSentTo($manager, NewMessageNotification::class, 1);
        Notification::assertSentTo($systemAdmin, NewMessageNotification::class, 1);
        $this->assertDatabaseCount('date_notification_logs', 2);
    }

    public function test_one_time_date_notification_runs_only_in_matching_year(): void
    {
        Notification::fake();

        $manager = $this->createUserWithRole('Manager');

        DateNotification::query()->create([
            'name' => 'One time notice',
            'run_month' => (int) now()->month,
            'run_day' => (int) now()->day,
            'repeats_yearly' => false,
            'run_year' => (int) now()->year,
            'enabled' => true,
            'title' => 'One time notice',
            'message' => 'Only this year.',
            'respect_user_preferences' => true,
        ])->recipients()->create([
            'recipient_type' => 'user',
            'recipient_value' => (string) $manager->id,
        ]);

        DateNotification::query()->create([
            'name' => 'Wrong year notice',
            'run_month' => (int) now()->month,
            'run_day' => (int) now()->day,
            'repeats_yearly' => false,
            'run_year' => (int) now()->year + 1,
            'enabled' => true,
            'title' => 'Wrong year notice',
            'message' => 'Not this year.',
            'respect_user_preferences' => true,
        ])->recipients()->create([
            'recipient_type' => 'user',
            'recipient_value' => (string) $manager->id,
        ]);

        app(DateNotificationService::class)->sendDueForToday();

        Notification::assertSentTo($manager, NewMessageNotification::class, function (NewMessageNotification $notification) {
            return $notification->payload['date_notification_id'] !== null;
        });

        $this->assertDatabaseCount('date_notification_logs', 1);
    }
}
