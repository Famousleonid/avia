<?php

namespace App\Observers;

use App\Models\Workorder;
use App\Services\WorkorderNotifyService;

class WorkorderObserver
{
    public function updated(Workorder $workorder): void
    {
        $byUser = auth()->user();

        if ($workorder->wasChanged('user_id') && $workorder->user_id && $byUser) {
            app(WorkorderNotifyService::class)->assigned(
                $workorder,
                $byUser->id,
                $byUser->name
            );
        }

        if (! $workorder->wasChanged('approve_at')) {
            return;
        }

        if (empty($workorder->approve_at) || ! $byUser) {
            return;
        }

        app(WorkorderNotifyService::class)->approved(
            $workorder,
            $byUser->id,
            $workorder->approve_name
        );
    }
}
