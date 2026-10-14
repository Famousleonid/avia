<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class TrainingHours
{
    /** Include the full dated history so a year boundary cannot turn an update into initial training. */
    public function forRecords(Collection $records): array
    {
        $previous = [];
        $hours = [];
        foreach ($records->sortBy([['date_training', 'asc'], ['id', 'asc']]) as $record) {
            if ($record->is_legacy || !$record->date_training || (string) $record->form_type === '132') continue;
            if ($record->matrix_row_id) {
                // SCA courses currently have no configured duration.
                $hours[$record->id] = null;
                continue;
            }
            $key = $record->user_id.':'.$record->manuals_id;
            $date = Carbon::parse($record->date_training);
            $initial = !isset($previous[$key]) || $previous[$key]->diffInDays($date) >= 365;
            $daily = $record->manual?->training_hours;
            $hours[$record->id] = $initial ? (is_numeric($daily) ? max(0, (float) $daily) * 5 : null) : 10.0;
            $previous[$key] = $date;
        }
        return $hours;
    }
}
