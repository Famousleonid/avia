<?php

namespace App\Services;

use App\Models\{Tdr, TdrProcess, TravelerProcessTemplate};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TravelerTemplateImages
{
    // Attachments are live, not frozen in the application snapshot (user decision 19/Oct/2026).
    // Only applications contributing rows to this printed group may attach images.
    public static function forProcesses(Tdr $tdr, Collection $processes): Collection
    {
        $rowIds = $processes->pluck('id')->all();
        if (!$rowIds) return collect();
        $templateIds = DB::table('tdr_traveler_template_applications')->where('tdr_id', $tdr->id)
            ->orderBy('id')->get()->filter(function ($application) use ($rowIds) {
                $ids = json_decode($application->process_ids, true);
                return is_array($ids) && array_intersect($rowIds, $ids) !== [];
            })->pluck('template_id')->unique();
        $templates = TravelerProcessTemplate::with('images')->whereIn('id', $templateIds)->get()->keyBy('id');
        return $templateIds->flatMap(fn ($id) => $templates->get($id)?->images ?? collect())->values();
    }

    public static function forTdr(Tdr $tdr): Collection
    {
        return self::forProcesses($tdr, TdrProcess::where('tdrs_id', $tdr->id)->where('ignore_row', false)->get(['id']));
    }
}
