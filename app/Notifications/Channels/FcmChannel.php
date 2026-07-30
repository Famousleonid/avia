<?php

namespace App\Notifications\Channels;

use App\Notifications\NewMessageNotification;
use App\Services\Push\FcmPushService;
use Illuminate\Notifications\Notification;

/**
 * Mirrors every in-app (database) notification to the user's mobile devices.
 * No-ops when Firebase credentials are absent or the user has no registered
 * device tokens, so the web flow is unaffected.
 */
class FcmChannel
{
    public function __construct(protected FcmPushService $push)
    {
    }

    public function send($notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof \App\Models\User || ! $this->push->enabled()) {
            return;
        }

        if ($notification instanceof NewMessageNotification) {
            $title = $notification->title ?: $notification->fromName;
            $this->push->sendToUser($notifiable, $title, $notification->text, $notification->url);
        }
    }
}
