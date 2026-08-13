<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\Process;
use App\Models\ProcessName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualProcessLockTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_permitted_user_with_special_flag_can_lock_and_unlock_process_group(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        $processName = ProcessName::query()->firstOrCreate([
            'name' => 'QA Lock Group',
        ], [
            'process_sheet_name' => 'QA',
            'form_number' => 'QA-001',
        ]);

        $this->actingAs($user)->postJson(route('manuals.process-name-locks.lock', [
            'manual' => $manual,
            'processName' => $processName,
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locked', true)
            ->assertJsonPath('locked_by', $user->selection_name);

        $this->assertDatabaseHas('manual_process_name_locks', [
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
        ]);

        $this->actingAs($user)->deleteJson(route('manuals.process-name-locks.unlock', [
            'manual' => $manual,
            'processName' => $processName,
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locked', false)
            ->assertJsonPath('locked_by', null);

        $this->assertDatabaseMissing('manual_process_name_locks', [
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
        ]);
    }

    public function test_lock_manager_can_lock_and_unlock_manual_process_as_json(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [, $manualProcess] = $this->createManualProcessFixture($manual->id);

        $this->actingAs($user)->postJson(route('manuals.manual-process-locks.lock', [
            'manual' => $manual,
            'manualProcess' => $manualProcess,
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locked', true)
            ->assertJsonPath('locked_by', $user->selection_name);

        $this->assertDatabaseHas('manual_processes', [
            'id' => $manualProcess->id,
            'is_locked' => true,
            'locked_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)->deleteJson(route('manuals.manual-process-locks.unlock', [
            'manual' => $manual,
            'manualProcess' => $manualProcess,
        ]))->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('locked', false)
            ->assertJsonPath('locked_by', null);

        $this->assertDatabaseHas('manual_processes', [
            'id' => $manualProcess->id,
            'is_locked' => false,
            'locked_by_user_id' => null,
        ]);
    }

    public function test_regular_user_cannot_update_locked_manual_process(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName, $manualProcess, $process] = $this->createManualProcessFixture($manual->id);

        $manualProcess->update([
            'is_locked' => true,
        ]);

        $response = $this->actingAs($user)
            ->putJson(route('manual_processes.update', $manualProcess), [
                'process' => 'Updated forbidden text',
            ]);

        $response->assertForbidden();
        $response->assertJsonPath('success', false);

        $this->assertSame('Initial QA Process', $process->fresh()->process);
    }

    public function test_regular_user_can_delete_existing_process_when_group_is_locked(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName, $manualProcess] = $this->createManualProcessFixture($manual->id);

        ManualProcessNameLock::query()->create([
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
            'locked_by_user_id' => $user->id,
            'locked_at' => now(),
        ]);

        $response = $this->from(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']))
            ->actingAs($user)
            ->delete(route('manual_processes.destroy', $manualProcess), [
                'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']),
            ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('manual_processes', [
            'id' => $manualProcess->id,
        ]);
    }

    public function test_permitted_user_with_special_flag_can_update_locked_manual_process(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [, $manualProcess, $process] = $this->createManualProcessFixture($manual->id);

        $manualProcess->update([
            'is_locked' => true,
            'locked_by_user_id' => $user->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($user)->putJson(route('manual_processes.update', $manualProcess), [
            'process' => 'Updated allowed text',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame('Updated allowed text', $process->fresh()->process);
    }

    public function test_unlocked_manual_process_can_be_updated_as_json_without_page_redirect(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName, $manualProcess] = $this->createManualProcessFixture($manual->id);

        $page = $this->actingAs($user)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'processes',
        ]));
        $page->assertOk();
        $page->assertSee('id="manualProcessEditModal"', false);
        $page->assertSee('data-update-url="'.route('manual_processes.update', $manualProcess).'"', false);
        $page->assertSee('data-manual-process-lock-toggle', false);
        $page->assertSee('class="manual-process-specification-text"', false);
        $page->assertSee("if (specificationText) specificationText.textContent = updated.process || '';", false);
        $page->assertDontSee("if (specificationCell) specificationCell.textContent = updated.process || '';", false);

        $response = $this->actingAs($user)->putJson(route('manual_processes.update', $manualProcess), [
            'process' => 'Updated in modal',
            'process_comment' => 'Saved without reloading the manual page',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('manual_process.id', $manualProcess->id);
        $response->assertJsonPath('manual_process.process', 'Updated in modal');
        $response->assertJsonPath('manual_process.process_comment', 'Saved without reloading the manual page');
        $response->assertJsonPath('manual_process.process_name', $processName->name);
        $this->assertDatabaseHas('manual_processes', [
            'id' => $manualProcess->id,
            'process_comment' => 'Saved without reloading the manual page',
        ]);
    }

    public function test_authenticated_user_without_manual_access_can_browse_process_catalog(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        [$processName] = $this->createManualProcessFixture($manual->id);

        Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Available QA Process',
        ]);

        $response = $this->actingAs($user)->getJson(route('processes.getProcesses', [
            'processNameId' => $processName->id,
            'manualId' => $manual->id,
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'process' => 'Initial QA Process',
        ]);
        $response->assertJsonFragment([
            'process' => 'Available QA Process',
        ]);
    }

    public function test_permitted_regular_user_can_browse_process_catalog_and_create_new_process_for_unlocked_group(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName] = $this->createManualProcessFixture($manual->id);

        Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Available QA Process',
        ]);

        $response = $this->actingAs($user)->getJson(route('processes.getProcesses', [
            'processNameId' => $processName->id,
            'manualId' => $manual->id,
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'process' => 'Initial QA Process',
        ]);
        $response->assertJsonFragment([
            'process' => 'Available QA Process',
        ]);
        $response->assertJsonPath('canCreateProcess', true);
        $response->assertJsonPath('createProcessMessage', null);
    }

    public function test_permitted_regular_user_can_attach_existing_process_without_creating_new_definition(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName, $manualProcess] = $this->createManualProcessFixture($manual->id);

        $availableProcess = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Attachable QA Process',
        ]);

        ManualProcessNameLock::query()->create([
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
            'locked_by_user_id' => $user->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('processes.store'), [
            'process_names_id' => $processName->id,
            'manual_id' => $manual->id,
            'selected_process_id' => $availableProcess->id,
            'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']),
        ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('manual_processes', [
            'manual_id' => $manual->id,
            'processes_id' => $availableProcess->id,
        ]);
    }

    public function test_permitted_regular_user_can_create_new_process_definition_when_group_is_unlocked(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName] = $this->createManualProcessFixture($manual->id);

        $response = $this->actingAs($user)->post(route('processes.store'), [
            'process_names_id' => $processName->id,
            'manual_id' => $manual->id,
            'process' => 'New unlocked subprocess',
            'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']),
        ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('processes', [
            'process_names_id' => $processName->id,
            'process' => 'New unlocked subprocess',
        ]);
    }

    public function test_authenticated_user_without_manual_access_can_create_new_process_definition_when_group_is_unlocked(): void
    {
        $user = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        [$processName] = $this->createManualProcessFixture($manual->id);

        $response = $this->actingAs($user)->post(route('processes.store'), [
            'process_names_id' => $processName->id,
            'manual_id' => $manual->id,
            'process' => 'Unlocked subprocess without manual permission',
            'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']),
        ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('processes', [
            'process_names_id' => $processName->id,
            'process' => 'Unlocked subprocess without manual permission',
        ]);
    }

    public function test_special_user_can_create_new_process_for_unlocked_process_name(): void
    {
        $user = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName] = $this->createManualProcessFixture($manual->id);

        $response = $this->actingAs($user)->getJson(route('processes.getProcesses', [
            'processNameId' => $processName->id,
            'manualId' => $manual->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('canCreateProcess', true);
        $response->assertJsonPath('createProcessMessage', null);
    }

    public function test_locked_process_name_blocks_new_process_creation_but_keeps_catalog_available(): void
    {
        $user = $this->createUserWithRole('Technician');
        $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
            'name' => 'Alice Manager',
        ]);
        $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Main Admin',
        ]);
        $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
            'name' => 'Bob Manager',
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        [$processName, $manualProcess] = $this->createManualProcessFixture($manual->id);

        $locker = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
            'name' => 'Lock Owner',
        ]);

        ManualProcessNameLock::query()->create([
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
            'locked_by_user_id' => $locker->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('processes.getProcesses', [
            'processNameId' => $processName->id,
            'manualId' => $manual->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('canCreateProcess', false);
        $this->assertStringContainsString(
            'Locked. Choose from the list',
            (string) $response->json('createProcessMessage')
        );
        $this->assertStringContainsString(
            'Alice Manager',
            (string) $response->json('createProcessMessage')
        );
        $this->assertStringContainsString(
            'Bob Manager',
            (string) $response->json('createProcessMessage')
        );
        $this->assertStringContainsString(
            'or System Administrator',
            (string) $response->json('createProcessMessage')
        );
    }

    public function test_locked_process_name_blocks_regular_user_from_creating_new_process_definition(): void
    {
        $user = $this->createUserWithRole('Technician');
        $locker = $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
            'name' => 'Lock Owner',
        ]);
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($user->id);
        $manual->permittedUsers()->attach($locker->id);
        [$processName] = $this->createManualProcessFixture($manual->id);

        ManualProcessNameLock::query()->create([
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
            'locked_by_user_id' => $locker->id,
            'locked_at' => now(),
        ]);

        $response = $this->from(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']))
            ->actingAs($user)
            ->post(route('processes.store'), [
                'process_names_id' => $processName->id,
                'manual_id' => $manual->id,
                'process' => 'Blocked subprocess',
                'return_to' => route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']),
            ]);

        $response->assertRedirect(route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Lock Owner', (string) session('error'));

        $this->assertDatabaseMissing('processes', [
            'process_names_id' => $processName->id,
            'process' => 'Blocked subprocess',
        ]);
    }

    public function test_locked_process_message_does_not_duplicate_admin_label(): void
    {
        $user = $this->createUserWithRole('Technician');
        $this->createUserWithRole('Technician', [
            'can_manage_locked_manual_processes' => true,
            'name' => 'Alice Manager',
        ]);
        $manual = $this->createManual();
        [$processName] = $this->createManualProcessFixture($manual->id);

        $locker = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Admin',
        ]);

        ManualProcessNameLock::query()->create([
            'manual_id' => $manual->id,
            'process_name_id' => $processName->id,
            'locked_by_user_id' => $locker->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('processes.getProcesses', [
            'processNameId' => $processName->id,
            'manualId' => $manual->id,
        ]));

        $response->assertOk();
        $message = (string) $response->json('createProcessMessage');
        $this->assertStringContainsString('Alice Manager', $message);
        $this->assertStringContainsString('System Administrator', $message);
        $this->assertStringNotContainsString('Admin or System Administrator', $message);
    }

    private function createManualProcessFixture(int $manualId): array
    {
        $processName = ProcessName::query()->firstOrCreate([
            'name' => 'QA Process Name '.uniqid(),
        ], [
            'process_sheet_name' => 'QA',
            'form_number' => 'QA-002',
        ]);

        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Initial QA Process',
        ]);

        $manualProcess = ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        return [$processName, $manualProcess, $process];
    }
}
