<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class UserAccessUpdateTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_non_system_admin_cannot_open_own_user_edit_page(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->get(route('users.edit', $technician));

        $response->assertForbidden();
    }

    /**
     * @group smoke
     */
    public function test_non_admin_cannot_open_other_user_edit_page(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $otherUser = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->get(route('users.edit', $otherUser));

        $response->assertForbidden();
    }

    public function test_non_admin_can_update_own_profile_without_changing_role_or_email(): void
    {
        $technician = $this->createUserWithRole('Technician', [
            'email' => 'technician.original@example.test',
            'stamp' => 'OLD',
        ]);
        $managerRole = Role::query()->firstOrCreate(['name' => 'Manager']);
        $team = Team::query()->firstOrCreate(['name' => 'Updated Team']);

        $response = $this->actingAs($technician)->put(route('profile.update'), [
            'name' => 'Updated Tech',
            'email' => 'should.not.change@example.test',
            'phone' => '123 456 7890',
            'stamp' => 'NEW',
            'birthday' => '1991-02-03',
            'team_id' => $team->id,
            'role_id' => $managerRole->id,
            'is_admin' => 1,
            'email_verified_at' => 1,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasNoErrors();

        $technician->refresh();

        $this->assertSame('Updated Tech', $technician->name);
        $this->assertSame('1234567890', $technician->phone);
        $this->assertSame('NEW', $technician->stamp);
        $this->assertSame('1991-02-03', $technician->birthday->format('Y-m-d'));
        $this->assertSame($team->id, $technician->team_id);
        $this->assertSame('technician.original@example.test', $technician->email);
        $this->assertNotSame($managerRole->id, $technician->role_id);
        $this->assertSame(0, (int) $technician->is_admin);
    }

    public function test_admin_can_update_other_user_role_and_email(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $target = $this->createUserWithRole('Technician', [
            'email' => 'target.before@example.test',
            'stamp' => 'OLD',
        ]);
        $managerRole = Role::query()->firstOrCreate(['name' => 'Manager']);
        $team = Team::query()->firstOrCreate(['name' => 'Manager Team']);

        $response = $this->actingAs($admin)->put(route('users.update', $target->id), [
            'name' => 'Target After',
            'email' => 'target.after@example.test',
            'phone' => '555 777 1111',
            'stamp' => 'ADM',
            'birthday' => '1989-05-10',
            'team_id' => $team->id,
            'role_id' => $managerRole->id,
            'is_admin' => '1',
            'email_verified_at' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertSame('target.after@example.test', $target->email);
        $this->assertSame($managerRole->id, $target->role_id);
        $this->assertSame($team->id, $target->team_id);
        $this->assertSame(1, (int) $target->is_admin);
        $this->assertNotNull($target->email_verified_at);
    }

    public function test_user_can_change_only_own_password_with_old_password(): void
    {
        $technician = $this->createUserWithRole('Technician', [
            'password' => bcrypt('oldpass'),
        ]);

        $response = $this->actingAs($technician)->post(route('profile.password'), [
            'old_pass' => 'oldpass',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $technician->refresh();
        $this->assertTrue(Hash::check('123', $technician->password));
    }
}
