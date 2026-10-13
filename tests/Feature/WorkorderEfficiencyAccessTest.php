<?php

namespace Tests\Feature;

use App\Models\UserFeatureAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderEfficiencyAccessTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_system_admin_has_automatic_access_and_can_assign_it_in_access_settings(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician');
        $this->actingAs($admin)->get(route('workorders.status.efficiency'))
            ->assertOk()->assertSee('Efficiency')->assertSee('WO Status tabs');
        $this->get(route('admin.access.index'))->assertOk()->assertSee('WO Status — Efficiency');
        $this->post(route('admin.access.store'), [
            'feature_key' => 'wo_status.efficiency', 'user_ids' => [$technician->id],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('user_feature_access', [
            'user_id' => $technician->id, 'feature_key' => 'wo_status.efficiency',
        ]);
    }

    public function test_manager_without_assignment_cannot_open_efficiency(): void
    {
        $this->actingAs($this->createUserWithRole('Manager'))
            ->get(route('workorders.status.efficiency'))->assertForbidden();
        $this->get(route('workorders.status'))->assertOk()
            ->assertDontSee('href="'.route('workorders.status.efficiency').'"', false);
    }

    public function test_assigned_technician_can_open_efficiency_but_revocation_blocks_direct_url(): void
    {
        $user = $this->createUserWithRole('Technician');
        $this->grantFeatureAccess($user, 'wo_status.efficiency');
        $this->actingAs($user)->get(route('workorders.status.efficiency'))->assertOk()
            ->assertSee('href="'.route('workorders.status.efficiency').'"', false);
        $this->get(route('workorders.status'))->assertForbidden();
        UserFeatureAccess::where('user_id', $user->id)->where('feature_key', 'wo_status.efficiency')->delete();
        $user->unsetRelation('featureAccesses');
        $this->get(route('workorders.status.efficiency'))->assertForbidden();
    }
}
