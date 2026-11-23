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
use App\Models\Workorder;
use App\Models\WorkStage;
use App\Services\Workorders\WorkorderVisibilityService;
use Illuminate\Support\Facades\Bus;
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
        $response->assertSee('workorders-customer-filter-wrapper', false);
        $response->assertSee('workorders-technik-filter-wrapper', false);
        $response->assertSee('initCustomerSelect2', false);
        $response->assertSee('initTechnikSelect2', false);
        $response->assertSee("'- All customers -'", false);
        $response->assertSee("'- All technicians -'", false);
        $response->assertSee("theme: 'bootstrap-5'", false);
        $response->assertSee('dropdownParent: $dropdownParent', false);
        $response->assertSee('select2:select.workordersFilterBridge', false);
        $response->assertSee("dispatchEvent(new Event('change'", false);
        $response->assertSee('handleTechnikFilterChange', false);
        $response->assertSee('handleCustomerFilterChange', false);
        $response->assertSee("select2:open", false);
        $response->assertSee('workorders-filter-select-dropdown', false);
        $response->assertSee('width: max-content', false);
        $response->assertSee('min-width: 100%', false);
        $response->assertSee('max-height: min(440px', false);
        $response->assertSee('white-space: nowrap', false);
        $response->assertSee('overscroll-behavior: contain', false);
        $response->assertSee('workorder-description-tooltip', false);
        $response->assertSee('data-bs-title="Visible workorder"', false);
        $response->assertSee('const tooltipText', false);
        $response->assertSee('delay: { show: 700, hide: 100 }', false);
        $response->assertDontSee('data-bs-title=""', false);
        $response->assertDontSee(' title="Visible workorder"', false);
        $response->assertDontSee('dropdownCssClass', false);
    }

    public function test_workorders_index_displays_open_date_in_project_format(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'open_at' => '2026-12-01',
        ]);

        $response = $this->actingAs($admin)->get(route('workorders.index'));

        $response->assertOk();
        $response->assertSee((string) $workorder->number);
        $response->assertSee('01/Dec/2026');
        $response->assertDontSee('01.12.2026');
    }

    public function test_review_accounts_are_absent_from_workorder_technician_selects(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician');
        $appReview = $this->createUserWithRole('Team Leader', [
            'name' => 'Apple Review Account',
            'email' => 'appreview@aviatechnik.ca',
        ]);
        $googleReview = $this->createUserWithRole('Team Leader', [
            'name' => 'Google Review Account',
            'email' => 'googleview@avIatechnik.ca',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $appReview->id,
        ]);

        foreach ([route('workorders.index'), route('workorders.create')] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertViewHas('users', function ($users) use ($technician, $appReview, $googleReview): bool {
                    $ids = $users->pluck('id')->map(fn ($id): int => (int) $id);

                    return $ids->contains($technician->id)
                        && ! $ids->contains($appReview->id)
                        && ! $ids->contains($googleReview->id);
                });
        }

        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertViewHas('users', fn ($users): bool => ! $users->contains('id', $appReview->id))
            ->assertSee('value="'.$appReview->id.'" selected hidden', false);
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
        $response->assertSee('data-workorder-delete-button', false);
        $this->assertStringContainsString(
            'data-form-id="deleteForm_' . $draft->id . '"',
            (string) $response->json('html')
        );
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

    public function test_number_only_filter_does_not_match_workorder_description(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 100677,
        ]);
        $containsButDoesNotEndWithSearch = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 106779,
        ]);
        $descriptionOnlyMatch = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 654321,
            'description' => 'Contains 0677 but is not the number',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'number_only' => 1,
            'q' => '0677',
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $matching->number);
        $response->assertDontSee((string) $containsButDoesNotEndWithSearch->number);
        $response->assertDontSee((string) $descriptionOnlyMatch->number);
    }

    public function test_quick_open_search_control_is_visible_for_allowed_email_and_system_admin(): void
    {
        $allowed = $this->createUserWithRole('Admin', [
            'email' => 'vkyushkevich@yahoo.ca',
        ]);
        $systemAdmin = $this->createUserWithRole('Admin', [
            'email' => 'system.admin.' . uniqid() . '@example.test',
            'is_admin' => true,
        ]);
        $adminWithoutFlag = $this->createUserWithRole('Admin', [
            'email' => 'limited.admin.' . uniqid() . '@example.test',
            'is_admin' => false,
        ]);
        $manager = $this->createUserWithRole('Manager');

        $allowedResponse = $this->actingAs($allowed)->get(route('workorders.index'));

        $allowedResponse->assertOk();
        $allowedResponse->assertSee('For Slava:', false);
        $allowedResponse->assertSee('id="quickOpenSearchInput"', false);
        $allowedResponse->assertSee('id="clearQuickOpenSearch"', false);
        $allowedResponse->assertDontSee('id="quickOpenSearchButton"', false);
        $allowedResponse->assertDontSee('id="quickOpenSearchStatus"', false);
        $allowedResponse->assertDontSee('Open WO...', false);

        $systemAdminResponse = $this->actingAs($systemAdmin)->get(route('workorders.index'));

        $systemAdminResponse->assertOk();
        $systemAdminResponse->assertSee('For Slava:', false);
        $systemAdminResponse->assertSee('id="quickOpenSearchInput"', false);
        $systemAdminResponse->assertSee('id="clearQuickOpenSearch"', false);

        $adminWithoutFlagResponse = $this->actingAs($adminWithoutFlag)->get(route('workorders.index'));

        $adminWithoutFlagResponse->assertOk();
        $adminWithoutFlagResponse->assertDontSee('For Slava:', false);
        $adminWithoutFlagResponse->assertDontSee('id="quickOpenSearchInput"', false);
        $adminWithoutFlagResponse->assertDontSee('id="clearQuickOpenSearch"', false);

        $managerResponse = $this->actingAs($manager)->get(route('workorders.index'));

        $managerResponse->assertOk();
        $managerResponse->assertDontSee('For Slava:', false);
        $managerResponse->assertDontSee('id="quickOpenSearchInput"', false);
        $managerResponse->assertDontSee('id="clearQuickOpenSearch"', false);
    }

    public function test_quick_open_search_returns_mains_url_when_exactly_one_workorder_matches(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'email' => 'vkyushkevich@yahoo.ca',
        ]);
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 765432,
            'description' => 'Quick open exact match',
        ]);
        $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 765499,
            'description' => 'Quick open other match',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.quick-open-search', [
            'q' => '765432',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('url', route('mains.show', $matching->id));
        $response->assertJsonPath('workorder.number', '765432');
    }

    public function test_quick_open_search_accepts_w_prefixed_workorder_number(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'email' => 'vkyushkevich@yahoo.ca',
        ]);
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 765432,
            'description' => 'Quick open with W prefix',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.quick-open-search', [
            'q' => 'W765432',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('url', route('mains.show', $matching->id));
        $response->assertJsonPath('workorder.number', '765432');
    }

    public function test_quick_open_search_uses_number_suffix_and_opens_highest_matching_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'email' => 'vkyushkevich@yahoo.ca',
        ]);
        $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 100677,
        ]);
        $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 110677,
        ]);
        $highestMatching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 120677,
        ]);
        $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 106779,
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.quick-open-search', [
            'q' => '0677',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('count', 3);
        $response->assertJsonPath('url', route('mains.show', $highestMatching->id));
        $response->assertJsonPath('workorder.number', '120677');
    }

    public function test_quick_open_search_matches_workorder_number_only(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'email' => 'vkyushkevich@yahoo.ca',
        ]);
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 765432,
        ]);
        $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 123456,
            'description' => 'Contains 765432 but is not the number',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.quick-open-search', [
            'q' => '765432',
        ]));

        $response->assertOk();
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('url', route('mains.show', $matching->id));
        $response->assertJsonPath('workorder.number', '765432');
    }

    public function test_quick_open_search_endpoint_is_forbidden_for_other_emails(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.quick-open-search', [
            'q' => '765432',
        ]));

        $response->assertForbidden();
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

    public function test_visibility_service_hides_fifth_general_task_for_shop_roles(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $teamLeader = $this->createUserWithRole('Team Leader');
        $manager = $this->createUserWithRole('Manager');

        $generalTasks = collect(range(1, 5))->map(fn (int $position): GeneralTask => new GeneralTask([
            'name' => 'Visibility Stage ' . $position,
            'sort_order' => $position,
        ]));

        $service = app(WorkorderVisibilityService::class);

        $this->assertSame(
            ['Visibility Stage 1', 'Visibility Stage 2', 'Visibility Stage 3', 'Visibility Stage 4'],
            $service->filterVisibleGeneralTasks($generalTasks, $technician)->pluck('name')->all()
        );
        $this->assertSame(
            ['Visibility Stage 1', 'Visibility Stage 2', 'Visibility Stage 3', 'Visibility Stage 4'],
            $service->filterVisibleGeneralTasks($generalTasks, $teamLeader)->pluck('name')->all()
        );
        $this->assertSame(
            ['Visibility Stage 1', 'Visibility Stage 2', 'Visibility Stage 3', 'Visibility Stage 4', 'Visibility Stage 5'],
            $service->filterVisibleGeneralTasks($generalTasks, $manager)->pluck('name')->all()
        );
    }

    public function test_shop_roles_active_filter_hides_submitted_final_inspection_workorders(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');
        $teamLeader = $this->createUserWithRole('Team Leader');

        $finalStage = GeneralTask::query()->create([
            'name' => 'Final inspection visibility stage',
            'sort_order' => 98,
        ]);
        $submittedTask = Task::query()->create([
            'name' => '2. WO Submitted for Final Inspection',
            'general_task_id' => $finalStage->id,
            'task_has_start_date' => false,
        ]);
        $regular = $this->createWorkorder([
            'user_id' => $technician->id,
            'number' => 108201,
        ]);
        $submitted = $this->createWorkorder([
            'user_id' => $technician->id,
            'number' => 108202,
        ]);
        $completed = $this->createWorkorder([
            'user_id' => $technician->id,
            'number' => 108203,
        ]);
        $completed->forceFill(['done_at' => '2026-05-05', 'done_user_id' => $manager->id])->save();

        $oldTask = Task::create(['name' => 'WO Submitted for Final Inspection', 'general_task_id' => $finalStage->id]);
        $oldTask->forceFill(['archived_at' => now()])->save();
        Main::create([
            'workorder_id' => $submitted->id,
            'general_task_id' => $finalStage->id,
            'task_id' => $oldTask->id,
            'date_finish' => '2026-05-01',
        ]);
        $submission = Main::query()->create([
            'workorder_id' => $submitted->id,
            'general_task_id' => $finalStage->id,
            'task_id' => $submittedTask->id,
            'user_id' => $technician->id,
            'date_finish' => '2026-05-04',
            'ignore_row' => false,
        ]);
        $technicianResponse = $this->actingAs($technician)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_active' => 1,
        ]));

        $technicianResponse->assertOk();
        $technicianResponse->assertJsonPath('total_count', 1);
        $technicianResponse->assertSee((string) $regular->number);
        $technicianResponse->assertDontSee((string) $submitted->number);
        $technicianResponse->assertDontSee((string) $completed->number);
        $technicianResponse->assertDontSee('wo-stage no-print');

        $this->get(route('workorders.index', ['only_active' => 1]))
            ->assertOk()
            ->assertViewHas('showStages', false)
            ->assertViewHas('stageLabels', fn ($stages) => $stages->isEmpty())
            ->assertViewHas('workorders', fn ($rows) => collect($rows)->every(
                fn (Workorder $row) => !$row->relationLoaded('main')
            ));

        $managerResponse = $this->actingAs($manager)->withSession([
            'auth.version' => (int) $manager->auth_version,
            'password_hash_web' => $manager->getAuthPassword(),
        ])->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_active' => 1,
        ]));

        $managerResponse->assertOk();
        $managerResponse->assertJsonPath('total_count', 2);
        $managerResponse->assertSee((string) $regular->number);
        $managerResponse->assertSee((string) $submitted->number);
        $managerResponse->assertDontSee((string) $completed->number);
        $managerResponse->assertSee('Final Assembly & Test');

        $fragment = route('workorders.index', ['fragment' => 1]);
        foreach ([$manager, $technician, $teamLeader] as $user) {
            $response = $this->actingAs($user)->withSession([
                'auth.version' => $user->auth_version, 'password_hash_web' => $user->getAuthPassword(),
            ])->getJson($fragment)->assertOk();
            $html = $response->json('html');
            $this->assertStringContainsString('<span class="text-muted">108203</span>', $html);
            $this->assertSame(!$user->roleIs('Manager'), str_contains($html, '<span class="text-muted">108202</span>'));
        }

        $this->actingAs($manager)->withSession([
            'auth.version' => $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword(),
        ])->patchJson(route('mains.update', $submission), ['date_finish' => null])->assertOk();
        $this->actingAs($teamLeader)->withSession([
            'auth.version' => $teamLeader->auth_version, 'password_hash_web' => $teamLeader->getAuthPassword(),
        ])->getJson(route('workorders.index', ['fragment' => 1, 'only_active' => 1]))
            ->assertOk()->assertJsonPath('total_count', 2)->assertSee((string) $submitted->number);
    }

    public function test_saving_or_clearing_an_old_task_preserves_workorder_completion(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $wo->forceFill(['done_at' => '2026-05-05', 'done_user_id' => $admin->id])->save();
        $group = GeneralTask::create(['name' => 'Ordinary task group', 'sort_order' => 10]);
        $task = Task::create(['name' => 'Ordinary task', 'general_task_id' => $group->id, 'task_has_start_date' => false]);

        $saved = $this->postJson(route('mains.store'), [
            'workorder_id' => $wo->id, 'task_id' => $task->id, 'date_finish' => '2026-05-06',
        ])->assertOk();
        $this->assertSame('2026-05-05', $wo->fresh()->doneDate()->toDateString());
        $this->patchJson(route('mains.update', $saved->json('main_id')), ['date_finish' => null])->assertOk();
        $this->assertSame('2026-05-05', $wo->fresh()->doneDate()->toDateString());
        $this->assertSame($admin->id, (int) $wo->fresh()->done_user_id);
    }

    public function test_disassembly_requires_a_photo_outside_as_received(): void
    {
        Bus::fake();
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $query = ['fragment' => 1, 'q' => $wo->number];
        $stageCell = '<td class="wo-stage no-print text-start work-stage-color">';
        $assertStage = function (string $label) use ($query, $stageCell): void {
            $response = $this->getJson(route('workorders.index', $query))->assertOk();
            $this->assertTrue(str_contains($response->json('html'), $stageCell.e($label).'</td>'));
        };

        $assertStage('');
        $this->post(route('workorders.media.upload', $wo), [
            'group' => 'received', 'files' => [$this->makeUploadedImage('received.png')],
        ])->assertOk();
        $assertStage('');
        $filtered = route('workorders.index', $query + ['stages' => 'disassembly']);
        $this->getJson($filtered)->assertOk()->assertJsonPath('total_count', 0);

        $this->post(route('workorders.media.upload', $wo), [
            'group' => 'damages', 'files' => [$this->makeUploadedImage('damage.png')],
        ])->assertOk();
        $assertStage('Disassembly');
        $this->getJson($filtered)->assertOk()->assertJsonPath('total_count', 1);

        $photo = $wo->media()->where('collection_name', 'damages')->firstOrFail();
        $this->deleteJson(route('workorders.photo.delete', $photo->id))->assertOk();
        $assertStage('');
    }

    public function test_workorder_stage_follows_saved_dates_in_both_directions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $group = GeneralTask::create(['name' => 'Stage dates', 'sort_order' => 10]);
        $index = route('workorders.index', ['q' => $workorder->number]);
        $fragment = route('workorders.index', ['fragment' => 1, 'q' => $workorder->number]);
        $stages = [
            'WO Submitted to inspection' => '',
            'Post Disassembly inspection' => 'Wait Records',
            'Wait records' => 'In Quotation',
            'In Quotation' => 'Wait Approval',
            'Wait Approval' => 'Wait Approval',
            'approve_at' => 'OVH in Process',
            '2. WO Submitted for Final Inspection' => 'Final Assembly & Test',
            'Final Inspection' => 'Final Inspection',
        ];
        $assertStage = function (string $label) use ($fragment): void {
            $response = $this->getJson($fragment)->assertOk();
            $color = $label === 'OVH in Process' ? ' style="--work-stage-color: #cde1d4"' : '';
            $this->assertStringContainsString('class="wo-stage no-print text-start work-stage-color"'.$color.'>'.e($label).'</td>', $response->json('html'));
        };

        $this->get($index)->assertOk()->assertSee('col-stages no-print', false);
        $assertStage('');
        $rows = [];
        foreach ($stages as $name => $label) {
            if ($name === 'approve_at') {
                Workorder::whereKey($workorder->id)->update(['approve_at' => '2026-11-20 12:00:00']);
                $rows[] = null;
                $assertStage($label);
                continue;
            }

            $task = Task::create(['name' => $name, 'general_task_id' => $group->id, 'task_has_start_date' => false]);
            $saved = $this->postJson(route('mains.store'), [
                'workorder_id' => $workorder->id,
                'task_id' => $task->id,
                'date_finish' => '2026-11-20',
            ])->assertOk();
            $rows[] = $saved->json('main_id');
            $assertStage($label);
        }

        $expected = array_reverse(['', ...array_slice(array_values($stages), 0, -1)]);
        foreach (array_reverse($rows) as $index => $row) {
            if ($row === null) {
                Workorder::whereKey($workorder->id)->update(['approve_at' => null]);
            } else {
                $this->patchJson(route('mains.update', $row), ['date_finish' => null])->assertOk();
            }
            $assertStage($expected[$index]);
        }
    }

    public function test_workorder_stage_uses_catalog_label_and_legacy_dates_until_current_date_is_cleared(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $group = GeneralTask::create(['name' => 'Current inspections', 'sort_order' => 2]);
        $archived = GeneralTask::create(['name' => 'Legacy Final Test', 'sort_order' => 3]);
        $old = Task::create(['name' => '1. Final inspection', 'general_task_id' => $archived->id]);
        $old->forceFill(['archived_at' => now()])->save();
        $archived->forceFill(['catalog_removed_at' => now()])->save();
        $final = Task::create(['name' => 'Final Inspection', 'general_task_id' => $group->id]);
        Main::create([
            'workorder_id' => $wo->id, 'task_id' => $old->id, 'general_task_id' => $archived->id, 'date_finish' => '2026-11-01',
        ]);
        WorkStage::where('workflow_key', 'final_inspection')->update(['name' => 'Quality check complete', 'sort_order' => 0]);
        $url = route('workorders.index', ['fragment' => 1, 'q' => $wo->number]);
        $this->getJson($url)->assertOk()->assertSee('Quality check complete');

        $current = Main::create([
            'workorder_id' => $wo->id, 'task_id' => $final->id, 'general_task_id' => $group->id, 'date_finish' => '2026-11-12',
        ]);
        $this->patchJson(route('mains.update', $current), ['date_finish' => null])->assertOk();
        $emptyStage = $this->getJson($url)->assertOk()->assertDontSee('Quality check complete');
        $this->assertTrue(str_contains($emptyStage->json('html'), '<td class="wo-stage no-print text-start work-stage-color"></td>'));

        $this->postJson(route('workorders.approve.ajax', $wo), ['approve_date' => '2026-11-04'])
            ->assertOk()->assertJsonPath('work_stage', 'OVH in Process')->assertJsonPath('work_stage_color', '#cde1d4');
        $this->postJson(route('workorders.approve.ajax', $wo), ['approve_date' => null])
            ->assertOk()->assertJsonPath('work_stage', null)->assertJsonPath('work_stage_color', null);

        foreach (['Technician', 'Team Leader'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->withSession([
                'auth.version' => $user->auth_version, 'password_hash_web' => $user->getAuthPassword(),
            ]);
            $this->get(route('workorders.index'))->assertOk()->assertDontSee('col-stages no-print', false);
            $this->getJson($url)->assertOk()->assertDontSee('wo-stage no-print');
        }
    }

    public function test_stage_filter_selects_multiple_stages_before_workorder_pagination(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $this->actingAs($manager);
        $approved = $this->createWorkorder(['user_id' => $manager->id, 'approve_at' => '2026-11-19']);
        $shared = $approved->only(['user_id', 'unit_id', 'customer_id', 'instruction_id']);
        $inspection = $this->createWorkorder($shared);
        $group = GeneralTask::create(['name' => 'Inspection test', 'sort_order' => 20]);
        $task = Task::create(['name' => 'Final Inspection', 'general_task_id' => $group->id]);
        Main::create([
            'workorder_id' => $inspection->id, 'general_task_id' => $group->id, 'task_id' => $task->id, 'date_finish' => '2026-11-20',
        ]);
        foreach (range(1, 11) as $index) {
            $this->createWorkorder($shared);
        }

        $query = ['stages' => 'ovh_in_process,final_inspection,invalid', 'per_page' => 10];
        $this->get(route('workorders.index', $query))
            ->assertOk()->assertViewHas('selectedStages', fn ($stages) => $stages->all() === ['ovh_in_process', 'final_inspection']);
        $this->getJson(route('workorders.index', $query + ['fragment' => 1]))->assertOk()
            ->assertJsonPath('total_count', 2)
            ->assertJsonPath('loaded_count', 2)
            ->assertSee((string) $approved->number)
            ->assertSee((string) $inspection->number)
            ->assertDontSee('Disassembly');

        $technician = $this->createUserWithRole('Technician');
        $this->actingAs($technician)->withSession([
            'auth.version' => $technician->auth_version, 'password_hash_web' => $technician->getAuthPassword(),
        ])->getJson(route('workorders.index', $query + ['fragment' => 1]))->assertOk()
            ->assertJsonPath('total_count', 13)
            ->assertJsonPath('loaded_count', 10)
            ->assertDontSee('wo-stage no-print');
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

    public function test_tdr_part_search_finds_multiple_workorders_without_duplicates_and_keeps_filters(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $first = $this->createWorkorder(['user_id' => $admin->id]);
        $second = $this->createWorkorder(['user_id' => $admin->id]);
        $unrelated = $this->createWorkorder(['user_id' => $admin->id, 'serial_number' => 'TDR-ONLY-877']);
        $part = Component::query()->create([
            'manual_id' => $first->unit->manual_id, 'part_number' => 'TDR-ONLY-877',
            'assy_part_number' => 'ASSEMBLY-SEARCH-887', 'name' => 'Search part', 'ipl_num' => '1-10',
        ]);
        foreach ([$first, $first, $second] as $wo) {
            Tdr::query()->create(['workorder_id' => $wo->id, 'component_id' => $part->id,
                'serial_number' => 'SN-TDR-9989', 'assy_serial_number' => 'SN-ASSY-889', 'qty' => 1,
                'use_tdr' => true, 'use_process_forms' => true]);
        }
        // Archived manual parts must remain searchable in historical workorders.
        $otherPart = Component::query()->create([
            'manual_id' => $first->unit->manual_id, 'part_number' => 'UNMATCHED-PART',
            'name' => 'Not a search result', 'ipl_num' => '9-99',
        ]);
        Tdr::query()->create(['workorder_id' => $first->id, 'component_id' => $otherPart->id,
            'serial_number' => 'UNMATCHED-SERIAL', 'qty' => 1, 'use_tdr' => true]);
        $part->delete();
        foreach (['TDR-ONLY-877', 'ASSEMBLY-SEARCH-887', 'SN-TDR-9989', 'SN-ASSY-889'] as $term) {
            $response = $this->actingAs($admin)->getJson(route('workorders.index', ['tdr_q' => $term]));
            $response->assertOk()->assertJsonPath('total_count', 2)->assertJsonPath('loaded_count', 2);
            $this->assertStringContainsString('data-id="'.$first->id.'"', $response->json('html'));
            $this->assertStringContainsString('data-id="'.$second->id.'"', $response->json('html'));
            $this->assertStringNotContainsString('data-id="'.$unrelated->id.'"', $response->json('html'));
            $this->assertSame(3, substr_count($response->json('html'), 'data-matched-tdr='));
            foreach (['P/N: TDR-ONLY-877', 'Search part', 'S/N: SN-TDR-9989', 'IPL: 1-10',
                'ASSEMBLY-SEARCH-887', 'SN-ASSY-889'] as $detail) {
                $this->assertStringContainsString($detail, $response->json('html'));
            }
            $this->assertStringNotContainsString('UNMATCHED-PART', $response->json('html'));
            $this->assertStringContainsString(route('tdrs.show', $first->id), $response->json('html'));
        }
        $this->actingAs($admin)->get(route('workorders.index', ['tdr_q' => 'TDR-ONLY-877']))
            ->assertOk()->assertSee('data-matched-tdr=', false)->assertSee('Search part');
        $this->actingAs($admin)->getJson(route('workorders.index'))
            ->assertOk()->assertDontSee('data-matched-tdr=', false);
        $this->actingAs($admin)->getJson(route('workorders.index', [
            'tdr_q' => 'TDR-ONLY-877', 'q' => $first->number, 'number_only' => 1,
        ]))->assertOk()->assertJsonPath('total_count', 1);
        $this->actingAs($admin)->getJson(route('workorders.index', ['tdr_q' => 'NO-SUCH-TDR-PART']))
            ->assertOk()->assertJsonPath('total_count', 0);
    }

    public function test_tdr_search_treats_wildcards_literally_and_searches_beyond_first_page(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $part = Component::query()->create([
            'manual_id' => $this->createManual()->id, 'part_number' => 'PN%_SEARCH',
            'name' => 'Literal search part', 'ipl_num' => '1-10',
        ]);
        for ($i = 0; $i < 11; $i++) {
            $wo = $this->createWorkorder(['user_id' => $admin->id]);
            Tdr::query()->create(['workorder_id' => $wo->id, 'component_id' => $part->id,
                'serial_number' => 'LITERAL-SERIAL', 'qty' => 1, 'use_tdr' => true]);
        }
        $params = ['tdr_q' => '%_', 'per_page' => 10];
        $first = $this->actingAs($admin)->getJson(route('workorders.index', $params));
        $first->assertOk()->assertJsonPath('total_count', 11)->assertJsonPath('loaded_count', 10)->assertJsonPath('has_more', true);
        $this->actingAs($admin)->getJson(route('workorders.index', $params + ['cursor' => $first->json('next_cursor')]))
            ->assertOk()->assertJsonPath('total_count', 11)->assertJsonPath('loaded_count', 1)->assertJsonPath('has_more', false);
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
