<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkorderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public array $payload,
        public int $byUserId,
        public string $byUserName,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $workorderId = $this->payload['workorder_id'] ?? null;
        $no          = $this->payload['workorder_no'] ?? ($workorderId ? "#{$workorderId}" : '');
        $approveName = $this->payload['approve_name'] ?? $this->byUserName;

        // Текст для UI (как у NewMessageNotification)
        if ($this->event === 'approved') {
            $text = "Workorder {$no} approved by {$approveName}";
        } else {
            $text = "Workorder {$no} updated";
        }

        return [
            'type'         => 'workorder',
            'event'        => $this->event,
            'payload'      => $this->payload,
            'text'         => $text,
            'url'       => $workorderId ? route('mains.show', $workorderId) : null,
            'by_user_id'   => $this->byUserId,
            'by_user_name' => $this->byUserName,
        ];
    }
}
