<?php

namespace App\Services\Measurements;

use App\Models\ProcessName;

/**
 * Carries inputs and accumulates outputs as the repair pipeline runs
 * through Start -> Main -> Finish phases for a single part (inspection component).
 *
 * Grouping is scope-aware (ProcessName.scope):
 *   point  → one row PER rule (per repair rule): Machining 1, Machining 2, …
 *   part   → one row for the whole part (NDT, shot peen, …)
 *
 * Final order:
 *   phase rank (start=0, main=1, finish=2) → insertion order (_seq).
 *   Main phase entries are pre-ordered by TopologicalProcessMerger and stored
 *   directly via setMainGroups() — no further reordering within Main.
 *   Start / Finish entries are deduped by nameId within their phase.
 */
class PipelineContext
{
    /** @var int|null inspection_component_id of the part being repaired */
    public ?int $inspectionComponentId = null;

    /** @var int[] ManualParameterRepairRule ids matched on failed points (Main) */
    public array $mainRuleIds = [];

    /** @var int[] codes_id of defects found on this part (for condition checks) */
    public array $defectCodeIds = [];

    /**
     * Accumulated process groups (NOT yet phase-sorted).
     * Each: ['process_names_id','process_ids','rule_process_ids','descriptions',
     *        'phase','stage','scope','point_key','_seq']
     * @var array<int,array>
     */
    public array $processGroups = [];

    /** process_names_id that Main contributes — pre-computed before Start runs,
     *  so BOTH Start and Finish conditions can reference what's in Main. */
    public array $mainProcessNameIds = [];

    /** EC gate flag — Finish phase is held until the concession is granted. */
    public bool $heldPendingEc = false;

    private const PHASE_RANK = ['start' => 0, 'main' => 1, 'finish' => 2];

    private int $insertSeq = 0;

    /** merge key → index into $processGroups (used for Start/Finish dedup only) */
    private array $mergeIndex = [];

    /** nameId → scope|null (lazy cache) */
    private array $scopeCache = [];
    /** nameId → stage|null (lazy cache) */
    private array $stageCache = [];

    private array $existsCache = [];

    // -------------------------------------------------------------------------
    // Main phase (topologically pre-ordered by TopologicalProcessMerger)
    // -------------------------------------------------------------------------

    /**
     * Store pre-merged, pre-ordered Main process groups produced by
     * TopologicalProcessMerger. Entries are appended in order — no further
     * deduplication or reordering is applied.
     *
     * @param array<int,array{process_names_id:int,process_ids:int[],rule_process_ids:int[],description:string,point_key:mixed,scope:string}> $entries
     */
    public function setMainGroups(array $entries): void
    {
        foreach ($entries as $e) {
            $nameId = (int) $e['process_names_id'];
            if (! $this->processNameExists($nameId)) {
                continue;
            }

            $this->warmCache($nameId);

            $this->processGroups[] = [
                'process_names_id' => $nameId,
                'process_ids'      => $e['process_ids'],
                'rule_process_ids' => $e['rule_process_ids'],
                'descriptions'     => array_filter((array) ($e['description'] ?? [])),
                'phase'            => 'main',
                'stage'            => $this->stageCache[$nameId] ?? null,
                'scope'            => $e['scope'],
                'point_key'        => $e['point_key'],
                '_seq'             => $this->insertSeq++,
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Start / Finish phases (from MasterRule phase rules)
    // -------------------------------------------------------------------------

    /**
     * Append phase-level (Start / Finish) process entries.
     *
     * scope=part entries deduplicate by nameId within the phase — the same
     * part-scope process from two different phase rules becomes one row.
     * scope=point entries (unusual in Start/Finish but supported) deduplicate
     * by nameId+pointKey within the phase.
     *
     * @param array<int,array{process_names_id:int,process_id:int,rule_process_id:?int,description:?string,point_key:mixed}> $entries
     */
    public function addPointGroups(string $phase, array $entries): void
    {
        foreach ($entries as $e) {
            $this->addEntry(
                $phase,
                (int) $e['process_names_id'],
                (int) $e['process_id'],
                $e['rule_process_id'] ?? null,
                $e['description'] ?? null,
                $e['point_key'] ?? null
            );
        }
    }

    private function addEntry(string $phase, int $nameId, int $pid, $rpId, ?string $desc, $pointKey): void
    {
        if (! $this->processNameExists($nameId)) {
            return;
        }

        $scope = $this->scopeOf($nameId);

        // Within Start/Finish: deduplicate scope=part by nameId, scope=point by nameId+pointKey
        $key = ($scope === 'point' && $pointKey !== null)
            ? $phase . '|' . $nameId . '|' . $pointKey
            : $phase . '|' . $nameId;

        if (isset($this->mergeIndex[$key])) {
            $g = &$this->processGroups[$this->mergeIndex[$key]];
            if ($pid) {
                $g['process_ids'][] = $pid;
            }
            if ($rpId) {
                $g['rule_process_ids'][] = $rpId;
            }
            if ($desc !== null && $desc !== '') {
                $g['descriptions'][] = $desc;
            }
            unset($g);

            return;
        }

        $this->mergeIndex[$key] = count($this->processGroups);
        $this->processGroups[]  = [
            'process_names_id' => $nameId,
            'process_ids'      => $pid ? [$pid] : [],
            'rule_process_ids' => $rpId ? [$rpId] : [],
            'descriptions'     => ($desc !== null && $desc !== '') ? [$desc] : [],
            'phase'            => $phase,
            'stage'            => $this->stageCache[$nameId] ?? null,
            'scope'            => $scope,
            'point_key'        => $pointKey,
            '_seq'             => $this->insertSeq++,
        ];
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    /**
     * Final ordered groups with sequential sort_order assigned.
     *
     * Sort: phase rank (start → main → finish), then insertion order (_seq)
     * within each phase. Main entries arrive pre-ordered from the topological
     * merger, so _seq preserves that order exactly.
     *
     * @return array<int,array>
     */
    public function orderedGroups(): array
    {
        $groups = $this->processGroups;
        usort($groups, function ($a, $b) {
            $ra = self::PHASE_RANK[$a['phase']] ?? 1;
            $rb = self::PHASE_RANK[$b['phase']] ?? 1;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }

            return $a['_seq'] <=> $b['_seq'];
        });

        $out  = [];
        $sort = 0;
        foreach ($groups as $g) {
            $notes   = array_values(array_unique(array_filter(
                $g['descriptions'],
                fn ($d) => $d !== null && $d !== ''
            )));
            $rpIds   = array_values(array_unique($g['rule_process_ids']));
            $isPhase = in_array($g['phase'], ['start', 'finish'], true);
            $out[]   = [
                'process_names_id'       => $g['process_names_id'],
                'process_ids'            => array_values(array_unique($g['process_ids'])),
                'rule_process_ids'       => $isPhase ? [] : $rpIds,
                'phase_rule_process_ids' => $isPhase ? $rpIds : [],
                'description'            => implode('; ', $notes),
                'sort_order'             => $sort++,
                'phase'                  => $g['phase'],
                'stage'                  => $g['stage'],
                'scope'                  => $g['scope'],
            ];
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function scopeOf(int $nameId): ?string
    {
        $this->warmCache($nameId);

        return $this->scopeCache[$nameId];
    }

    private function warmCache(int $nameId): void
    {
        if (!array_key_exists($nameId, $this->scopeCache)) {
            $pn                       = ProcessName::find($nameId);
            $this->scopeCache[$nameId] = $pn?->scope;
            $this->stageCache[$nameId] = $pn?->stage;
        }
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

    public function isEmpty(): bool
    {
        return empty($this->processGroups);
    }
}
