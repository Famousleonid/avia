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

        $this->notifyUsers($recipients, $notification);
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
    protected function notifyUsers(Collection $users, WorkorderNotification $notification): void
    {
        if ($users->isEmpty()) return;

        $users->each(fn(User $u) => $u->notify($notification));
    }
}

