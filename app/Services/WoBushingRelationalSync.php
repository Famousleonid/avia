<?php

namespace App\Services;

use App\Models\WoBushing;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use Illuminate\Support\Facades\DB;

class WoBushingRelationalSync
{
    /** @return list<string> */
    private static function stressReliefProcessNames(): array
    {
        return ['Bake (Stress relief)', 'Stress Relief'];
    }

    /**
     * Сохраняет группы из запроса в wo_bushing_lines + wo_bushing_processes.
     *
     * @param  array<string, array<string, mixed>>  $groupBushingsData
     * @return list<array{bushing: int, qty: int, processes: array<string, mixed>}>
     */
    public function syncFromGroupBushings(WoBushing $woBushing, array $groupBushingsData): array
    {
        $workorderId = (int) $woBushing->workorder_id;

        $bushDataArray = $this->buildBushDataArrayFromGroups($groupBushingsData);

        DB::transaction(function () use ($woBushing, $groupBushingsData, $workorderId, $bushDataArray) {
            $woBushing->lines()->delete();

            $sortOrder = 0;
            foreach ($groupBushingsData as $groupKey => $groupData) {
                if (! isset($groupData['components']) || ! is_array($groupData['components'])) {
                    continue;
                }
                foreach ($groupData['components'] as $componentId) {
                    $ndtInput = $groupData['ndt'] ?? [];
                    if (is_null($ndtInput) || $ndtInput === '') {
                        $ndtValues = [];
                    } elseif (is_array($ndtInput)) {
                        $ndtValues = array_map('intval', $ndtInput);
                    } else {
                        $ndtValues = [(int) $ndtInput];
                    }

                    $qty = (int) ($groupData['qty'] ?? 1);

                    $line = WoBushingLine::create([
                        'wo_bushing_id' => $woBushing->id,
                        'workorder_id' => $workorderId,
                        'component_id' => (int) $componentId,
                        'qty' => $qty,
                        'qty_remaining' => $qty,
                        'group_key' => is_string($groupKey) ? $groupKey : (string) $groupKey,
                        'sort_order' => $sortOrder++,
                    ]);

                    $attach = function (?int $processId, int $q) {
                        if ($processId) {
                            return [['process_id' => $processId, 'qty' => $q]];
                        }

                        return [];
                    };

                    $rows = array_merge(
                        $attach(! empty($groupData['machining'] ?? null) ? (int) $groupData['machining'] : null, $qty),
                        $attach(! empty($groupData['stress_relief'] ?? null) ? (int) $groupData['stress_relief'] : null, $qty),
                        $attach(! empty($groupData['passivation'] ?? null) ? (int) $groupData['passivation'] : null, $qty),
                        $attach(! empty($groupData['cad'] ?? null) ? (int) $groupData['cad'] : null, $qty),
                        $attach(! empty($groupData['anodizing'] ?? null) ? (int) $groupData['anodizing'] : null, $qty),
                        $attach(! empty($groupData['xylan'] ?? null) ? (int) $groupData['xylan'] : null, $qty),
                    );

                    foreach ($ndtValues as $ndtPid) {
                        if ($ndtPid > 0) {
                            $rows[] = ['process_id' => $ndtPid, 'qty' => $qty];
                        }
                    }

                    foreach ($rows as $row) {
                        WoBushingProcess::create([
                            'wo_bushing_line_id' => $line->id,
                            'process_id' => $row['process_id'],
                            'qty' => $row['qty'],
                            'date_start' => null,
                            'date_finish' => null,
                        ]);
                    }
                }
            }
        });

        return $bushDataArray;
    }

    /**
     * @param  array<string, array<string, mixed>>  $groupBushingsData
     * @return list<array{bushing: int, qty: int, processes: array<string, mixed>}>
     */
    public function buildBushDataArrayFromGroups(array $groupBushingsData): array
    {
        $bushDataArray = [];
        foreach ($groupBushingsData as $groupData) {
            if (! isset($groupData['components']) || ! is_array($groupData['components'])) {
                continue;
            }
            foreach ($groupData['components'] as $componentId) {
                $ndtInput = $groupData['ndt'] ?? [];
                if (is_null($ndtInput) || $ndtInput === '') {
                    $ndtValues = [];
                } elseif (is_array($ndtInput)) {
                    $ndtValues = array_map('intval', $ndtInput);
                } else {
                    $ndtValues = [(int) $ndtInput];
                }

                $bushDataArray[] = [
                    'bushing' => (int) $componentId,
                    'qty' => (int) ($groupData['qty'] ?? 1),
                    'processes' => [
                        'machining' => ! empty($groupData['machining'] ?? null) ? (int) $groupData['machining'] : null,
                        'stress_relief' => ! empty($groupData['stress_relief'] ?? null) ? (int) $groupData['stress_relief'] : null,
                        'ndt' => $ndtValues,
                        'passivation' => ! empty($groupData['passivation'] ?? null) ? (int) $groupData['passivation'] : null,
                        'cad' => ! empty($groupData['cad'] ?? null) ? (int) $groupData['cad'] : null,
                        'anodizing' => ! empty($groupData['anodizing'] ?? null) ? (int) $groupData['anodizing'] : null,
                        'xylan' => ! empty($groupData['xylan'] ?? null) ? (int) $groupData['xylan'] : null,
                    ],
                ];
            }
        }

        return $bushDataArray;
    }

    /**
     * Массив для blade в прежней форме (bushing / qty / processes), только из wo_bushing_lines + wo_bushing_processes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveBushDataForViews(WoBushing $woBushing): array
    {
        if ($woBushing->relationLoaded('lines') ? $woBushing->lines->isNotEmpty() : $woBushing->lines()->exists()) {
            return $this->bushDataFromLines($woBushing);
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function bushDataFromLines(WoBushing $woBushing): array
    {
        $lines = $woBushing->lines()->with(['processes.process.process_name'])->orderBy('sort_order')->get();

        $out = [];
        foreach ($lines as $line) {
            $processes = [
                'machining' => null,
                'stress_relief' => null,
                'ndt' => [],
                'passivation' => null,
                'cad' => null,
                'anodizing' => null,
                'xylan' => null,
            ];

            foreach ($line->processes as $wp) {
                $p = $wp->process;
                if (! $p || ! $p->process_name) {
                    continue;
                }
                $name = $p->process_name->name;
                if ($name === 'Machining') {
                    $processes['machining'] = $p->id;
                } elseif (in_array($name, self::stressReliefProcessNames(), true)) {
                    $processes['stress_relief'] = $p->id;
                } elseif (str_starts_with($name, 'NDT') || $name === 'Eddy Current Test' || $name === 'BNI') {
                    $processes['ndt'][] = $p->id;
                } elseif ($name === 'Passivation') {
                    $processes['passivation'] = $p->id;
                } elseif ($name === 'Cad plate') {
                    $processes['cad'] = $p->id;
                } elseif ($name === 'Anodizing') {
                    $processes['anodizing'] = $p->id;
                } elseif ($name === 'Xylan coating') {
                    $processes['xylan'] = $p->id;
                }
            }

            $processes['ndt'] = array_values(array_unique($processes['ndt']));

            $out[] = [
                'bushing' => (int) $line->component_id,
                'qty' => (int) $line->qty,
                'processes' => $processes,
            ];
        }

        return $out;
    }

    /**
     * Агрегаты по WO: всего по линиям, остаток, сумма по процессам (сколько «назначено» на каждый process_id).
     *
     * @return array{total_qty: int, qty_remaining: int, by_process_id: array<int, int>}
     */
    public function workorderQuantityStats(int $workorderId): array
    {
        $lineTotals = WoBushingLine::query()
            ->where('workorder_id', $workorderId)
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(qty_remaining), 0) as qty_remaining')
            ->first();

        $byProcess = WoBushingProcess::query()
            ->whereHas('line', fn ($q) => $q->where('workorder_id', $workorderId))
            ->selectRaw('process_id, SUM(qty) as qty')
            ->groupBy('process_id')
            ->pluck('qty', 'process_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [
            'total_qty' => (int) ($lineTotals->total_qty ?? 0),
            'qty_remaining' => (int) ($lineTotals->qty_remaining ?? 0),
            'by_process_id' => $byProcess,
        ];
    }

    /**
     * Перенос из колонки bush_data (legacy JSON) в wo_bushing_lines + wo_bushing_processes.
     * Не трогает JSON; при необходимости очистить дублирование — отдельная задача.
     *
     * @param  bool  $overwrite  если true — удалить существующие строки и записать заново
     */
    public function migrateLegacyJsonToRelations(WoBushing $woBushing, bool $overwrite = false): bool
    {
        if (! $overwrite && $woBushing->lines()->exists()) {
            return false;
        }

        $raw = $woBushing->bush_data;
        $items = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);
        if (! is_array($items) || $items === []) {
            return false;
        }

        $validCount = 0;
        foreach ($items as $item) {
            if (is_array($item) && (int) ($item['bushing'] ?? 0) > 0) {
                $validCount++;
            }
        }
        if ($validCount === 0) {
            return false;
        }

        DB::transaction(function () use ($woBushing, $items, $overwrite) {
            if ($overwrite) {
                $woBushing->lines()->delete();
            }

            $workorderId = (int) $woBushing->workorder_id;
            $sortOrder = 0;

            foreach ($items as $idx => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $componentId = (int) ($item['bushing'] ?? 0);
                if ($componentId <= 0) {
                    continue;
                }

                $qty = (int) ($item['qty'] ?? 1);
                $processes = is_array($item['processes'] ?? null) ? $item['processes'] : [];

                $line = WoBushingLine::create([
                    'wo_bushing_id' => $woBushing->id,
                    'workorder_id' => $workorderId,
                    'component_id' => $componentId,
                    'qty' => $qty,
                    'qty_remaining' => $qty,
                    'group_key' => 'legacy_'.$idx,
                    'sort_order' => $sortOrder++,
                ]);

                $ndtRaw = $processes['ndt'] ?? [];
                if (is_null($ndtRaw) || $ndtRaw === '') {
                    $ndtValues = [];
                } elseif (is_array($ndtRaw)) {
                    $ndtValues = array_map('intval', $ndtRaw);
                } else {
                    $ndtValues = [(int) $ndtRaw];
                }

                $attach = function (?int $processId, int $q) {
                    if ($processId) {
                        return [['process_id' => $processId, 'qty' => $q]];
                    }

                    return [];
                };

                $rows = array_merge(
                    $attach(! empty($processes['machining'] ?? null) ? (int) $processes['machining'] : null, $qty),
                    $attach(! empty($processes['stress_relief'] ?? null) ? (int) $processes['stress_relief'] : null, $qty),
                    $attach(! empty($processes['passivation'] ?? null) ? (int) $processes['passivation'] : null, $qty),
                    $attach(! empty($processes['cad'] ?? null) ? (int) $processes['cad'] : null, $qty),
                    $attach(! empty($processes['anodizing'] ?? null) ? (int) $processes['anodizing'] : null, $qty),
                    $attach(! empty($processes['xylan'] ?? null) ? (int) $processes['xylan'] : null, $qty),
                );

                foreach ($ndtValues as $ndtPid) {
                    if ($ndtPid > 0) {
                        $rows[] = ['process_id' => $ndtPid, 'qty' => $qty];
                    }
                }

                foreach ($rows as $row) {
                    WoBushingProcess::create([
                        'wo_bushing_line_id' => $line->id,
                        'process_id' => $row['process_id'],
                        'qty' => $row['qty'],
                        'date_start' => null,
                        'date_finish' => null,
                    ]);
                }
            }
        });

        return true;
    }
}
