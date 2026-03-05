<?php
// app/Services/Events/TdrProcessOverdueStartEvent.php
namespace App\Services\Events;

use App\Models\TdrProcess;
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
        // Берём только те, у кого есть processName и std_days
        return TdrProcess::query()
            ->whereNotNull('date_start')
            ->whereNull('date_finish')
            ->whereHas('processName', fn($q) => $q->whereNotNull('std_days'))
            ->with(['processName.notifyUser', 'tdr.workorder'])
            ->get()
            ->filter(function (TdrProcess $tp) {
                $std = (int) ($tp->processName->std_days ?? 0);
                if ($std <= 0) return false;

                $deadline = Carbon::parse($tp->date_start)->addDays($std)->endOfDay();
                return now()->greaterThan($deadline);
            })
            ->values();
    }

    public function recipient($subject): ?\App\Models\User
    {
        /** @var TdrProcess $subject */
        return $subject->processName?->notifyUser;
    }

    public function message($subject): array
    {
        /** @var \App\Models\TdrProcess $subject */

        $pName = $subject->processName?->name ?? 'Process';
        $std   = (int) ($subject->processName?->std_days ?? 0);
        $start = $subject->date_start?->format('Y-m-d');

        $wo = $subject->tdr?->workorder;
        $woNo = $wo?->number ? ('WO ' . $wo->number) : 'WO ?';

        // deadline + overdue days
        $deadline = ($subject->date_start && $std > 0)
            ? \Carbon\Carbon::parse($subject->date_start)->addDays($std)->startOfDay()
            : null;

        $overdueDays = ($deadline && now()->startOfDay()->greaterThan($deadline))
            ? $deadline->diffInDays(now()->startOfDay())
            : 0;


        $url = $wo ? route('mains.show', $wo->id) : null;

        return [
            'fromUserId' => 0,
            'fromName'   => 'System',

            'type'     => 'process',
            'event'    => 'overdue',
            'severity' => 'danger',

            'ui' => [
                'workorder' => ['id' => $wo?->id, 'no' => $wo?->number],
                'process'   => ['name' => $pName],
                'dates'     => ['start' => $start],
                'std_days'  => $std,
                'overdue_days' => $overdueDays,
            ],

            'text' => "{$woNo}. Overdue: {$pName}. Start: {$start}. Std days: {$std}. Overdue days: {$overdueDays}",
            'url'  => $url,
        ];
    }

    public function repeatEveryMinutes(): int
    {
        // например, повторять не чаще чем раз в сутки:
        return 60 * 24;
        // если хочешь “один раз” — верни 0
    }

    public function shouldRun($subject): bool
    {
        $std = (int)($subject->processName?->std_days ?? 0);

        // если норматив не задан — не проверяем
        if ($std <= 0) {
            return false;
        }

        // если нет даты старта
        if (!$subject->date_start) {
            return false;
        }

        // если уже завершено
        if ($subject->date_finish) {
            return false;
        }

        return true;
    }
}
