<?php

namespace App\Services;

use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Support\Collection;

/**
 * Строки для экрана Machining: бушинги с колонкой machining (как группировка на main).
 */
final class MachiningBushingRowsBuilder
{
    /**
     * @return Collection<int, object>
     */
    public static function forWorkorder(Workorder $wo): Collection
    {
        $wpCollection = $wo->relationLoaded('woBushingProcesses')
            ? $wo->woBushingProcesses
            : $wo->woBushingProcesses()->with(['line.component', 'process.process_name', 'batch', 'vendor'])->get();

        $machining = $wpCollection->filter(static function (WoBushingProcess $wp) {
            return WoBushingProcessColumnKey::fromProcess($wp->process) === 'machining';
        });

        if ($machining->isEmpty()) {
            return collect();
        }

        $batches = self::buildBatchesStructure($machining);
        $rows = collect();

        foreach ($batches as $batch) {
            $isBatch = ! empty($batch['is_batch']);
            $processRows = $batch['process_rows'] ?? [];

            if (! $isBatch && is_array($processRows) && count($processRows) > 1) {
                foreach ($processRows as $pr) {
                    $wp = $machining->firstWhere('id', (int) ($pr['id'] ?? 0));
                    if (! $wp) {
                        continue;
                    }
                    $rows->push(self::makeRow($wo, $batch, $pr, $wp));
                }

                continue;
            }

            $batchModel = $batch['batch_model'] ?? null;
            $wpModel = ! $isBatch ? $machining->firstWhere('id', (int) ($batch['id'] ?? 0)) : null;

            if ($isBatch && ! $batchModel) {
                continue;
            }
            if (! $isBatch && ! $wpModel) {
                continue;
            }

            $rows->push(self::makeRow($wo, $batch, null, $wpModel));
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $batch
     * @param  array<string, mixed>|null  $processRowOverride
     */
    private static function makeRow(Workorder $wo, array $batch, ?array $processRowOverride, ?WoBushingProcess $wpModel): object
    {
        $isBatch = ! empty($batch['is_batch']);
        $batchModel = $batch['batch_model'] ?? null;

        if ($processRowOverride !== null) {
            $start = $processRowOverride['date_start'] ?? null;
            $finish = $processRowOverride['date_finish'] ?? null;
        } elseif ($isBatch) {
            $start = $batch['date_start'] ?? null;
            $finish = $batch['date_finish'] ?? null;
        } else {
            $start = $wpModel?->date_start;
            $finish = $wpModel?->date_finish;
        }

        $lineItems = collect($batch['line_items'] ?? []);
        $partNumbers = $lineItems->pluck('part_number')->map(static fn ($pn) => trim((string) $pn))->filter()->unique()->values();
        $detailLabel = $partNumbers->isNotEmpty() ? $partNumbers->implode(', ') : '—';

        $names = $lineItems->pluck('name')->map(static fn ($n) => trim((string) $n))->filter()->unique()->values();
        $detailName = $isBatch
            ? 'Bushing · Batch'
            : ($names->isNotEmpty() ? $names->implode(', ') : 'Bushing');

        return (object) [
            'workorder' => $wo,
            'row_source' => 'bushing',
            'detail_label' => $detailLabel,
            'detail_name' => $detailName,
            'date_start' => $start,
            'date_finish' => $finish,
            'edit_machining_process' => null,
            'machining_queue_position' => null,
            'is_queue_master' => false,
            'bushing_is_batch' => $isBatch,
            'bushing_batch' => $isBatch ? $batchModel : null,
            'bushing_process' => (! $isBatch && $wpModel) ? $wpModel : null,
        ];
    }

    /**
     * @param  Collection<int, WoBushingProcess>  $group
     * @return Collection<int, array<string, mixed>>
     */
    private static function buildBatchesStructure(Collection $group): Collection
    {
        return $group->groupBy(static function (WoBushingProcess $wp) {
            if (! empty($wp->batch_id)) {
                return 'batch_'.$wp->batch_id;
            }
            $lineId = (int) ($wp->wo_bushing_line_id ?? 0);

            return $lineId > 0 ? 'single_line_'.$lineId : 'single_wp_'.$wp->id;
        })->map(static function (Collection $batchRows) {
            $firstWp = $batchRows->first();
            $batch = $firstWp->batch;
            $isBatch = ! empty($firstWp->batch_id);

            $lineItems = $batchRows->map(static function (WoBushingProcess $wp) {
                $c = $wp->line?->component;

                return [
                    'id' => (int) $wp->id,
                    'qty' => (int) $wp->qty,
                    'part_number' => trim((string) ($c?->part_number ?? '')),
                    'ipl_num' => trim((string) ($c?->ipl_num ?? '')),
                    'name' => trim((string) ($c?->name ?? '')),
                    'process_detail' => self::lineProcessDetail($wp),
                ];
            })->sortBy(static fn (array $row) => ($row['part_number'] !== '' ? $row['part_number'] : 'zzz').'|'.$row['ipl_num'])->values();

            if (! $isBatch && $lineItems->count() > 1) {
                $firstLine = $lineItems->first();
                $mergedDetail = $lineItems->pluck('process_detail')
                    ->map(static fn ($d) => trim((string) $d))
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $mergedQty = (int) $lineItems->max(static fn (array $r) => (int) ($r['qty'] ?? 0));
                $lineItems = collect([[
                    'id' => (int) $firstLine['id'],
                    'qty' => $mergedQty,
                    'part_number' => $firstLine['part_number'],
                    'ipl_num' => $firstLine['ipl_num'],
                    'name' => $firstLine['name'],
                    'process_detail' => $mergedDetail,
                ]]);
            }

            $processRows = null;
            if (! $isBatch) {
                $processRows = $batchRows->map(static function (WoBushingProcess $wp) {
                    return [
                        'id' => (int) $wp->id,
                        'repair_order' => (string) ($wp->repair_order ?? ''),
                        'vendor_id' => $wp->vendor_id ? (int) $wp->vendor_id : null,
                        'date_start' => $wp->date_start,
                        'date_finish' => $wp->date_finish,
                    ];
                })->values()->all();
            }

            $batchQty = (int) $batchRows->sum('qty');
            if (! $isBatch && $batchRows->count() > 1) {
                $batchQty = (int) $batchRows->max('qty');
            }

            return [
                'is_batch' => $isBatch,
                'id' => $isBatch ? (int) $firstWp->batch_id : (int) $firstWp->id,
                'qty' => $batchQty,
                'repair_order' => $isBatch ? (string) ($batch?->repair_order ?? '') : (string) ($firstWp->repair_order ?? ''),
                'vendor_id' => $isBatch
                    ? ($batch?->vendor_id ? (int) $batch->vendor_id : null)
                    : ($firstWp->vendor_id ? (int) $firstWp->vendor_id : null),
                'date_start' => $isBatch ? $batch?->date_start : $firstWp->date_start,
                'date_finish' => $isBatch ? $batch?->date_finish : $firstWp->date_finish,
                'line_items' => $lineItems,
                'process_rows' => $processRows,
                'batch_model' => $isBatch ? $batch : null,
            ];
        })->values();
    }

    private static function lineProcessDetail(WoBushingProcess $wp): string
    {
        $p = $wp->process;
        $pn = $p?->process_name;
        $prName = trim((string) ($pn->name ?? ''));
        $prNum = trim((string) ($p->process ?? ''));

        return trim(($prName !== '' ? $prName : 'Process').($prNum !== '' ? ' '.$prNum : ''));
    }
}
