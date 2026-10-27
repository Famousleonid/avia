<?php

namespace App\Services;

use App\Models\ProcessName;
use Illuminate\Support\Collection;

class QuantumRoMatrix
{
    public function build(Collection $lines, ?Collection $tdrs = null): array
    {
        $normalize = static fn ($value) => preg_replace('/\s+/', '', strtoupper(trim((string) $value)));
        $processes = ProcessName::query()->inSpFormOrder()->get();
        $codes = $processes->filter(fn ($process) => trim((string) $process->code) !== '')
            ->groupBy(fn ($process) => $normalize($process->code));
        $columns = [];
        $rows = [];
        foreach ($lines as $line) {
            // A shared P/N alone cannot identify a serialized component.
            $columnKey = json_encode([$normalize($line->pn), $normalize($line->serial_number)]);
            $columns[$columnKey] ??= [
                'pn' => $line->pn, 'serial' => $line->serial_number, 'description' => $line->description,
            ];
            $ref = $normalize($line->bom_ref);
            $matches = $codes->get($ref, collect());
            $label = $ref === '' ? 'Unspecified process' : 'REF ' . $ref;
            $order = 10000;
            if (preg_match('/^T(\d*)$/', $ref, $traveler) && (int) ($traveler[1] ?: 1) > 0) {
                $label = 'Traveler ' . (int) ($traveler[1] ?: 1);
                $order = 9000;
            } elseif ($matches->count() === 1) {
                $process = $matches->first();
                $label = $process->name;
                $order = $processes->search(fn ($item) => $item->id === $process->id);
            }
            $rows[$ref] ??= ['label' => $label, 'ref' => $ref, 'order' => $order, 'cells' => []];
            // Keep every source detail and its own dates within the cell.
            $rows[$ref]['cells'][$columnKey][] = $line;
        }

        $completion = [];
        foreach ($lines as $line) {
            $key = json_encode([$normalize($line->pn), $normalize($line->serial_number)]);
            $completion[$key][] = ! empty($line->returned_date);
        }
        foreach ($tdrs ?? collect() as $tdr) {
            $part = $tdr->component;
            if (! $part) continue;
            // Use the same visible process scope and grouping as Parts / Processes.
            $visible = $tdr->tdrProcesses->filter(fn ($p) => $p->processName
                && ProcessName::normalizedNameKey($p->processName->identityName()) !== 'inspect'
                && $p->processName->show_in_process_picker !== false && ! $p->ignore_row)
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
            $groups = $visible->groupBy(fn ($p) => $p->in_traveler
                ? 'traveler:'.(int) ($p->traveler_group ?: 1) : 'process:'.$p->id)->values();
            if ($groups->isEmpty()) continue;
            $key = json_encode([$normalize($part->part_number), $normalize($tdr->serial_number)]);
            $columns[$key] ??= ['pn' => $part->part_number, 'serial' => $tdr->serial_number, 'description' => $part->name];
            foreach ($groups as $index => $group) {
                $leader = $group->first();
                $finished = $group->every(fn ($p) => ! empty($p->date_finish));
                $completion[$key][] = $finished;
                // Number only steps that have not been sent and do not yet have an RO.
                if ($finished || $group->contains(fn ($p) => ! empty($p->date_start) || trim((string) $p->repair_order) !== '')) continue;
                $process = $leader->processName;
                $ref = $normalize($process->code);
                $order = $processes->search(fn ($p) => $p->id === $process->id);
                $rowKey = 'process:'.$process->id;
                $label = $process->name;
                if ($leader->in_traveler) {
                    $groupNumber = (int) ($leader->traveler_group ?: 1);
                    $ref = 'T'.$groupNumber;
                    $rowKey = $groupNumber === 1 && isset($rows['T']) ? 'T' : $ref;
                    $label = 'Traveler '.$groupNumber;
                    $order = 9000;
                } elseif ($ref !== '' && $codes->get($ref, collect())->count() === 1) {
                    $rowKey = $ref;
                }
                $rows[$rowKey] ??= ['label' => $label, 'ref' => $ref, 'order' => $order === false ? 10000 : $order, 'cells' => []];
                $rows[$rowKey]['pending'][$key][] = $index + 1;
            }
        }
        foreach ($columns as $key => &$column) {
            $states = $completion[$key] ?? [];
            $column['complete'] = $states !== [] && ! in_array(false, $states, true);
        }
        unset($column);

        return ['columns' => $columns, 'rows' => collect($rows)->sortBy('order')->values()->all()];
    }
}
