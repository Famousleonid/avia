<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_desktop_profile_updates_only_allowed_fields(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'email' => 'profile.original@example.test',
            'is_admin' => 0,
        ]);
        $managerRole = Role::query()->firstOrCreate(['name' => 'Manager']);
        $team = Team::query()->firstOrCreate(['name' => 'Profile Team']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Profile Name',
            'phone' => '111 222',
            'birthday' => '1992-04-05',
            'stamp' => 'P1',
            'team_id' => $team->id,
            'email' => 'profile.changed@example.test',
            'role_id' => $managerRole->id,
            'is_admin' => 1,
            'email_verified_at' => null,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Profile Name', $user->name);
        $this->assertSame('111222', $user->phone);
        $this->assertSame('1992-04-05', $user->birthday->format('Y-m-d'));
        $this->assertSame('P1', $user->stamp);
        $this->assertSame($team->id, $user->team_id);
        $this->assertSame('profile.original@example.test', $user->email);
        $this->assertNotSame($managerRole->id, $user->role_id);
        $this->assertSame(0, (int) $user->is_admin);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_mobile_profile_accepts_mobile_birthday_format_and_avatar(): void
    {
        Bus::fake();

        $user = $this->createUserWithRole('Technician');
        $team = Team::query()->firstOrCreate(['name' => 'Mobile Profile Team']);

        $response = $this->actingAs($user)->put(route('mobile.update.profile'), [
            'name' => 'Mobile Name',
            'phone' => '333 444',
            'birthday' => '06.apr.1993',
            'stamp' => 'M1',
            'team_id' => $team->id,
            'file' => $this->makeUploadedImage('avatar.png'),
        ]);

        $response->assertRedirect(route('mobile.profile'));
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Mobile Name', $user->name);
        $this->assertSame('333444', $user->phone);
        $this->assertSame('1993-04-06', $user->birthday->format('Y-m-d'));
        $this->assertSame('M1', $user->stamp);
        $this->assertSame($team->id, $user->team_id);
        $this->assertCount(1, $user->getMedia('avatar'));
    }

    public function test_wrong_old_password_does_not_change_password(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'password' => bcrypt('oldpass'),
        ]);

        $response = $this->actingAs($user)->from(route('profile.edit'))->post(route('profile.password'), [
            'old_pass' => 'wrong',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertTrue(Hash::check('oldpass', $user->fresh()->password));
    }
}
