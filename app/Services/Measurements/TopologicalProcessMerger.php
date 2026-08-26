<?php

namespace App\Services\Measurements;

use App\Models\ProcessName;
use Illuminate\Database\Eloquent\Collection;

/**
 * Merges Main-phase processes from multiple repair rules into a single ordered
 * list using topological sort.
 *
 * Algorithm:
 *   1. For each rule, read its processes in sort_order.
 *   2. Each consecutive pair (A, B) becomes a constraint "A must precede B".
 *   3. scope=point processes are unique per rule (one node per rule).
 *   4. scope=part processes are merged across rules by (nameId, occurrenceIndex)
 *      — the N-th occurrence of the same nameId in a rule matches the N-th
 *      occurrence in other rules, creating a shared node that accumulates
 *      process_ids from all rules that define it at that position.
 *   5. Kahn's topological sort produces the final order; ties are broken by
 *      first-seen insertion order (stable).
 *
 * Result: flat array of pre-merged, ordered process group entries ready for
 * PipelineContext::setMainGroups().
 */
class TopologicalProcessMerger
{
    /** @var array<int,string|null> nameId → scope cache */
    private array $scopeCache = [];

    private array $existsCache = [];

    /**
     * @param  Collection $rules  ManualParameterRepairRule (with processes.manualProcess.process loaded)
     * @return array<int,array>   ordered entries: each has process_names_id, process_ids[],
     *                            rule_process_ids[], description, point_key, scope
     */
    public function merge(Collection $rules): array
    {
        /** @var array<string,array>  nodeKey → node */
        $nodes       = [];
        /** @var array<string,array<string,bool>>  nodeKey → [predKey => true] */
        $predsOf     = [];
        /** @var array<string,int>  nodeKey → first-seen index (for stable sort) */
        $insertOrder = [];
        $seq         = 0;

        // Plan contents for row conditions: process_name ids contributed by the
        // UNCONDITIONAL rows of all matched rules (single pass, no fixpoint).
        $planNameIds = [];
        foreach ($rules as $rule) {
            foreach ($rule->processes as $rp) {
                if (!empty($rp->condition)) {
                    continue;
                }
                $nameId = (int) ($rp->manualProcess?->process?->process_names_id ?? 0);
                if ($nameId > 0 && $this->processNameExists($nameId)) {
                    $planNameIds[$nameId] = true;
                }
            }
        }
        $planNameIds = array_keys($planNameIds);

        foreach ($rules as $rule) {
            $prevKey        = null;
            $occurrenceCount = [];   // nameId => count seen in this rule

            foreach ($rule->processes->sortBy('sort_order') as $rp) {
                // Conditional row that doesn't apply: skip WITHOUT breaking the
                // chain — its neighbors connect directly (prev → next).
                if (! $rp->conditionMet($planNameIds)) {
                    continue;
                }

                $process = $rp->manualProcess?->process;
                if (!$process) {
                    $prevKey = null;   // gap in the chain — reset
                    continue;
                }

                $nameId = (int) ($process->process_names_id ?? 0);
                if (! $this->processNameExists($nameId)) {
                    $prevKey = null;
                    continue;
                }

                $scope  = $this->scopeOf($nameId);

                if ($scope === 'point') {
                    // Each rule contributes its own unique row for point-scope processes
                    $nodeKey = 'pt|' . $rule->id . '|' . $rp->id;
                } else {
                    // Part-scope: merges across rules only for the SAME OPERATION —
                    // the same process name with different operations means
                    // different rows, in sequence. N-th occurrence of the same
                    // (name, operation) in any rule maps to the same node.
                    $procId                   = (int) $process->id;
                    $occKey                   = $nameId . '|' . $procId;
                    $occ                      = $occurrenceCount[$occKey] ?? 0;
                    $occurrenceCount[$occKey] = $occ + 1;
                    $nodeKey                  = 'p|' . $nameId . '|' . $procId . '|' . $occ;
                }

                // Create node on first encounter
                if (!isset($nodes[$nodeKey])) {
                    $nodes[$nodeKey]       = [
                        'process_names_id' => $nameId,
                        'process_ids'      => [],
                        'rule_process_ids' => [],
                        'descriptions'     => [],
                        'point_key'        => $scope === 'point' ? (int) $rule->id : null,
                        'scope'            => $scope,
                    ];
                    $predsOf[$nodeKey]     = [];
                    $insertOrder[$nodeKey] = $seq++;
                }

                // Accumulate ids/descriptions
                $nodes[$nodeKey]['process_ids'][]      = (int) $process->id;
                $nodes[$nodeKey]['rule_process_ids'][] = (int) $rp->id;
                if ($rp->description !== null && $rp->description !== '') {
                    $nodes[$nodeKey]['descriptions'][] = $rp->description;
                }

                // Ordering constraint: prevKey must come before nodeKey
                if ($prevKey !== null && $prevKey !== $nodeKey) {
                    $predsOf[$nodeKey][$prevKey] = true;
                }

                $prevKey = $nodeKey;
            }
        }

        return $this->topoSort($nodes, $predsOf, $insertOrder);
    }

    /**
     * Kahn's algorithm. Ties among READY nodes are broken by ProcessName
     * plan_order (lower = earlier, null = 100; e.g. Machining = 10 — in-house
     * work runs before the part ships to vendors), then by first-seen insertion
     * order so the output stays deterministic.
     */
    private function topoSort(array $nodes, array $predsOf, array $insertOrder): array
    {
        // Build successor lists
        $successors = array_fill_keys(array_keys($nodes), []);
        foreach ($predsOf as $nodeKey => $preds) {
            foreach (array_keys($preds) as $pred) {
                $successors[$pred][] = $nodeKey;
            }
        }

        // In-degree per node
        $inDegree = [];
        foreach ($nodes as $nodeKey => $_) {
            $inDegree[$nodeKey] = count($predsOf[$nodeKey]);
        }

        $rank = fn ($k) => [$this->planOrderOf((int) $nodes[$k]['process_names_id']), $insertOrder[$k]];

        // Initial ready queue: nodes with no predecessors
        $queue = array_values(array_filter(
            array_keys($inDegree),
            fn ($k) => $inDegree[$k] === 0
        ));
        usort($queue, fn ($a, $b) => $rank($a) <=> $rank($b));

        $ordered = [];
        while (!empty($queue)) {
            $current   = array_shift($queue);
            $ordered[] = $current;

            $newReady = [];
            foreach ($successors[$current] as $succ) {
                if (--$inDegree[$succ] === 0) {
                    $newReady[] = $succ;
                }
            }
            usort($newReady, fn ($a, $b) => $rank($a) <=> $rank($b));
            // Append new-ready nodes to front of remaining queue so they are
            // processed before previously-ready nodes that happened to share
            // the same predecessor (keeps each place's chain locally together).
            $queue = array_merge($newReady, $queue);
        }

        // Build output
        $entries = [];
        foreach ($ordered as $nodeKey) {
            $n = $nodes[$nodeKey];
            $entries[] = [
                'process_names_id' => $n['process_names_id'],
                'process_ids'      => array_values(array_unique($n['process_ids'])),
                'rule_process_ids' => array_values(array_unique($n['rule_process_ids'])),
                'description'      => implode('; ', array_unique(array_filter($n['descriptions']))),
                'point_key'        => $n['point_key'],
                'scope'            => $n['scope'],
            ];
        }

        return $entries;
    }

    private function scopeOf(int $nameId): ?string
    {
        if (!array_key_exists($nameId, $this->scopeCache)) {
            $this->scopeCache[$nameId] = ProcessName::find($nameId)?->scope;
        }

        return $this->scopeCache[$nameId];
    }

    /** @var array<int,int> nameId → plan_order (null → 100) */
    private array $planOrderCache = [];

    private function planOrderOf(int $nameId): int
    {
        if (!array_key_exists($nameId, $this->planOrderCache)) {
            $this->planOrderCache[$nameId] = (int) (ProcessName::find($nameId)?->plan_order ?? 100);
        }

        return $this->planOrderCache[$nameId];
    }

    private function processNameExists(int $nameId): bool
    {
        if ($nameId <= 0) {
            return false;
        }

        if (! array_key_exists($nameId, $this->existsCache)) {
            $this->existsCache[$nameId] = ProcessName::query()->whereKey($nameId)->exists();
        }

        return $this->existsCache[$nameId];
    }
}
