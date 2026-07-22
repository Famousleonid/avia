<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Material;
use App\Models\MobileApiToken;
use App\Models\Necessary;
use App\Models\ProcessName;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Team;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_mobile_api_login_returns_bearer_token(): void
    {
        $user = $this->createUserWithRole('Manager');

        $response = $this->postJson(route('api.mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Feature Test iPhone',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);

        $plainToken = (string) $response->json('data.token');
        $this->assertNotSame('', $plainToken);
        $this->assertDatabaseHas('mobile_api_tokens', [
            'user_id' => $user->id,
            'token_hash' => MobileApiToken::hashPlainTextToken($plainToken),
        ]);
    }

    public function test_mobile_api_requires_bearer_token(): void
    {
        $response = $this->getJson(route('api.mobile.bootstrap'));

        $response->assertUnauthorized()
            ->assertJsonPath('ok', false);
    }

    public function test_mobile_api_validation_errors_use_api_envelope(): void
    {
        $response = $this->postJson(route('api.mobile.auth.login'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_mobile_api_bootstrap_returns_capabilities_and_photo_policy(): void
    {
        $user = $this->createUserWithRole('Manager');

        $response = $this->withMobileToken($user)
            ->getJson(route('api.mobile.bootstrap'));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.capabilities.can_create_draft', true)
            ->assertJsonPath('data.user.capabilities.can_update_storage', true)
            ->assertJsonPath('data.menu_mode', 'workorders')
            ->assertJsonPath('data.available_menu_modes.1', 'paint')
            ->assertJsonPath('data.available_menu_modes.2', 'machining')
            ->assertJsonPath('data.offline_mode', false)
            ->assertJsonPath('data.photo_upload.compress_on_client', false)
            ->assertJsonPath('data.photo_upload.queue_on_client', true)
            ->assertJsonPath('data.photo_upload.delete_local_after_success', true)
            ->assertJsonPath('data.display_date_format', 'dd/mmm/yyyy')
            ->assertJsonPath('data.navigation.top_menu_modes.paint.0.key', 'wo')
            ->assertJsonPath('data.navigation.top_menu_modes.paint.1.key', 'lost')
            ->assertJsonPath('data.navigation.available_sections.3.key', 'paint')
            ->assertJsonPath('data.screens.draft_create.visible_flags.0', 'external_damage')
            ->assertJsonPath('data.screens.draft_create.visible_flags.1', 'nameplate_missing')
            ->assertJsonPath('data.screens.draft_create.pending_unit_quick_fields.0', 'part_number')
            ->assertJsonPath('data.screens.workorder_parts.component_edit_fields.5', 'log_card');
    }

    public function test_mobile_api_public_app_config_returns_launch_and_login_metadata(): void
    {
        $response = $this->getJson(route('api.mobile.public.app-config'));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.app.theme', 'dark')
            ->assertJsonPath('data.auth.login_title', 'Login')
            ->assertJsonPath('data.auth.remember_me_supported', true)
            ->assertJsonPath('data.auth.remember_me_mode', 'client_token_persistence')
            ->assertJsonPath('data.auth.forgot_password_supported', true)
            ->assertJsonPath('data.auth.forgot_password_url', route('password.request'))
            ->assertJsonPath('data.launch.initial_route', 'login');
    }

    public function test_mobile_api_paint_and_machining_indexes_return_native_metadata(): void
    {
        $paintUser = $this->createUserWithRole('Paint');
        $machiningUser = $this->createUserWithRole('Machining');

        $paintResponse = $this->withMobileToken($paintUser)
            ->getJson(route('api.mobile.paint.index'));

        $paintResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.menu_mode', 'paint')
            ->assertJsonPath('data.top_menu.1.key', 'lost')
            ->assertJsonPath('data.tabs.0.key', 'wo')
            ->assertJsonPath('data.tabs.1.key', 'lost');

        $machiningResponse = $this->withMobileToken($machiningUser)
            ->getJson(route('api.mobile.machining.index', ['my_wo' => 1]));

        $machiningResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.menu_mode', 'machining')
            ->assertJsonPath('data.top_menu.1.key', 'my_wo')
            ->assertJsonPath('data.my_wo', true);
    }

    public function test_mobile_api_can_create_and_delete_paint_lost_part(): void
    {
        $paintUser = $this->createUserWithRole('Paint');

        $createResponse = $this->withMobileToken($paintUser)
            ->post(route('api.mobile.paint.lost.store'), [
                'part_number' => 'LOST-PN',
                'serial_number' => 'LOST-SN',
                'comment' => 'Lost part from mobile API',
                'photo' => UploadedFile::fake()->image('lost.png'),
            ], ['Accept' => 'application/json']);

        $createResponse->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.lost_part.part_number', 'LOST-PN');

        $lostId = (int) $createResponse->json('data.lost_part.id');

        $deleteResponse = $this->withMobileToken($paintUser)
            ->deleteJson(route('api.mobile.paint.lost.destroy', $lostId));

        $deleteResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.id', $lostId);
    }

    public function test_mobile_api_logout_revokes_current_token(): void
    {
        $user = $this->createUserWithRole('Technician');
        $plainToken = $this->makeMobileToken($user);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->postJson(route('api.mobile.auth.logout'));

        $logoutResponse->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('mobile_api_tokens', [
            'token_hash' => MobileApiToken::hashPlainTextToken($plainToken),
        ]);

        $bootstrapResponse = $this->withHeader('Authorization', 'Bearer ' . $plainToken)
            ->getJson(route('api.mobile.bootstrap'));

        $bootstrapResponse->assertUnauthorized()
            ->assertJsonPath('ok', false);
    }

    public function test_mobile_api_workorders_returns_current_user_workorders(): void
    {
        $user = $this->createUserWithRole('Technician');
        $own = $this->createWorkorder(['user_id' => $user->id, 'number' => 812345]);
        $otherUser = $this->createUserWithRole('Technician');
        $this->createWorkorder(['user_id' => $otherUser->id, 'number' => 812346]);

        $response = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.index'));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.items.0.id', $own->id)
            ->assertJsonPath('data.items.0.owned_by_current_user', true);

        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertContains($own->id, $ids);
    }

    public function test_mobile_api_done_scope_returns_only_done_workorders(): void
    {
        $user = $this->createUserWithRole('Technician');
        $open = $this->createWorkorder(['user_id' => $user->id, 'number' => 812347]);
        $done = $this->createWorkorder(['user_id' => $user->id, 'number' => 812348]);

        $generalTask = GeneralTask::query()->create([
            'name' => 'Completion ' . uniqid(),
            'sort_order' => 999,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => false,
        ]);
        Main::query()->create([
            'user_id' => $user->id,
            'workorder_id' => $done->id,
            'general_task_id' => $generalTask->id,
            'task_id' => $completedTask->id,
            'date_finish' => now()->toDateString(),
            'ignore_row' => false,
        ]);

        $response = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.index', ['scope' => 'done']));

        $response->assertOk()
            ->assertJsonPath('ok', true);

        $ids = collect($response->json('data.items'))->pluck('id')->all();
        $this->assertContains($done->id, $ids);
        $this->assertNotContains($open->id, $ids);
    }

    public function test_shipping_role_can_update_workorder_storage(): void
    {
        $shipper = $this->createUserWithRole('Shipping');
        $workorder = $this->createWorkorder(['user_id' => $shipper->id]);

        $response = $this->withMobileToken($shipper)
            ->patchJson(route('api.mobile.workorders.storage.update', $workorder->id), [
                'storage_rack' => 1,
                'storage_level' => 2,
                'storage_column' => 3,
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.storage.location', 'Rack: 1 _ Level: 2 _ Column: 3');

        $this->assertDatabaseHas('workorders', [
            'id' => $workorder->id,
            'storage_rack' => 1,
            'storage_level' => 2,
            'storage_column' => 3,
        ]);
    }

    public function test_shipping_role_can_update_workorder_arrival_box(): void
    {
        $shipper = $this->createUserWithRole('Shipping');
        $workorder = $this->createWorkorder(['user_id' => $shipper->id]);

        $response = $this->withMobileToken($shipper)
            ->patchJson(route('api.mobile.workorders.arrival-box.update', $workorder->id), [
                'arrival_box_status' => 'easy',
                'arrival_box_notes' => 'Lid latch bent',
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.arrival_box.status', 'easy')
            ->assertJsonPath('data.arrival_box.status_label', 'Light repair')
            ->assertJsonPath('data.arrival_box.notes', 'Lid latch bent')
            ->assertJsonPath('data.arrival_box.can_update', true);

        $this->assertDatabaseHas('workorders', [
            'id' => $workorder->id,
            'arrival_box_status' => 'easy',
            'arrival_box_notes' => 'Lid latch bent',
            'arrival_box_recorded_by' => $shipper->id,
        ]);
        $this->assertNotNull($workorder->fresh()->arrival_box_recorded_at);
    }

    public function test_technician_cannot_update_storage_or_create_draft(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $storageResponse = $this->withMobileToken($user)
            ->patchJson(route('api.mobile.workorders.storage.update', $workorder->id), [
                'storage_rack' => 1,
            ]);

        $storageResponse->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Forbidden.');

        $arrivalBoxResponse = $this->withMobileToken($user)
            ->patchJson(route('api.mobile.workorders.arrival-box.update', $workorder->id), [
                'arrival_box_status' => 'medium',
            ]);

        $arrivalBoxResponse->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Forbidden.');

        $draftResponse = $this->withMobileToken($user)
            ->postJson(route('api.mobile.drafts.store'), [
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
            ]);

        $draftResponse->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Forbidden.');
    }

    public function test_mobile_api_can_create_draft_with_draft_instruction(): void
    {
        $shipper = $this->createUserWithRole('Shipping');
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $response = $this->withMobileToken($shipper)
            ->postJson(route('api.mobile.drafts.store'), [
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'serial_number' => 'SN-DRAFT-API',
                'description' => 'API Draft',
                'open_at' => '10.aug.2026',
                'customer_po' => 'PO-API',
                'external_damage' => true,
                'storage_rack' => 7,
                'storage_level' => 8,
                'storage_column' => 9,
                'arrival_box_status' => 'replace',
                'arrival_box_notes' => 'Corner dented',
            ]);

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.workorder.is_draft', true)
            ->assertJsonPath('data.workorder.serial_number', 'SN-DRAFT-API');

        $workorderId = (int) $response->json('data.workorder.id');
        $this->assertDatabaseHas('workorders', [
            'id' => $workorderId,
            'is_draft' => 1,
            'description' => 'API Draft',
            'arrival_box_status' => 'replace',
            'arrival_box_notes' => 'Corner dented',
            'arrival_box_recorded_by' => $shipper->id,
        ]);
        $workorder = Workorder::withDrafts()->findOrFail($workorderId);
        $this->assertSame('Draft', $workorder->instruction?->name);
        $this->assertNotNull($workorder->arrival_box_recorded_at);
        $this->assertSame('API Draft', $unit->fresh()->name);
    }

    public function test_mobile_api_can_create_and_reuse_pending_draft_unit(): void
    {
        $shipper = $this->createUserWithRole('Shipping');

        $createResponse = $this->withMobileToken($shipper)
            ->postJson(route('api.mobile.draft-units.store'), [
                'part_number' => 'PENDING-API',
                'name' => 'Pending API Unit',
                'description' => 'Pending unit from iOS',
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.unit.part_number', 'PENDING-API')
            ->assertJsonPath('data.unit.manual_id', null)
            ->assertJsonPath('data.unit.verified', true);

        $unitId = (int) $createResponse->json('data.unit.id');

        $reuseResponse = $this->withMobileToken($shipper)
            ->postJson(route('api.mobile.draft-units.store'), [
                'part_number' => 'PENDING-API',
                'name' => 'Different ignored name',
            ]);

        $reuseResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.unit.id', $unitId);

        $this->assertSame(1, Unit::query()
            ->whereNull('manual_id')
            ->where('part_number', 'PENDING-API')
            ->count());
    }

    public function test_mobile_api_can_update_regular_task_dates_and_blocks_restricted_finish_for_technician(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $generalTask = GeneralTask::query()->create([
            'name' => 'API Task Group ' . uniqid(),
            'sort_order' => 5,
        ]);
        $regularTask = Task::query()->create([
            'name' => 'API Regular Task ' . uniqid(),
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => true,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => false,
        ]);

        $updateResponse = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $regularTask->id]), [
                'date_start' => '2026-05-25',
                'date_finish' => '2026-05-26',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.main.task_id', $regularTask->id)
            ->assertJsonPath('data.main.date_start', '2026-05-25')
            ->assertJsonPath('data.main.date_finish', '2026-05-26');

        $restrictedResponse = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $completedTask->id]), [
                'date_finish' => '2026-05-27',
            ]);

        $restrictedResponse->assertForbidden()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Forbidden.');
    }

    public function test_mobile_api_task_dates_expose_explicit_permissions_and_preserve_dates_when_toggling_ignore_row(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $group = GeneralTask::query()->create(['name' => 'Mobile dates ' . uniqid(), 'sort_order' => 5]);
        $task = Task::query()->create([
            'name' => 'Mobile editable task ' . uniqid(),
            'general_task_id' => $group->id,
            'task_has_start_date' => true,
        ]);

        $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $task->id]), [
                'date_start' => '2026-07-03',
                'date_finish' => '2026-07-19',
            ])
            ->assertOk();

        $ignoreResponse = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $task->id]), ['ignore_row' => true]);

        $ignoreResponse->assertOk()
            ->assertJsonPath('data.main.ignore_row', true)
            ->assertJsonPath('data.main.date_start', '2026-07-03')
            ->assertJsonPath('data.main.date_finish', '2026-07-19');

        $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $task->id]), [
                'date_start' => null,
                'date_finish' => null,
                'ignore_row' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.main.date_start', null)
            ->assertJsonPath('data.main.date_finish', null)
            ->assertJsonPath('data.main.ignore_row', false);

        $tasksResponse = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.tasks.index', $workorder->id));
        $taskPayload = collect($tasksResponse->json('data.groups'))
            ->flatMap(fn (array $item) => $item['tasks'])
            ->firstWhere('id', $task->id);

        $this->assertTrue($taskPayload['has_start_date']);
        $this->assertTrue($taskPayload['can_edit_start']);
        $this->assertTrue($taskPayload['can_edit_finish']);
        $this->assertFalse($taskPayload['restricted_finish']);
    }

    public function test_mobile_api_blocks_quote_submission_dates_for_technician_and_team_leader(): void
    {
        $group = GeneralTask::query()->create(['name' => 'Quote ' . uniqid(), 'sort_order' => 7]);
        $task = Task::query()->create([
            'name' => 'WO Submitted for Quate',
            'general_task_id' => $group->id,
            'task_has_start_date' => true,
        ]);

        foreach (['Technician', 'Team Leader'] as $role) {
            $user = $this->createUserWithRole($role);
            $workorder = $this->createWorkorder(['user_id' => $user->id]);

            $this->withMobileToken($user)
                ->putJson(route('api.mobile.workorders.tasks.dates', [$workorder->id, $task->id]), [
                    'date_start' => '2026-07-03',
                    'date_finish' => '2026-07-19',
                ])
                ->assertForbidden();

            $response = $this->withMobileToken($user)
                ->getJson(route('api.mobile.workorders.tasks.index', $workorder->id));
            $payload = collect($response->json('data.groups'))
                ->flatMap(fn (array $item) => $item['tasks'])
                ->firstWhere('id', $task->id);

            $this->assertFalse($payload['can_edit_start']);
            $this->assertFalse($payload['can_edit_finish']);
            $this->assertSame('manager_only_quote_submission_dates', $payload['restriction_code']);
            $this->assertFalse($payload['main']['ignore_row']);
        }
    }

    public function test_mobile_api_components_attach_and_process_dates_flow(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '10-20',
            'part_number' => 'PN-API',
            'name' => 'API Component',
        ]);
        $missingCode = Code::query()->create([
            'name' => 'Missing',
            'code' => 'MIS',
        ]);
        Necessary::query()->create(['name' => 'Repair']);

        $attachResponse = $this->withMobileToken($user)
            ->postJson(route('api.mobile.workorders.component-attachments.store', $workorder->id), [
                'component_id' => $component->id,
                'code_id' => $missingCode->id,
                'qty' => 4,
            ]);

        $attachResponse->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.attachment.qty', 4)
            ->assertJsonPath('data.attachment.use_tdr', false)
            ->assertJsonPath('data.attachment.use_process_forms', false);

        $tdr = Tdr::query()->findOrFail((int) $attachResponse->json('data.attachment.id'));

        $componentsResponse = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.components.index', $workorder->id));
        $componentsResponse->assertOk()
            ->assertJsonPath('data.attached_components.0.ipl_num', '10-20')
            ->assertJsonPath('data.attached_components.0.part_number', 'PN-API')
            ->assertJsonPath('data.attached_components.0.tdrs.0.id', $tdr->id)
            ->assertJsonPath('data.attached_components.0.tdrs.0.qty', 4);
        $processName = ProcessName::query()->create([
            'name' => 'API Process ' . uniqid(),
            'process_sheet_name' => 'API',
            'form_number' => 'API',
            'sequence_exempt' => true,
        ]);
        $process = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
        ]);

        $indexResponse = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.processes.index', $workorder->id));

        $indexResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.components.0.processes.0.id', $process->id);

        $dateResponse = $this->withMobileToken($user)
            ->patchJson(route('api.mobile.tdr-processes.dates.update', $process->id), [
                'date_start' => '2026-05-25',
            ]);

        $dateResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.process.date_start', '2026-05-25');
    }

    public function test_mobile_api_process_date_permissions_are_explicit_and_enforced(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '12-34',
            'part_number' => 'PN-PROCESS',
            'name' => 'Process component',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
        ]);
        $editableName = ProcessName::query()->create([
            'name' => 'Machining',
            'process_sheet_name' => 'MACHINING',
            'form_number' => 'M-1',
            'sequence_exempt' => true,
        ]);
        $lockedName = ProcessName::query()->create([
            'name' => 'NDT-1',
            'process_sheet_name' => 'NDT',
            'form_number' => 'NDT-1',
            'sequence_exempt' => true,
        ]);
        $editable = TdrProcess::query()->create(['tdrs_id' => $tdr->id, 'process_names_id' => $editableName->id]);
        $locked = TdrProcess::query()->create(['tdrs_id' => $tdr->id, 'process_names_id' => $lockedName->id]);

        $index = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.processes.index', $workorder->id));
        $processes = collect($index->json('data.components.0.processes'))->keyBy('id');

        $this->assertTrue($processes[$editable->id]['can_edit_start']);
        $this->assertTrue($processes[$editable->id]['can_edit_finish']);
        $this->assertTrue($processes[$editable->id]['can_edit_promise']);
        $this->assertFalse($processes[$locked->id]['can_edit_start']);
        $this->assertFalse($processes[$locked->id]['can_edit_finish']);

        $this->withMobileToken($user)
            ->patchJson(route('api.mobile.tdr-processes.dates.update', $editable->id), [
                'date_start' => '2026-07-03',
                'date_finish' => '2026-07-19',
            ])
            ->assertOk();

        $this->withMobileToken($user)
            ->patchJson(route('api.mobile.tdr-processes.dates.update', $editable->id), ['date_start' => null])
            ->assertOk()
            ->assertJsonPath('data.process.date_start', null);

        $this->withMobileToken($user)
            ->patchJson(route('api.mobile.tdr-processes.dates.update', $locked->id), ['date_finish' => '2026-07-19'])
            ->assertForbidden();
    }

    public function test_mobile_api_review_account_is_limited_to_configured_synthetic_workorders(): void
    {
        $reviewUser = $this->createUserWithRole('Team Leader', ['email' => 'review@example.test']);
        $demo = $this->createWorkorder(['number' => 100500, 'user_id' => $reviewUser->id]);
        $production = $this->createWorkorder(['number' => 100501]);
        config()->set('mobile_review.accounts', [
            'review@example.test' => ['workorder_numbers' => [100500]],
        ]);

        $bootstrap = $this->withMobileToken($reviewUser)
            ->getJson(route('api.mobile.bootstrap'));
        $bootstrap->assertOk()
            ->assertJsonPath('data.user.capabilities.can_view_all_workorders', false)
            ->assertJsonPath('data.user.capabilities.can_view_done_workorders', false);

        $list = $this->withMobileToken($reviewUser)
            ->getJson(route('api.mobile.workorders.index', ['scope' => 'all']));
        $list->assertOk();
        $this->assertSame([$demo->id], collect($list->json('data.items'))->pluck('id')->all());

        $this->withMobileToken($reviewUser)
            ->getJson(route('api.mobile.workorders.show', $production->id))
            ->assertNotFound();
        $this->withMobileToken($reviewUser)
            ->getJson(route('api.mobile.workorders.tasks.index', $production->id))
            ->assertNotFound();
    }

    public function test_public_privacy_and_support_pages_do_not_require_login(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
        $this->get('/support')->assertOk()->assertSee('Aviatechnik App Support');
    }

    public function test_mobile_api_can_update_component_log_card_flag(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '11-22',
            'part_number' => 'PN-LOG-CARD',
            'name' => 'Log Card Component',
            'log_card' => false,
        ]);

        $response = $this->withMobileToken($user)
            ->patchJson(route('api.mobile.components.update', $component->id), [
                'log_card' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.component.id', $component->id)
            ->assertJsonPath('data.component.log_card', true);

        $this->assertDatabaseHas('components', [
            'id' => $component->id,
            'log_card' => 1,
        ]);
    }

    public function test_mobile_api_materials_can_be_searched_and_updated(): void
    {
        $user = $this->createUserWithRole('Technician');
        $material = Material::query()->create([
            'code' => 'MAT-API',
            'material' => 'Titanium',
            'specification' => 'AMS-API',
            'description' => 'Old',
        ]);

        $listResponse = $this->withMobileToken($user)
            ->getJson(route('api.mobile.materials.index', ['search' => 'Titanium']));

        $listResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.items.0.id', $material->id);

        $updateResponse = $this->withMobileToken($user)
            ->patchJson(route('api.mobile.materials.update', $material->id), [
                'description' => 'New description',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.material.description', 'New description');
    }

    public function test_mobile_api_profile_can_be_read_updated_and_password_changed(): void
    {
        $user = $this->createUserWithRole('Technician');
        $team = Team::query()->create(['name' => 'Mobile API Team ' . uniqid()]);

        $profileResponse = $this->withMobileToken($user)
            ->getJson(route('api.mobile.profile.show'));

        $profileResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.profile.id', $user->id);

        $updateResponse = $this->withMobileToken($user)
            ->putJson(route('api.mobile.profile.update'), [
                'name' => 'Mobile API Name',
                'phone' => '123 456',
                'birthday' => '10.aug.2000',
                'stamp' => 'MAPI',
                'team_id' => $team->id,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.profile.name', 'Mobile API Name')
            ->assertJsonPath('data.profile.phone', '123456')
            ->assertJsonPath('data.profile.birthday', '2000-08-10')
            ->assertJsonPath('data.profile.team.id', $team->id);

        $passwordResponse = $this->withMobileToken($user->fresh())
            ->postJson(route('api.mobile.profile.password.update'), [
                'old_pass' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $passwordResponse->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    private function makeMobileToken($user): string
    {
        $plain = 'test-token-' . uniqid('', true);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Feature test',
            'token_hash' => MobileApiToken::hashPlainTextToken($plain),
        ]);

        return $plain;
    }

    private function withMobileToken($user): self
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->makeMobileToken($user));
    }
}
