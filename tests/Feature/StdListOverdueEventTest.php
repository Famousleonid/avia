<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\NotificationEventRule;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Services\Events\EventRunner;
use App\Services\Events\TdrProcessOverdueStartEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\BuildsDomainData;
use Tests\TestCase;

class StdListOverdueEventTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_closed_preferred_std_row_suppresses_legacy_overdue_notification(): void
    {
        Notification::fake();

        $this->createUserWithRole('Admin', ['name' => 'System Admin']);
        $assignedUser = $this->createUserWithRole('Technician');
        $notifyUser = $this->createUserWithRole('Manager');
        $workorderOwner = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'number' => 5101,
            'user_id' => $workorderOwner->id,
        ]);

        $component = Component::query()->create([
            'manual_id' => $this->createManual()->id,
            'part_number' => 'PN-LEGACY-5101',
            'assy_part_number' => 'APN-LEGACY-5101',
            'name' => 'WASHER, FLAT',
            'ipl_num' => 'IPL-5101',
            'assy_ipl_num' => 'AIPL-5101',
            'eff_code' => 'ALL',
            'units_assy' => 1,
            'log_card' => false,
            'repair' => false,
            'is_bush' => false,
        ]);

        $legacyTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SER-LEGACY-5101',
            'qty' => 1,
        ]);

        $carrierTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'serial_number' => 'NSN',
            'qty' => 1,
        ]);

        $processName = ProcessName::query()->create([
            'name' => 'STD Paint List',
            'process_sheet_name' => 'Paint',
            'form_number' => 'FORM-STD-5101',
            'std_days' => 1,
            'notify_user_id' => $notifyUser->id,
            'print_form' => false,
            'show_in_process_picker' => true,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $legacyTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => now()->subDays(5)->toDateString(),
            'date_finish' => null,
            'user_id' => $assignedUser->id,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => now()->subDays(5)->toDateString(),
            'date_finish' => now()->subDay()->toDateString(),
            'user_id' => $assignedUser->id,
        ]);

        $this->createRule('tdr_process.overdue_start', [
            ['type' => 'dynamic', 'value' => 'tdr_process_user'],
            ['type' => 'dynamic', 'value' => 'process_notify_user'],
            ['type' => 'dynamic', 'value' => 'system_admins'],
        ], [
            'name' => 'STD overdue recipients',
            'severity' => 'danger',
        ]);

        app(EventRunner::class)->run([new TdrProcessOverdueStartEvent()]);

        Notification::assertNothingSent();
    }

    public function test_std_overdue_message_does_not_include_component_part_details(): void
    {
        $assignedUser = $this->createUserWithRole('Technician');
        $notifyUser = $this->createUserWithRole('Manager');
        $workorderOwner = $this->createUserWithRole('Shipping');

        $workorder = $this->createWorkorder([
            'number' => 5102,
            'user_id' => $workorderOwner->id,
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => Component::query()->create([
                'manual_id' => $this->createManual()->id,
                'part_number' => 'PN-LEGACY-5102',
                'assy_part_number' => 'APN-LEGACY-5102',
                'name' => 'WASHER, FLAT',
                'ipl_num' => 'IPL-5102',
                'assy_ipl_num' => 'AIPL-5102',
                'eff_code' => 'ALL',
                'units_assy' => 1,
                'log_card' => false,
                'repair' => false,
                'is_bush' => false,
            ])->id,
            'serial_number' => 'SER-LEGACY-5102',
            'qty' => 1,
        ]);

        $carrierTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'serial_number' => 'NSN',
            'qty' => 1,
        ]);

        $processName = ProcessName::query()->create([
            'name' => 'STD Paint List',
            'process_sheet_name' => 'Paint',
            'form_number' => 'FORM-STD-5102',
            'std_days' => 1,
            'notify_user_id' => $notifyUser->id,
            'print_form' => false,
            'show_in_process_picker' => true,
        ]);

        $carrierProcess = TdrProcess::query()->create([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => now()->subDays(5)->toDateString(),
            'date_finish' => null,
            'user_id' => $assignedUser->id,
        ]);

        $carrierProcess->load(['processName.notifyUser', 'tdr.workorder.user', 'tdr.component']);

        $message = (new TdrProcessOverdueStartEvent())->message($carrierProcess);

        $this->assertSame('STD Paint List', $message['ui']['process']['name']);
        $this->assertSame('', $message['ui']['part']['number']);
        $this->assertSame('', $message['ui']['part']['name']);
        $this->assertStringNotContainsString('WASHER, FLAT', $message['text']);
        $this->assertStringNotContainsString('PN-LEGACY-5102', $message['text']);
    }

    protected function createRule(string $eventKey, array $recipients, array $attributes = []): NotificationEventRule
    {
        $defaults = [
            'event_key' => $eventKey,
            'name' => 'Test rule',
            'enabled' => true,
            'severity' => 'info',
            'title_template' => 'Notification',
            'message_template' => 'Message',
            'respect_user_preferences' => true,
            'exclude_actor' => false,
            'repeat_policy' => 'event_default',
            'repeat_every_minutes' => null,
        ];

        $rule = NotificationEventRule::query()->create(array_merge($defaults, $attributes));

        foreach ($recipients as $recipient) {
            $rule->recipients()->create([
                'recipient_type' => $recipient['type'],
                'recipient_value' => (string) $recipient['value'],
            ]);
        }

        return $rule;
    }
}
