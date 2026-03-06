<?php
// app/Services/Events/EventRunner.php
namespace App\Services\Events;

use App\Models\EventLog;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Log;

class EventRunner
{
    /** @param EventDefinition[] $events */
    public function run(array $events): void
    {
        foreach ($events as $event) {

            $subjects = $event->dueSubjects();

            foreach ($subjects as $subject) {

                if (method_exists($event, 'shouldRun') && !$event->shouldRun($subject)) {
                    continue;
                }

                $recipients = $event->recipients($subject) ?? [];

                if (empty($recipients)) {
                    continue;
                }

                $msg = $event->message($subject);

                if (!$msg || !is_array($msg)) {
                    continue;
                }

                foreach ($recipients as $recipient) {
                    if (!$recipient) {
                        continue;
                    }

                    $log = EventLog::query()->firstOrNew([
                        'event_key'         => $event->key(),
                        'subject_type'      => get_class($subject),
                        'subject_id'        => $subject->getKey(),
                        'recipient_user_id' => $recipient->id,
                    ]);

                    if ($log->exists && $log->last_sent_at) {
                        if (method_exists($event, 'oncePerDay') && $event->oncePerDay()) {
                            if ($log->last_sent_at->isToday()) {
                                continue;
                            }
                        } else {
                            $repeatMin = (int)($event->repeatEveryMinutes() ?? 0);

                            if ($repeatMin === 0) {
                                continue;
                            }

                            if ($log->last_sent_at->copy()->addMinutes($repeatMin)->isFuture()) {
                                continue;
                            }
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
            }
        }
    }
}
