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
        $woNoRaw     = $this->payload['workorder_no'] ?? null;
        $woNo        = $woNoRaw ?: ($workorderId ? (string)$workorderId : null); // без "#"
        $approveName = $this->payload['approve_name'] ?? $this->byUserName;

        // 1) Нормализуем "UI payload"
        $ui = [
            'workorder' => [
                'id' => $workorderId,
                'no' => $woNo, // номер без "#", UI сам решит формат
            ],
            'actor' => [
                'id' => $this->byUserId,
                'name' => $this->byUserName,
            ],
            // сюда можно добавлять "process", "days", "part", etc. по мере нужды
        ];

        // 2) UI-поля
        $severity = 'info';
        $title    = 'Workorder update';
        $text     = 'Workorder updated';

        if ($this->event === 'approved') {
            $severity = 'success';
            $title    = 'Approved';
            $text     = "Workorder #{$woNo} approved by {$approveName}";
            $ui['approve'] = ['by' => $approveName];
        }

        // 3) URL — оставляем
        $url = $workorderId ? route('mains.show', $workorderId) : null;

        return [
            'type'         => 'workorder',
            'event'        => $this->event,

            // ВАЖНО: сохраняем оба: оригинальный payload и нормализованный ui
            'payload'      => $this->payload,
            'ui'           => $ui,

            // UI-friendly доп поля
            'severity'     => $severity,   // info|success|warning|danger
            'title'        => $title,

            // fallback текст (на случай неизвестного event)
            'text'         => $text,

            'url'          => $url,

            'by_user_id'   => $this->byUserId,
            'by_user_name' => $this->byUserName,
        ];
    }
}
