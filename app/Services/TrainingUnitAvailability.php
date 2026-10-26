<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingUnitAvailability
{
    /** Training P/N series are identified by manual; Units hold their actual P/Ns. */
    public function forManuals(iterable $manualIds): array
    {
        $ids = collect($manualIds)->filter()->unique()->values()->all();
        if (!$ids) return [];

        $arrivals = DB::table('workorders as w')->join('units as u', 'u.id', '=', 'w.unit_id')
            ->whereIn('u.manual_id', $ids)->whereNull('w.deleted_at')
            ->where('w.is_draft', false)->where('w.open_at', '<=', now())
            ->selectRaw('u.manual_id, MAX(w.open_at) as last_arrival')
            ->groupBy('u.manual_id')->pluck('last_arrival', 'manual_id');
        $cutoff = now()->startOfDay()->subMonthsNoOverflow(12);
        $result = [];
        foreach ($ids as $id) {
            $last = $arrivals[$id] ?? null;
            $available = $last && Carbon::parse($last)->gte($cutoff);
            $message = $last
                ? __('Last unit arrival: :date.', ['date' => format_project_date($last)])
                : __('No dated unit arrival on record.');
            $result[$id] = ['available' => (bool) $available, 'last_arrival' => $last,
                'message' => $message . ($available ? '' : ' ' . __('No workorder in the last 12 months. Training history can still be recorded.'))];
        }
        return $result;
    }

}
