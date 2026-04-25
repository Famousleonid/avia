<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workorder;
use App\Notifications\NewMessageNotification;
use App\Notifications\WorkorderNotification;
use Illuminate\Support\Collection;

class WorkorderNotifyService
{
    public function assigned(Workorder $workorder, int $byUserId, string $byUserName): void
    {
        $msg = [
            'fromUserId' => $byUserId,
            'fromName' => $byUserName,
            'type' => 'workorder',
            'event' => 'assigned',
            'severity' => 'info',
            'title' => 'Workorder assigned',
            'text' => "Workorder {$workorder->number} was assigned by {$byUserName}",
            'url' => route('mains.show', $workorder->id),
            'ui' => [
                'workorder' => [
                    'id' => $workorder->id,
                    'no' => $workorder->number,
                ],
                'actor' => [
                    'id' => $byUserId,
                    'name' => $byUserName,
                ],
            ],
            'payload' => [
                'workorder_id' => $workorder->id,
                'workorder_no' => $workorder->number,
                'assigned_to_user_id' => $workorder->user_id,
            ],
        ];

        $this->notifyByRules('workorder.assigned', $workorder, $msg);
    }

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

        $this->notifyByRules('workorder.approved', $workorder, $msg);
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
        $resolver = app(NotificationEventRuleResolver::class);
        $rules = $resolver->activeRules($eventKey);

        if ($rules->isEmpty()) {
            return false;
        }

        foreach ($rules as $rule) {
            $ruleMsg = $resolver->renderMessage($rule, $msg);
            $recipients = $resolver->recipients($rule, $workorder, $ruleMsg);

            foreach ($recipients as $recipient) {
                if (
                    $rule->respect_user_preferences
                    && ! $this->canReceiveWorkorderNotification(
                        $recipient,
                        (int) $workorder->id,
                        is_null($workorder->number) ? null : (int) $workorder->number
                    )
                ) {
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

    public function notifyRoles(array $roleNames, WorkorderNotification $notification, ?int $excludeUserId = null): void
    {
        $query = User::query()->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', $roleNames));

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $users = $query->get();

        $this->notifyUsers($users, $notification);
    }

    protected function notifyUsers(
        Collection $users,
        WorkorderNotification $notification,
        ?int $workorderId = null,
        ?int $workorderNo = null
    ): void {
        if ($users->isEmpty()) {
            return;
        }

        $users
            ->filter(fn (User $user) => $this->canReceiveWorkorderNotification($user, $workorderId, $workorderNo))
            ->each(fn (User $user) => $user->notify($notification));
    }

    protected function canReceiveWorkorderNotification(User $user, ?int $workorderId, ?int $workorderNo): bool
    {
        $prefs = $user->notification_prefs ?? [];

        if (! empty($prefs['mute_all'])) {
            return false;
        }

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
