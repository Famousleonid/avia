<?php

namespace App\Observers;

use App\Models\Tdr;
use App\Models\Workorder;
use App\Services\WorkorderStdProcessItemsService;

class TdrObserver
{
    public function saved(Tdr $tdr): void
    {
        $this->rebuildStdItems($tdr);
    }

    public function deleted(Tdr $tdr): void
    {
        $this->rebuildStdItems($tdr);
    }

    public function restored(Tdr $tdr): void
    {
        $this->rebuildStdItems($tdr);
    }

    protected function rebuildStdItems(Tdr $tdr): void
    {
        if (! $tdr->workorder_id) {
            return;
        }

        $workorder = Workorder::query()->with('unit.manuals')->find($tdr->workorder_id);
        if (! $workorder) {
            return;
        }

        app(WorkorderStdProcessItemsService::class)->rebuild($workorder);
    }
}
