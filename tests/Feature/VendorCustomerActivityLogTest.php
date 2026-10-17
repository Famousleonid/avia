<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class VendorCustomerActivityLogTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_vendor_http_changes_record_actor_values_and_deletion(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->actingAs($admin);
        $name = 'Audit Vendor '.uniqid();
        $id = $this->postJson(route('vendors.store'), ['name' => $name])
            ->assertOk()->json('vendor.id');
        $logs = fn () => Activity::where('subject_type', Vendor::class)->where('subject_id', $id);
        $created = $logs()->where('event', 'created')->sole();
        $this->assertEquals($admin->id, $created->causer_id);
        $this->assertSame('vendor', $created->log_name);
        $this->assertSame($name, $created->properties['attributes']['name']);
        $this->assertNotNull(Vendor::findOrFail($id)->created_at);

        $editor = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->actingAs($editor)->withSession([
            'auth.version' => (int) $editor->auth_version,
            'password_hash_web' => $editor->getAuthPassword(),
        ]);
        $data = ['name' => $name.' revised', 'is_trusted' => true, 'description' => 'Approved supplier'];
        $this->patchJson(route('vendors.meta.update', $id), $data)->assertOk();
        $updated = $logs()->where('event', 'updated')->sole();
        $this->assertEquals($editor->id, $updated->causer_id);
        $this->assertSame($name, $updated->properties['old']['name']);
        $this->assertSame(false, $updated->properties['old']['is_trusted']);
        $this->assertSame($data, $updated->properties['attributes']);
        $this->patchJson(route('vendors.meta.update', $id), $data)->assertOk();
        $this->assertSame(2, $logs()->count());

        $this->patchJson(route('directories.toggle', ['directory' => 'vendors', 'id' => $id, 'field' => 'is_trusted']))->assertOk();
        $this->assertSame(false, $logs()->latest('id')->first()->properties['attributes']['is_trusted']);
        $this->delete(route('vendors.destroy', $id))->assertRedirect();
        $deleted = $logs()->where('event', 'deleted')->sole();
        $this->assertEquals($editor->id, $deleted->causer_id);
        $this->assertSame($data['name'], $deleted->properties['vendor_name']);
        $this->get(route('admin.activity.index', ['log_name' => 'vendor', 'q' => $name]))
            ->assertOk()->assertSee($data['name']);
    }

    public function test_customer_http_lifecycle_and_restore_are_audited(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->actingAs($admin);
        $name = 'Audit Customer '.uniqid();
        $id = $this->postJson(route('customers.store'), ['name' => $name])->assertCreated()->json('id');
        $logs = fn () => Activity::where('subject_type', Customer::class)->where('subject_id', $id);
        $created = $logs()->where('event', 'created')->sole();
        $this->assertEquals($admin->id, $created->causer_id);
        $this->assertSame('customer', $created->log_name);
        $this->assertSame($name, $created->properties['attributes']['name']);
        $this->put(route('customers.update', $id), ['name' => $name.' revised'])->assertRedirect();
        $updated = $logs()->where('event', 'updated')->sole();
        $this->assertSame($name, $updated->properties['old']['name']);
        $this->assertSame($name.' revised', $updated->properties['attributes']['name']);
        $this->put(route('customers.update', $id), ['name' => $name.' revised'])->assertRedirect();
        $this->assertSame(2, $logs()->count());
        $this->delete(route('customers.destroy', $id))->assertRedirect();
        $this->assertEquals($admin->id, $logs()->where('event', 'deleted')->sole()->causer_id);
        $this->get(route('admin.activity.index', ['log_name' => 'customer', 'q' => $name]))
            ->assertOk()->assertSee($name.' revised');
        Customer::withTrashed()->findOrFail($id)->restore();
        $restored = $logs()->where('event', 'restored')->sole();
        $this->assertEquals($admin->id, $restored->causer_id);
        $this->assertSame($name.' revised', $restored->properties['customer_name']);
        $this->assertSame(4, $logs()->count());
    }
}
