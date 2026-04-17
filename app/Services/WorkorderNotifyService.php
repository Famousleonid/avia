<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workorder;
use App\Notifications\NewMessageNotification;
use App\Notifications\WorkorderNotification;
use Illuminate\Support\Collection;

class WorkorderNotifyService
{

    public function approved(Workorder $workorder, int $byUserId, string $approveName): void
    {
        $msg = [
            'fromUserId' => $byUserId,
            'fromName' => $approveName,
            'type' => 'workorder',
            'event' => 'approved',
            'severity' => 'success',
            'title' => 'Approved',
            'text' => "Workorder {$workorder->number} approved by {$approveName}",
            'url' => route('mains.show', $workorder->id),
            'ui' => [
                'workorder' => [
                    'id' => $workorder->id,
                    'no' => $workorder->number,
                ],
                'actor' => [
                    'id' => $byUserId,
                    'name' => $approveName,
                ],
            ],
            'payload' => [
                'workorder_id' => $workorder->id,
                'workorder_no' => $workorder->number,
                'approve_name' => $approveName,
            ],
        ];

        if ($this->notifyByRules('workorder.approved', $workorder, $msg)) {
            return;
        }

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

    public function draftCreated(Workorder $workorder, int $byUserId, string $byUserName): void
    {
        $workorder->loadMissing(['unit', 'customer']);

        $msg = [
            'fromUserId' => $byUserId,
            'fromName' => $byUserName,
            'type' => 'workorder',
            'event' => 'draft_created',
            'severity' => 'info',
            'title' => 'Draft Workorder created',
            'text' => "Draft WO {$workorder->number} created by {$byUserName}",
            'url' => route('workorders.edit', $workorder->id),
            'ui' => [
                'workorder' => [
                    'id' => $workorder->id,
                    'no' => $workorder->number,
                    'is_draft' => (bool) $workorder->is_draft,
                ],
                'actor' => [
                    'id' => $byUserId,
                    'name' => $byUserName,
                ],
                'part' => [
                    'number' => $workorder->unit?->part_number,
                ],
                'unit' => [
                    'id' => $workorder->unit_id,
                    'serial_number' => $workorder->serial_number,
                ],
                'customer' => [
                    'id' => $workorder->customer_id,
                    'name' => $workorder->customer?->name,
                ],
            ],
            'payload' => [
                'workorder_id' => $workorder->id,
                'workorder_no' => $workorder->number,
                'created_by_user_id' => $byUserId,
            ],
        ];

        $this->notifyByRules('workorder.draft_created', $workorder, $msg);
    }

    protected function notifyByRules(string $eventKey, Workorder $workorder, array $msg): bool
    {
        $resolver = app(\App\Services\NotificationEventRuleResolver::class);
        $rules = $resolver->activeRules($eventKey);

        if ($rules->isEmpty()) {
            return false;
        }

        foreach ($rules as $rule) {
            $ruleMsg = $resolver->renderMessage($rule, $msg);
            $recipients = $resolver->recipients($rule, $workorder, $ruleMsg);

            foreach ($recipients as $recipient) {
                if ($rule->respect_user_preferences && ! $this->canReceiveWorkorderNotification($recipient, (int) $workorder->id, is_null($workorder->number) ? null : (int) $workorder->number)) {
                    continue;
                }

                $recipient->notify(new NewMessageNotification(
                    fromUserId: (int) ($ruleMsg['fromUserId'] ?? $ruleMsg['from_user_id'] ?? 0),
                    fromName: (string) ($ruleMsg['fromName'] ?? $ruleMsg['from_name'] ?? ''),
                    text: (string) ($ruleMsg['text'] ?? ''),
                    url: $ruleMsg['url'] ?? null,
                    type: $ruleMsg['type'] ?? 'workorder',
                    event: $ruleMsg['event'] ?? $eventKey,
                    ui: $ruleMsg['ui'] ?? [],
                    severity: $ruleMsg['severity'] ?? 'info',
                    title: $ruleMsg['title'] ?? 'Notification',
                    payload: $ruleMsg['payload'] ?? [],
                ));
            }
        }

        return true;
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

