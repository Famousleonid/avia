<?php

namespace App\Services;

use App\Models\WoBushingBatch;
use App\Models\WoBushingLine;
use App\Models\Workorder;
use App\Support\WoBushingProcessColumnKey;

class BushingSpecProcessGroups
{
    public const LABELS = [
        'machining' => 'Machining',
        'stress_relief' => 'Bake (Stress relief)',
        'ndt' => 'NDT',
        'passivation' => 'Passivation',
        'cad' => 'CAD',
        'anodizing' => 'Anodizing',
        'xylan' => 'Xylan',
    ];

    public function pageCount(Workorder $workorder): int
    {
        return (int) ceil(count($this->build($workorder)) / 6);
    }

    private function batchKey(WoBushingBatch $batch): string
    {
        return trim((string) $batch->process_column_key) ?: WoBushingProcessColumnKey::fromProcess($batch->process);
    }

    private function batchLabels(Workorder $workorder): array
    {
        $labels = [];
        foreach (app(BushingRouteBatches::class)->labels((int) $workorder->id) as $byId) {
            $labels += $byId;
        }
        return $labels;
    }

    /** One column per route; legacy operations are joined by their exact batch membership. */
    public function build(Workorder $workorder): array
    {
        $labelMap = self::LABELS;
        $batchLabels = $this->batchLabels($workorder);
        $sortOrder = array_flip(array_keys($labelMap));
        $partNumbersPerCell = 6;
        $maxPartNumberCellsPerColumn = 7;
        $maxPartNumbersPerColumn = $partNumbersPerCell * $maxPartNumberCellsPerColumn;

        $groupBuckets = [];
        $lines = WoBushingLine::query()
            ->where('workorder_id', $workorder->id)
            ->with([
                'component',
                'processes.process.process_name',
                'processes.batch' => fn ($query) => $query->withCount('machiningWorkSteps'),
                'processes' => fn ($query) => $query->withCount('machiningWorkSteps'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($lines as $line) {
            $component = $line->component;
            if (! $component) {
                continue;
            }

            $processRows = [];
            foreach ($line->processes as $woProcess) {
                $key = WoBushingProcessColumnKey::fromProcess($woProcess->process);
                if (! array_key_exists($key, $labelMap)) {
                    continue;
                }

                $processRows[] = [
                    'key' => $key,
                    'order' => $sortOrder[$key] ?? 999,
                ];
            }

            if ($processRows === []) {
                continue;
            }

            usort($processRows, function (array $left, array $right): int {
                return ((int) $left['order'] <=> (int) $right['order'])
                    ?: strcmp((string) $left['key'], (string) $right['key']);
            });

            $processSignature = implode('|', array_values(array_unique(array_map(
                fn (array $row): string => $row['key'],
                $processRows
            ))));
            $partNumber = trim((string) $component->part_number);
            $sentBatches = $line->processes
                ->filter(fn ($row) => $row->batch && ($row->batch->route_number || app(BushingRouteBatches::class)->hasHistory($row->batch) || app(BushingRouteBatches::class)->hasHistory($row))
                    && (int) $row->batch->workorder_id === (int) $workorder->id
                    && isset($labelMap[WoBushingProcessColumnKey::fromProcess($row->process)]))
                ->groupBy(function ($row) use ($line) {
                    if ($row->batch->route_number) return 'route:'.$row->batch->route_number;
                    // Same P/N or B label alone does not prove the same physical shipment.
                    // Keep differing operation quantities separate (partial shipments).
                    $memberships = $line->processes->filter(fn ($process) => $process->batch
                        && !$process->batch->route_number
                        && (int) $process->batch->workorder_id === (int) $line->workorder_id)
                        ->pluck('batch_id')->unique()->sort()->values()->all();
                    $quantities = $line->processes->filter(fn ($process) => $process->batch
                        && !$process->batch->route_number
                        && (app(BushingRouteBatches::class)->hasHistory($process->batch)
                            || app(BushingRouteBatches::class)->hasHistory($process)))
                        ->pluck('qty')->unique();
                    return 'legacy:'.implode(',', $memberships)
                        .($quantities->count() > 1 ? ':partial:'.$row->qty : '');
                });
            foreach ($sentBatches as $routeKey => $batchRows) {
                $batch = $batchRows->sortBy('batch_id')->first()->batch;
                $batchId = $batch->id;
                $batchKey = $this->batchKey($batch);
                $signature = $routeKey . ':' . $processSignature;

                if (! isset($groupBuckets[$signature])) {
                    $groupBuckets[$signature] = [
                        'batch_id' => (int) $batchId,
                        'route_number' => $batch->route_number,
                        'batch_label' => $batchLabels[$batchId] ?? 'B',
                        'batch_process_label' => $batch->route_number ? '' : ($batchKey === 'stress_relief' ? 'Stress Relief' : ($labelMap[$batchKey] ?? $batchKey)),
                        'sent_at' => ($batch->route_number ? '' : $batch->date_start?->format('Y-m-d')) ?? '',
                        'process_keys' => [],
                        'components_by_line' => [],
                        'part_numbers' => [],
                        'min_process_order' => 999,
                        'min_line_order' => (int) ($line->sort_order ?? 0),
                        'min_line_id' => (int) $line->id,
                    ];
                }

                $bucket = &$groupBuckets[$signature];
                foreach ($processRows as $row) {
                    $bucket['process_keys'][$row['key']] = true;
                    $bucket['min_process_order'] = min((int) $bucket['min_process_order'], (int) $row['order']);
                }
                $bucket['min_line_order'] = min((int) $bucket['min_line_order'], (int) ($line->sort_order ?? 0));
                $bucket['min_line_id'] = min((int) $bucket['min_line_id'], (int) $line->id);
                $normalizedPartNumber = mb_strtoupper($partNumber);
                if ($normalizedPartNumber !== '' && ! isset($bucket['part_numbers'][$normalizedPartNumber])) {
                    $bucket['part_numbers'][$normalizedPartNumber] = [
                        'part_number' => $partNumber,
                        'sort_order' => (int) ($line->sort_order ?? 0),
                        'line_id' => (int) $line->id,
                    ];
                }
                $bucket['components_by_line'][(int) $line->id] = [
                    'line_id' => (int) $line->id,
                    'component_id' => (int) $component->id,
                    'component' => $component,
                    'qty' => max(1, (int) ($batchRows->max('qty') ?? $line->qty ?? 1)),
                    'sort_order' => (int) ($line->sort_order ?? 0),
                ];
                unset($bucket);
            }
        }

        $groups = [];
        foreach ($groupBuckets as $bucket) {
            $processKeys = array_keys($bucket['process_keys']);
            usort($processKeys, fn (string $left, string $right): int => ($sortOrder[$left] ?? 999) <=> ($sortOrder[$right] ?? 999));

            $processes = [];
            $processNumbers = [];
            foreach ($processKeys as $idx => $processKey) {
                $processLabel = $labelMap[$processKey];
                $processes[] = $processLabel;
                $processNumbers[$processLabel] = $idx + 1;
            }

            $components = array_values($bucket['components_by_line']);
            usort($components, function (array $left, array $right): int {
                return ((int) $left['sort_order'] <=> (int) $right['sort_order'])
                    ?: ((int) $left['line_id'] <=> (int) $right['line_id']);
            });

            $partNumbers = array_values($bucket['part_numbers']);
            usort($partNumbers, function (array $left, array $right): int {
                return ((int) $left['sort_order'] <=> (int) $right['sort_order'])
                    ?: ((int) $left['line_id'] <=> (int) $right['line_id'])
                    ?: strnatcasecmp((string) $left['part_number'], (string) $right['part_number']);
            });

            $partNumberColumns = array_chunk($partNumbers, $maxPartNumbersPerColumn);
            if ($partNumberColumns === []) {
                $partNumberColumns = [[]];
            }

            foreach ($partNumberColumns as $columnIndex => $partNumberColumn) {
                $columnPartNumbers = array_column($partNumberColumn, 'part_number');
                $allowedPartNumbers = array_flip(array_map(
                    fn (string $partNumber): string => mb_strtoupper(trim($partNumber)),
                    $columnPartNumbers
                ));
                $columnComponents = $columnPartNumbers === []
                    ? $components
                    : array_values(array_filter(
                        $components,
                        fn (array $entry): bool => isset($allowedPartNumbers[mb_strtoupper(trim((string) ($entry['component']->part_number ?? '')))])
                    ));

                $groups[] = [
                    'batch_id' => $bucket['batch_id'],
                    'route_number' => $bucket['route_number'],
                    'batch_label' => $bucket['batch_label'],
                    'batch_process_label' => $bucket['batch_process_label'],
                    'sent_at' => $bucket['sent_at'],
                    'process_key' => $processKeys[0] ?? '',
                    'process_order' => (int) $bucket['min_process_order'],
                    'line_order' => (int) $bucket['min_line_order'],
                    'line_id' => (int) $bucket['min_line_id'],
                    'part_number' => (string) ($columnPartNumbers[0] ?? ''),
                    'part_numbers' => $columnPartNumbers,
                    'part_number_cells' => array_chunk($columnPartNumbers, $partNumbersPerCell),
                    'processes' => $processes,
                    'components' => $columnComponents,
                    'total_qty' => array_sum(array_map(fn (array $entry): int => (int) $entry['qty'], $columnComponents)),
                    'process_numbers' => $processNumbers,
                    'split_index' => $columnIndex,
                ];
            }
        }

        usort($groups, function (array $left, array $right) use ($sortOrder): int {
            $leftOrder = $left['line_order'] ?? 999;
            $rightOrder = $right['line_order'] ?? 999;
            $leftProcessOrder = $left['process_order'] ?? ($sortOrder[$left['process_key'] ?? ''] ?? 999);
            $rightProcessOrder = $right['process_order'] ?? ($sortOrder[$right['process_key'] ?? ''] ?? 999);

            // Historical columns stay first; newly added automatic routes append after them.
            return (!empty($left['route_number']) <=> !empty($right['route_number']))
                ?: strcmp($left['sent_at'], $right['sent_at'])
                ?: (($left['route_number'] ?? $left['batch_id']) <=> ($right['route_number'] ?? $right['batch_id']))
                ?: ($leftOrder <=> $rightOrder)
                ?: ($leftProcessOrder <=> $rightProcessOrder)
                ?: strnatcasecmp((string) ($left['part_number'] ?? ''), (string) ($right['part_number'] ?? ''))
                ?: ((int) ($left['split_index'] ?? 0) <=> (int) ($right['split_index'] ?? 0))
                ?: ((int) ($left['line_id'] ?? 0) <=> (int) ($right['line_id'] ?? 0));
        });

        return array_values($groups);
    }

}
