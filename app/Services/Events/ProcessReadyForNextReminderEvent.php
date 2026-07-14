<?php

namespace App\Services\Events;

use App\Models\EventLog;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Services\ProcessSequenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProcessReadyForNextReminderEvent implements EventDefinition
{
    public function key(): string
    {
        return ProcessSequenceGuard::READY_EVENT_KEY;
    }

    public function dueSubjects(): Collection
    {
        return EventLog::query()
            ->where('event_key', $this->key())
            ->whereIn('subject_type', [TdrProcess::class, WorkorderStdProcess::class])
            ->whereNotNull('last_sent_at')
            ->get()
            ->map(fn (EventLog $log) => $log->subject)
            ->filter(fn ($subject): bool => $subject instanceof TdrProcess || $subject instanceof WorkorderStdProcess)
            ->unique(fn (Model $subject): string => get_class($subject).':'.$subject->getKey())
            ->filter(fn (TdrProcess|WorkorderStdProcess $subject): bool => $this->shouldRun($subject))
            ->values();
    }

    public function recipients($subject): array
    {
        return [];
    }

    public function message($subject): array
    {
        /** @var TdrProcess|WorkorderStdProcess $subject */
        $subject->loadMissing($subject instanceof TdrProcess
            ? ['processName', 'tdr.workorder.user', 'tdr.component']
            : ['processName', 'workorder.user', 'sourceTdr.component']);

        $workorder = $this->workorderFor($subject);
        $processName = (string) ($subject->processName?->name ?? 'Process');
        $previousName = $this->previousProcessName($subject);
        $workorderUserName = trim((string) ($workorder?->user?->selection_name ?? ''));
        $detail = $this->detailInfo($subject);

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'workorder',
            'event' => 'process_ready_for_next',
            'severity' => 'info',
            'title' => 'Send detail to next process',
            'text' => 'WO '.$workorder?->number.': send the detail to '.$processName.'.',
            'url' => $workorder ? route('mains.show', $workorder->id).'#qa-main:process:'.$this->processIdForUrl($subject) : null,
            'ui' => [
                'workorder' => [
                    'id' => $workorder?->id,
                    'no' => $workorder?->number,
                    'owner_name' => $workorderUserName,
                ],
                'actor' => [
                    'id' => 0,
                    'name' => 'System',
                ],
                'process' => [
                    'id' => $this->processIdForUrl($subject),
                    'name' => $processName,
                    'previous_name' => $previousName,
                    'previous_date_start_user_id' => 0,
                    'next_date_start_user_id' => (int) ($subject->date_start_user_id ?? 0),
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
                'workorder_id' => $workorder?->id,
                'workorder_no' => $workorder?->number,
                'workorder_user_name' => $workorderUserName,
                'process_id' => $this->processIdForUrl($subject),
                'process_name' => $processName,
                'previous_process_name' => $previousName,
                'detail_label' => $detail['label'],
                'part_name' => $detail['name'],
                'part_number' => $detail['part_number'],
                'serial_number' => $detail['serial_number'],
                'previous_date_start_user_id' => 0,
                'next_date_start_user_id' => (int) ($subject->date_start_user_id ?? 0),
            ],
        ];
    }

    public function repeatEveryMinutes(): int
    {
        return 1;
    }

    public function shouldRun($subject): bool
    {
        if (! $subject instanceof TdrProcess && ! $subject instanceof WorkorderStdProcess) {
            return false;
        }

        if ((bool) ($subject->ignore_row ?? false)) {
            return false;
        }

        if (! empty($subject->date_start)) {
            return false;
        }

        return $this->workorderFor($subject) instanceof Workorder;
    }

    private function workorderFor(TdrProcess|WorkorderStdProcess $process): ?Workorder
    {
        if ($process instanceof WorkorderStdProcess) {
            $process->loadMissing('workorder.user');

            return $process->workorder;
        }

        $process->loadMissing('tdr.workorder.user');

        return $process->tdr?->workorder;
    }

    private function previousProcessName(TdrProcess|WorkorderStdProcess $process): string
    {
        if ($process instanceof WorkorderStdProcess) {
            $previous = WorkorderStdProcess::query()
                ->with('processName')
                ->where('workorder_id', (int) $process->workorder_id)
                ->where('id', '<', (int) $process->id)
                ->where('ignore_row', false)
                ->orderByDesc('id')
                ->first();

            return (string) ($previous?->processName?->name ?? 'Previous process');
        }

        $previous = TdrProcess::query()
            ->with('processName')
            ->where('tdrs_id', (int) $process->tdrs_id)
            ->where(function ($query) use ($process): void {
                $query->where('sort_order', '<', (int) ($process->sort_order ?? 0))
                    ->orWhere(function ($query) use ($process): void {
                        $query->where('sort_order', (int) ($process->sort_order ?? 0))
                            ->where('id', '<', (int) $process->id);
                    });
            })
            ->where('ignore_row', false)
            ->whereHas('processName', fn ($query) => $query->where('sequence_exempt', false))
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        return (string) ($previous?->processName?->name ?? 'Previous process');
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
