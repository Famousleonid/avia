<?php

namespace Tests\Feature;

use App\Models\MessageGroup;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MessageGroupsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function signIn(\App\Models\User $user): self
    {
        return $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ]);
    }

    public function test_only_admin_and_manager_can_see_and_manage_shared_groups(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $tech = $this->createUserWithRole('Technician');
        $other = $this->createUserWithRole('Manager');
        $response = $this->signIn($admin)->postJson(route('admin.messages.groups.store'), [
            'name' => 'Workshop', 'user_ids' => [$tech->id, $admin->id],
        ])->assertOk();
        $id = $response->json('id');
        $this->signIn($tech)->getJson(route('admin.messages.groups.index'))->assertForbidden();
        $this->postJson(route('admin.messages.groups.store'), ['name' => 'Forbidden', 'user_ids' => [$tech->id]])->assertForbidden();
        $this->putJson(route('admin.messages.groups.update', $id), ['name' => 'Changed', 'user_ids' => [$other->id]])->assertForbidden();
        $this->deleteJson(route('admin.messages.groups.destroy', $id))->assertForbidden();
        $this->signIn($other)->getJson(route('admin.messages.groups.index'))->assertOk()
            ->assertJsonPath('can_manage', true)->assertJsonFragment(['name' => 'Workshop']);
        $this->putJson(route('admin.messages.groups.update', $id), ['name' => 'Workshop 2', 'user_ids' => [$other->id]])->assertOk();
        $this->assertSame([$other->id], MessageGroup::findOrFail($id)->users->pluck('id')->all());
        $this->deleteJson(route('admin.messages.groups.destroy', $id))->assertOk();
        $this->assertDatabaseMissing('message_group_user', ['message_group_id' => $id]);
        $this->postJson(route('admin.messages.groups.store'), ['name' => 'Manager group', 'user_ids' => [$tech->id]])->assertOk();
    }

    public function test_group_and_screen_send_permissions_cannot_be_bypassed_by_requests(): void
    {
        Notification::fake();
        $recipient = $this->createUserWithRole('Technician');
        $group = MessageGroup::create(['name' => 'Restricted group']);
        $group->users()->attach($recipient->id);
        foreach (['Technician', 'Team Leader'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->signIn($user)->postJson(route('admin.messages.send'), [
                'group_ids' => [$group->id], 'message' => 'Forbidden group send',
            ])->assertForbidden();
            $this->postJson(route('admin.messages.send'), [
                'user_ids' => [$recipient->id], 'message' => 'Forbidden screen send', 'on_screen' => true,
            ])->assertForbidden();
            $this->getJson(route('admin.messages.groups.index'))->assertForbidden();
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('screen_messages', 0);
        $manager = $this->createUserWithRole('Manager');
        $this->signIn($manager)->postJson(route('admin.messages.send'), [
            'group_ids' => [$group->id], 'message' => 'Manager announcement', 'on_screen' => true,
        ])->assertOk()->assertJsonPath('ok', true);
        // All selected roles still receive and dismiss screen messages.
        $this->signIn($recipient)->getJson(route('messages.screen.index'))->assertOk()->assertJsonCount(1, 'items');
        $this->postJson(route('messages.screen.dismiss', DB::table('screen_messages')->value('id')))->assertOk();
    }

    public function test_sidebar_only_renders_group_and_screen_controls_for_admin_and_manager(): void
    {
        foreach (['Admin', 'Manager', 'Technician', 'Team Leader'] as $role) {
            $this->signIn($this->createUserWithRole($role));
            $html = view('components.sidebar')->render();
            foreach (['id="msgGroupsList"', 'id="msgGroupEditor"', 'id="msgOnScreen"'] as $control) {
                if (in_array($role, ['Admin', 'Manager'], true)) {
                    $this->assertStringContainsString($control, $html);
                } else {
                    $this->assertStringNotContainsString($control, $html);
                }
            }
            $this->assertStringContainsString('id="msgUsersList"', $html);
        }
    }

    public function test_private_group_delivery_deduplicates_recipients_and_respects_blocks(): void
    {
        Notification::fake();
        $sender = $this->createUserWithRole('Admin');
        $a = $this->createUserWithRole('Technician');
        $b = $this->createUserWithRole('Technician');
        $muted = $this->createUserWithRole('Technician', ['notification_prefs' => ['muted_users' => [(string) $sender->id]]]);
        $review = $this->createUserWithRole('Team Leader', ['email' => 'appreview@aviatechnik.ca']);
        $g1 = MessageGroup::create(['name' => 'Team one']); $g1->users()->attach([$sender->id, $a->id, $b->id, $muted->id, $review->id]);
        $g2 = MessageGroup::create(['name' => 'Team two']); $g2->users()->attach([$a->id, $b->id]);
        $this->actingAs($sender)->postJson(route('admin.messages.send'), [
            'group_ids' => [$g1->id, $g2->id], 'user_ids' => [$a->id], 'message' => 'Team update',
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonCount(2, 'sent')->assertJsonCount(1, 'blocked');
        Notification::assertSentToTimes($a, NewMessageNotification::class, 1);
        Notification::assertSentToTimes($b, NewMessageNotification::class, 1);
        Notification::assertNotSentTo([$muted, $review, $sender], NewMessageNotification::class);
        $this->assertDatabaseCount('screen_messages', 0);
    }

    public function test_screen_message_is_private_to_recipients_and_dismissal_is_per_user(): void
    {
        Notification::fake();
        $sender = $this->createUserWithRole('Admin');
        $a = $this->createUserWithRole('Technician'); $b = $this->createUserWithRole('Manager');
        $outsider = $this->createUserWithRole('Technician');
        $group = MessageGroup::create(['name' => 'Shift']); $group->users()->attach([$a->id, $b->id]);
        $this->actingAs($sender)->postJson(route('admin.messages.send'), [
            'group_ids' => [$group->id], 'message' => "Important\nKeep this text", 'on_screen' => true,
        ])->assertOk()->assertJsonCount(2, 'sent');
        Notification::assertNothingSent();
        $id = DB::table('screen_messages')->value('id');
        $this->signIn($outsider)->getJson(route('messages.screen.index'))->assertOk()->assertJsonCount(0, 'items');
        $this->postJson(route('messages.screen.dismiss', $id))->assertNotFound();
        $this->signIn($a)->getJson(route('messages.screen.index'))->assertOk()->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.text', "Important\nKeep this text")
            ->assertJsonPath('items.0.kind', 'info');
        $this->postJson(route('messages.screen.dismiss', $id))->assertOk();
        $dismissedAt = DB::table('screen_message_recipients')->where('user_id', $a->id)->value('dismissed_at');
        $this->assertNotNull($dismissedAt);
        $this->travel(1)->minutes();
        $this->postJson(route('messages.screen.dismiss', $id))->assertOk();
        $this->assertSame($dismissedAt, DB::table('screen_message_recipients')->where('user_id', $a->id)->value('dismissed_at'));
        $this->getJson(route('messages.screen.index'))->assertJsonCount(0, 'items');
        $this->signIn($b)->getJson(route('messages.screen.index'))->assertJsonCount(1, 'items');
        // Changing/deleting the group cannot revoke or expose an already sent message.
        $group->users()->sync([$outsider->id]); $group->delete();
        $this->getJson(route('messages.screen.index'))->assertJsonCount(1, 'items');
        $this->signIn($outsider)->getJson(route('messages.screen.index'))->assertJsonCount(0, 'items');
    }

    public function test_screen_mode_respects_muted_sender_and_never_creates_an_empty_broadcast(): void
    {
        Notification::fake();
        $sender = $this->createUserWithRole('Admin');
        $muted = $this->createUserWithRole('Technician', ['notification_prefs' => ['muted_users' => [$sender->id]]]);
        $this->actingAs($sender)->postJson(route('admin.messages.send'), [
            'user_ids' => [$muted->id], 'message' => 'Blocked', 'on_screen' => true,
        ])->assertOk()->assertJsonPath('ok', false)->assertJsonCount(1, 'blocked');
        $this->assertDatabaseCount('screen_messages', 0);
        Notification::assertNothingSent();
    }

    public function test_existing_individual_message_contract_is_preserved(): void
    {
        Notification::fake();
        $sender = $this->createUserWithRole('Technician'); $recipient = $this->createUserWithRole('Manager');
        $this->actingAs($sender)->postJson(route('admin.messages.send'), [
            'user_ids' => [$recipient->id], 'message' => 'Personal', 'group_ids' => [], 'on_screen' => false,
        ])->assertOk()->assertJsonPath('ok', true);
        Notification::assertSentTo($recipient, NewMessageNotification::class, fn ($notification) => $notification->text === 'Personal');
        $this->postJson(route('admin.messages.send'), ['user_ids' => [(string) $sender->id], 'message' => 'Self'])->assertStatus(422);
        $this->postJson(route('admin.messages.send'), ['user_ids' => [], 'group_ids' => [], 'message' => 'Empty'])->assertUnprocessable();
        $this->postJson(route('admin.messages.send'), ['group_ids' => [9999999], 'message' => 'Missing group'])->assertForbidden();
    }

    public function test_groups_validate_names_and_members(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->postJson(route('admin.messages.groups.store'), ['name' => 'Empty', 'user_ids' => []])->assertUnprocessable();
        $this->postJson(route('admin.messages.groups.store'), ['name' => 'Invalid', 'user_ids' => [9999999]])->assertUnprocessable();
        MessageGroup::create(['name' => 'Duplicate']);
        $this->postJson(route('admin.messages.groups.store'), ['name' => 'Duplicate', 'user_ids' => [$admin->id]])->assertUnprocessable();
    }

    public function test_screen_endpoints_require_authentication(): void
    {
        $this->getJson(route('messages.screen.index'))->assertUnauthorized();
        $this->postJson(route('messages.screen.dismiss', 1))->assertUnauthorized();
    }

    public function test_screen_message_kind_is_single_validated_and_persisted_for_the_recipient(): void
    {
        Notification::fake();
        $sender = $this->createUserWithRole('Manager');
        $recipient = $this->createUserWithRole('Technician');
        foreach (['alert', 'info', 'message'] as $kind) {
            $this->signIn($sender)->postJson(route('admin.messages.send'), [
                'user_ids' => [$recipient->id], 'message' => 'Type '.$kind,
                'on_screen' => true, 'screen_kind' => $kind,
            ])->assertOk()->assertJsonPath('ok', true);
            $this->assertDatabaseHas('screen_messages', ['message' => 'Type '.$kind, 'kind' => $kind]);
        }
        foreach (['unknown', ['alert', 'message']] as $invalid) {
            $this->postJson(route('admin.messages.send'), [
                'user_ids' => [$recipient->id], 'message' => 'Invalid',
                'on_screen' => true, 'screen_kind' => $invalid,
            ])->assertUnprocessable()->assertJsonValidationErrors('screen_kind');
        }
        $this->assertDatabaseCount('screen_messages', 3);
        $this->signIn($recipient)->getJson(route('messages.screen.index'))->assertOk()
            ->assertJsonPath('items.0.kind', 'alert')->assertJsonPath('items.1.kind', 'info')->assertJsonPath('items.2.kind', 'message');
        Notification::assertNothingSent();
    }
}
