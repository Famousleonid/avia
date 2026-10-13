<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\ManualServiceBulletin;
use App\Models\Task;
use App\Models\WorkorderServiceBulletinLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ServiceBulletinLogTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_print_settings_use_current_table_font_as_default_and_persist_per_user(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder(['user_id' => $manager->id]);
        $this->createManualServiceBulletinForWorkorder($workorder);

        $response = $this->actingAs($manager)->withSession([
            'auth.version' => (int) $manager->auth_version,
            'password_hash_web' => $manager->getAuthPassword(),
        ])->get(
            route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id])
        );

        $response
            ->assertOk()
            ->assertSee('Print Settings', false)
            ->assertSee('Table Font (pt)', false)
            ->assertSee("data-note-preset-text=\"P/N doesn't match\"", false)
            ->assertSee('data-note-preset-text="Superseded by"', false)
            ->assertSee("data-note-preset-text=\"S/N doesn't match\"", false)
            ->assertSee('data-note-preset-color="violet"', false)
            ->assertSee("var activeNotesInput = null;", false)
            ->assertSee('insertNotePreset(', false)
            ->assertSee('var defaultTableFontSize = 8.3;', false)
            ->assertSee("var settingsScope = 'tdrs.service-bulletin-log';", false)
            ->assertSee('window.UserUiSettings.get(settingsScope, tableFontSizeKey, defaultTableFontSize)', false)
            ->assertSee('window.UserUiSettings.set(settingsScope, tableFontSizeKey, value)', false)
            ->assertDontSee('localStorage', false)
            ->assertDontSee('sessionStorage', false);

        $this->assertStringContainsString(
            '--sb-table-font-size: 8.3pt;',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            'font-size: var(--sb-table-font-size);',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            'font-size: calc(var(--sb-table-font-size) + 2pt);',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            '.sb-stamp-option:has(.sb-print-stamp.is-selected)',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            '.sb-notes-row input.is-note-preset-danger',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            '.sb-notes-row input.is-note-preset-primary',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
        $this->assertStringContainsString(
            '.sb-notes-row input.is-note-preset-violet',
            file_get_contents(public_path('css/forms/service-bulletin-log.css'))
        );
    }

    public function test_technician_cannot_update_service_bulletin_log_after_post_disassembly_inspection_is_finished(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $bulletin = $this->createManualServiceBulletinForWorkorder($workorder);
        $this->finishPostDisassemblyInspection($workorder);

        $response = $this->actingAs($technician)->post(route('tdrs.serviceBulletinLog.update', ['workorder' => $workorder->id]), [
            'rows' => [
                $bulletin->id => [
                    'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
                    'notes' => 'Technician edit should be blocked',
                ],
            ],
        ]);

        $response
            ->assertRedirect(route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]))
            ->assertSessionHasErrors('service_bulletin_log');

        $this->assertDatabaseMissing('workorder_service_bulletin_logs', [
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $bulletin->id,
        ]);
    }

    public function test_manager_can_update_service_bulletin_log_after_post_disassembly_inspection_is_finished(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $bulletin = $this->createManualServiceBulletinForWorkorder($workorder);
        $this->finishPostDisassemblyInspection($workorder);

        $response = $this->actingAs($manager)->post(route('tdrs.serviceBulletinLog.update', ['workorder' => $workorder->id]), [
            'rows' => [
                $bulletin->id => [
                    'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
                    'notes' => 'Manager edit is allowed',
                ],
            ],
        ]);

        $response->assertRedirect(route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]));

        $this->assertDatabaseHas('workorder_service_bulletin_logs', [
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $bulletin->id,
            'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
            'notes' => 'Manager edit is allowed',
        ]);
    }

    public function test_notes_preserve_stamp_and_only_status_changes_replace_signer(): void
    {
        $technician = $this->createUserWithRole('Technician', ['stamp' => '42']);
        $admin = $this->createUserWithRole('Admin', ['stamp' => '00']);
        $workorder = $this->createWorkorder();
        $bulletin = $this->createManualServiceBulletinForWorkorder($workorder);
        $url = route('tdrs.serviceBulletinLog.update', $workorder);
        $save = function ($user, ?string $status, string $notes, array $extra = []) use ($url, $bulletin): void {
            $this->actingAs($user)->withSession([
                'auth.version' => (int) $user->auth_version,
                'password_hash_web' => $user->getAuthPassword(),
            ])->post($url, $extra + ['rows' => [$bulletin->id => compact('status', 'notes')]])
                ->assertRedirect()->assertSessionHasNoErrors();
        };

        $this->travelTo(now()->startOfSecond());
        $save($technician, WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT, 'Initial note');
        $log = WorkorderServiceBulletinLog::where('workorder_id', $workorder->id)->firstOrFail();
        $originalTime = $log->stamped_at->toDateTimeString();
        $this->assertSame($technician->id, (int) $log->stamp_user_id);

        $this->travel(5)->minutes();
        foreach (['Edited by admin', 'Edited by admin', ''] as $notes) {
            $save($admin, WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT, $notes);
            $log->refresh();
            $this->assertSame($technician->id, (int) $log->stamp_user_id);
            $this->assertSame($originalTime, $log->stamped_at->toDateTimeString());
            $this->assertSame($notes !== '' ? $notes : null, $log->notes);
        }
        $this->get(route('tdrs.serviceBulletinLog', $workorder))->assertOk()
            ->assertSee('is-selected">42</span>', false)->assertDontSee('is-selected">00</span>', false);

        $save($admin, WorkorderServiceBulletinLog::STATUS_PREVIOUSLY_CARRIED_OUT, 'Status changed');
        $log->refresh();
        $this->assertSame($admin->id, (int) $log->stamp_user_id);
        $this->assertSame(now()->toDateTimeString(), $log->stamped_at->toDateTimeString());

        $save($admin, WorkorderServiceBulletinLog::STATUS_PREVIOUSLY_CARRIED_OUT, 'Keep note',
            ['clear_status_bulletin_id' => $bulletin->id]);
        $log->refresh();
        $this->assertNull($log->status);
        $this->assertNull($log->stamp_user_id);
        $this->assertNull($log->stamped_at);
        $save($technician, null, 'Notes only');
        $this->assertNull($log->fresh()->stamp_user_id);
        $save($technician, WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT, 'Signed again');
        $this->assertSame($technician->id, (int) $log->fresh()->stamp_user_id);
        $this->travelBack();
    }

    private function createManualServiceBulletinForWorkorder($workorder): ManualServiceBulletin
    {
        return ManualServiceBulletin::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'sort_order' => 1,
            'year_introduced' => '2026',
            'ac_mfg_service_bulletin_no' => 'AC-SB-1',
            'oem_service_bulletin_no' => 'OEM-SB-1',
            'awd_no' => 'AWD-1',
            'identification_method' => 'Visual',
            'description' => 'QA service bulletin',
            'default_requirement' => ManualServiceBulletin::REQUIREMENT_MANDATORY,
            'is_active' => true,
        ]);
    }

    private function finishPostDisassemblyInspection($workorder): void
    {
        $generalTask = GeneralTask::query()->create([
            'name' => 'QA',
            'sort_order' => 1,
        ]);
        $task = Task::query()->create([
            'name' => 'Post Disassembly inspection',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => 0,
        ]);

        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $generalTask->id,
            'task_id' => $task->id,
            'date_finish' => '2026-05-20',
        ]);
    }
}
