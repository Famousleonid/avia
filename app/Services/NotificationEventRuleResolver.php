<?php

namespace App\Services;

use App\Models\NotificationEventRule;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class NotificationEventRuleResolver
{
    public function activeRules(string $eventKey): Collection
    {
        return NotificationEventRule::query()
            ->with('recipients')
            ->where('event_key', $eventKey)
            ->where('enabled', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function recipients(NotificationEventRule $rule, mixed $subject, array $message): Collection
    {
        $users = collect();

        foreach ($rule->recipients as $recipient) {
            $value = (string) $recipient->recipient_value;

            if ($recipient->recipient_type === 'user') {
                $user = User::find((int) $value);
                if ($user) {
                    $users->push($user);
                }
                continue;
            }

            if ($recipient->recipient_type === 'role') {
                $users = $users->merge(
                    User::query()
                        ->where('role_id', (int) $value)
                        ->get()
                );
                continue;
            }

            if ($recipient->recipient_type === 'dynamic') {
                $users = $users->merge($this->dynamicRecipients($value, $subject, $message));
            }
        }

        if ($rule->exclude_actor) {
            $actorId = (int) (
                $message['fromUserId']
                ?? $message['from_user_id']
                ?? $message['by_user_id']
                ?? Arr::get($message, 'ui.actor.id')
                ?? 0
            );

            if ($actorId > 0) {
                $users = $users->reject(fn (User $user) => (int) $user->id === $actorId);
            }
        }

        return $users
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function dynamicRecipients(string $key, mixed $subject, array $message): Collection
    {
        $users = collect();

        if ($key === 'tdr_process_user' && ! empty($subject->user_id)) {
            $user = User::find((int) $subject->user_id);
            if ($user) {
                $users->push($user);
            }
        }

        if ($key === 'process_notify_user' && $subject?->processName?->notifyUser) {
            $users->push($subject->processName->notifyUser);
        }

        if ($key === 'workorder_technician') {
            $user = $subject?->tdr?->workorder?->user ?? $subject?->user ?? null;
            if ($user) {
                $users->push($user);
            }
        }

        if ($key === 'draft_creator') {
            $actorId = (int) (
                $message['fromUserId']
                ?? $message['from_user_id']
                ?? $message['by_user_id']
                ?? Arr::get($message, 'ui.actor.id')
                ?? 0
            );
            $user = $actorId > 0 ? User::find($actorId) : null;
            if ($user) {
                $users->push($user);
            }
        }

        if ($key === 'birthday_user' && $subject instanceof User) {
            $users->push($subject);
        }

        return $users;
    }

    public function renderMessage(NotificationEventRule $rule, array $message): array
    {
        $vars = $this->variables($message);

        $title = $rule->title_template ?: ($message['title'] ?? null);
        $text = $rule->message_template ?: ($message['text'] ?? '');

        $message['title'] = $this->renderTemplate($title, $vars);
        $message['text'] = $this->renderTemplate($text, $vars);
        $message['severity'] = $rule->severity ?: ($message['severity'] ?? 'info');
        $message['event_rule_id'] = $rule->id;

        return $message;
    }

    protected function renderTemplate(?string $template, array $vars): ?string
    {
        if ($template === null) {
            return null;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($match) use ($vars) {
            return (string) ($vars[$match[1]] ?? '');
        }, $template);
    }

    protected function variables(array $message): array
    {
        return [
            'event' => (string) ($message['event'] ?? ''),
            'type' => (string) ($message['type'] ?? ''),
            'workorder_id' => (string) (Arr::get($message, 'ui.workorder.id') ?? Arr::get($message, 'payload.workorder_id') ?? ''),
            'workorder_no' => (string) (Arr::get($message, 'ui.workorder.no') ?? Arr::get($message, 'payload.workorder_no') ?? ''),
            'owner_name' => (string) (Arr::get($message, 'ui.workorder.owner_name') ?? ''),
            'process_name' => (string) (Arr::get($message, 'ui.process.name') ?? ''),
            'part_number' => (string) (Arr::get($message, 'ui.part.number') ?? ''),
            'serial_number' => (string) (Arr::get($message, 'ui.unit.serial_number') ?? ''),
            'customer_name' => (string) (Arr::get($message, 'ui.customer.name') ?? ''),
            'start_date' => (string) (Arr::get($message, 'ui.dates.start') ?? ''),
            'std_days' => (string) (Arr::get($message, 'ui.std_days') ?? ''),
            'overdue_days' => (string) (Arr::get($message, 'ui.overdue_days') ?? ''),
            'actor_name' => (string) (Arr::get($message, 'ui.actor.name') ?? $message['by_user_name'] ?? $message['fromName'] ?? $message['from_name'] ?? ''),
            'birthday_user_name' => (string) (Arr::get($message, 'ui.birthday.user.name') ?? ''),
            'birthday_age' => (string) (Arr::get($message, 'ui.birthday.age') ?? ''),
        ];
    }
}
