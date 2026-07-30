<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $fromUserId,
        public string $fromName,
        public string $text,
        public ?string $url = null,

        // ✅ NEW: необязательные поля для "красивых" уведомлений
        public ?string $type = null,        // process / message / workorder / ...
        public ?string $event = null,       // overdue / approved / ...
        public array $ui = [],              // structured payload for UI
        public ?string $severity = null,    // info/success/warning/danger
        public ?string $title = null,       // optional header
        public array $payload = [],         // optional raw payload
    ) {}

    public function via($notifiable): array
    {
        // FCM mirrors the in-app notification to registered mobile devices;
        // the channel no-ops without Firebase credentials / device tokens.
        return ['database', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            // было message — оставляем по умолчанию, но даём переопределить
            'type'         => $this->type ?: 'message',
            'event'        => $this->event,
            'severity'     => $this->severity,
            'title'        => $this->title,

            'ui'           => $this->ui,
            'payload'      => $this->payload, // по желанию

            'from_user_id' => $this->fromUserId,
            'from_name'    => $this->fromName,
            'text'         => $this->text,
            'url'          => $this->url,
        ];
    }
}
