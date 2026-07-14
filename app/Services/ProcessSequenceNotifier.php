<?php

namespace App\Services;

use App\Models\EventLog;
use App\Models\NotificationEventRule;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Notifications\NewMessageNotification;

class ProcessSequenceNotifier
{
    public function __construct(
        private NotificationEventRuleResolver $resolver,
    ) {}

    public function notifyReady(TdrProcess|WorkorderStdProcess|null $nextProcess, TdrProcess|WorkorderStdProcess $completedProcess): void
    {
        if (! $nextProcess) {
            return;
        }

        $workorder = $this->workorderFor($nextProcess);
        if (! $workorder) {
            return;
        }

        $nextProcess->loadMissing($nextProcess instanceof TdrProcess
            ? ['processName.notifyUser', 'tdr.component']
            : ['processName.notifyUser', 'sourceTdr.component']
        );
        $completedProcess->loadMissing(['processName']);

        $actor = auth()->user();
        $workorderUserName = trim((string) ($workorder->user?->selection_name ?? ''));
        $detail = $this->detailInfo($nextProcess);
        $msg = [
            'fromUserId' => (int) ($actor?->id ?? 0),
            'fromName' => (string) ($actor?->name ?? 'System'),
            'type' => 'workorder',
            'event' => 'process_ready_for_next',
            'severity' => 'info',
            'title' => 'Send detail to next process',
            'text' => 'WO '.$workorder->number.': send the detail to '.$this->processName($nextProcess).'.',
            'url' => route('mains.show', $workorder->id).'#qa-main:process:'.$this->processIdForUrl($nextProcess),
            'ui' => [
                'workorder' => [
                    'id' => $workorder->id,
                    'no' => $workorder->number,
                    'owner_name' => $workorderUserName,
                ],
                'actor' => [
                    'id' => (int) ($actor?->id ?? 0),
                    'name' => (string) ($actor?->name ?? 'System'),
                ],
                'process' => [
                    'id' => $this->processIdForUrl($nextProcess),
                    'name' => $this->processName($nextProcess),
                    'previous_name' => $this->processName($completedProcess),
                    'previous_date_start_user_id' => (int) ($completedProcess->date_start_user_id ?? 0),
                    'next_date_start_user_id' => (int) ($nextProcess->date_start_user_id ?? 0),
                ],
                'part' => [
                    'name' => $detail['name'],
                    'number' => $detail['part_number'],
                    'label' => $detail['label'],
                ],
                'unit' => [
                    'serial_number' => $detail['serial_number'],
                ],
            ],
            'payload' => [
                'workorder_id' => $workorder->id,
                'workorder_no' => $workorder->number,
                'workorder_user_name' => $workorderUserName,
                'process_id' => $this->processIdForUrl($nextProcess),
                'process_name' => $this->processName($nextProcess),
                'previous_process_name' => $this->processName($completedProcess),
                'detail_label' => $detail['label'],
                'part_name' => $detail['name'],
                'part_number' => $detail['part_number'],
                'serial_number' => $detail['serial_number'],
                'previous_date_start_user_id' => (int) ($completedProcess->date_start_user_id ?? 0),
                'next_date_start_user_id' => (int) ($nextProcess->date_start_user_id ?? 0),
            ],
        ];

        $rules = $this->resolver->activeRules(ProcessSequenceGuard::READY_EVENT_KEY);
        foreach ($rules as $rule) {
            $ruleMsg = $this->resolver->renderMessage($rule, $msg);
            $recipients = $this->resolver->recipients($rule, $nextProcess, $ruleMsg);

            foreach ($recipients as $recipient) {
                $this->sendOnce($rule, $nextProcess, $recipient, $ruleMsg);
            }
        }
    }

    private function sendOnce(NotificationEventRule $rule, TdrProcess|WorkorderStdProcess $subject, User $recipient, array $msg): void
    {
        $log = EventLog::query()->firstOrNew([
            'event_key' => ProcessSequenceGuard::READY_EVENT_KEY,
            'notification_event_rule_id' => $rule->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'recipient_user_id' => $recipient->id,
        ]);

        if ($log->exists && $log->last_sent_at) {
            return;
        }

        $recipient->notify(new NewMessageNotification(
            fromUserId: (int) ($msg['fromUserId'] ?? 0),
            fromName: (string) ($msg['fromName'] ?? 'System'),
            text: (string) ($msg['text'] ?? ''),
            url: $msg['url'] ?? null,
            type: $msg['type'] ?? 'workorder',
            event: $msg['event'] ?? ProcessSequenceGuard::READY_EVENT_KEY,
            ui: $msg['ui'] ?? [],
            severity: $msg['severity'] ?? 'info',
            title: $msg['title'] ?? 'Notification',
            payload: $msg['payload'] ?? [],
        ));

        $now = now();
        $log->first_sent_at = $log->first_sent_at ?: $now;
        $log->last_sent_at = $now;
        $log->sent_count = (int) ($log->sent_count ?? 0) + 1;
        $log->save();
    }

    private function workorderFor(TdrProcess|WorkorderStdProcess $process): ?Workorder
    {
        if ($process instanceof WorkorderStdProcess) {
            return $process->workorder ?: Workorder::query()->with('user')->find($process->workorder_id);
        }

        $process->loadMissing('tdr.workorder.user');

        return $process->tdr?->workorder;
    }

    private function processName(TdrProcess|WorkorderStdProcess $process): string
    {
        return (string) ($process->processName?->name ?? 'Process');
    }

    /**
     * @return array{name: string, part_number: string, serial_number: string, label: string}
     */
    private function detailInfo(TdrProcess|WorkorderStdProcess $process): array
    {
        $tdr = $process instanceof WorkorderStdProcess ? $process->sourceTdr : $process->tdr;
        $component = $tdr?->component;
        $name = trim((string) ($component?->name ?? ''));
        $partNumber = trim((string) ($component?->part_number ?? ''));
        $serialNumber = trim((string) ($tdr?->serial_number ?? ''));

        $labelParts = array_filter([
            $name,
            $partNumber !== '' ? 'p/n '.$partNumber : '',
            $serialNumber !== '' ? 's/n '.$serialNumber : '',
        ]);

        return [
            'name' => $name,
            'part_number' => $partNumber,
            'serial_number' => $serialNumber,
            'label' => implode(' | ', $labelParts),
        ];
    }

    private function processIdForUrl(TdrProcess|WorkorderStdProcess $process): int
    {
        if ($process instanceof WorkorderStdProcess) {
            return (int) ($process->source_tdr_process_id ?: $process->id);
        }

        return (int) $process->id;
    }
}
