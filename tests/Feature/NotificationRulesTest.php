<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\EventLog;
use App\Models\NotificationEventRule;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WorkorderStdProcess;
use App\Notifications\NewMessageNotification;
use App\Services\Events\BirthdayInTwoDaysEvent;
use App\Services\Events\BirthdayTodayEvent;
use App\Services\Events\EventRunner;
use App\Services\Events\ProcessReadyForNextReminderEvent;
use App\Services\Events\TdrProcessOverdueStartEvent;
use App\Services\WorkorderNotifyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
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
            ->assertSee('Notification Events');

        $response = $this->actingAs($admin)->post(route('admin.notification-rules.store'), [
            'event_key' => 'workorder.draft_created',
            'name' => 'Admin + Manager',
            'enabled' => 1,
            'respect_user_preferences' => 1,
            'exclude_actor' => 1,
            'recipient_roles' => [$admin->role_id, $manager->role_id],
        ]);

        $response->assertRedirect(route('admin.notification-rules.index'));
        $response->assertSessionHasNoErrors();

        $rule = NotificationEventRule::query()->where('name', 'Admin + Manager')->firstOrFail();

        $this->assertTrue($rule->enabled);
        $this->assertSame('workorder.draft_created', $rule->event_key);
        $this->assertSame('info', $rule->severity);
        $this->assertSame('event_default', $rule->repeat_policy);
        $this->assertDatabaseHas('notification_event_rule_recipients', [
            'notification_event_rule_id' => $rule->id,
            'recipient_type' => 'role',
            'recipient_value' => (string) $admin->role_id,
        ]);
        $this->assertDatabaseHas('notification_event_rule_recipients', [
            'notification_event_rule_id' => $rule->id,
            'recipient_type' => 'role',
            'recipient_value' => (string) $manager->role_id,
        ]);
    }

    public function test_draft_workorder_created_rule_notifies_admin_and_manager_roles(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $shipping = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'number' => 2,
            'user_id' => $shipping->id,
            'is_draft' => true,
        ]);

        $this->createRule('workorder.draft_created', [
            ['type' => 'role', 'value' => (string) $admin->role_id],
            ['type' => 'role', 'value' => (string) $manager->role_id],
        ], [
            'name' => 'Draft created recipients',
            'title_template' => 'New draft',
            'message_template' => 'Draft WO {workorder_no} created by {actor_name}. Unit: {part_number}.',
        ]);

        app(WorkorderNotifyService::class)->draftCreated($workorder, $shipping->id, $shipping->name);

        Notification::assertSentTo($admin, NewMessageNotification::class, function ($notification) use ($shipping) {
            $data = $notification->toDatabase($shipping);

            return $data['event'] === 'draft_created'
                && $data['type'] === 'workorder'
                && $data['title'] === 'New draft'
                && str_contains($data['text'], 'Draft WO 2');
        });

        Notification::assertSentTo($manager, NewMessageNotification::class, function ($notification) use ($shipping) {
            $data = $notification->toDatabase($shipping);

            return str_contains($data['text'], $shipping->name);
        });
    }

    public function test_approved_workorder_uses_rules_only_and_notifies_technician_and_system_admin(): void
    {
        Notification::fake();

        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['name' => 'Role Admin', 'is_admin' => false]);
        $technician = $this->createUserWithRole('Technician');
        $approver = $this->createUserWithRole('Manager');

        $workorder = $this->createWorkorder([
            'number' => 3001,
            'user_id' => $technician->id,
            'approve_at' => now(),
            'approve_name' => $approver->name,
        ]);

        $this->createRule('workorder.approved', [
            ['type' => 'dynamic', 'value' => 'workorder_technician'],
            ['type' => 'dynamic', 'value' => 'system_admins'],
        ], [
            'name' => 'Approved recipients',
            'severity' => 'success',
            'title_template' => 'Approved',
            'message_template' => 'Workorder {workorder_no} approved by {actor_name}.',
        ]);

        app(WorkorderNotifyService::class)->approved($workorder, $approver->id, $approver->name);

        Notification::assertSentTo($technician, NewMessageNotification::class);
        Notification::assertSentTo($systemAdmin, NewMessageNotification::class);
        Notification::assertNotSentTo($roleOnlyAdmin, NewMessageNotification::class);
    }

    public function test_registered_approved_event_without_rule_does_not_use_legacy_fallback(): void
    {
        Notification::fake();

        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $technician = $this->createUserWithRole('Technician');
        $approver = $this->createUserWithRole('Manager');

        $workorder = $this->createWorkorder([
            'number' => 3002,
            'user_id' => $technician->id,
            'approve_at' => now(),
            'approve_name' => $approver->name,
        ]);

        app(WorkorderNotifyService::class)->approved($workorder, $approver->id, $approver->name);

        Notification::assertNothingSentTo($technician);
        Notification::assertNothingSentTo($systemAdmin);
    }

    public function test_unapproved_workorder_notifies_technician_and_system_admin(): void
    {
        Notification::fake();
        NotificationEventRule::query()->where('event_key', 'workorder.unapproved')->delete();

        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['name' => 'Role Admin', 'is_admin' => false]);
        $technician = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');

        $workorder = $this->createWorkorder([
            'number' => 3003,
            'user_id' => $technician->id,
            'approve_at' => now(),
            'approve_name' => $manager->name,
        ]);

        $this->createRule('workorder.unapproved', [
            ['type' => 'dynamic', 'value' => 'workorder_technician'],
            ['type' => 'dynamic', 'value' => 'system_admins'],
        ], [
            'name' => 'Unapproved recipients',
            'severity' => 'warning',
            'title_template' => 'Unapproved',
            'message_template' => 'Workorder {workorder_no} unapproved by {actor_name}.',
        ]);

        app(WorkorderNotifyService::class)->unapproved($workorder, $manager->id, $manager->name);

        Notification::assertSentTo($technician, NewMessageNotification::class, function ($notification) use ($manager) {
            $data = $notification->toDatabase($manager);

            return $data['event'] === 'unapproved'
                && $data['severity'] === 'warning'
                && str_contains($data['text'], 'Workorder 3003 unapproved by ' . $manager->name);
        });
        Notification::assertSentTo($systemAdmin, NewMessageNotification::class);
        Notification::assertNotSentTo($roleOnlyAdmin, NewMessageNotification::class);
    }

    public function test_unapproved_workorder_observer_fires_when_approval_is_removed(): void
    {
        Notification::fake();
        NotificationEventRule::query()->where('event_key', 'workorder.unapproved')->delete();

        $technician = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');

        $workorder = $this->createWorkorder([
            'number' => 3004,
            'user_id' => $technician->id,
            'approve_at' => now(),
            'approve_name' => 'Previous Approver',
        ]);

        $this->createRule('workorder.unapproved', [
            ['type' => 'dynamic', 'value' => 'workorder_technician'],
        ], [
            'name' => 'Unapproved observer recipients',
            'severity' => 'warning',
            'title_template' => 'Unapproved',
            'message_template' => 'Workorder {workorder_no} unapproved by {actor_name}.',
        ]);

        $this->actingAs($manager);
        $workorder->approve_at = null;
        $workorder->approve_name = null;
        $workorder->save();

        Notification::assertSentTo($technician, NewMessageNotification::class, function ($notification) use ($manager) {
            $data = $notification->toDatabase($manager);

            return $data['event'] === 'unapproved'
                && str_contains($data['text'], 'Workorder 3004 unapproved by ' . $manager->name);
        });
    }

    public function test_assigned_workorder_rule_notifies_assigned_user_and_admin_role(): void
    {
        Notification::fake();

        $assignedUser = $this->createUserWithRole('Technician');
        $admin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manager = $this->createUserWithRole('Manager');

        $workorder = $this->createWorkorder([
            'number' => 3201,
            'user_id' => $assignedUser->id,
        ]);

        $this->createRule('workorder.assigned', [
            ['type' => 'dynamic', 'value' => 'assigned_user'],
            ['type' => 'role', 'value' => (string) $admin->role_id],
        ], [
            'name' => 'Assigned recipients',
            'title_template' => 'Workorder assigned',
            'message_template' => 'Workorder {workorder_no} was assigned to you by {actor_name}.',
        ]);

        app(WorkorderNotifyService::class)->assigned($workorder, $manager->id, $manager->name);

        Notification::assertSentTo($assignedUser, NewMessageNotification::class);
        Notification::assertSentTo($admin, NewMessageNotification::class);
    }

    public function test_overdue_event_uses_rules_only_and_notifies_assigned_notify_user_and_system_admin(): void
    {
        Notification::fake();

        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['name' => 'Role Admin', 'is_admin' => false]);
        $assignedUser = $this->createUserWithRole('Technician');
        $notifyUser = $this->createUserWithRole('Manager');
        $workorderOwner = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'number' => 4001,
            'user_id' => $workorderOwner->id,
        ]);

        $component = Component::query()->create([
            'manual_id' => $this->createManual()->id,
            'part_number' => 'PN-4001',
            'assy_part_number' => 'APN-4001',
            'name' => 'Test Component',
            'ipl_num' => 'IPL-4001',
            'assy_ipl_num' => 'AIPL-4001',
            'eff_code' => 'ALL',
            'units_assy' => 1,
            'log_card' => false,
            'repair' => false,
            'is_bush' => false,
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SER-4001',
            'qty' => 1,
        ]);

        $processName = ProcessName::query()->create([
            'name' => 'Inspect',
            'process_sheet_name' => 'Inspect Sheet',
            'form_number' => 'FORM-4001',
            'std_days' => 1,
            'notify_user_id' => $notifyUser->id,
            'print_form' => false,
            'show_in_process_picker' => true,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => now()->subDays(3)->toDateString(),
            'user_id' => $assignedUser->id,
        ]);

        $this->createRule('tdr_process.overdue_start', [
            ['type' => 'dynamic', 'value' => 'tdr_process_user'],
            ['type' => 'dynamic', 'value' => 'process_notify_user'],
            ['type' => 'dynamic', 'value' => 'system_admins'],
        ], [
            'name' => 'Overdue recipients',
            'severity' => 'danger',
            'title_template' => 'Process overdue',
            'message_template' => 'WO {workorder_no}: {process_name} overdue.',
            'exclude_actor' => false,
        ]);

        app(EventRunner::class)->run([new TdrProcessOverdueStartEvent()]);

        Notification::assertSentTo($assignedUser, NewMessageNotification::class);
        Notification::assertSentTo($notifyUser, NewMessageNotification::class);
        Notification::assertSentTo($systemAdmin, NewMessageNotification::class);
        Notification::assertNotSentTo($roleOnlyAdmin, NewMessageNotification::class);
    }

    public function test_birthday_in_two_days_rule_notifies_manager_and_system_admin(): void
    {
        Notification::fake();

        $manager = $this->createUserWithRole('Manager');
        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $birthdayUser = $this->createUserWithRole('Technician', [
            'birthday' => now()->addDays(2)->toDateString(),
        ]);

        $this->createRule('user.birthday_2days', [
            ['type' => 'role', 'value' => (string) $manager->role_id],
            ['type' => 'dynamic', 'value' => 'system_admins'],
        ], [
            'name' => 'Birthday in two days',
            'title_template' => 'Birthday in 2 days',
            'message_template' => '{birthday_user_name} has a birthday in 2 days.',
            'exclude_actor' => false,
        ]);

        app(EventRunner::class)->run([new BirthdayInTwoDaysEvent()]);

        Notification::assertSentTo($manager, NewMessageNotification::class);
        Notification::assertSentTo($systemAdmin, NewMessageNotification::class);
        Notification::assertNotSentTo($birthdayUser, NewMessageNotification::class);
    }

    public function test_birthday_today_rule_can_notify_all_users(): void
    {
        Notification::fake();

        $birthdayUser = $this->createUserWithRole('Technician', [
            'birthday' => now()->toDateString(),
        ]);
        $manager = $this->createUserWithRole('Manager');
        $admin = $this->createUserWithRole('Admin', ['is_admin' => false]);

        $this->createRule('user.birthday_today', [
            ['type' => 'dynamic', 'value' => 'all_users'],
        ], [
            'name' => 'Birthday today',
            'title_template' => 'Birthday today',
            'message_template' => 'Today is {birthday_user_name} birthday.',
            'exclude_actor' => false,
        ]);

        app(EventRunner::class)->run([new BirthdayTodayEvent()]);

        Notification::assertSentTo($birthdayUser, NewMessageNotification::class);
        Notification::assertSentTo($manager, NewMessageNotification::class);
        Notification::assertSentTo($admin, NewMessageNotification::class);
    }

    public function test_notification_rule_update_is_logged_in_activity_log(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manager = $this->createUserWithRole('Manager');

        $rule = $this->createRule('workorder.draft_created', [
            ['type' => 'role', 'value' => (string) $admin->role_id],
        ], [
            'name' => 'Draft recipients',
            'enabled' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.notification-rules.update', $rule), [
            'event_key' => 'workorder.draft_created',
            'name' => 'Draft recipients',
            'enabled' => 0,
            'respect_user_preferences' => 1,
            'exclude_actor' => 1,
            'recipient_roles' => [$admin->role_id, $manager->role_id],
        ])->assertRedirect(route('admin.notification-rules.index'));

        $activity = Activity::query()
            ->where('log_name', 'notification_rules')
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('notification_rule_updated', $activity->description);
        $this->assertSame($admin->id, (int) $activity->causer_id);
        $this->assertSame($rule->id, (int) $activity->subject_id);
        $this->assertSame(false, data_get($activity->properties->toArray(), 'after.enabled'));
    }

    public function test_parts_process_cannot_start_until_previous_process_is_returned(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'SEQ-' . uniqid(),
            'name' => 'Sequence Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SEQ',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Sequence First');
        $secondName = $this->createProcessName('Sequence Second');

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_start' => '2026-05-02',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');
    }

    public function test_parts_process_locks_previous_dates_after_next_process_started(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LOCK-' . uniqid(),
            'name' => 'Lock Component',
            'ipl_num' => '2-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'LOCK',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Lock First');
        $secondName = $this->createProcessName('Lock Second');

        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
            'date_start' => '2026-05-03',
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $first), [
                'date_finish' => '2026-05-04',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');
    }

    public function test_parts_process_allows_backfilling_gap_when_later_process_already_has_dates(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'GAP-' . uniqid(),
            'name' => 'Gap Component',
            'ipl_num' => '2-2',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'GAP',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Gap First');
        $secondName = $this->createProcessName('Gap Second');
        $thirdName = $this->createProcessName('Gap Third');

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $thirdName->id,
            'sort_order' => 3,
            'date_start' => '2026-05-05',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_start' => '2026-05-03',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $second->fresh()), [
                'date_finish' => '2026-05-04',
            ])
            ->assertOk();
    }

    public function test_parts_process_rejects_dates_outside_sequence_order(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'SEQ-' . uniqid(),
            'name' => 'Sequence Component',
            'ipl_num' => '3-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SEQ',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Sequence First');
        $secondName = $this->createProcessName('Sequence Second');
        $thirdName = $this->createProcessName('Sequence Third');

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
            'date_start' => '2026-05-03',
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $thirdName->id,
            'sort_order' => 3,
            'date_start' => '2026-05-05',
        ]);

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_finish' => '2026-05-06',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_finish');

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_start' => '2026-05-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');

        $this->actingAs($manager)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_finish' => '2026-05-05',
            ])
            ->assertOk();
    }

    public function test_returning_process_notifies_that_next_process_is_ready(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $notifyUser = $this->createUserWithRole('Manager');
        $sentAuthor = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'READY-' . uniqid(),
            'name' => 'Ready Component',
            'ipl_num' => '3-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'READY',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Ready First');
        $secondName = $this->createProcessName('Ready Second', ['notify_user_id' => $notifyUser->id]);

        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
            'date_start_user_id' => $sentAuthor->id,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
        ]);

        $this->createRule('tdr_process.ready_for_next', [
            ['type' => 'dynamic', 'value' => 'process_notify_user'],
            ['type' => 'dynamic', 'value' => 'previous_date_start_user'],
        ], [
            'name' => 'Next process ready',
            'title_template' => 'Next process ready',
            'message_template' => 'WO {workorder_no}: {process_name} after {previous_process_name}.',
            'exclude_actor' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $first), [
                'date_finish' => '2026-05-02',
            ])
            ->assertOk();

        Notification::assertSentTo($notifyUser, NewMessageNotification::class, function ($notification) use ($admin, $component) {
            $data = $notification->toDatabase($admin);

            return $data['event'] === 'process_ready_for_next'
                && $data['title'] === 'Next process ready'
                && str_contains($data['text'], 'Ready Second')
                && str_contains($data['text'], 'Ready First')
                && $data['payload']['part_number'] === $component->part_number
                && $data['payload']['serial_number'] === 'READY'
                && str_contains($data['payload']['detail_label'], $component->name);
        });

        Notification::assertSentTo($sentAuthor, NewMessageNotification::class, function ($notification) use ($sentAuthor) {
            $data = $notification->toDatabase($sentAuthor);

            return $data['event'] === 'process_ready_for_next'
                && $data['payload']['previous_date_start_user_id'] === $sentAuthor->id;
        });
    }

    public function test_ready_for_next_notification_repeats_until_next_sent_date_is_filled(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $recipient = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $recipient->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'READY-' . uniqid(),
            'name' => 'Ready Repeat Component',
            'ipl_num' => '3-2',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'READY-REPEAT',
            'qty' => 1,
        ]);
        $firstName = $this->createProcessName('Ready Repeat First');
        $secondName = $this->createProcessName('Ready Repeat Second');

        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-01',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondName->id,
            'sort_order' => 2,
        ]);

        $this->createRule('tdr_process.ready_for_next', [
            ['type' => 'dynamic', 'value' => 'workorder_technician'],
        ], [
            'name' => 'Next process ready repeat',
            'title_template' => 'Next process ready',
            'message_template' => 'WO {workorder_no}: send the detail to {process_name}.',
            'exclude_actor' => false,
        ]);

        \Carbon\Carbon::setTestNow('2026-05-11 06:00:00');
        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $first), [
                'date_finish' => '2026-05-02',
            ])
            ->assertOk();

        Notification::assertSentToTimes($recipient, NewMessageNotification::class, 1);

        \Carbon\Carbon::setTestNow('2026-05-12 06:00:00');
        app(EventRunner::class)->run([new ProcessReadyForNextReminderEvent()]);

        Notification::assertSentToTimes($recipient, NewMessageNotification::class, 2);
        Notification::assertSentTo($recipient, NewMessageNotification::class, function ($notification) use ($recipient, $component) {
            $data = $notification->toDatabase($recipient);

            return $data['event'] === 'process_ready_for_next'
                && ($data['payload']['part_number'] ?? null) === $component->part_number
                && ($data['payload']['serial_number'] ?? null) === 'READY-REPEAT'
                && str_contains((string) ($data['payload']['detail_label'] ?? ''), $component->name);
        });

        $second->forceFill(['date_start' => '2026-05-12'])->save();
        \Carbon\Carbon::setTestNow('2026-05-13 06:00:00');
        app(EventRunner::class)->run([new ProcessReadyForNextReminderEvent()]);

        Notification::assertSentToTimes($recipient, NewMessageNotification::class, 2);
        \Carbon\Carbon::setTestNow();
    }

    public function test_ready_for_next_reminder_skips_process_without_workorder(): void
    {
        Notification::fake();

        $recipient = $this->createUserWithRole('Technician');
        $component = Component::query()->create([
            'manual_id' => $this->createManual()->id,
            'part_number' => 'ORPHAN-' . uniqid(),
            'name' => 'Orphan Component',
            'ipl_num' => '9-9',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => null,
            'component_id' => $component->id,
            'serial_number' => 'ORPHAN',
            'qty' => 1,
        ]);
        $processName = $this->createProcessName('Orphan Process');
        $process = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
        ]);

        $rule = $this->createRule('tdr_process.ready_for_next', [
            ['type' => 'user', 'value' => $recipient->id],
        ], [
            'name' => 'Orphan ready reminder',
            'title_template' => 'Next process ready',
            'message_template' => 'WO {workorder_no}: send the detail to {process_name}.',
            'exclude_actor' => false,
        ]);

        EventLog::query()->create([
            'event_key' => 'tdr_process.ready_for_next',
            'notification_event_rule_id' => $rule->id,
            'subject_type' => TdrProcess::class,
            'subject_id' => $process->id,
            'recipient_user_id' => $recipient->id,
            'first_sent_at' => now()->subMinutes(2),
            'last_sent_at' => now()->subMinutes(2),
            'sent_count' => 1,
        ]);

        app(EventRunner::class)->run([new ProcessReadyForNextReminderEvent()]);

        Notification::assertNothingSent();
    }

    public function test_std_process_cannot_start_until_previous_std_process_is_returned(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $ndtName = $this->createProcessName('STD NDT List', ['show_in_process_picker' => false]);
        $cadName = $this->createProcessName('STD CAD List', ['show_in_process_picker' => false]);

        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $ndtName->id,
            'date_start' => '2026-05-01',
        ]);
        $cad = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $cadName->id,
        ]);

        $this->actingAs($manager)
            ->patchJson(route('workorder_std_processes.updateDate', $cad), [
                'date_start' => '2026-05-02',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_start');
    }

    protected function createRule(string $eventKey, array $recipients, array $attributes = []): NotificationEventRule
    {
        $defaults = [
            'event_key' => $eventKey,
            'name' => 'Rule for ' . $eventKey,
            'enabled' => true,
            'severity' => 'info',
            'title_template' => 'Notification',
            'message_template' => 'Message',
            'repeat_policy' => 'event_default',
            'repeat_every_minutes' => null,
            'respect_user_preferences' => true,
            'exclude_actor' => true,
        ];

        $rule = NotificationEventRule::query()->create(array_merge($defaults, $attributes));

        foreach ($recipients as $recipient) {
            $rule->recipients()->create([
                'recipient_type' => $recipient['type'],
                'recipient_value' => $recipient['value'],
            ]);
        }

        return $rule;
    }

    protected function createProcessName(string $name, array $attributes = []): ProcessName
    {
        return ProcessName::query()->create(array_merge([
            'name' => $name . ' ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
            'std_days' => 1,
            'print_form' => false,
            'show_in_process_picker' => true,
        ], $attributes));
    }
}
