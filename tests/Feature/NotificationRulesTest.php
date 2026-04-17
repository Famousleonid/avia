<?php

namespace Tests\Feature;

use App\Models\NotificationEventRule;
use App\Services\WorkorderNotifyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class NotificationRulesTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_admin_can_create_notification_event_rule(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');

        $this->actingAs($admin)
            ->get(route('admin.notification-rules.index'))
            ->assertOk()
            ->assertSee('Notification Rules');

        $response = $this->actingAs($admin)->post(route('admin.notification-rules.store'), [
            'event_key' => 'tdr_process.overdue_start',
            'name' => 'Overdue managers',
            'enabled' => 1,
            'severity' => 'danger',
            'repeat_policy' => 'daily',
            'title_template' => 'Process overdue',
            'message_template' => 'WO {workorder_no}: {process_name}',
            'respect_user_preferences' => 1,
            'exclude_actor' => 1,
            'recipient_roles' => [$manager->role_id],
            'recipient_users' => [$manager->id],
            'recipient_dynamic' => ['process_notify_user'],
        ]);

        $response->assertRedirect(route('admin.notification-rules.index'));
        $response->assertSessionHasNoErrors();

        $rule = NotificationEventRule::query()->where('name', 'Overdue managers')->firstOrFail();

        $this->assertTrue($rule->enabled);
        $this->assertSame('tdr_process.overdue_start', $rule->event_key);
        $this->assertDatabaseHas('notification_event_rule_recipients', [
            'notification_event_rule_id' => $rule->id,
            'recipient_type' => 'dynamic',
            'recipient_value' => 'process_notify_user',
        ]);
    }

    public function test_draft_workorder_created_rule_notifies_admin_role_and_selected_user(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $selectedUser = $this->createUserWithRole('Manager');
        $shipping = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'number' => 2,
            'user_id' => $shipping->id,
            'is_draft' => true,
        ]);

        $rule = NotificationEventRule::query()->create([
            'event_key' => 'workorder.draft_created',
            'name' => 'Draft created recipients',
            'enabled' => true,
            'severity' => 'info',
            'title_template' => 'New draft',
            'message_template' => 'Draft WO {workorder_no} created by {actor_name}. Unit: {part_number}.',
            'repeat_policy' => 'event_default',
            'repeat_minutes' => null,
            'respect_user_preferences' => true,
            'exclude_actor' => true,
        ]);
        $rule->recipients()->create([
            'recipient_type' => 'role',
            'recipient_value' => (string) $admin->role_id,
        ]);
        $rule->recipients()->create([
            'recipient_type' => 'user',
            'recipient_value' => (string) $selectedUser->id,
        ]);

        app(WorkorderNotifyService::class)->draftCreated($workorder, $shipping->id, $shipping->name);

        $adminNotification = $admin->notifications()->first();
        $selectedNotification = $selectedUser->notifications()->first();

        $this->assertNotNull($adminNotification);
        $this->assertNotNull($selectedNotification);
        $this->assertSame('draft_created', $adminNotification->data['event']);
        $this->assertSame('workorder', $adminNotification->data['type']);
        $this->assertSame('New draft', $adminNotification->data['title']);
        $this->assertStringContainsString('Draft WO 2', $adminNotification->data['text']);
        $this->assertStringContainsString($shipping->name, $selectedNotification->data['text']);
    }
}
