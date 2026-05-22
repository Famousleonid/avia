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
}
