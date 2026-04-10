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
