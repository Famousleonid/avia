<?php

namespace App\Services;

use App\Models\{Component, Manual, Workorder};
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ManualLogCardOrder
{
    public function followsManualOrder(Workorder $workorder): bool
    {
        return !$workorder->done_at
            && !app(LogCardTdrAccessService::class)->isLockedByPostDisassemblyInspection($workorder);
    }

    /** Sort presentation only; retain original keys for row update endpoints. */
    public function displayRows(array $rows, $workorder): array
    {
        if (!$this->followsManualOrder($workorder)) return $rows;
        $components = Component::with('manual')->whereIn('id', collect($rows)->pluck('component_id')->filter())->get()->keyBy('id');
        $slots = [];
        foreach ($rows as $key => $row) {
            if (!is_array($row) || ($row['row_type'] ?? '') === 'manual') continue;
            $component = $components->get((int) ($row['component_id'] ?? 0));
            if ($component?->manual?->log_card_order) $slots[$component->manual_id][] = $key;
        }
        foreach ($slots as $keys) {
            $manual = $components->get((int) $rows[$keys[0]]['component_id'])->manual;
            $ranks = $this->ranks($manual);
            $sorted = collect($keys)->sortBy(fn ($key) => $ranks[(int) $rows[$key]['component_id']] ?? PHP_INT_MAX)->values()->all();
            // Reorder key/value pairs, not their identity: edits still target storage indices.
            $replacement = array_combine($keys, $sorted);
            $next = [];
            foreach ($rows as $key => $row) {
                $source = $replacement[$key] ?? $key;
                $next[$source] = $rows[$source];
            }
            $rows = $next;
        }
        return $rows;
    }

    public function revision(Manual $manual): string
    {
        return hash('sha256', json_encode($manual->log_card_order ?? []));
    }

    public function family(Component $component): string
    {
        return app(LogCardAssemblyIdentity::class)->logCardFamilyKey([
            'ipl_num'=>$component->ipl_num, 'component_id'=>$component->id,
        ]);
    }

    public function groups(Manual $manual): Collection
    {
        $positions = array_flip($manual->log_card_order ?? []);
        return Component::where('manual_id', $manual->id)->where('log_card', true)->orderBy('id')->get()
            ->groupBy(fn ($c) => $this->family($c))
            ->map(fn ($parts, $key) => [
                'key'=>$key, 'ids'=>$parts->pluck('id')->all(),
                'parts'=>$parts->map(fn ($c) => ['id'=>$c->id, 'ipl'=>$c->ipl_num, 'pn'=>$c->part_number, 'name'=>$c->name])->values()->all(),
                'rank'=>$parts->min(fn ($c) => $positions[$c->id] ?? PHP_INT_MAX),
            ])->sortBy('rank')->values();
    }

    public function ranks(Manual $manual): array
    {
        if (!$manual->log_card_order) return [];
        $ranks = [];
        foreach ($this->groups($manual) as $group) {
            foreach ($group['ids'] as $id) $ranks[$id] = $group['rank'];
        }
        return $ranks;
    }

    /** Preview only: no flags, parts, or existing order are changed. */
    public function preview(Manual $manual, string $path): array
    {
        $book = IOFactory::load($path);
        try {
            $sheets = array_values(array_filter($book->getAllSheets(), fn ($sheet) =>
                in_array(strtoupper(trim($sheet->getTitle())), ['LLP', 'LOG CARD', 'LOGCARD'], true)));
            if (count($sheets) !== 1) throw ValidationException::withMessages(['file'=>'Select a workbook with exactly one LLP or Log Card sheet.']);
            $groups = $this->groups($manual);
            $byPn = [];
            foreach ($groups as $group) foreach ($group['parts'] as $part) {
                $byPn[$this->pn($part['pn'])][$group['key']] = $group;
            }
            $ordered = []; $issues = []; $sourceRows = [];
            // Standard package LLP: description in A, part number(s) in B.
            foreach ($sheets[0]->toArray(null, false, false, false) as $index => $row) {
                $name = trim((string) ($row[0] ?? ''));
                $cell = trim((string) ($row[1] ?? ''));
                if ($name === '' || $cell === '') continue;
                if (in_array(preg_replace('/[^A-Z]/', '', strtoupper($cell)), ['PARTNUMBER','PARTNUMBERS','PARTNO','PN'], true)) continue;
                $tokens = preg_split('/[\r\n;,]+/', $cell, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $token) {
                    $matches = $byPn[$this->pn($token)] ?? [];
                    if (count($matches) !== 1) {
                        $issues[] = 'Row '.($index + 1).': '.$token.' — '.(count($matches) ? 'ambiguous part number' : 'not found among this manual’s Log Card parts');
                        continue;
                    }
                    $group = reset($matches);
                    $ordered[$group['key']] = $group;
                    $sourceRows[] = ['row'=>$index+1, 'part_number'=>trim($token), 'group'=>$group['key']];
                }
            }
            if (!$ordered && !$issues) $issues[] = 'No Log Card parts matched. Expected description in column A and part number in column B.';
            if ($issues) throw ValidationException::withMessages(['file'=>$issues]);
            $unmatched = $groups->reject(fn ($g) => isset($ordered[$g['key']]))->values();
            return ['groups'=>array_merge(array_values($ordered), $unmatched->all()),
                'source_rows'=>$sourceRows, 'sheet'=>$sheets[0]->getTitle(), 'appended'=>$unmatched->count()];
        } finally { $book->disconnectWorksheets(); }
    }

    private function pn(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($value)));
    }
}
