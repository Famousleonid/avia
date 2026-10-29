<?php

namespace App\Services;

use App\Models\Tdr;
use App\Models\Workorder;
use Illuminate\Support\Collection;

class WorkorderRepairRows
{
    public static function isCustomKey(string $key): bool
    {
        return preg_match('/^custom:[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $key) === 1;
    }

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
        foreach ($overrides as $key => $override) {
            if (self::isCustomKey((string) $key)) {
                $rows->push([
                    'key' => $key, 'ipl' => '', 'part_description' => '', 'mod_repair' => 'Repair',
                    'description' => '', 'ident_method' => '', 'ident_number' => '', 'deleted' => false,
                ]);
            }
        }
        return $rows->map(function ($row) use ($overrides) {
            $override = $overrides->get($row['key'], []);
            $row = array_replace($row, array_intersect_key($override, array_flip([
                'ipl', 'part_description', 'mod_repair', 'description', 'ident_method', 'ident_number', 'deleted',
            ])));
            $row['ipl'] = (string) ($row['ipl'] ?? '');
            $row['part_description'] = (string) ($row['part_description'] ?? '');
            $row['deleted'] = (bool) $row['deleted'];
            return $row;
        });
    }
}
