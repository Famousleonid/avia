<?php

namespace Tests\Feature;

use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_latest_notifications_returns_unread_items_for_bell(): void
    {
        $user = $this->createUserWithRole('Admin');

        $user->notify(new NewMessageNotification(
            fromUserId: 0,
            fromName: 'System',
            text: 'Bell check message',
            severity: 'info',
            title: 'Bell check',
        ));

        $this->actingAs($user)
            ->getJson(route('notifications.unreadCount'))
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->actingAs($user)
            ->getJson(route('notifications.latest', ['per_page' => 10]))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('items.0.text', 'Bell check message')
            ->assertJsonPath('items.0.from_name', 'System')
            ->assertJsonPath('items.0.severity', 'info');
    }

    public function test_latest_notifications_hydrates_workorder_user_for_bell(): void
    {
        $recipient = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Bilinoy leonid',
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107580,
            'user_id' => $technician->id,
        ]);

        $recipient->notify(new NewMessageNotification(
            fromUserId: 0,
            fromName: 'System',
            text: 'WO 107580: send the detail to STD CAD List.',
            type: 'workorder',
            event: 'process_ready_for_next',
            ui: [
                'workorder' => [
                    'id' => $workorder->id,
                ],
            ],
            payload: [
                'workorder_id' => $workorder->id,
            ],
        ));

        $this->actingAs($recipient)
            ->getJson(route('notifications.latest', ['per_page' => 10]))
            ->assertOk()
            ->assertJsonPath('items.0.ui.workorder.no', '107580')
            ->assertJsonPath('items.0.ui.workorder.owner_name', 'Bilinoy leonid')
            ->assertJsonPath('items.0.payload.workorder_user_name', 'Bilinoy leonid');
    }

    public function test_notifications_page_links_draft_created_text_to_main_draft(): void
    {
        $recipient = $this->createUserWithRole('Admin');
        $shipping = $this->createUserWithRole('Shipping');
        $workorder = $this->createWorkorder([
            'number' => 8,
            'user_id' => $shipping->id,
            'is_draft' => true,
        ]);

        $recipient->notify(new NewMessageNotification(
            fromUserId: $shipping->id,
            fromName: $shipping->name,
            text: 'Draft WO 8 created by ' . $shipping->name,
            url: route('mains.show', $workorder->id),
            type: 'workorder',
            event: 'draft_created',
            ui: [
                'workorder' => [
                    'id' => $workorder->id,
                    'no' => $workorder->number,
                    'is_draft' => true,
                ],
                'actor' => [
                    'id' => $shipping->id,
                    'name' => $shipping->name,
                ],
            ],
            title: 'Draft Workorder created',
        ));

        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('href="' . route('mains.show', $workorder->id) . '"', false)
            ->assertSee('Draft WO 8 created by ' . e($shipping->name), false);
    }

    public function test_existing_notification_header_uses_unit_pn_not_process_part_or_recipient(): void
    {
        $recipient = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder();
        $workorder->unit->update(['part_number' => 'HEADER-UNIT-123']);
        $recipient->notify(new NewMessageNotification(
            fromUserId: 0,
            fromName: 'Header Sender',
            text: 'Process update',
            type: 'workorder',
            event: 'assigned',
            ui: [
                'workorder' => ['id' => $workorder->id, 'no' => $workorder->number, 'owner_name' => 'Saved Owner'],
                'part' => ['number' => 'PROCESS-PART-456'],
            ],
        ));

        $this->actingAs($recipient)->getJson(route('notifications.latest'))
            ->assertOk()
            ->assertJsonPath('items.0.ui.workorder.unit_part_number', 'HEADER-UNIT-123')
            ->assertJsonPath('items.0.ui.workorder.owner_name', 'Saved Owner')
            ->assertJsonPath('items.0.ui.part.number', 'PROCESS-PART-456');
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('From: Header Sender')
            ->assertSee('P/N: HEADER-UNIT-123')
            ->assertDontSee('class="text-muted mx-1">to</span>', false);
    }
}
