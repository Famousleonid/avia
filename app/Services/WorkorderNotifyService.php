<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workorder;
use App\Notifications\WorkorderNotification;
use Illuminate\Support\Collection;

class WorkorderNotifyService
{

    public function approved(Workorder $workorder, int $byUserId, string $approveName): void
    {
        $notification = new WorkorderNotification(
            event: 'approved',
            payload: [
            'workorder_id' => $workorder->id,
            'workorder_no' => $workorder->number ?? null,
            'approved_at'  => optional($workorder->approved_at)->toDateTimeString(),
            'approve_name' => $approveName,
        ],
            byUserId: $byUserId,
            byUserName: $approveName,
        );

        $recipients = collect();

        // owner
        if ($workorder->user_id && $workorder->user_id !== $byUserId) {
            $owner = User::find($workorder->user_id);
            if ($owner) $recipients->push($owner);
        }

        // managers
        $managers = User::query()
            ->where('id', '!=', $byUserId)
            ->whereHas('role', fn($q) => $q->where('name', 'Manager'))
            ->get();

        $recipients = $recipients->merge($managers)->unique('id');

        // IMPORTANT:
        // notification_prefs.muted_workorders в UI сейчас хранит НОМЕРА WO (workorders.number),
        // а не workorders.id. Поэтому передаём оба, чтобы фильтр работал корректно.
        $this->notifyUsers(
            $recipients,
            $notification,
            workorderId: (int) $workorder->id,
            workorderNo: is_null($workorder->number) ? null : (int) $workorder->number
        );
    }


    /**
     * Универсально: уведомить пользователей по ролям (по имени роли из таблицы roles.name)
     * $excludeUserId — кого исключить (обычно автора события)
     */
    public function notifyRoles(array $roleNames, WorkorderNotification $notification, ?int $excludeUserId = null): void
    {
        $q = User::query()->whereHas('role', fn($r) => $r->whereIn('name', $roleNames));

        if ($excludeUserId) {
            $q->where('id', '!=', $excludeUserId);
        }

        $users = $q->get();

        $this->notifyUsers($users, $notification);
    }

    /**
     * Внутренний хелпер: разослать уведомление коллекции пользователей
     */
    protected function notifyUsers(
        Collection $users,
        WorkorderNotification $notification,
        ?int $workorderId = null,
        ?int $workorderNo = null
    ): void
    {
        if ($users->isEmpty()) return;

        $users
            ->filter(fn (User $u) => $this->canReceiveWorkorderNotification($u, $workorderId, $workorderNo))
            ->each(fn(User $u) => $u->notify($notification));
    }

    protected function canReceiveWorkorderNotification(User $user, ?int $workorderId, ?int $workorderNo): bool
    {
        $prefs = $user->notification_prefs ?? [];

        if (!empty($prefs['mute_all'])) {
            return false;
        }

        // UI сохраняет muted_workorders как номера WO (workorders.number).
        // На всякий случай поддержим и вариант, если там окажутся id.
        if ($workorderId || $workorderNo) {
            $muted = $prefs['muted_workorders'] ?? [];
            $muted = array_map('intval', is_array($muted) ? $muted : []);
            if ($workorderNo && in_array((int) $workorderNo, $muted, true)) {
                return false;
            }
            if ($workorderId && in_array((int) $workorderId, $muted, true)) {
                return false;
            }
        }

        return true;
    }
}

