<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_open_manuals_index_and_see_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-100',
            'title' => 'Main Manual',
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.index'));

        $response->assertOk();
        $response->assertSee($manual->number);
        $response->assertSee('Main Manual');
    }

    public function test_non_admin_sees_only_permitted_manuals(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $allowedManual = $this->createManual([
            'number' => 'CMM-ALLOW',
            'title' => 'Allowed Manual',
        ]);
        $hiddenManual = $this->createManual([
            'number' => 'CMM-HIDE',
            'title' => 'Hidden Manual',
        ]);

        $allowedManual->permittedUsers()->attach($technician->id);

        $response = $this->actingAs($technician)->get(route('manuals.index'));

        $response->assertOk();
        $response->assertSee('Allowed Manual');
        $response->assertDontSee('Hidden Manual');
        $response->assertDontSee($hiddenManual->number);
    }

    /**
     * @group smoke
     */
    public function test_admin_can_create_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $plane = $this->createPlane();
        $builder = $this->createBuilder();
        $scope = $this->createScope();

        $response = $this->actingAs($admin)->post(route('manuals.store'), [
            'number' => 'CMM-200',
            'title' => 'Created Manual',
            'revision_date' => '2026-01-01',
            'unit_name' => 'Hydraulic Unit',
            'unit_name_training' => 'Training Unit',
            'training_hours' => '4',
            'ovh_life' => '1000',
            'reg_sb' => 'SB-1',
            'planes_id' => $plane->id,
            'builders_id' => $builder->id,
            'scopes_id' => $scope->id,
            'lib' => 'LIB-200',
            'units' => ['UNIT-200-A', 'UNIT-200-B'],
            'eff_codes' => ['ALL', 'A1'],
        ]);

        $response->assertRedirect(route('manuals.index'));
        $response->assertSessionHasNoErrors();

        $manual = Manual::query()->where('number', 'CMM-200')->first();
        $this->assertNotNull($manual);
        $this->assertDatabaseHas('manuals', [
            'number' => 'CMM-200',
            'title' => 'Created Manual',
        ]);
        $this->assertDatabaseHas('units', [
            'manual_id' => $manual->id,
            'part_number' => 'UNIT-200-A',
        ]);
        $this->assertDatabaseHas('units', [
            'manual_id' => $manual->id,
            'part_number' => 'UNIT-200-B',
        ]);
    }

    public function test_admin_can_update_manual_core_fields_and_permissions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $permittedUser = $this->createUserWithRole('Technician');
        $manual = $this->createManual([
            'number' => 'CMM-300',
            'title' => 'Before Update',
        ]);

        $response = $this->actingAs($admin)->put(route('manuals.update', $manual->id), [
            'number' => 'CMM-300',
            'title' => 'After Update',
            'revision_date' => '2026-02-01',
            'unit_name' => 'Updated Unit',
            'unit_name_training' => 'Updated Training',
            'training_hours' => '8',
            'ovh_life' => '2000',
            'reg_sb' => 'SB-2',
            'planes_id' => $manual->planes_id,
            'builders_id' => $manual->builders_id,
            'scopes_id' => $manual->scopes_id,
            'lib' => 'LIB-UPDATED',
            'units' => ['UNIT-UPDATED-1'],
            'eff_codes' => ['ALL'],
            'permitted_user_ids' => [$permittedUser->id],
        ]);

        $response->assertRedirect(route('manuals.index'));
        $response->assertSessionHasNoErrors();

        $manual->refresh();

        $this->assertSame('After Update', $manual->title);
        $this->assertSame('LIB-UPDATED', $manual->lib);
        $this->assertTrue($manual->permittedUsers()->where('users.id', $permittedUser->id)->exists());
        $this->assertDatabaseHas('units', [
            'manual_id' => $manual->id,
            'part_number' => 'UNIT-UPDATED-1',
        ]);
    }
}
