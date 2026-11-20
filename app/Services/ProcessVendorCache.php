<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Precomputed readiness. Scheduled and explicit refreshes share one lock; reads never build it. */
class ProcessVendorCache
{
    private const KEY = 'process-vendor:v3:';

    private function key(string $suffix): string
    {
        return self::KEY.hash('sha256', DB::connection()->getDatabaseName()).':'.$suffix;
    }

    public function read(): array
    {
        $snapshot = Cache::get($this->key('snapshot'));
        $rows = collect($snapshot['rows'] ?? []);
        // Completed/archived WO and newly reserved items disappear immediately.
        $active = app(ProcessVendorQueue::class)->workorders()->whereIn('id', $rows->pluck('workorder_id')->unique())->pluck('id');
        $rows = app(ProcessVendorQueue::class)->shippingState($rows->whereIn('workorder_id', $active))
            ->reject(fn ($row) => $row['repair_order_id'])->values();
        return ['rows' => $rows, 'cache' => ['ready' => $snapshot !== null,
            'updated_at' => $snapshot['updated_at'] ?? null,
            'stale' => !$snapshot || now()->diffInSeconds($snapshot['updated_at']) > 660,
            'refreshing' => (bool) Cache::get($this->key('refreshing')),
            'workorders' => $snapshot['workorders'] ?? 0]];
    }

    public function refresh(bool $force = false): array
    {
        $lock = Cache::lock($this->key('refresh-lock'), 3600);
        if (!$lock->get()) return ['busy' => true, 'rebuilt' => 0];
        try {
            Cache::put($this->key('refreshing'), true, 3600);
            $signatures = $this->sourceSignatures();
            $previous = Cache::get($this->key('snapshot'), []);
            $entries = $previous['entries'] ?? [];
            $rebuilt = 0;
            foreach ($signatures as $id => $signature) {
                if (!$force && ($entries[$id]['signature'] ?? null) === $signature) continue;
                $rows = app(ProcessVendorQueue::class)->rows([$id]);
                $entries[$id] = ['signature' => $signature, 'rows' => $rows->all()];
                $rebuilt++;
            }
            $entries = array_intersect_key($entries, $signatures);
            // Publish only a successful complete pass; readers retain the previous snapshot on failure.
            Cache::forever($this->key('snapshot'), ['updated_at' => now()->toIso8601String(),
                'workorders' => count($signatures), 'entries' => $entries,
                'rows' => collect($entries)->flatMap(fn ($entry) => $entry['rows'])->values()->all()]);
            return ['busy' => false, 'rebuilt' => $rebuilt, 'workorders' => count($signatures)];
        } finally {
            Cache::forget($this->key('refreshing'));
            $lock->release();
        }
    }

    /** Raw content hashes detect bulk SQL updates, removals and pivots without timestamp/observer assumptions. */
    private function sourceSignatures(): array
    {
        $catalog = hash_init('sha256');
        hash_update($catalog, ProcessVendorQueue::READINESS_VERSION);
        hash_update($catalog, today()->toDateString());
        foreach (['components', 'manuals', 'units', 'instructions', 'process_names', 'processes', 'tasks',
            'std_processes', 'manual_part_groups', 'manual_part_group_options', 'manual_part_group_coverages',
            'manual_ipl_branch_rules', 'codes', 'necessaries'] as $table) {
            hash_update($catalog, $table);
            DB::table($table)->orderBy('id')->chunk(500, function ($rows) use ($catalog) {
                foreach ($rows as $row) hash_update($catalog, json_encode($row, JSON_THROW_ON_ERROR));
            });
        }
        // Only display names are relevant; never read account credentials into cache signatures.
        foreach (['users', 'customers'] as $table) {
            hash_update($catalog, json_encode(DB::table($table)->orderBy('id')->get(['id', 'name']), JSON_THROW_ON_ERROR));
        }
        $seed = hash_final($catalog);
        $workorders = app(ProcessVendorQueue::class)->workorders()->orderBy('id')->get();
        $contexts = [];
        foreach ($workorders as $wo) {
            $contexts[$wo->id] = hash_init('sha256');
            hash_update($contexts[$wo->id], $seed.json_encode([$wo->getRawOriginal(), (bool) $wo->approve_at?->lte(now())], JSON_THROW_ON_ERROR));
        }
        $ids = array_keys($contexts);
        foreach (['mains', 'tdrs', 'workorder_std_processes', 'workorder_std_process_items',
            'wo_bushing_lines', 'wo_bushing_batches', 'workorder_part_group_selections',
            'workorder_assy_configuration_choices', 'log_cards'] as $table) {
            foreach (DB::table($table)->whereIn('workorder_id', $ids)->orderBy('id')->get() as $row) {
                hash_update($contexts[$row->workorder_id], $table.json_encode($row, JSON_THROW_ON_ERROR));
            }
        }
        foreach ([['tdr_processes', 'tdrs', 'tdrs_id'], ['wo_bushing_processes', 'wo_bushing_lines', 'wo_bushing_line_id']] as [$table, $parent, $fk]) {
            foreach (DB::table($table)->join($parent, "$parent.id", '=', "$table.$fk")
                ->whereIn("$parent.workorder_id", $ids)->orderBy("$table.id")
                ->get(["$table.*", "$parent.workorder_id as cache_wo_id"]) as $row) {
                hash_update($contexts[$row->cache_wo_id], $table.json_encode($row, JSON_THROW_ON_ERROR));
            }
        }
        return array_map(fn ($context) => hash_final($context), $contexts);
    }
}
