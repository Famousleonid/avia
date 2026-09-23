<?php

namespace App\Services;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Tdr, Workorder, Necessary, Transfer};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkorderPartsList
{
    public function rows(Workorder $workorder): Collection
    {
        $generator = app(TdrPrintFormController::class);
        $kit = $generator->kitRows($workorder);
        $prl = $generator->prlRows($workorder);
        $receipts = DB::table('workorder_part_receipts')->where('workorder_id', $workorder->id)->get()->keyBy('row_key');
        // Only inherit an unambiguous historical receipt from a TDR removed by KIT supply.
        $legacy = Tdr::where('workorder_id', $workorder->id)
            ->where('necessaries_id', Necessary::where('name', 'Order New')->value('id'))
            ->whereNotIn('id', $prl->filter(fn ($row) => $row instanceof Tdr)->pluck('id'))
            ->get();
        $transfers = Transfer::where('workorder_id', $workorder->id)->get();
        $usedLegacy = [];
        return $kit->map(fn ($row) => ['row' => $row, 'kit' => true])
            ->concat($prl->map(fn ($row) => ['row' => $row, 'kit' => false]))
            ->map(function ($entry) use ($receipts, $legacy, $transfers, &$usedLegacy) {
                $row = $entry['row'];
                $key = $this->key($row);
                $component = data_get($row, 'orderComponent') ?? data_get($row, 'component');
                $assembly = data_get($row, 'orderComponentAssembly');
                $options = collect(data_get($row, 'prl_part_numbers', data_get($row, 'kit_component_options', [])));
                $activeIds = $options->reject(fn ($o) => !empty($o['crossed_out']))->pluck('component_id');
                if ($options->isEmpty()) $activeIds = collect([data_get($component, 'id')]);
                $tdr = $row instanceof Tdr ? $row : null;
                if (!$tdr && $entry['kit']) {
                    $matches = $legacy->filter(fn ($t) => !isset($usedLegacy[$t->id]) && (int) $t->qty === (int) data_get($row, 'qty') && $activeIds->contains($t->order_component_id ?: $t->component_id));
                    if ($matches->count() === 1) {
                        $tdr = $matches->first();
                        $usedLegacy[$tdr->id] = true;
                    }
                }
                $saved = $receipts->get($key);
                $transfer = $transfers->first(fn ($t) => $t->receipt_row_key === $key || ($tdr && (int) $t->tdr_id === (int) $tdr->id));
                $display = (object) [
                    'id' => data_get($component, 'id'),
                    'ipl_num' => data_get($assembly, 'assy_ipl_num') ?: data_get($component, 'ipl_num'),
                    'part_number' => data_get($assembly, 'assy_part_number') ?: data_get($component, 'part_number'),
                    'name' => data_get($component, 'name'), 'kit' => $entry['kit'],
                ];
                return (object) [
                    'id' => $key, 'transfer_id' => $transfer?->id, 'tdr_id' => $tdr?->id, 'component' => $display, 'orderComponent' => null,
                    'qty' => data_get($row, 'qty'), 'options' => $options,
                    'received_qty' => $saved?->received_qty,
                    'crossed_out' => (bool) data_get($row, 'prl_crossed_out', false),
                    'po_num' => $row instanceof Tdr ? $row->po_num : ($saved ? $saved->po_num : ($tdr?->po_num ?? data_get($row, 'po_num'))),
                    'received' => $row instanceof Tdr ? $row->received : ($saved ? $saved->received : $tdr?->received),
                ];
            })->values();
    }

    public function key($row): string
    {
        if ($row instanceof Tdr) return 'tdr:'.$row->id;
        if (empty($row['receipt_key'])) throw new \LogicException('Generated PRL row has no receipt identity.');
        $options = collect($row['prl_part_numbers'] ?? $row['kit_component_options'] ?? [])
            ->reject(fn ($o) => !empty($o['crossed_out']))
            ->map(fn ($o) => [(int) $o['component_id'], $o['qty'] ?? null])->sortBy(0)->values()->all();
        // A changed quantity or active variant is a new receipt, not an already received part.
        return $row['receipt_key'].':'.substr(hash('sha256', json_encode([(int) ($row['qty'] ?? 0), $options])), 0, 16);
    }

    public function forPrint(Workorder $workorder, Collection $rows): Collection
    {
        $displayRows = $this->rows($workorder)->keyBy('id');
        return $rows->map(function ($row) use ($displayRows) {
            $receipt = $displayRows->get($this->key($row));
            if ($receipt) {
                if ($row instanceof Tdr) $row->po_num = $receipt->po_num;
                else $row['po_num'] = $receipt->po_num;
            }
            return $row;
        });
    }
}
