<?php

namespace Tests;

use App\Models\Manual;

/** Existing training flows require a unit that has actually arrived in the shop. */
trait BuildsActiveTrainingData
{
    use BuildsDomainData {
        createManual as private createManualWithoutArrival;
    }

    protected function createManual(array $attributes = []): Manual
    {
        $manual = $this->createManualWithoutArrival($attributes);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => now()]);

        return $manual;
    }
}
