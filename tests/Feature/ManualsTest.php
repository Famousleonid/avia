<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
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

    public function test_system_traveler_process_is_hidden_from_manual_processes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $travelerName = ProcessName::query()->create([
            'name' => ProcessName::SYSTEM_TRAVELER_NAME,
            'process_sheet_name' => 'TRAVELER',
            'form_number' => 'TRV',
            'show_in_process_picker' => true,
        ]);
        $travelerProcess = Process::query()->create([
            'process_names_id' => $travelerName->id,
            'process' => 'Rechrome',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manual->id,
            'processes_id' => $travelerProcess->id,
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'processes',
        ]));

        $response->assertOk();
        $response->assertDontSee('Rechrome');
        $response->assertDontSee(ProcessName::SYSTEM_TRAVELER_NAME);
        $this->assertFalse(ProcessName::forPicker()->whereKey($travelerName->id)->exists());
    }

    public function test_system_traveler_process_cannot_be_added_to_manual_processes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $travelerName = ProcessName::query()->create([
            'name' => ProcessName::SYSTEM_TRAVELER_NAME,
            'process_sheet_name' => 'TRAVELER',
            'form_number' => 'TRV',
            'show_in_process_picker' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('processes.store'), [
            'manual_id' => $manual->id,
            'process_names_id' => $travelerName->id,
            'process' => 'Traveler should not attach',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('processes', [
            'process_names_id' => $travelerName->id,
            'process' => 'Traveler should not attach',
        ]);
    }

    public function test_manual_parts_sort_ipl_with_section_suffix_naturally(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        foreach (['9A-300', '9A-30', '9A-290'] as $ipl) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => $ipl,
                'part_number' => 'PN-' . $ipl,
                'name' => 'Part ' . $ipl,
                'units_assy' => 1,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'parts',
        ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertLessThan(strpos($html, '>9A-290<'), strpos($html, '>9A-30<'));
        $this->assertLessThan(strpos($html, '>9A-300<'), strpos($html, '>9A-290<'));
    }

    public function test_manual_parts_forms_accept_ipl_section_suffix_pattern(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'parts',
        ]));

        $response->assertOk();
        $response->assertSee('pattern="^\d+[A-Za-z]*-\d+[A-Za-z0-9]*$"', false);
        $response->assertSee('pattern="^$|^\d+[A-Za-z]*-\d+[A-Za-z0-9]*$"', false);
    }
}
