<?php

namespace App\Services;

use App\Models\Tdr;
use App\Models\Workorder;
use Illuminate\Support\Collection;

class WorkorderRepairRows
{
    public function rows(Workorder $workorder, Collection $records, ?array $overrides = null): Collection
    {
        $saved = json_decode((string) $workorder->rm_report, true) ?: [];
        $overrides = collect($overrides ?? ($saved['editor_rows'] ?? []))->keyBy('key');
        $rows = Tdr::where('workorder_id', $workorder->id)
            ->where('use_tdr', true)
            ->whereHas('necessaries', fn ($q) => $q->where('name', 'Repair'))
            ->with('component')->inDisplayOrder()->get()->filter(fn ($tdr) => $tdr->component)
            ->map(fn ($tdr) => [
                'key' => 'tdr:'.$tdr->id,
                'ipl' => (string) $tdr->component->ipl_num,
                'part_description' => (string) $tdr->component->name,
                'mod_repair' => 'Repair', 'description' => '', 'ident_method' => '', 'ident_number' => '',
                'deleted' => false,
            ])->values();
        foreach ($records as $record) {
            $rows->push([
                'key' => 'record:'.$record->id, 'ipl' => '',
                'part_description' => $record->part_description,
                'mod_repair' => $record->mod_repair === 'Mod' ? 'Modification' : $record->mod_repair,
                'description' => $record->description, 'ident_method' => (string) $record->ident_method,
                'ident_number' => '', 'deleted' => false,
            ]);
        }
        return $rows->map(function ($row) use ($overrides) {
            $override = $overrides->get($row['key'], []);
            $row = array_replace($row, array_intersect_key($override, array_flip([
                'mod_repair', 'description', 'ident_method', 'ident_number', 'deleted',
            ])));
            $row['deleted'] = (bool) $row['deleted'];
            return $row;
        });
    }
}
