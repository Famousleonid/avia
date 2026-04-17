<?php
// app/Services/Events/EventRunner.php
namespace App\Services\Events;

use App\Models\EventLog;
use App\Models\NotificationEventRule;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\NotificationEventRuleResolver;

class EventRunner
{
    public function __construct(
        protected NotificationEventRuleResolver $ruleResolver,
    ) {}

    /** @param EventDefinition[] $events */
    public function run(array $events): void
    {
        foreach ($events as $event) {

            $subjects = $event->dueSubjects();

            foreach ($subjects as $subject) {

                if (method_exists($event, 'shouldRun') && !$event->shouldRun($subject)) {
                    continue;
                }

                $msg = $event->message($subject);

                if (!$msg || !is_array($msg)) {
                    continue;
                }

                $rules = $this->ruleResolver->activeRules($event->key());

                if ($rules->isNotEmpty()) {
                    foreach ($rules as $rule) {
                        $ruleMsg = $this->ruleResolver->renderMessage($rule, $msg);
                        $recipients = $this->ruleResolver->recipients($rule, $subject, $ruleMsg);
                        $repeatMin = $rule->repeatEveryMinutes((int)($event->repeatEveryMinutes() ?? 0));

                        foreach ($recipients as $recipient) {
                            $this->sendToRecipient($event, $subject, $recipient, $ruleMsg, $repeatMin, $rule);
                        }
                    }

                    continue;
                }

                $recipients = $event->recipients($subject) ?? [];

                foreach ($recipients as $recipient) {
                    $repeatMin = method_exists($event, 'oncePerDay') && $event->oncePerDay()
                        ? 60 * 24
                        : (int)($event->repeatEveryMinutes() ?? 0);

                    $this->sendToRecipient($event, $subject, $recipient, $msg, $repeatMin);
                }
            }
        }
    }

    protected function sendToRecipient(
        EventDefinition $event,
        mixed $subject,
        mixed $recipient,
        array $msg,
        int $repeatMin,
        ?NotificationEventRule $rule = null
    ): void {
        if (!$recipient instanceof User) {
            return;
        }

        if (($rule?->respect_user_preferences ?? true) && !$this->canReceiveEventNotification($recipient, $msg)) {
            return;
        }

        $log = EventLog::query()->firstOrNew([
            'event_key' => $event->key(),
            'notification_event_rule_id' => $rule?->id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'recipient_user_id' => $recipient->id,
        ]);

        if ($log->exists && $log->last_sent_at) {
            if ($repeatMin === 0) {
                return;
            }

            if ($log->last_sent_at->copy()->addMinutes($repeatMin)->isFuture()) {
                return;
            }
        }

        $recipient->notify(new NewMessageNotification(
            fromUserId: (int)($msg['fromUserId'] ?? $msg['from_user_id'] ?? 0),
            fromName: (string)($msg['fromName'] ?? $msg['from_name'] ?? 'System'),
            text: (string)($msg['text'] ?? ''),
            url: $msg['url'] ?? null,

            type: $msg['type'] ?? null,
            event: $msg['event'] ?? null,
            ui: $msg['ui'] ?? [],
            severity: $msg['severity'] ?? null,
            title: $msg['title'] ?? null,
            payload: $msg['payload'] ?? [],
        ));

        $now = now();
        if (!$log->first_sent_at) {
            $log->first_sent_at = $now;
        }
        $log->last_sent_at = $now;
        $log->sent_count = (int)($log->sent_count ?? 0) + 1;
        $log->save();
    }

    protected function canReceiveEventNotification($recipient, array $msg): bool
    {
        // recipient is usually App\Models\User (Notifiable)
        $prefs = $recipient->notification_prefs ?? [];

        if (!empty($prefs['mute_all'])) {
            return false;
        }

        $mutedWorkorders = $prefs['muted_workorders'] ?? [];
        $mutedWorkorders = array_map('intval', is_array($mutedWorkorders) ? $mutedWorkorders : []);

        if (empty($mutedWorkorders)) {
            return true;
        }

        // UI сохраняет muted_workorders как номера WO (workorders.number).
        // Для совместимости проверяем и id и number.
        $woId = null;
        $woNo = null;

        if (isset($msg['ui']['workorder']['id'])) {
            $woId = (int) $msg['ui']['workorder']['id'];
        }
        if (isset($msg['ui']['workorder']['no'])) {
            $woNo = (int) $msg['ui']['workorder']['no'];
        }
        if (is_null($woId) && isset($msg['payload']['workorder_id'])) {
            $woId = (int) $msg['payload']['workorder_id'];
        }
        if (is_null($woNo) && isset($msg['payload']['workorder_no'])) {
            $woNo = (int) $msg['payload']['workorder_no'];
        }

        if ($woNo && in_array($woNo, $mutedWorkorders, true)) return false;
        if ($woId && in_array($woId, $mutedWorkorders, true)) return false;

        return true;
    }
}
