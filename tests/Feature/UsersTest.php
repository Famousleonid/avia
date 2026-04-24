<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\BuildsDomainData;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_create_user(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('Admin');
        $role = Role::query()->firstOrCreate(['name' => 'Manager']);
        $team = Team::query()->firstOrCreate(['name' => 'QA Team']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Created User',
            'email' => 'created.user@example.test',
            'password' => 'secret123',
            'birthday' => '1990-01-01',
            'role_id' => $role->id,
            'team_id' => $team->id,
            'is_admin' => '1',
            'email_verified_at' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'created.user@example.test',
            'name' => 'Created User',
            'role_id' => $role->id,
            'team_id' => $team->id,
            'is_admin' => 1,
        ]);

        $this->assertNotNull(User::query()->where('email', 'created.user@example.test')->value('email_verified_at'));
    }

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Public User',
            'email' => 'public.user@example.test',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertNotFound();
    }

    public function test_admin_role_without_is_admin_flag_can_open_users_index(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);

        $response = $this->actingAs($roleOnlyAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertDontSee('Add User');
    }

    public function test_system_admin_sees_add_user_button_on_users_index(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Add User');
    }

    public function test_admin_role_without_is_admin_flag_cannot_create_user(): void
    {
        Notification::fake();

        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);
        $role = Role::query()->firstOrCreate(['name' => 'Manager']);
        $team = Team::query()->firstOrCreate(['name' => 'QA Team']);

        $response = $this->actingAs($roleOnlyAdmin)->post(route('users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked.user@example.test',
            'password' => 'secret123',
            'birthday' => '1990-01-01',
            'role_id' => $role->id,
            'team_id' => $team->id,
            'is_admin' => '1',
            'email_verified_at' => '1',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'blocked.user@example.test',
        ]);
    }

    /**
     * @group smoke
     */
    public function test_user_creation_validation_rejects_invalid_payload(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->from(route('users.create'))
            ->actingAs($admin)
            ->post(route('users.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => '',
                'birthday' => now()->addDay()->toDateString(),
                'role_id' => 999999,
                'team_id' => 999999,
            ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'birthday',
            'role_id',
            'team_id',
        ]);
    }
}
