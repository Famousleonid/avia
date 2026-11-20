<?php

namespace Tests\Feature;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class VendorContactDetailsTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_contacts_are_saved_audited_and_legacy_updates_preserve_them(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $fields = ['address' => "123 Test Street\nToronto, ON", 'contact_name' => 'Test Contact',
            'phone' => '+1 555 0100 ext 2', 'email' => 'contact@example.test', 'vendor_number' => 'V-123',
            'ship_via' => 'Courier', 'terms' => 'Net 30'];
        $response = $this->postJson(route('vendors.store'), ['name' => 'Vendor contacts test'] + $fields)->assertOk();
        $vendor = Vendor::findOrFail($response->json('vendor.id'));
        $this->assertSame($fields, $vendor->only(Vendor::CONTACT_FIELDS));
        $this->getJson(route('vendors.show', $vendor))->assertOk()->assertJsonPath('vendor.email', $fields['email']);
        $this->patchJson(route('vendors.meta.update', $vendor), ['is_trusted' => true, 'description' => 'Legacy client'])
            ->assertOk()->assertJsonPath('vendor.address', $fields['address']);
        $this->assertSame($fields, $vendor->fresh()->only(Vendor::CONTACT_FIELDS));
        $this->patchJson(route('vendors.meta.update', $vendor), ['is_trusted' => true, 'email' => 'bad email'])
            ->assertUnprocessable();
        $this->assertSame($fields['email'], $vendor->fresh()->email);
        $this->patchJson(route('vendors.meta.update', $vendor), ['is_trusted' => true, 'email' => '', 'phone' => 'Updated phone'])
            ->assertOk()->assertJsonPath('vendor.email', null)->assertJsonPath('vendor.phone', 'Updated phone');
        $audit = \Spatie\Activitylog\Models\Activity::where('subject_type', Vendor::class)->where('subject_id', $vendor->id)
            ->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame('Updated phone', data_get($audit->properties, 'attributes.phone'));
    }

    public function test_process_vendor_access_does_not_grant_vendor_edit_access(): void
    {
        $user = $this->createUserWithRole('Technician');
        $this->grantFeatureAccess($user, 'process_vendor'); $this->actingAs($user);
        $vendor = Vendor::create(['name' => 'Protected vendor', 'address' => 'Existing address']);
        $this->patchJson(route('vendors.meta.update', $vendor), ['is_trusted' => false, 'address' => 'Replacement'])->assertForbidden();
        $this->assertSame('Existing address', $vendor->fresh()->address);
        $this->get(route('process-vendor.index'))->assertOk()->assertSee('Existing address');
    }
}
