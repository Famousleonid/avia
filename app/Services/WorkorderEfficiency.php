<?php

namespace App\Services;

use App\Models\Main;
use App\Models\Workorder;

class WorkorderEfficiency
{
    public function partFamily(string $number): string
    {
        $number = preg_replace('/\s+/', '', mb_strtoupper($number));
        // Efficiency groups every dash variant under the prefix before the first dash.
        return explode('-', $number, 2)[0];
    }

    public function report(?string $partNumber): array
    {
        $calendar = new OntarioWorkingDays();
        // Only numbers actually used in a workorder header, including retired units.
        $storedNumbers = Workorder::query()->join('units', 'units.id', '=', 'workorders.unit_id')
            ->whereNotNull('units.part_number')->where('units.part_number', '<>', '')
            ->distinct()->orderBy('units.part_number')->pluck('units.part_number');
        $families = $storedNumbers->groupBy(fn ($number) => $this->partFamily($number));
        $partNumbers = $families->keys()->sort()->values();
        $partNumber = $partNumber ? $this->partFamily($partNumber) : null;
        $matchingNumbers = $families->get($partNumber, collect());
        $workorders = Workorder::with(['user' => fn ($q) => $q->withTrashed()])
            ->whereHas('unit', fn ($q) => $q->whereIn('part_number', $matchingNumbers))
            ->when(!$partNumber, fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('number')->orderBy('id')->get();
        $stages = Main::whereIn('workorder_id', $workorders->modelKeys())
            ->whereHas('task', fn ($q) => $q->where('name', '2. Disassembly'))
            ->orderByDesc('id')->get()->unique('workorder_id')->keyBy('workorder_id');
        $rows = [];
        $allDays = [];
        foreach ($workorders as $wo) {
            $key = $wo->user_id ?: 0;
            $rows[$key] ??= ['name' => $wo->user?->selection_name ?: 'Unassigned', 'cells' => [], 'days' => []];
            $stage = $stages->get($wo->id);
            $days = null;
            $note = 'Dates not completed';
            if ($stage?->ignore_row) {
                $note = 'Skipped';
            } elseif ($stage?->date_start && $stage?->date_finish) {
                $note = format_project_date($stage->date_start).' → '.format_project_date($stage->date_finish);
                if ($stage->date_finish->gte($stage->date_start)) {
                    $days = $calendar->between($stage->date_start, $stage->date_finish);
                } else {
                    $note .= ' (invalid date range)';
                }
            }
            $rows[$key]['cells'][$wo->id] = compact('days', 'note');
            if ($days !== null) {
                $rows[$key]['days'][] = $days;
                $allDays[] = $days;
            }
        }
        foreach ($rows as &$row) {
            $row['average'] = count($row['days']) ? array_sum($row['days']) / count($row['days']) : null;
        }
        unset($row);
        $rows = collect($rows)->sortBy('name')->values();
        $average = count($allDays) ? array_sum($allDays) / count($allDays) : null;
        return compact('partNumbers', 'partNumber', 'workorders', 'rows', 'average');
    }
}
