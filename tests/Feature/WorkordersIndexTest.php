<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Task;
use App\Models\UserUiSetting;
use App\Models\WorkorderGeneralTaskStatus;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkordersIndexTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_open_workorders_index_page(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'description' => 'Visible workorder',
        ]);

        $response = $this->actingAs($admin)->get(route('workorders.index'));

        $response->assertOk();
        $response->assertSee((string) $workorder->number);
        $response->assertSee('Visible workorder');
    }

    /**
     * @group smoke
     */
    public function test_guest_is_redirected_from_workorders_index(): void
    {
        $response = $this->get(route('workorders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_only_my_filter_returns_only_current_user_workorders(): void
    {
        $currentUser = $this->createUserWithRole('Admin');
        $otherUser = $this->createUserWithRole('Manager');

        $ownWorkorder = $this->createWorkorder([
            'user_id' => $currentUser->id,
            'description' => 'Own WO',
        ]);
        $otherWorkorder = $this->createWorkorder([
            'user_id' => $otherUser->id,
            'description' => 'Other WO',
        ]);

        $response = $this->actingAs($currentUser)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertJsonPath('loaded_count', 1);
        $response->assertSee((string) $ownWorkorder->number);
        $response->assertDontSee((string) $otherWorkorder->number);
    }

    public function test_approved_filter_returns_only_approved_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $approved = $this->createWorkorder([
            'user_id' => $admin->id,
            'approve_at' => now(),
            'approve_name' => $admin->name,
            'description' => 'Approved WO',
        ]);
        $notApproved = $this->createWorkorder([
            'user_id' => $admin->id,
            'description' => 'Pending WO',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'only_approved' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $approved->number);
        $response->assertDontSee((string) $notApproved->number);
    }

    public function test_draft_filter_returns_only_drafts(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $draft = $this->createWorkorder([
            'user_id' => $admin->id,
            'is_draft' => true,
            'description' => 'Draft WO',
        ]);
        $regular = $this->createWorkorder([
            'user_id' => $admin->id,
            'is_draft' => false,
            'description' => 'Regular WO',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'show_drafts' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $draft->number);
        $response->assertDontSee((string) $regular->number);
    }

    /**
     * @group smoke
     */
    public function test_fragment_endpoint_returns_expected_json_shape(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'next_cursor',
            'has_more',
            'loaded_count',
            'total_count',
            'overall_total',
        ]);
    }

    public function test_search_by_number_works(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 543210,
            'description' => 'Find me',
        ]);
        $other = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 654321,
            'description' => 'Do not find me',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'q' => '543210',
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $matching->number);
        $response->assertDontSee((string) $other->number);
    }

    public function test_search_by_full_number_with_active_filter_applies_query(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107608,
            'description' => 'Exact active workorder',
        ]);
        $other = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107699,
            'description' => 'Other active workorder',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'only_active' => 1,
            'q' => '107608',
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $matching->number);
        $response->assertDontSee((string) $other->number);
    }

    public function test_stage_cache_matches_all_tasks_in_complete_stage(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $completeStage = GeneralTask::query()->create([
            'name' => 'Complete Test Stage ' . uniqid(),
            'sort_order' => 900,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $completeStage->id,
        ]);
        Task::query()->create([
            'name' => '1. WO invoiced to Customer',
            'general_task_id' => $completeStage->id,
        ]);
        Task::query()->create([
            'name' => '2. WO Paid by Customer',
            'general_task_id' => $completeStage->id,
        ]);
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $completeStage->id,
            'task_id' => $completedTask->id,
            'user_id' => $admin->id,
            'date_finish' => '2026-03-12',
            'ignore_row' => false,
        ]);

        $workorder->recalcGeneralTaskStatuses($completeStage->id);

        $this->assertFalse((bool) WorkorderGeneralTaskStatus::query()
            ->where('workorder_id', $workorder->id)
            ->where('general_task_id', $completeStage->id)
            ->value('is_done'));
    }

    public function test_ec_sort_works_with_active_filter(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $ecName = ProcessName::query()->firstOrCreate(['name' => 'EC'], [
            'process_sheet_name' => 'EC',
            'form_number' => 'EC',
        ]);

        $finished = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107700,
            'description' => 'Finished EC workorder',
        ]);
        $started = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107701,
            'description' => 'Started EC workorder',
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->createWorkorder([
                'user_id' => $admin->id,
                'number' => 107710 + $i,
                'description' => 'No EC workorder ' . $i,
            ]);
        }

        $this->createEcTdrProcess($finished, $ecName, [
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        $this->createEcTdrProcess($started, $ecName, [
            'date_start' => '2026-05-03',
            'date_finish' => null,
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 10,
            'only_my' => 0,
            'only_active' => 1,
            'sort' => 'ec',
            'direction' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSee('Finished EC workorder');

        $cursor = $response->json('next_cursor');
        $this->assertNotEmpty($cursor);

        $nextResponse = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 10,
            'only_my' => 0,
            'only_active' => 1,
            'sort' => 'ec',
            'direction' => 'desc',
            'cursor' => $cursor,
        ]));

        $nextResponse->assertOk();
        $nextResponse->assertJsonStructure(['html', 'next_cursor', 'has_more']);
    }

    private function createEcTdrProcess($workorder, ProcessName $processName, array $attributes = []): TdrProcess
    {
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'EC-PN-' . uniqid(),
            'name' => 'EC Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'EC-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        return TdrProcess::query()->create(array_merge([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
        ], $attributes));
    }

    public function test_user_ui_settings_are_saved_per_user(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->postJson(route('user-ui-settings.store'), [
            'scope' => 'workorders.index',
            'key' => 'filters',
            'value' => [
                'q' => '107736',
                'onlyMy' => true,
                'onlyActive' => false,
                'sort' => 'number',
                'direction' => 'desc',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('user_ui_settings', [
            'user_id' => $admin->id,
            'scope' => 'workorders.index',
            'key' => 'filters',
        ]);
    }

    public function test_user_ui_settings_accept_scalar_values(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->postJson(route('user-ui-settings.store'), [
            'scope' => 'manuals.show',
            'key' => 'activeTab:123',
            'value' => 'parts',
        ]);

        $response->assertOk();
        $response->assertJsonPath('setting.value', 'parts');

        $this->actingAs($admin)
            ->getJson(route('user-ui-settings.index', ['scope' => 'manuals.show']))
            ->assertOk()
            ->assertJsonPath('settings.activeTab:123', 'parts');
    }

    public function test_user_ui_settings_remove_null_values(): void
    {
        $admin = $this->createUserWithRole('Admin');

        UserUiSetting::query()->create([
            'user_id' => $admin->id,
            'scope' => 'browser-storage',
            'key' => 'theme',
            'value' => 'dark',
        ]);

        $response = $this->actingAs($admin)->postJson(route('user-ui-settings.store'), [
            'scope' => 'browser-storage',
            'key' => 'theme',
            'value' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('setting.value', null);

        $this->assertDatabaseMissing('user_ui_settings', [
            'user_id' => $admin->id,
            'scope' => 'browser-storage',
            'key' => 'theme',
        ]);
    }

    public function test_user_ui_settings_are_isolated_between_users(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');

        UserUiSetting::query()->create([
            'user_id' => $manager->id,
            'scope' => 'workorders.index',
            'key' => 'filters',
            'value' => ['q' => 'manager-only'],
        ]);

        $response = $this->actingAs($admin)->getJson(route('user-ui-settings.index', [
            'scope' => 'workorders.index',
        ]));

        $response->assertOk();
        $response->assertJsonPath('settings', []);
    }

    public function test_workorders_index_embeds_current_user_saved_filters(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $other = $this->createUserWithRole('Manager');

        UserUiSetting::query()->create([
            'user_id' => $admin->id,
            'scope' => 'workorders.index',
            'key' => 'filters',
            'value' => [
                'q' => 'admin-saved-search',
                'onlyMy' => true,
                'onlyActive' => true,
            ],
        ]);
        UserUiSetting::query()->create([
            'user_id' => $other->id,
            'scope' => 'workorders.index',
            'key' => 'filters',
            'value' => [
                'q' => 'other-user-search',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('workorders.index'));

        $response->assertOk();
        $response->assertSee('admin-saved-search');
        $response->assertDontSee('other-user-search');
        $response->assertDontSee('woSearchInput');
        $response->assertDontSee('woCustomerFilter');
        $response->assertDontSee('woTechnikFilter');
        $response->assertDontSee('myWorkordersCheckbox');
    }
}
