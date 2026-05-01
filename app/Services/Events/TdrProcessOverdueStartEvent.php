<?php

namespace App\Services\Events;

use App\Models\TdrProcess;
use App\Models\User;
use App\Services\WorkorderStdListProcessesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TdrProcessOverdueStartEvent implements EventDefinition
{
    public function key(): string
    {
        return 'tdr_process.overdue_start';
    }

    public function dueSubjects(): Collection
    {
        $stdListNames = array_values(WorkorderStdListProcessesService::NAME_BY_KEY);
        $stdListResolver = app(WorkorderStdListProcessesService::class);
        $preferredStdProcessIds = [];

        return TdrProcess::query()
            ->whereNotNull('date_start')
            ->whereNull('date_finish')
            ->whereHas('processName', fn ($query) => $query->whereNotNull('std_days'))
            ->whereHas('tdr.workorder')
            ->with(['processName.notifyUser', 'tdr.workorder.user', 'tdr.component'])
            ->get()
            ->filter(function (TdrProcess $process): bool {
                $std = (int) ($process->processName->std_days ?? 0);
                if ($std <= 0) {
                    return false;
                }

                $deadline = Carbon::parse($process->date_start)->addDays($std)->endOfDay();

                return now()->greaterThan($deadline);
            })
            ->filter(function (TdrProcess $process) use ($stdListNames, $stdListResolver, &$preferredStdProcessIds): bool {
                $processName = trim((string) ($process->processName?->name ?? ''));
                if (!in_array($processName, $stdListNames, true)) {
                    return true;
                }

                $workorder = $process->tdr?->workorder;
                if (!$workorder) {
                    return false;
                }

                $cacheKey = $workorder->getKey() . ':' . (int) $process->process_names_id;
                if (!array_key_exists($cacheKey, $preferredStdProcessIds)) {
                    $preferred = $stdListResolver->findPreferredStdListProcessForWorkorder(
                        $workorder,
                        (int) $process->process_names_id
                    );

                    $preferredStdProcessIds[$cacheKey] = $preferred?->getKey();
                }

                return (int) $preferredStdProcessIds[$cacheKey] === (int) $process->getKey();
            })
            ->values();
    }

    public function recipients($subject): array
    {
        $users = collect();
        $processName = $subject->processName;

        if (!empty($subject->user_id)) {
            $user = User::find($subject->user_id);
            if ($user) {
                $users->push($user);
            }
        }

        if ($processName?->notifyUser) {
            $users->push($processName->notifyUser);
        }

        return $users
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }

    public function message($subject): array
    {
        /** @var TdrProcess $subject */

        $processName = $subject->processName?->name ?? 'Process';
        $isStdList = $this->isStdListProcess($subject);
        $stdDays = (int) ($subject->processName?->std_days ?? 0);
        $start = $subject->date_start?->format('j.M.y');

        $workorder = $subject->tdr?->workorder;
        $workorderLabel = $workorder?->number ? ('WO ' . $workorder->number) : 'WO ?';
        $ownerName = $workorder?->user?->name;

        $partName = '';
        $partDisplay = '';
        if (!$isStdList) {
            $component = $subject->tdr?->component;
            $partNumber = $component?->part_number ?: $component?->assy_part_number;
            $partName = trim((string) ($component?->name ?? ''));
            $partDisplay = $partNumber ?: '';

            if ($partDisplay !== '' && $partName !== '') {
                $partDisplay .= " ({$partName})";
            } elseif ($partDisplay === '' && $partName !== '') {
                $partDisplay = $partName;
            }
        }

        $deadline = ($subject->date_start && $stdDays > 0)
            ? Carbon::parse($subject->date_start)->addDays($stdDays)->startOfDay()
            : null;

        $overdueDays = ($deadline && now()->startOfDay()->greaterThan($deadline))
            ? $deadline->diffInDays(now()->startOfDay())
            : 0;

        $url = $workorder ? route('mains.show', $workorder->id) : null;

        return [
            'fromUserId' => 0,
            'fromName' => 'System',
            'type' => 'process',
            'event' => 'overdue',
            'severity' => 'danger',
            'ui' => [
                'workorder' => [
                    'id' => $workorder?->id,
                    'no' => $workorder?->number,
                    'owner_name' => $ownerName,
                ],
                'process' => ['name' => $processName],
                'part' => [
                    'number' => $partDisplay,
                    'name' => $partName,
                ],
                'dates' => ['start' => $start],
                'std_days' => $stdDays,
                'overdue_days' => $overdueDays,
            ],
            'text' => sprintf(
                '%s. Overdue: %s. Start: %s. Std days: %d. Overdue days: %d',
                $ownerName ? "{$workorderLabel} ({$ownerName})" : $workorderLabel,
                $partDisplay ? "{$processName} - {$partDisplay}" : $processName,
                $start ?? '',
                $stdDays,
                $overdueDays
            ),
            'url' => $url,
        ];
    }

    public function repeatEveryMinutes(): int
    {
        return 60 * 24;
    }

    public function shouldRun($subject): bool
    {
        $std = (int) ($subject->processName?->std_days ?? 0);

        if ($std <= 0) {
            return false;
        }

        if (!$subject->date_start) {
            return false;
        }

        if ($subject->date_finish) {
            return false;
        }

        $deadline = Carbon::parse($subject->date_start)
            ->addDays($std)
            ->startOfDay();

        return now()->greaterThanOrEqualTo($deadline);
    }

    public function oncePerDay(): bool
    {
        return true;
    }

    private function isStdListProcess(TdrProcess $subject): bool
    {
        return in_array(
            trim((string) ($subject->processName?->name ?? '')),
            array_values(WorkorderStdListProcessesService::NAME_BY_KEY),
            true
        );
    }
}
