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
            ->assertJsonPath('data.offline_mode', false)
            ->assertJsonPath('data.photo_upload.compress_on_client', false)
            ->assertJsonPath('data.photo_upload.queue_on_client', true)
            ->assertJsonPath('data.photo_upload.delete_local_after_success', true);
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
        ]);
        $this->assertSame('Draft', Workorder::withDrafts()->findOrFail($workorderId)->instruction?->name);
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
