<?php

namespace App\Services;

use App\Models\DateNotification;
use App\Models\DateNotificationLog;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Collection;

class DateNotificationService
{
    public function sendDueForToday(): void
    {
        $today = now();

        $notifications = DateNotification::query()
            ->with('recipients')
            ->where('enabled', true)
            ->where('run_month', (int) $today->month)
            ->where('run_day', (int) $today->day)
            ->where(function ($query) use ($today) {
                $query->where('repeats_yearly', true)
                    ->orWhere(function ($inner) use ($today) {
                        $inner->where('repeats_yearly', false)
                            ->where('run_year', (int) $today->year);
                    });
            })
            ->get();

        foreach ($notifications as $notification) {
            $recipients = $this->recipients($notification);

            foreach ($recipients as $recipient) {
                if (
                    $notification->respect_user_preferences
                    && ! $this->canReceiveNotification($recipient)
                ) {
                    continue;
                }

                $alreadySent = DateNotificationLog::query()
                    ->where('date_notification_id', $notification->id)
                    ->where('recipient_user_id', $recipient->id)
                    ->whereDate('sent_on', $today->toDateString())
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $recipient->notify(new NewMessageNotification(
                    fromUserId: 0,
                    fromName: 'System',
                    text: (string) $notification->message,
                    url: null,
                    type: 'date_notification',
                    event: 'date_notification',
                    ui: [
                        'date_notification' => [
                            'id' => $notification->id,
                            'name' => $notification->name,
                            'year' => $notification->run_year,
                            'month' => $notification->run_month,
                            'day' => $notification->run_day,
                            'repeats_yearly' => (bool) $notification->repeats_yearly,
                        ],
                    ],
                    severity: 'info',
                    title: $notification->title ?: $notification->name,
                    payload: [
                        'date_notification_id' => $notification->id,
                        'name' => $notification->name,
                    ],
                ));

                DateNotificationLog::query()->create([
                    'date_notification_id' => $notification->id,
                    'recipient_user_id' => $recipient->id,
                    'sent_on' => $today->toDateString(),
                ]);
            }
        }
    }

    public function recipients(DateNotification $notification): Collection
    {
        $users = collect();

        foreach ($notification->recipients as $recipient) {
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
                    User::query()->where('role_id', (int) $value)->get()
                );
                continue;
            }

            if ($recipient->recipient_type === 'dynamic') {
                if ($value === 'system_admins') {
                    $users = $users->merge(
                        User::query()
                            ->where('is_admin', true)
                            ->whereHas('role', fn ($query) => $query->where('name', 'Admin'))
                            ->get()
                    );
                }

                if ($value === 'all_users') {
                    $users = $users->merge(User::query()->get());
                }
            }
        }

        return $users
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function canReceiveNotification(User $user): bool
    {
        $prefs = $user->notification_prefs ?? [];

        return empty($prefs['mute_all']);
    }
}
