<?php

namespace App\Services\Measurements;

use App\Models\ProcessName;

/**
 * Carries inputs and accumulates outputs as the repair pipeline runs
 * through Start -> Main -> Finish phases for a single part (inspection component).
 *
 * Grouping is scope-aware (ProcessName.scope):
 *   point  → one row PER point (per repair rule): Machining 1, Machining 2, …
 *   part   → one row for the whole part (NDT, shot peen, paint, …)
 *
 * Final order is by ProcessName.stage (start → prep → ndt → post → finish),
 * NOT by the order phases were appended. Unclassified (null) stage falls back
 * to the phase it came from so it stays roughly in place.
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
     * Accumulated, scope-merged groups (NOT yet stage-ordered).
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

    /** Stage execution order; gate sits at `ndt`, EC holds post + finish. */
    private const STAGE_RANK = ['start' => 10, 'prep' => 20, 'ndt' => 30, 'post' => 40, 'finish' => 50];

    /** Fallback rank for unclassified (null) stage — keep within its phase. */
    private const PHASE_FALLBACK = ['start' => 11, 'main' => 35, 'finish' => 51];

    private int $insertSeq = 0;

    /** merge key => index into $processGroups (for scope aggregation) */
    private array $mergeIndex = [];

    /** process_names_id => stage|null / scope|null (lazy cache) */
    private array $stageCache = [];
    private array $scopeCache = [];

    /**
     * Part-level append (Start / Finish). Processes aggregate by process_names_id
     * within the phase; a point_key is irrelevant here. If a ProcessName happens
     * to be scope=point it still aggregates (no point identity available).
     *
     * @param array<int,int[]>    $byNameId           [process_names_id => [process_id, ...]]
     * @param array<int,int[]>    $byNameRpIds        [process_names_id => [rule_process_id, ...]]
     * @param array<int,string[]> $byNameDescriptions [process_names_id => [note, ...]]
     */
    public function addPhaseGroups(string $phase, array $byNameId, array $byNameRpIds = [], array $byNameDescriptions = []): void
    {
        foreach ($byNameId as $nameId => $processIds) {
            foreach (array_values($processIds) as $i => $pid) {
                $this->addEntry(
                    $phase,
                    (int) $nameId,
                    (int) $pid,
                    $byNameRpIds[$nameId][$i] ?? null,
                    $byNameDescriptions[$nameId][$i] ?? null,
                    null
                );
            }
        }
    }

    /**
     * Point-aware append (Main). For scope=point ProcessNames each point (repair
     * rule) gets its own row; scope=part ProcessNames still aggregate to one row.
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
        $scope = $this->scopeOf($nameId);

        // scope=point with a known point → row per point; otherwise aggregate per name.
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
        $this->processGroups[] = [
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

    private function scopeOf(int $nameId): ?string
    {
        if (!array_key_exists($nameId, $this->scopeCache)) {
            $pn = ProcessName::find($nameId);
            $this->scopeCache[$nameId] = $pn?->scope;
            $this->stageCache[$nameId] = $pn?->stage;
        }

        return $this->scopeCache[$nameId];
    }

    /**
     * Final groups in stage execution order, with sort_order assigned.
     * Each: ['process_names_id','process_ids','rule_process_ids','description',
     *        'sort_order','phase','stage','scope'].
     *
     * @return array<int,array>
     */
    public function orderedGroups(): array
    {
        $groups = $this->processGroups;
        usort($groups, function ($a, $b) {
            $ra = $this->rankOf($a);
            $rb = $this->rankOf($b);

            return $ra === $rb ? ($a['_seq'] <=> $b['_seq']) : ($ra <=> $rb);
        });

        $out  = [];
        $sort = 0;
        foreach ($groups as $g) {
            $notes = array_values(array_unique(array_filter(
                $g['descriptions'],
                fn ($d) => $d !== null && $d !== ''
            )));
            $out[] = [
                'process_names_id' => $g['process_names_id'],
                'process_ids'      => array_values(array_unique($g['process_ids'])),
                'rule_process_ids' => array_values(array_unique($g['rule_process_ids'])),
                'description'      => implode('; ', $notes),
                'sort_order'       => $sort++,
                'phase'            => $g['phase'],
                'stage'            => $g['stage'],
                'scope'            => $g['scope'],
            ];
        }

        return $out;
    }

    private function rankOf(array $g): int
    {
        if (!empty($g['stage']) && isset(self::STAGE_RANK[$g['stage']])) {
            return self::STAGE_RANK[$g['stage']];
        }

        return self::PHASE_FALLBACK[$g['phase']] ?? 35;
    }

    public function isEmpty(): bool
    {
        return empty($this->processGroups);
    }
}
