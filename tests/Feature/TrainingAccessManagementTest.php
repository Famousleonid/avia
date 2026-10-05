<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TrainingAccessManagementTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_existing_flags_are_visible_and_granted_and_revoked_in_access(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $tech = $this->createUserWithRole('Technician', ['can_sign_certificates' => true]);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->get(route('admin.access.index'))->assertOk()
            ->assertSee('SCA (training qualification)')->assertSee('Manage approved trainings')
            ->assertSee(route('admin.access.training-flag.destroy', ['feature' => 'trainings.sca', 'user' => $tech->id]), false);
        foreach (['trainings.sca' => 'can_sign_certificates', 'trainings.manage_approved' => 'can_manage_approved_trainings'] as $key => $flag) {
            $this->post(route('admin.access.store'), ['feature_key' => $key, 'user_ids' => [$tech->id]])->assertRedirect();
            $this->assertTrue($tech->fresh()->$flag);
            $this->delete(route('admin.access.training-flag.destroy', ['feature' => $key, 'user' => $tech->id]))->assertRedirect();
            $this->assertFalse($tech->fresh()->$flag);
        }
        $this->assertDatabaseHas('activity_log', ['log_name' => 'user', 'subject_id' => $tech->id, 'causer_id' => $admin->id]);
        $this->get(route('users.edit', $tech))->assertOk()->assertDontSee('name="can_sign_certificates"', false)
            ->assertDontSee('name="can_manage_approved_trainings"', false);
        $tech->refresh()->update(['can_sign_certificates' => true, 'can_manage_approved_trainings' => true]);
        $this->put(route('users.update', $tech), ['name' => $tech->name, 'email' => $tech->email,
            'stamp' => $tech->stamp, 'role_id' => $tech->role_id, 'team_id' => $tech->team_id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($tech->fresh()->can_sign_certificates);
        $this->assertTrue($tech->fresh()->can_manage_approved_trainings);
    }

    public function test_technician_cannot_assign_or_revoke_flags(): void
    {
        $tech = $this->createUserWithRole('Technician', ['is_admin' => false]);
        $this->actingAs($tech)->post(route('admin.access.store'), ['feature_key' => 'trainings.sca', 'user_ids' => [$tech->id]])->assertForbidden();
        $this->delete(route('admin.access.training-flag.destroy', ['feature' => 'trainings.sca', 'user' => $tech->id]))->assertForbidden();
    }
}
