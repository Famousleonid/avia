<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualRevisionCheck;
use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use App\Services\Ai\Tools\ListManualRevisionChecksDueTool;
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

    public function test_system_admin_sees_and_can_permanently_delete_manual(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $manual = $this->createManual([
            'number' => 'CMM-FORCE',
            'title' => 'Force Delete Manual',
        ]);
        $component = Component::create([
            'part_number' => 'FORCE-PART-1',
            'name' => 'Force Part One',
            'ipl_num' => '10-1',
            'manual_id' => $manual->id,
        ]);
        $softDeletedComponent = Component::create([
            'part_number' => 'FORCE-PART-2',
            'name' => 'Force Part Two',
            'ipl_num' => '10-2',
            'manual_id' => $manual->id,
        ]);
        $softDeletedComponent->delete();

        $index = $this->actingAs($systemAdmin)->get(route('manuals.index'));
        $index->assertOk();
        $index->assertSee(route('manuals.force-destroy', ['manual' => $manual->id]), false);

        $response = $this->actingAs($systemAdmin)->delete(route('manuals.force-destroy', ['manual' => $manual->id]));

        $response->assertRedirect(route('manuals.index'));
        $this->assertDatabaseMissing('manuals', ['id' => $manual->id]);
        $this->assertDatabaseMissing('components', ['id' => $component->id]);
        $this->assertDatabaseMissing('components', ['id' => $softDeletedComponent->id]);
    }

    public function test_admin_role_without_is_admin_cannot_permanently_delete_manual(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manual = $this->createManual([
            'number' => 'CMM-NO-FORCE',
            'title' => 'No Force Delete Manual',
        ]);

        $index = $this->actingAs($roleOnlyAdmin)->get(route('manuals.index'));
        $index->assertOk();
        $index->assertDontSee(route('manuals.force-destroy', ['manual' => $manual->id]), false);

        $response = $this->actingAs($roleOnlyAdmin)->delete(route('manuals.force-destroy', ['manual' => $manual->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('manuals', ['id' => $manual->id]);
    }

    public function test_admin_can_toggle_soft_deleted_manuals_in_index(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-SOFT-DELETED',
            'title' => 'Soft Deleted Manual',
        ]);
        $manual->delete();

        $defaultResponse = $this->actingAs($admin)->get(route('manuals.index'));
        $defaultResponse->assertOk();
        $defaultResponse->assertDontSee('CMM-SOFT-DELETED');

        $withDeletedResponse = $this->actingAs($admin)->get(route('manuals.index', ['with_deleted' => 1]));
        $withDeletedResponse->assertOk();
        $withDeletedResponse->assertSee('CMM-SOFT-DELETED');
        $withDeletedResponse->assertSee('Soft deleted');
        $withDeletedResponse->assertSee('manual-soft-deleted-row', false);
        $withDeletedResponse->assertSee('showDeletedManualsCheckbox', false);
        $withDeletedResponse->assertSee('Permanent delete CMM-SOFT-DELETED', false);

        $deleteResponse = $this->actingAs($admin)->delete(route('manuals.force-destroy', ['manual' => $manual->id]));

        $deleteResponse->assertRedirect(route('manuals.index'));
        $this->assertDatabaseMissing('manuals', ['id' => $manual->id]);
    }

    public function test_non_admin_cannot_include_soft_deleted_manuals_in_index(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manual = $this->createManual([
            'number' => 'CMM-HIDDEN-DELETED',
            'title' => 'Hidden Deleted Manual',
        ]);
        $manual->permittedUsers()->attach($technician->id);
        $manual->delete();

        $response = $this->actingAs($technician)->get(route('manuals.index', ['with_deleted' => 1]));

        $response->assertOk();
        $response->assertDontSee('CMM-HIDDEN-DELETED');
        $response->assertDontSee('id="showDeletedManualsCheckbox"', false);
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

    public function test_manual_show_renders_components_parts_and_processes_tabs(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-TABS',
            'title' => 'Tabs Manual',
        ]);
        $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => 'UNIT-TABS-1',
            'eff_code' => 'ALL',
        ]);
        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PART-TABS-1',
            'name' => 'Tabs Replaceable Part',
            'units_assy' => 1,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Tabs Process Name',
            'process_sheet_name' => 'TP',
            'form_number' => 'TP-1',
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Tabs Process Spec',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manual->id,
            'processes_id' => $process->id,
            'process_comment' => 'Tabs process comment',
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'processes',
        ]));

        $response->assertOk();
        $response->assertSee('id="nav-components-tab"', false);
        $response->assertSee('id="nav-parts-tab"', false);
        $response->assertSee('id="nav-processes-tab"', false);
        $response->assertSee('UNIT-TABS-1');
        $response->assertSee('PART-TABS-1');
        $response->assertSee('Tabs Replaceable Part');
        $response->assertSee('Tabs Process Name');
        $response->assertSee('Tabs Process Spec');
        $response->assertSee('Tabs process comment');
        $response->assertSee(route('processes.create', ['manual_id' => $manual->id, 'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes'])]), false);
    }

    public function test_manual_parts_delete_uses_project_confirm_dialog(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PART-DELETE-1',
            'name' => 'Delete Confirm Part',
            'units_assy' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'parts',
        ]));

        $response->assertOk();
        $response->assertSee('data-manual-part-delete-form', false);
        $response->assertSee('data-manual-part-delete-button', false);
        $response->assertSee('data-no-spinner', false);
        $response->assertSee('initManualPartDeleteConfirm', false);
        $response->assertDontSee("onclick=\"return confirm('Are you sure you want to delete this component?');\"", false);
    }

    public function test_manual_show_uses_saved_tab_on_first_render(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-SAVED-TAB',
            'title' => 'Saved Tab Manual',
        ]);

        $this->actingAs($admin)->postJson(route('user-ui-settings.store'), [
            'scope' => 'manuals.show',
            'key' => 'activeTab:'.$manual->id,
            'value' => 'processes',
        ])->assertOk();

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
        ]));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<button class="nav-link\s+active\s*"\s+id="nav-processes-tab"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<button class="nav-link\s+active\s*"\s+id="nav-components-tab"/', $html);
    }

    public function test_admin_can_record_manual_revision_check(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-REV',
            'title' => 'Revision Manual',
            'revision_date' => '2026-01-01',
        ]);

        $show = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'revision',
        ]));

        $show->assertOk();
        $show->assertSee('id="nav-revision-tab"', false);
        $show->assertSee('Revision Checks');

        $response = $this->actingAs($admin)->post(route('manuals.revision-checks.store', $manual), [
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
            'revision_number' => '9',
            'revision_date' => '2026-01-01',
            'checked_at' => now()->toDateString(),
            'notes' => 'No change',
        ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'revision']));
        $this->assertDatabaseHas('manual_revision_checks', [
            'manual_id' => $manual->id,
            'revision_number' => '9',
            'revision_date' => '2026-01-01',
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
            'notes' => 'No change',
        ]);
    }

    public function test_manual_revision_tab_dates_use_capital_month_project_format(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-REV-DATE',
            'title' => 'Revision Date Manual',
            'revision_date' => '2026-06-07',
        ]);

        ManualRevisionCheck::query()->create([
            'manual_id' => $manual->id,
            'revision_number' => '10',
            'revision_date' => '2026-06-07',
            'checked_at' => '2026-06-08',
            'checked_by_user_id' => $admin->id,
            'checked_by_stamp' => $admin->stamp,
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
            'notes' => 'No change',
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'revision',
        ]));

        $response->assertOk();
        $response->assertSee('Current revision date', false);
        $response->assertSee('07/Jun/2026', false);
        $response->assertSee('08/Jun/2026', false);
        $response->assertSee('name="revision_date" class="form-control form-control-sm"', false);
        $response->assertSee('value="07/Jun/2026"', false);
        $response->assertSee('name="checked_at" class="form-control form-control-sm"', false);
        $response->assertSee('data-project-date-capital', false);
    }

    public function test_manual_revision_check_accepts_capital_month_project_dates(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-REV-INPUT-DATE',
            'title' => 'Revision Input Date Manual',
            'revision_date' => '2026-06-01',
        ]);

        $response = $this->actingAs($admin)->post(route('manuals.revision-checks.store', $manual), [
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
            'revision_number' => '11',
            'revision_date' => '07/Jun/2026',
            'checked_at' => '08/Jun/2026',
            'notes' => 'No change',
        ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'revision']));
        $this->assertDatabaseHas('manual_revision_checks', [
            'manual_id' => $manual->id,
            'revision_number' => '11',
            'revision_date' => '2026-06-07',
            'checked_at' => '2026-06-08',
        ]);
    }

    public function test_manual_revision_ai_tool_lists_due_manuals(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-DUE',
            'title' => 'Due Manual',
            'revision_date' => '2026-01-01',
        ]);
        ManualRevisionCheck::query()->create([
            'manual_id' => $manual->id,
            'revision_number' => '8',
            'revision_date' => '2026-01-01',
            'checked_at' => now()->subMonths(3)->subDay()->toDateString(),
            'checked_by_user_id' => $admin->id,
            'checked_by_stamp' => $admin->stamp,
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
        ]);

        $result = app(ListManualRevisionChecksDueTool::class)->run($admin, [
            'days' => 30,
            'limit' => 10,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertNotEmpty($result['manuals']);
        $this->assertContains('CMM-DUE', collect($result['manuals'])->pluck('manual_number')->all());
    }

    public function test_duplicate_sidebar_page_routes_are_removed_but_manual_operations_remain(): void
    {
        $this->assertFalse(Route::has('units.index'));
        $this->assertFalse(Route::has('units.edit'));
        $this->assertFalse(Route::has('processes.index'));
        $this->assertFalse(Route::has('processes.edit'));
        $this->assertFalse(Route::has('processes.update'));
        $this->assertFalse(Route::has('processes.destroy'));

        $this->assertTrue(Route::has('units.show'));
        $this->assertTrue(Route::has('units.update'));
        $this->assertTrue(Route::has('processes.create'));
        $this->assertTrue(Route::has('processes.store'));
        $this->assertTrue(Route::has('processes.getProcesses'));
        $this->assertTrue(Route::has('manual_processes.edit'));
        $this->assertTrue(Route::has('manual_processes.update'));
        $this->assertTrue(Route::has('manual_processes.destroy'));
    }

    public function test_manual_parts_sort_ipl_with_section_suffix_naturally(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        foreach (['9A-300', '9A-30', '9A-290', '13-71', '13-70 RS', '13-70'] as $ipl) {
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
        $this->assertLessThan(strpos($html, '>13-70 RS<'), strpos($html, '>13-70<'));
        $this->assertLessThan(strpos($html, '>13-71<'), strpos($html, '>13-70 RS<'));
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
        $response->assertSee('pattern="^\d+[A-Za-z]*-\d+(?:\s*[A-Za-z][A-Za-z0-9]*)?$"', false);
        $response->assertSee('pattern="^$|^\d+[A-Za-z]*-\d+(?:\s*[A-Za-z][A-Za-z0-9]*)?$"', false);
    }
}
