<?php

namespace Tests\Feature;

use App\Models\CustomerAircraft;
use App\Models\CustomerInteractionNote;
use App\Models\CustomerMarketingProfile;
use App\Models\MarketingCompanyType;
use App\Models\MarketingSegment;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MarketingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_marketing_access_is_limited_to_admin_and_manager(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($admin)
            ->get(route('marketing.index'))
            ->assertOk();

        $this->actingAs($manager)
            ->getJson(route('marketing.customers.index'))
            ->assertOk();

        $this->actingAs($technician)
            ->get(route('marketing.index'))
            ->assertForbidden();

        $this->actingAs($technician)
            ->getJson(route('marketing.customers.index'))
            ->assertForbidden();
    }

    public function test_marketing_customer_profile_can_be_updated_and_listed(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $customer = $this->createCustomer(['name' => 'SkyService']);
        $companyType = MarketingCompanyType::query()->firstOrCreate(['name' => 'MRO'], ['sort_order' => 10]);
        $segment = MarketingSegment::query()->firstOrCreate(['name' => 'Regional'], ['sort_order' => 10]);
        $plane = $this->createPlane(['type' => 'CL601']);

        $this->actingAs($admin)
            ->get(route('marketing.index'))
            ->assertOk()
            ->assertSee('data-marketing-page', false)
            ->assertSee('id="marketingShell"', false)
            ->assertSee('id="marketingSplitter"', false)
            ->assertSee('placeholder="Company, contact, country, A/C"', false)
            ->assertSee('id="marketingWorkordersScroll"', false)
            ->assertSee('id="marketingMediaModal"', false)
            ->assertSee('id="marketingProfileForm" data-no-spinner', false)
            ->assertSee('id="marketingContactForm" class="marketing-section" data-no-spinner', false)
            ->assertSee('id="marketingNoteForm" class="marketing-section" data-no-spinner', false)
            ->assertSee('id="marketingCreateForm" data-no-spinner', false)
            ->assertSee('data-contact-id="${contact.id}" data-no-spinner', false);

        $update = $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer), [
            'name' => 'SkyService Ltd',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'country' => 'Canada',
            'address' => 'Toronto',
            'company_type_id' => $companyType->id,
            'segment_id' => $segment->id,
            'terms_label' => 'NET 30',
            'aircraft_ids' => [$plane->id],
        ]);

        $update->assertOk()
            ->assertJsonPath('customer.name', 'SkyService Ltd')
            ->assertJsonPath('customer.country', 'Canada')
            ->assertJsonPath('customer.aircraft.0.type', 'CL601');

        $this->assertDatabaseHas('customer_marketing_profiles', [
            'customer_id' => $customer->id,
            'country' => 'Canada',
            'terms_label' => 'NET 30',
        ]);
        $this->assertDatabaseHas('customer_aircraft', [
            'customer_id' => $customer->id,
            'plane_id' => $plane->id,
        ]);

        $list = $this->actingAs($admin)->getJson(route('marketing.customers.index', [
            'q' => 'SkyService',
            'plane_id' => $plane->id,
        ]));

        $list->assertOk()
            ->assertJsonPath('items.0.name', 'SkyService Ltd')
            ->assertJsonPath('items.0.company_type', 'MRO');

        $created = $this->actingAs($admin)->postJson(route('marketing.customers.store'), [
            'name' => 'New Prospect Co',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country' => 'USA',
            'company_type_id' => $companyType->id,
            'segment_id' => $segment->id,
            'aircraft_ids' => [$plane->id],
        ]);

        $created->assertCreated()
            ->assertJsonPath('customer.name', 'New Prospect Co')
            ->assertJsonPath('customer.profile.lifecycle_status', CustomerMarketingProfile::STATUS_POTENTIAL);

        $createdCustomerId = $created->json('customer.id');

        $this->assertDatabaseHas('customers', [
            'id' => $createdCustomerId,
            'name' => 'New Prospect Co',
        ]);
        $this->assertDatabaseHas('customer_marketing_profiles', [
            'customer_id' => $createdCustomerId,
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country' => 'USA',
        ]);
    }

    public function test_marketing_changes_are_logged_with_human_readable_values(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $customer = $this->createCustomer(['name' => 'Readable Logs Inc']);
        $mro = MarketingCompanyType::query()->firstOrCreate(['name' => 'MRO'], ['sort_order' => 10]);
        $oem = MarketingCompanyType::query()->firstOrCreate(['name' => 'OEM'], ['sort_order' => 20]);
        $regional = MarketingSegment::query()->firstOrCreate(['name' => 'Regional'], ['sort_order' => 10]);
        $business = MarketingSegment::query()->firstOrCreate(['name' => 'Business'], ['sort_order' => 20]);
        $atr = $this->createPlane(['type' => 'ATR-42']);
        $cl = $this->createPlane(['type' => 'CL601']);

        $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer), [
            'name' => 'Readable Logs Canada',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_EXISTING,
            'country' => 'Canada',
            'address' => 'Toronto',
            'company_type_id' => $mro->id,
            'segment_id' => $regional->id,
            'terms_label' => 'NET 30',
            'aircraft_ids' => [$atr->id],
        ])->assertOk();

        $this->actingAs($admin)->patchJson(route('marketing.customers.profile.update', $customer->fresh()), [
            'name' => 'Readable Logs Canada',
            'lifecycle_status' => CustomerMarketingProfile::STATUS_POTENTIAL,
            'country' => 'Canada',
            'address' => 'Montreal',
            'company_type_id' => $oem->id,
            'segment_id' => $business->id,
            'terms_label' => 'Pre-Payment',
            'aircraft_ids' => [$atr->id, $cl->id],
        ])->assertOk();

        $activity = Activity::query()
            ->where('log_name', 'marketing')
            ->where('description', 'Marketing company updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $props = $activity->properties->toArray();

        $this->assertSame('Readable Logs Canada', $props['customer']);
        $this->assertSame('MRO', $props['old']['type']);
        $this->assertSame('OEM', $props['new']['type']);
        $this->assertSame('Regional', $props['old']['segment']);
        $this->assertSame('Business', $props['new']['segment']);
        $this->assertSame('ATR-42', $props['old']['aircraft']);
        $this->assertSame('ATR-42, CL601', $props['new']['aircraft']);
        $this->assertSame('Pre-Payment', $props['new']['terms']);
    }

    public function test_marketing_note_follow_up_command_sends_due_notifications(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $author = $this->createUserWithRole('Manager', ['name' => 'Note Author']);
        $otherManager = $this->createUserWithRole('Manager', ['name' => 'Other Manager']);
        $sales = $this->createUserWithRole('Sales');
        $customer = $this->createCustomer(['name' => 'Jazz Aviation LP']);

        $note = CustomerInteractionNote::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $author->id,
            'note' => 'Call about ERJ units.',
            'interaction_at' => now()->subDay()->toDateString(),
            'follow_up_at' => now()->toDateString(),
            'follow_up_status' => CustomerInteractionNote::STATUS_OPEN,
        ]);

        $this->artisan('marketing:send-follow-ups')->assertExitCode(0);

        Notification::assertSentTo($admin, NewMessageNotification::class);
        Notification::assertSentTo($author, NewMessageNotification::class);
        Notification::assertNotSentTo($otherManager, NewMessageNotification::class);
        Notification::assertNotSentTo($sales, NewMessageNotification::class);

        $this->assertNotNull($note->fresh()->reminder_sent_at);
    }
}
