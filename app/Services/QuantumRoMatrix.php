<?php

namespace App\Services;

use App\Models\ProcessName;
use Illuminate\Support\Collection;

class QuantumRoMatrix
{
    public function build(Collection $lines): array
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

        return ['columns' => $columns, 'rows' => collect($rows)->sortBy('order')->values()->all()];
    }
}
