<?php

namespace Tests\Feature;

use App\Models\{Component, ProcessName, Tdr, TdrProcess, WorkorderStdProcess};
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class ProcessReminderClosureTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    private function fixture(): array
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $component = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => uniqid('CLOSE-'), 'name' => 'Closure part', 'ipl_num' => '1-1']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $component->id, 'qty' => 1]);
        $name = ProcessName::create(['name' => uniqid('Closure process '), 'process_sheet_name' => 'QA', 'form_number' => 'QA', 'std_days' => 3]);
        $process = TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id]);
        return [$user, $wo, $process, $name];
    }

    private function notice($user, $wo, $process, $name, bool $typed = false, string $event = 'process_ready_for_next')
    {
        $payload = $typed ? ['subject_type' => get_class($process), 'subject_id' => $process->id] : [];
        $before = $user->notifications()->pluck('id')->all();
        $user->notify(new NewMessageNotification(fromUserId: 0, fromName: 'System', text: 'Closure reminder', type: 'workorder', event: $event,
            ui: ['workorder' => ['id' => $wo->id, 'no' => $wo->number], 'process' => ['id' => $process->source_tdr_process_id ?: $process->id, 'name' => $name->name]], payload: $payload));
        return $user->notifications()->whereNotIn('id', $before)->firstOrFail();
    }

    public function test_sent_closes_all_recipient_reminders_but_preserves_history_and_other_events(): void
    {
        [$user, $wo, $process, $name] = $this->fixture();
        $other = $this->createUserWithRole('Admin');
        $old = $this->notice($user, $wo, $process, $name);
        $typed = $this->notice($other, $wo, $process, $name, true);
        $assigned = $this->notice($user, $wo, $process, $name, false, 'assigned');
        $waiting = TdrProcess::create(['tdrs_id' => $process->tdrs_id, 'process_names_id' => $name->id]);
        $waitingNotice = $this->notice($other, $wo, $waiting, $name, false);
        $process->update(['date_finish' => '2026-10-26']);
        $this->assertNull($old->fresh()->read_at);
        $process->update(['date_start' => '2026-10-26']);
        $this->assertNotNull($old->fresh()->read_at);
        $this->assertNotNull($typed->fresh()->read_at);
        $this->assertNull($assigned->fresh()->read_at);
        $this->assertNull($waitingNotice->fresh()->read_at);
        $process->update(['date_start' => null]);
        $this->assertNotNull($old->fresh()->read_at);
    }

    public function test_bell_reconciles_legacy_and_bulk_sync_dates_without_touching_other_users(): void
    {
        [$user, $wo, $process, $name] = $this->fixture();
        $old = $this->notice($user, $wo, $process, $name);
        $other = $this->createUserWithRole('Admin');
        $otherNotice = $this->notice($other, $wo, $process, $name);
        TdrProcess::whereKey($process->id)->update(['date_start' => '2026-10-27']);
        $this->actingAs($user)->getJson(route('notifications.unreadCount'))->assertOk()->assertJsonPath('count', 0);
        $this->getJson(route('notifications.latest'))->assertJsonPath('pagination.total', 0);
        $this->assertNotNull($old->fresh()->read_at);
        $this->assertNull($otherNotice->fresh()->read_at);
        $this->get(route('notifications.index'))->assertOk()->assertSee('data-notif-id="'.$old->id.'"', false)->assertSee('PROCESS READY');
    }

    public function test_std_alias_and_typed_identity_do_not_close_unstarted_or_wrong_workorder(): void
    {
        [$user, $wo, $process, $name] = $this->fixture();
        $std = WorkorderStdProcess::create(['workorder_id' => $wo->id, 'process_name_id' => $name->id, 'std_type' => 'cad', 'source_tdr_process_id' => $process->id]);
        $typed = $this->notice($user, $wo, $std, $name, true);
        $legacy = $this->notice($user, $wo, $std, $name);
        $wrongWo = $this->createWorkorder();
        $wrong = $this->notice($user, $wrongWo, $std, $name, true);
        $process->update(['date_start' => '2026-10-27']);
        $this->assertNull($typed->fresh()->read_at);
        $this->assertNull($legacy->fresh()->read_at);
        $std->update(['date_start' => '2026-10-27']);
        $this->assertNotNull($typed->fresh()->read_at);
        $this->assertNotNull($legacy->fresh()->read_at);
        $this->actingAs($user)->getJson(route('notifications.latest'))->assertOk();
        $this->assertNull($wrong->fresh()->read_at);
    }

    public function test_approved_and_assigned_page_has_summary_without_duplicate_explanation(): void
    {
        [$user, $wo, $process, $name] = $this->fixture();
        $this->notice($user, $wo, $process, $name, false, 'approved');
        $this->notice($user, $wo, $process, $name, false, 'assigned');
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()
            ->assertSee('APPROVED')->assertSee('ASSIGNED')->assertSee('WO #'.$wo->number)->assertDontSee('Closure reminder');
    }
}
