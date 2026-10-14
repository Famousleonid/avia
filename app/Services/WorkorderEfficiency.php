<?php

namespace App\Services;

use App\Models\Main;
use App\Models\Workorder;

class WorkorderEfficiency
{
    public function partFamily(string $number): string
    {
        $number = preg_replace('/\s+/', '', mb_strtoupper($number));
        // Keep the full base number; only the final dash suffix identifies a variant.
        $lastDash = strrpos($number, '-');
        return $lastDash === false ? $number : substr($number, 0, $lastDash);
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
        $partNumber = $partNumber ? preg_replace('/\s+/', '', mb_strtoupper($partNumber)) : null;
        // Select options already contain family keys, which may themselves contain dashes.
        if ($partNumber !== null && !$families->has($partNumber)) {
            $partNumber = $this->partFamily($partNumber);
        }
        $matchingNumbers = $families->get($partNumber, collect());
        $workorders = Workorder::with(['user' => fn ($q) => $q->withTrashed()])
            ->whereHas('unit', fn ($q) => $q->whereIn('part_number', $matchingNumbers))
            ->when(!$partNumber, fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('number')->orderBy('id')->get();
        $taskNames = ['2. Disassembly', '3. WO Submitted to inspection', 'WO Submitted to inspection', 'Post Disassembly inspection'];
        $stages = Main::with('task:id,name')->whereIn('workorder_id', $workorders->modelKeys())
            ->whereHas('task', fn ($q) => $q->whereIn('name', $taskNames))
            ->orderByDesc('id')->get()->groupBy('workorder_id');
        $rows = [];
        $allDays = [];
        foreach ($workorders as $wo) {
            $key = $wo->user_id ?: 0;
            $rows[$key] ??= ['name' => $wo->user?->selection_name ?: 'Unassigned', 'cells' => [], 'days' => []];
            $woStages = $stages->get($wo->id, collect());
            $stage = $woStages->first(fn ($row) => $row->task->name === '2. Disassembly');
            $days = null;
            $note = 'Dates not completed';
            $start = $stage?->date_start;
            $finish = $stage?->date_finish;
            $source = 'Disassembly';
            if (!$start || !$finish) {
                $inspectionStages = $woStages->unique('task_id')->filter(fn ($row) => !$row->ignore_row && $row->date_finish);
                $inspection = $inspectionStages->first(fn ($row) => in_array($row->task->name, ['3. WO Submitted to inspection', 'WO Submitted to inspection'], true))
                    ?? $inspectionStages->first(fn ($row) => $row->task->name === 'Post Disassembly inspection');
                $start = $wo->open_at?->copy()->startOfDay();
                $finish = $inspection?->date_finish;
                $source = 'WO Open date → '.($inspection?->task->name ?? 'inspection');
            } elseif ($stage->ignore_row) {
                $note = 'Skipped';
            }
            if ($note !== 'Skipped' && $start && $finish) {
                $note = $source.': '.format_project_date($start).' → '.format_project_date($finish);
                if ($finish->gte($start)) {
                    $days = $calendar->between($start, $finish);
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
