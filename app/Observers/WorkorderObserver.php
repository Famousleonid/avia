<?php

namespace App\Observers;

use App\Models\Workorder;
use App\Services\WorkorderNotifyService;

class WorkorderObserver
{
    public function updated(Workorder $workorder): void
    {
        // Срабатывает только если approve_at изменился
        if (!$workorder->wasChanged('approve_at')) {
            return;
        }

        // Если approve сняли (стало null) — ничего не делаем
        if (empty($workorder->approve_at)) {
            return;
        }

        // Кто сделал approve
        $byUser = auth()->user();

        if (!$byUser) {
            return; // если обновление без авторизации
        }

        app(WorkorderNotifyService::class)->approved(
            $workorder,
            $byUser->id,
            $workorder->approve_name // 👈 берём из модели
        );

        // 2) пример на будущее: если нужно ловить другие изменения
        // if ($workorder->wasChanged('status_id')) { ... }
        // if ($workorder->wasChanged('assigned_user_id')) { ... }
    }
}
