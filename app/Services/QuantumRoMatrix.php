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
        $targetParts = [];
        foreach ($tdrs ?? collect() as $tdr) {
            if (! $tdr->component) continue;
            foreach ($tdr->tdrProcesses as $process) {
                $targetParts[$process->id] = [
                    'pn' => $tdr->component->part_number, 'serial' => $tdr->serial_number,
                    'description' => $tdr->component->name,
                ];
            }
        }
        $lineParts = [];
        foreach ($lines as $line) {
            $part = [
                'pn' => $line->pn, 'serial' => $line->serial_number, 'description' => $line->description,
            ];
            // A missing source serial can be resolved by an explicit applied
            // TDR target in this WO, never by P/N alone or conflicting serials.
            if ($line->apply_status === 'applied' && $normalize($line->serial_number) === '') {
                $targets = collect($line->applied_targets ?? []);
                if ($targets->isEmpty() && $line->applied_target_table) {
                    $targets = collect([['table' => $line->applied_target_table, 'id' => $line->applied_target_id]]);
                }
                $targets = $targets->where('table', 'tdr_processes');
                $parts = $targets->map(fn ($target) => $targetParts[$target['id'] ?? 0] ?? null);
                if ($parts->isNotEmpty() && $parts->every(fn ($p) => $p && $normalize($p['pn']) === $normalize($line->pn))) {
                    $identities = $parts->unique(fn ($p) => json_encode([$normalize($p['pn']), $normalize($p['serial'])]));
                    if ($identities->count() === 1) $part = $identities->first();
                }
            }
            $columnKey = json_encode([$normalize($part['pn']), $normalize($part['serial'])]);
            $lineParts[spl_object_id($line)] = $columnKey;
            $columns[$columnKey] ??= $part;
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
            $key = $lineParts[spl_object_id($line)];
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
                $pending = ! $finished && ! $group->contains(fn ($p) => ! empty($p->date_start) || trim((string) $p->repair_order) !== '');
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
                // AT is a local operation, so it has no Quantum RO source line.
                // A dated blank-RO operation without a vendor is also local,
                // unless this exact part/process already has a Quantum RO.
                $hasSource = ! empty($rows[$rowKey]['cells'][$key]);
                $internal = $group->filter(function ($p) use ($normalize, $hasSource) {
                    $ro = $normalize($p->repair_order);
                    return $ro === 'AT' || ($ro === '' && ! $p->vendor_id && ! $hasSource
                        && (! empty($p->date_start) || ! empty($p->date_finish)));
                });
                if (! $pending && $internal->isEmpty()) continue;
                $rows[$rowKey] ??= ['label' => $label, 'ref' => $ref, 'order' => $order === false ? 10000 : $order, 'cells' => []];
                if ($pending) $rows[$rowKey]['pending'][$key][] = $index + 1;
                foreach ($internal as $local) {
                    $rows[$rowKey]['internal'][$key][] = [
                        'process' => $local->processName->name,
                        'sent' => $local->date_start, 'returned' => $local->date_finish,
                    ];
                }
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
