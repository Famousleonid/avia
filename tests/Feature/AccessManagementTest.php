<?php

namespace Tests\Feature;

use App\Models\UserFeatureAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_system_admin_can_manage_marketing_access(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $manager = $this->createUserWithRole('Manager', ['name' => 'Marketing User']);

        $this->actingAs($systemAdmin)
            ->get(route('admin.access.index'))
            ->assertOk()
            ->assertSee('Access')
            ->assertSee('Marketing')
            ->assertSee('Quality Assurance')
            ->assertSee('EC')
            ->assertSee('Vendor Tracking')
            ->assertSee('Can sign certificates')
            ->assertSee('Manuals full access')
            ->assertSee('Manage locked manual processes')
            ->assertSee('Manage locked manual parts')
            ->assertSee('Marketing User');

        $this->assertFalse(Gate::forUser($manager)->allows('feature.marketing'));

        $this->actingAs($systemAdmin)
            ->post(route('admin.access.store'), [
                'feature_key' => 'marketing',
                'user_ids' => [$manager->id],
            ])
            ->assertRedirect(route('admin.access.index'));

        $this->assertDatabaseHas('user_feature_access', [
            'feature_key' => 'marketing',
            'user_id' => $manager->id,
            'granted_by_user_id' => $systemAdmin->id,
        ]);
        $this->assertTrue(Gate::forUser($manager->fresh())->allows('feature.marketing'));

        $this->actingAs($systemAdmin)
            ->get(route('admin.access.index'))
            ->assertOk()
            ->assertSee('Marketing User')
            ->assertSee($systemAdmin->name);

        $access = UserFeatureAccess::query()
            ->where('feature_key', 'marketing')
            ->where('user_id', $manager->id)
            ->firstOrFail();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_feature_access',
            'event' => 'created',
            'subject_type' => UserFeatureAccess::class,
            'subject_id' => $access->id,
            'causer_id' => $systemAdmin->id,
        ]);

        $this->actingAs($systemAdmin)
            ->delete(route('admin.access.destroy', $access))
            ->assertRedirect(route('admin.access.index'));

        $this->assertDatabaseMissing('user_feature_access', [
            'id' => $access->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_feature_access',
            'event' => 'deleted',
            'subject_type' => UserFeatureAccess::class,
            'subject_id' => $access->id,
            'causer_id' => $systemAdmin->id,
        ]);
        $this->assertFalse(Gate::forUser($manager->fresh())->allows('feature.marketing'));
    }

    public function test_role_admin_without_is_admin_cannot_manage_access(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);

        $this->actingAs($roleOnlyAdmin)
            ->get(route('admin.access.index'))
            ->assertForbidden();
    }

    public function test_marketing_is_not_granted_by_role_anymore(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manager = $this->createUserWithRole('Manager');

        $this->assertTrue(Gate::forUser($systemAdmin)->allows('feature.marketing'));
        $this->assertFalse(Gate::forUser($roleOnlyAdmin)->allows('feature.marketing'));
        $this->assertFalse(Gate::forUser($manager)->allows('feature.marketing'));

        UserFeatureAccess::query()->create([
            'feature_key' => 'marketing',
            'user_id' => $manager->id,
            'granted_by_user_id' => $systemAdmin->id,
        ]);

        $this->assertTrue(Gate::forUser($manager->fresh())->allows('feature.marketing'));
    }

    public function test_system_admin_is_not_assignable_because_access_is_automatic(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $otherSystemAdmin = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Hidden System Admin',
        ]);

        UserFeatureAccess::query()->firstOrCreate(
            [
                'feature_key' => 'quality_assurance',
                'user_id' => $otherSystemAdmin->id,
            ],
            [
                'granted_by_user_id' => $systemAdmin->id,
            ]
        );

        $this->actingAs($systemAdmin)
            ->get(route('admin.access.index'))
            ->assertOk()
            ->assertDontSee('Hidden System Admin');

        $this->actingAs($systemAdmin)
            ->post(route('admin.access.store'), [
                'feature_key' => 'marketing',
                'user_ids' => [$otherSystemAdmin->id],
            ])
            ->assertRedirect(route('admin.access.index'));

        $this->assertDatabaseMissing('user_feature_access', [
            'feature_key' => 'marketing',
            'user_id' => $otherSystemAdmin->id,
        ]);
    }

    public function test_system_admin_can_grant_access_to_multiple_users_at_once(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $first = $this->createUserWithRole('Technician', ['name' => 'First QA User']);
        $second = $this->createUserWithRole('Manager', ['name' => 'Second QA User']);

        $this->actingAs($systemAdmin)
            ->post(route('admin.access.store'), [
                'feature_key' => 'quality_assurance',
                'user_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('admin.access.index'));

        $this->assertTrue(Gate::forUser($first->fresh())->allows('feature.quality_assurance'));
        $this->assertTrue(Gate::forUser($second->fresh())->allows('feature.quality_assurance'));
        $this->assertTrue($first->fresh()->canAccessQualityAssurancePage());
        $this->assertTrue($second->fresh()->canAccessQualityAssurancePage());
    }

    public function test_vendor_crud_requires_vendor_tracking_access(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $vendorUser = $this->createUserWithRole('Technician', ['name' => 'Vendor Access User']);
        $this->grantFeatureAccess($vendorUser, 'vendor_tracking');

        $this->actingAs($technician)
            ->postJson(route('vendors.store'), [
                'name' => 'Blocked Vendor',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('vendors', [
            'name' => 'Blocked Vendor',
        ]);

        $this->actingAs($vendorUser)
            ->postJson(route('vendors.store'), [
                'name' => 'Allowed Vendor',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Allowed Vendor',
        ]);
    }
}
