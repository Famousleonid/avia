<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\GeneralTask;
use App\Models\LogCard;
use App\Models\ManualPartGroup;
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
            'name' => 'Machining',
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

    public function test_mobile_api_can_create_and_edit_workorder_tdrs(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '32-10',
            'part_number' => 'PN-MOBILE-TDR',
            'name' => 'Mobile TDR Component',
        ]);
        $repairCode = Code::query()->create(['name' => 'Repair', 'code' => 'RPR']);
        $repairNecessary = Necessary::query()->create(['name' => 'Repair']);
        $orderNewNecessary = Necessary::query()->create(['name' => 'Order New']);

        $components = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.components.index', $workorder->id));
        $components->assertOk()
            ->assertJsonPath('data.can_create_tdr', true)
            ->assertJsonPath('data.can_update_tdr', true)
            ->assertJsonPath('data.can_delete_tdr', false)
            ->assertJsonFragment(['id' => $component->id, 'name' => 'Mobile TDR Component'])
            ->assertJsonFragment(['id' => $repairCode->id, 'name' => 'Repair'])
            ->assertJsonFragment(['id' => $orderNewNecessary->id, 'name' => 'Order New']);

        $create = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), [
                'component_id' => $component->id,
                'code_id' => $repairCode->id,
                'necessaries_id' => $repairNecessary->id,
                'qty' => 2,
                'serial_number' => 'MOBILE-001',
            ]);
        $create->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.tdr.component_id', $component->id)
            ->assertJsonPath('data.tdr.code_id', $repairCode->id)
            ->assertJsonPath('data.tdr.code_name', 'Repair')
            ->assertJsonPath('data.tdr.necessaries_id', $repairNecessary->id)
            ->assertJsonPath('data.tdr.necessaries_name', 'Repair')
            ->assertJsonPath('data.tdr.qty', 2)
            ->assertJsonPath('data.tdr.serial_number', 'MOBILE-001');

        $tdrId = (int) $create->json('data.tdr.id');
        $reload = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.components.index', $workorder->id));
        $reload->assertOk()
            ->assertJsonPath('data.attached_components.0.id', $component->id)
            ->assertJsonPath('data.attached_components.0.name', 'Mobile TDR Component')
            ->assertJsonPath('data.attached_components.0.ipl_num', '32-10')
            ->assertJsonPath('data.attached_components.0.part_number', 'PN-MOBILE-TDR')
            ->assertJsonPath('data.attached_components.0.tdrs.0.id', $tdrId)
            ->assertJsonPath('data.attached_components.0.tdrs.0.code_name', 'Repair');

        $edit = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), [
                'tdr_id' => $tdrId,
                'component_id' => $component->id,
                'code_id' => $repairCode->id,
                'necessaries_id' => $orderNewNecessary->id,
                'qty' => 3,
                'serial_number' => null,
            ]);
        $edit->assertOk()
            ->assertJsonPath('data.tdr.id', $tdrId)
            ->assertJsonPath('data.tdr.necessaries_id', $orderNewNecessary->id)
            ->assertJsonPath('data.tdr.necessaries_name', 'Order New')
            ->assertJsonPath('data.tdr.qty', 3)
            ->assertJsonPath('data.tdr.serial_number', null);

        $this->assertDatabaseHas('tdrs', [
            'id' => $tdrId,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $repairCode->id,
            'necessaries_id' => $orderNewNecessary->id,
            'order_component_id' => $component->id,
            'qty' => 3,
            'serial_number' => null,
            'tdr_type' => Tdr::TYPE_ORDER_NEW,
        ]);
        $this->assertTrue((bool) $workorder->fresh()->new_parts);
    }

    public function test_mobile_api_tdr_write_permissions_and_workorder_scope_are_enforced(): void
    {
        $writer = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $writer->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '32-11',
            'part_number' => 'PN-TDR-SCOPE',
            'name' => 'Scoped Component',
        ]);
        $code = Code::query()->create(['name' => 'Repair', 'code' => 'RPR-SCOPE']);
        $necessary = Necessary::query()->create(['name' => 'Repair']);
        $shipping = $this->createUserWithRole('Shipping');

        $this->withMobileToken($shipping)
            ->getJson(route('api.mobile.workorders.components.index', $workorder->id))
            ->assertOk()
            ->assertJsonPath('data.can_create_tdr', false)
            ->assertJsonPath('data.can_update_tdr', false)
            ->assertJsonPath('data.can_delete_tdr', false);

        $payload = [
            'component_id' => $component->id,
            'code_id' => $code->id,
            'necessaries_id' => $necessary->id,
            'qty' => 1,
            'serial_number' => 'NO-WRITE',
        ];
        $this->withMobileToken($shipping)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), $payload)
            ->assertForbidden();
        $this->assertDatabaseMissing('tdrs', ['workorder_id' => $workorder->id, 'serial_number' => 'NO-WRITE']);

        $this->withMobileToken($writer)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), array_merge($payload, ['qty' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $foreignComponent = Component::query()->create([
            'manual_id' => $this->createManual()->id,
            'ipl_num' => '99-99',
            'part_number' => 'PN-FOREIGN',
            'name' => 'Foreign Component',
        ]);
        $this->withMobileToken($writer)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), array_merge($payload, ['component_id' => $foreignComponent->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');

        $otherWorkorder = $this->createWorkorder(['user_id' => $writer->id]);
        $otherTdr = Tdr::query()->create([
            'workorder_id' => $otherWorkorder->id,
            'component_id' => $component->id,
            'codes_id' => $code->id,
            'necessaries_id' => $necessary->id,
            'qty' => 1,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $this->withMobileToken($writer)
            ->putJson(route('api.mobile.workorders.tdrs.upsert', $workorder->id), array_merge($payload, ['tdr_id' => $otherTdr->id]))
            ->assertNotFound();
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

    public function test_mobile_log_card_template_groups_assy_choices_and_saves_selected_option(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $manualId = (int) $workorder->unit->manual_id;
        $base = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Mobile ASSY base',
            'part_number' => 'M-ASSY-BASE',
            'ipl_num' => '8-10',
            'units_assy' => 1,
            'log_card' => true,
        ]);
        $member = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Mobile ASSY member',
            'part_number' => 'M-ASSY-MEMBER',
            'ipl_num' => '8-20',
            'units_assy' => 1,
            'log_card' => true,
        ]);
        $group = ManualPartGroup::query()->create([
            'manual_id' => $manualId,
            'code' => 'MPG-MOBILE-LC',
            'name' => 'Mobile ASSY',
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
        $option = $group->options()->create([
            'component_id' => $base->id,
            'part_number' => 'M-ASSY-COMPLETE',
            'ipl_num' => '8-10A',
            'option_kind' => 'assy',
            'is_default' => true,
        ]);
        $option->coverages()->createMany([
            ['component_id' => $base->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
            ['component_id' => $member->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
        ]);

        $template = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.log-card.template', $workorder->id));

        $template->assertOk()
            ->assertJsonPath('data.assy_groups.0.group_id', $group->id)
            ->assertJsonPath('data.assy_groups.0.selection_type', 'radio');
        $this->assertSame(3, count($template->json('data.assy_groups.0.choices')));
        $this->assertSame([], $template->json('data.groups'));

        $this->withMobileToken($user)
            ->postJson(route('api.mobile.workorders.log-card.store', $workorder->id), [
                'rows' => [[
                    'component_id' => $base->id,
                    'manual_part_group_id' => $group->id,
                    'manual_part_group_option_id' => $option->id,
                    'manual_part_group_choice' => 'assy',
                    'ipl_group' => 'assy_group_'.$group->id,
                ]],
            ])
            ->assertOk();

        $rows = json_decode((string) LogCard::query()
            ->where('workorder_id', $workorder->id)
            ->firstOrFail()
            ->component_data, true);
        $this->assertSame('M-ASSY-COMPLETE', $rows[1]['assy_part_number']);
        $this->assertSame((string) $option->id, $rows[1]['manual_part_group_option_id']);
    }

    public function test_mobile_api_log_card_can_be_created_viewed_and_fully_updated_like_desktop(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $user->id]);
        $primaryManual = $workorder->unit->manual;
        $extraManual = $this->createManual(['number' => 'EXTRA-LOG-CARD']);
        $outsideManual = $this->createManual(['number' => 'OUTSIDE-LOG-CARD']);
        $primaryManual->update(['additional_manual_ids' => [$extraManual->id]]);

        $primary = Component::query()->create([
            'manual_id' => $primaryManual->id,
            'name' => 'Primary Log Card Component',
            'part_number' => 'LC-PRIMARY-1',
            'ipl_num' => '1-190',
            'log_card' => true,
        ]);
        $variant = Component::query()->create([
            'manual_id' => $primaryManual->id,
            'name' => 'Primary Log Card Variant',
            'part_number' => 'LC-PRIMARY-2',
            'ipl_num' => '1-190A',
            'log_card' => true,
        ]);
        $variantAssemblyA = ComponentAssembly::query()->create([
            'component_id' => $variant->id,
            'assy_part_number' => 'ASSY-A',
            'assy_ipl_num' => '1-10',
            'units_assy' => '1',
            'sort_order' => 0,
        ]);
        $variantAssemblyB = ComponentAssembly::query()->create([
            'component_id' => $variant->id,
            'assy_part_number' => 'ASSY-B',
            'assy_ipl_num' => '1-20',
            'units_assy' => '2',
            'sort_order' => 1,
        ]);
        $extra = Component::query()->create([
            'manual_id' => $extraManual->id,
            'name' => 'Extra Manual Component',
            'part_number' => 'LC-EXTRA-1',
            'ipl_num' => '2-100',
            'log_card' => true,
        ]);

        $primaryTemplate = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.log-card.template', $workorder->id));
        $primaryTemplate->assertOk()
            ->assertJsonPath('data.manual.id', $primaryManual->id)
            ->assertJsonPath('data.is_primary_manual', true);
        $this->assertContains(
            $extraManual->id,
            collect($primaryTemplate->json('data.available_manuals'))->pluck('id')->all()
        );
        $this->assertNotContains(
            $outsideManual->id,
            collect($primaryTemplate->json('data.available_manuals'))->pluck('id')->all()
        );
        $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.log-card.template', [
                'workorderId' => $workorder->id,
                'manual_id' => $outsideManual->id,
            ]))
            ->assertUnprocessable();

        $extraTemplate = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.log-card.template', [
                'workorderId' => $workorder->id,
                'manual_id' => $extraManual->id,
            ]));
        $extraTemplate->assertOk()
            ->assertJsonPath('data.manual.id', $extraManual->id)
            ->assertJsonPath('data.is_primary_manual', false)
            ->assertJsonPath('data.groups.0.variants.0.component_id', $extra->id);

        $create = $this->withMobileToken($user)
            ->postJson(route('api.mobile.workorders.log-card.store', $workorder->id), [
                'rows' => [
                    [
                        'component_id' => $primary->id,
                        'serial_number' => 'PRIMARY-SN',
                        'reason' => '5',
                    ],
                    [
                        'component_id' => $extra->id,
                        'manual_id' => $extraManual->id,
                        'serial_number' => 'EXTRA-SN',
                    ],
                ],
            ]);
        $create->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.rows_count', 2);

        $card = LogCard::query()->where('workorder_id', $workorder->id)->firstOrFail();
        $protectedRows = json_decode((string) $card->component_data, true);
        $protectedRows[0]['qa_header_part_number'] = 'QA-HEADER';
        $protectedRows[1]['name'] = 'QA component description';
        $protectedRows[1]['qa_fit_date'] = '05/aug/2026';
        $protectedRows[1]['qa_cell_colors'] = ['description' => '#d3f9d8'];
        $card->component_data = json_encode($protectedRows, JSON_UNESCAPED_UNICODE);
        $card->save();

        $show = $this->withMobileToken($user)
            ->getJson(route('api.mobile.workorders.log-card.show', $workorder->id));
        $show->assertOk()
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.rows.0.kind', 'manual')
            ->assertJsonPath('data.rows.1.serial_number', 'PRIMARY-SN')
            ->assertJsonPath('data.rows.2.kind', 'manual')
            ->assertJsonPath('data.rows.3.serial_number', 'EXTRA-SN');

        $update = $this->withMobileToken($user)
            ->putJson(route('api.mobile.workorders.log-card.update', $workorder->id), [
                'rows' => [
                    [
                        'component_id' => $variant->id,
                        'component_assembly_id' => $variantAssemblyA->id,
                        'serial_number' => 'UPDATED-SN',
                        'assy_serial_number' => 'ASSY-SN',
                        'reason' => '7',
                        'new_serial_number' => 'NEW-SN',
                    ],
                    [
                        'component_id' => $extra->id,
                        'manual_id' => $extraManual->id,
                        'serial_number' => 'EXTRA-SN-2',
                    ],
                ],
            ]);
        $update->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.log_card_id', $card->id)
            ->assertJsonPath('data.rows_count', 2);

        $rows = json_decode((string) $card->fresh()->component_data, true);
        $this->assertSame('manual', $rows[0]['row_type']);
        $this->assertSame((string) $variant->id, $rows[1]['component_id']);
        $this->assertSame((string) $variantAssemblyA->id, $rows[1]['component_assembly_id']);
        $this->assertSame('UPDATED-SN', $rows[1]['serial_number']);
        $this->assertSame('QA-HEADER', $rows[0]['qa_header_part_number']);
        $this->assertSame('QA component description', $rows[1]['name']);
        $this->assertSame('05/aug/2026', $rows[1]['qa_fit_date']);
        $this->assertSame('#d3f9d8', $rows[1]['qa_cell_colors']['description']);
        $this->assertSame('manual', $rows[2]['row_type']);
        $this->assertSame((string) $extra->id, $rows[3]['component_id']);

        $this->withMobileToken($user)
            ->patchJson(route('api.mobile.log-card.rows.assembly.update', [$card->id, 1]), [
                'component_assembly_id' => $variantAssemblyB->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.component_assembly_id', $variantAssemblyB->id)
            ->assertJsonPath('data.assy_part_number', 'ASSY-B');

        $this->withMobileToken($user)
            ->patchJson(route('api.mobile.log-card.rows.update', [$card->id, 1]), [
                'field' => 'included',
                'value' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.value', '0');
    }

    public function test_mobile_api_log_card_row_mutations_respect_review_workorder_scope(): void
    {
        $reviewUser = $this->createUserWithRole('Team Leader', ['email' => 'log-card-review@example.test']);
        $this->createWorkorder(['number' => 100500, 'user_id' => $reviewUser->id]);
        $production = $this->createWorkorder(['number' => 100501]);
        $component = Component::query()->create([
            'manual_id' => $production->unit->manual_id,
            'name' => 'Protected Log Card Component',
            'part_number' => 'LC-PROTECTED',
            'ipl_num' => '9-100',
            'log_card' => true,
        ]);
        $card = LogCard::query()->create([
            'workorder_id' => $production->id,
            'component_data' => json_encode([[
                'component_id' => (string) $component->id,
                'serial_number' => 'ORIGINAL',
            ]]),
        ]);
        config()->set('mobile_review.accounts', [
            'log-card-review@example.test' => ['workorder_numbers' => [100500]],
        ]);

        $this->withMobileToken($reviewUser)
            ->patchJson(route('api.mobile.log-card.rows.update', [$card->id, 0]), [
                'field' => 'serial_number',
                'value' => 'FORBIDDEN',
            ])
            ->assertNotFound();

        $rows = json_decode((string) $card->fresh()->component_data, true);
        $this->assertSame('ORIGINAL', $rows[0]['serial_number']);
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
                'password' => 'new-password@',
                'password_confirmation' => 'new-password@',
            ]);

        $passwordResponse->assertOk()
            ->assertJsonPath('ok', true);
        $this->assertTrue(Hash::check('new-password@', $user->fresh()->password));
        $this->assertDatabaseMissing('mobile_api_tokens', ['user_id' => $user->id]);
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
