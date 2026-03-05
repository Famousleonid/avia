<?php
// app/Services/Events/EventRunner.php
namespace App\Services\Events;

use App\Models\EventLog;
use App\Notifications\NewMessageNotification;

class EventRunner
{
    /** @param EventDefinition[] $events */
    public function run(array $events): void
    {
        foreach ($events as $event) {

            // 1) subjects (что нужно проверить)
            $subjects = $event->dueSubjects();
            if (empty($subjects)) {
                continue;
            }

            foreach ($subjects as $subject) {

                // 2) optional per-subject gate (std_days, date_finish, etc.)
                if (method_exists($event, 'shouldRun')) {
                    try {
                        if (!$event->shouldRun($subject)) {
                            continue;
                        }
                    } catch (\Throwable $e) {
                        // если shouldRun упал — лучше пропустить, чем завалить весь runner
                        continue;
                    }
                }

                // 3) кому слать
                $recipient = $event->recipient($subject);
                if (!$recipient) {
                    continue;
                }

                // 4) лог по событию+субъекту+получателю
                $log = EventLog::query()->firstOrNew([
                    'event_key'         => $event->key(),
                    'subject_type'      => get_class($subject),
                    'subject_id'        => $subject->getKey(),
                    'recipient_user_id' => $recipient->id,
                ]);

                // 5) антидубликат + периодичность
                $repeatMin = (int)($event->repeatEveryMinutes() ?? 0);

                if ($log->exists && $log->last_sent_at) {

                    // 0 = один раз и всё
                    if ($repeatMin === 0) {
                        continue;
                    }

                    // ещё рано повторять
                    $next = $log->last_sent_at->copy()->addMinutes($repeatMin);
                    if ($next->isFuture()) {
                        continue;
                    }
                }

                // 6) сформировать сообщение
                $msg = $event->message($subject);

                // message может вернуть null/false → не отправляем
                if (!$msg || !is_array($msg)) {
                    continue;
                }

                // 7) нормализуем обязательные ключи (чтобы не ловить undefined)
                $fromUserId = (int)($msg['fromUserId'] ?? $msg['from_user_id'] ?? 0);
                $fromName   = (string)($msg['fromName'] ?? $msg['from_name'] ?? 'System');
                $text       = (string)($msg['text'] ?? '');
                $url        = $msg['url'] ?? null;

                // если нет текста и нет ui — нечего слать
                $ui = $msg['ui'] ?? [];
                if ($text === '' && (empty($ui) || !is_array($ui))) {
                    continue;
                }

                // 8) отправка
                $recipient->notify(new NewMessageNotification(
                    fromUserId: $fromUserId,
                    fromName: $fromName,
                    text: $text,
                    url: $url,

                    type: $msg['type'] ?? null,
                    event: $msg['event'] ?? null,
                    ui: is_array($ui) ? $ui : [],
                    severity: $msg['severity'] ?? null,
                    title: $msg['title'] ?? null,
                    payload: is_array($msg['payload'] ?? null) ? ($msg['payload'] ?? []) : [],
                ));

                // 9) записать лог
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
