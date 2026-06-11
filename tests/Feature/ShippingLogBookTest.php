<?php

namespace Tests\Feature;

use App\Models\Workorder;
use App\Models\NotificationEventRule;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ShippingLogBookTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manager_can_open_shipping_log_book(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $customer = $this->createCustomer(['name' => 'Porter']);
        $unit = $this->createUnit(['part_number' => '47500-7']);
        $workorder = $this->createWorkorder([
            'user_id' => $manager->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'number' => 107691,
            'customer_po' => 'P2377526',
            'shipping_freight_forwarder' => 'Picked Up',
        ]);
        $workorder->forceFill(['done_at' => '2026-05-15'])->save();
        $workorder->forceFill(['shipping_shipment_at' => '2026-05-16'])->save();

        $response = $this->actingAs($manager)->get(route('shipping-log-book.index'));

        $response->assertOk();
        $response->assertSee('Shipping Log Book');
        $response->assertSee('w' . $workorder->number);
        $response->assertSee('text-info');
        $response->assertSee('47500-7');
        $response->assertSee('Porter');
        $response->assertSee('P2377526');
        $response->assertSee('Completed');
        $response->assertSee('Shipment');
        $response->assertSee('15/May/2026');
        $response->assertSee('16/May/2026');
        $response->assertSee('Picked Up');
        $response->assertSeeInOrder(['Technician', 'Shipping Log Book', 'Materials']);
    }

    public function test_admin_can_update_shipping_log_fields(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->patchJson(route('shipping-log-book.update', $workorder), [
            'shipping_shipment_at' => '03/jun/2026',
            'shipping_freight_forwarder' => 'DHL',
            'shipping_awb_no' => 'AWB-123',
            'shipping_notes' => 'Left with front desk',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('workorder.shipping_shipment_at_display', '03/Jun/2026');

        $this->assertDatabaseHas('workorders', [
            'id' => $workorder->id,
            'shipping_shipment_at' => '2026-06-03',
            'shipping_freight_forwarder' => 'DHL',
            'shipping_awb_no' => 'AWB-123',
            'shipping_notes' => 'Left with front desk',
        ]);
    }

    public function test_shipping_log_field_changes_are_logged_with_user_and_time(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $workorder->forceFill([
            'shipping_shipment_at' => '2026-06-01',
            'shipping_freight_forwarder' => 'Old Forwarder',
            'shipping_awb_no' => 'OLD-AWB',
            'shipping_notes' => 'Old notes',
        ])->saveQuietly();

        $startedAt = now()->subSecond();

        $this->actingAs($admin)->patchJson(route('shipping-log-book.update', $workorder), [
            'shipping_shipment_at' => '03/jun/2026',
            'shipping_freight_forwarder' => 'DHL',
            'shipping_awb_no' => 'AWB-123',
            'shipping_notes' => 'Left with front desk',
        ])->assertOk();

        $activity = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', Workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('event', 'updated')
            ->where('causer_id', $admin->id)
            ->latest('id')
            ->firstOrFail();

        $properties = $activity->properties->toArray();
        $attributes = $properties['attributes'] ?? [];
        $old = $properties['old'] ?? [];

        $this->assertTrue($activity->created_at->greaterThanOrEqualTo($startedAt));
        $this->assertStringStartsWith('2026-06-03', (string) ($attributes['shipping_shipment_at'] ?? ''));
        $this->assertStringStartsWith('2026-06-01', (string) ($old['shipping_shipment_at'] ?? ''));
        $this->assertSame('DHL', $attributes['shipping_freight_forwarder'] ?? null);
        $this->assertSame('Old Forwarder', $old['shipping_freight_forwarder'] ?? null);
        $this->assertSame('AWB-123', $attributes['shipping_awb_no'] ?? null);
        $this->assertSame('OLD-AWB', $old['shipping_awb_no'] ?? null);
        $this->assertSame('Left with front desk', $attributes['shipping_notes'] ?? null);
        $this->assertSame('Old notes', $old['shipping_notes'] ?? null);

        $logsResponse = $this->actingAs($admin)
            ->getJson(route('workorders.logs-json', $workorder))
            ->assertOk()
            ->assertJsonFragment(['causer_name' => $admin->name])
            ->assertSee('Shipment')
            ->assertSee('Freight Forwarder')
            ->assertSee('AWB No.')
            ->assertSee('Shipping notes');

        $updatedLog = collect($logsResponse->json())->firstWhere('event', 'updated');
        $shipmentChange = collect($updatedLog['changes'] ?? [])->firstWhere('field', 'shipping_shipment_at');

        $this->assertSame('01/jun/2026', $shipmentChange['old'] ?? null);
        $this->assertSame('03/jun/2026', $shipmentChange['new'] ?? null);
    }

    public function test_shipping_log_update_notifies_rule_recipients(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $recipient = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107776,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notification-rules.index'))
            ->assertOk()
            ->assertSee('Shipping Log Book updated');

        $this->createRule('workorder.shipping_log_updated', [
            ['type' => 'user', 'value' => (string) $recipient->id],
        ], [
            'name' => 'Shipping log recipients',
            'title_template' => 'Shipping changed',
            'message_template' => 'WO {workorder_no}: {shipping_changed_fields} changed by {actor_name}.',
            'exclude_actor' => true,
        ]);

        $this->actingAs($admin)->patchJson(route('shipping-log-book.update', $workorder), [
            'shipping_shipment_at' => '03/jun/2026',
            'shipping_freight_forwarder' => 'DHL',
            'shipping_awb_no' => '',
            'shipping_notes' => '',
        ])->assertOk();

        Notification::assertSentTo($recipient, NewMessageNotification::class, function ($notification) use ($recipient, $admin, $workorder) {
            $data = $notification->toDatabase($recipient);

            return $data['event'] === 'shipping_log_updated'
                && $data['type'] === 'workorder'
                && $data['title'] === 'Shipping changed'
                && $data['url'] === route('shipping-log-book.index', ['q' => $workorder->number])
                && str_contains($data['text'], 'WO 107776')
                && str_contains($data['text'], 'Shipment')
                && str_contains($data['text'], 'Freight Forwarder')
                && str_contains($data['text'], $admin->name);
        });
    }

    public function test_fragment_endpoint_supports_infinite_scroll(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $first = $this->createWorkorder(['user_id' => $manager->id, 'number' => 107700]);
        $second = $this->createWorkorder(['user_id' => $manager->id, 'number' => 107701]);

        $first->forceFill(['done_at' => '2026-05-15'])->save();
        $second->forceFill(['done_at' => '2026-05-16'])->save();

        $response = $this->actingAs($manager)->getJson(route('shipping-log-book.index', [
            'fragment' => 1,
            'per_page' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['html', 'next_page', 'has_more', 'loaded_count', 'total_count']);
        $response->assertJsonPath('loaded_count', 1);
        $response->assertJsonPath('has_more', true);
        $this->assertStringContainsString('name="shipping_shipment_at"', $response->json('html'));
    }

    public function test_shipping_log_book_sorts_by_workorder_completed_and_shipment(): void
    {
        $manager = $this->createUserWithRole('Manager');

        $smaller = $this->createWorkorder([
            'user_id' => $manager->id,
            'number' => 107700,
            'customer_po' => 'SORT-SHIPPING-LOG',
        ]);
        $larger = $this->createWorkorder([
            'user_id' => $manager->id,
            'number' => 107701,
            'customer_po' => 'SORT-SHIPPING-LOG',
        ]);

        $smaller->forceFill([
            'done_at' => '2026-05-17',
            'shipping_shipment_at' => '2026-05-14',
        ])->save();
        $larger->forceFill([
            'done_at' => '2026-05-16',
            'shipping_shipment_at' => '2026-05-15',
        ])->save();

        $default = $this->actingAs($manager)->getJson(route('shipping-log-book.index', [
            'fragment' => 1,
            'per_page' => 10,
            'q' => 'SORT-SHIPPING-LOG',
        ]));

        $default->assertOk();
        $this->assertStringContainsString('w107701', $default->json('html'));
        $this->assertStringContainsString('w107700', $default->json('html'));
        $this->assertLessThan(
            strpos($default->json('html'), 'w107700'),
            strpos($default->json('html'), 'w107701')
        );

        $completed = $this->actingAs($manager)->getJson(route('shipping-log-book.index', [
            'fragment' => 1,
            'per_page' => 10,
            'q' => 'SORT-SHIPPING-LOG',
            'sort' => 'completed',
            'direction' => 'desc',
        ]));

        $completed->assertOk();
        $this->assertLessThan(
            strpos($completed->json('html'), 'w107701'),
            strpos($completed->json('html'), 'w107700')
        );

        $shipment = $this->actingAs($manager)->getJson(route('shipping-log-book.index', [
            'fragment' => 1,
            'per_page' => 10,
            'q' => 'SORT-SHIPPING-LOG',
            'sort' => 'shipment',
            'direction' => 'desc',
        ]));

        $shipment->assertOk();
        $this->assertLessThan(
            strpos($shipment->json('html'), 'w107700'),
            strpos($shipment->json('html'), 'w107701')
        );
    }

    public function test_shipping_role_cannot_open_or_update_shipping_log_book(): void
    {
        $shipping = $this->createUserWithRole('Shipping');
        $workorder = $this->createWorkorder();

        $this->actingAs($shipping)
            ->get(route('shipping-log-book.index'))
            ->assertForbidden();

        $this->actingAs($shipping)
            ->patchJson(route('shipping-log-book.update', $workorder), [
                'shipping_freight_forwarder' => 'Blocked',
            ])
            ->assertForbidden();
    }

    public function test_technician_cannot_update_shipping_log_fields(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();

        $this->actingAs($technician)
            ->patchJson(route('shipping-log-book.update', $workorder), [
                'shipping_freight_forwarder' => 'Blocked',
            ])
            ->assertForbidden();

        $this->assertSame('', (string) Workorder::find($workorder->id)->shipping_freight_forwarder);
    }

    protected function createRule(string $eventKey, array $recipients, array $attributes = []): NotificationEventRule
    {
        $rule = NotificationEventRule::query()->create(array_merge([
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
        ], $attributes));

        foreach ($recipients as $recipient) {
            $rule->recipients()->create([
                'recipient_type' => $recipient['type'],
                'recipient_value' => $recipient['value'],
            ]);
        }

        return $rule;
    }
}
