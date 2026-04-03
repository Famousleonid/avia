<?php
// app/Services/Events/TdrProcessOverdueStartEvent.php
namespace App\Services\Events;

use App\Models\TdrProcess;
use App\Models\User;
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
            ->with(['processName.notifyUser', 'tdr.workorder.user', 'tdr.component'])
            ->get()
            ->filter(function (TdrProcess $tp) {
                $std = (int) ($tp->processName->std_days ?? 0);
                if ($std <= 0) return false;

                $deadline = Carbon::parse($tp->date_start)->addDays($std)->endOfDay();
                return now()->greaterThan($deadline);
            })
            ->values();
    }

    public function recipients($subject): array
    {

        $users = collect();
        $processName = $subject->processName;

        // пользователь из TdrProcess
        if (!empty($subject->user_id)) {
            $user = User::find($subject->user_id);
            if ($user) {
                $users->push($user);
            }
        }

        if ($processName?->notifyUser) {
            $users->push($processName->notifyUser);
        }

//        $managers = User::query()
//            ->whereHas('roles', function ($q) {
//                $q->where('name', 'Manager');
//            })
//            ->get();
       // $users = $users->merge($managers);


        return $users
            ->filter()
            ->unique('id')
            ->values()
            ->all();
    }

    public function message($subject): array
    {
        /** @var \App\Models\TdrProcess $subject */

        $pName = $subject->processName?->name ?? 'Process';
        $std   = (int) ($subject->processName?->std_days ?? 0);
        $start = $subject->date_start?->format('j.M.y');

        $wo = $subject->tdr?->workorder;
        $woNo = $wo?->number ? ('WO ' . $wo->number) : 'WO ?';
        $ownerName = $wo?->user?->name;
        $partNumber = $subject->tdr?->component?->part_number
            ?: $subject->tdr?->component?->assy_part_number;

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
                'workorder' => [
                    'id' => $wo?->id,
                    'no' => $wo?->number,
                    'owner_name' => $ownerName,
                ],
                'process'   => ['name' => $pName],
                'part'      => ['number' => $partNumber],
                'dates'     => ['start' => $start],
                'std_days'  => $std,
                'overdue_days' => $overdueDays,
            ],

            'text' => sprintf(
                '%s. Overdue: %s. Start: %s. Std days: %d. Overdue days: %d',
                $ownerName ? "{$woNo} ({$ownerName})" : $woNo,
                $partNumber ? "{$pName} - {$partNumber}" : $pName,
                $start ?? '',
                $std,
                $overdueDays
            ),
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

        if ($std <= 0) return false;
        if (!$subject->date_start) return false;
        if ($subject->date_finish) return false;

        $deadline = \Carbon\Carbon::parse($subject->date_start)
            ->addDays($std)
            ->startOfDay();
        return now()->greaterThanOrEqualTo($deadline);
    }

    public function oncePerDay(): bool
    {
        return true;
    }
}
