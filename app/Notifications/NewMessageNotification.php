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
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // 👈 пишем в notifications
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'message',
            'from_user_id'=> $this->fromUserId,
            'from_name'   => $this->fromName,
            'text'        => $this->text,
        ];
    }
}
