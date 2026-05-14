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
}
