<?php

namespace App\Services\Measurements;

/**
 * Carries inputs and accumulates outputs as the repair pipeline runs
 * through Start -> Main -> Finish phases for a single part (inspection component).
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
     * Accumulated process groups, in execution order.
     * Each entry: ['process_names_id' => int, 'process_ids' => int[], 'sort_order' => int, 'phase' => string]
     * @var array<int,array>
     */
    public array $processGroups = [];

    /** process_names_id that Main contributes — pre-computed before Start runs,
     *  so BOTH Start and Finish conditions can reference what's in Main. */
    public array $mainProcessNameIds = [];

    /** EC gate flag (Stage 4 — reserved) */
    public bool $heldPendingEc = false;

    private int $sortCursor = 0;

    /**
     * Add grouped processes for the current phase.
     * Groups by process_names_id WITHIN the phase (dedupes process ids),
     * but keeps phases separate so Start/Finish stay in their own rows.
     *
     * @param array<int,int[]>    $byNameId           [process_names_id => [process_id, ...]]
     * @param array<int,int[]>    $byNameRpIds        [process_names_id => [rule_process_id, ...]] (optional)
     * @param array<int,string[]> $byNameDescriptions [process_names_id => [note, ...]] (optional)
     */
    public function addPhaseGroups(string $phase, array $byNameId, array $byNameRpIds = [], array $byNameDescriptions = []): void
    {
        foreach ($byNameId as $nameId => $processIds) {
            $notes = array_values(array_unique(array_filter(
                $byNameDescriptions[$nameId] ?? [],
                fn($d) => $d !== null && $d !== ''
            )));
            $this->processGroups[] = [
                'process_names_id' => $nameId,
                'process_ids'      => array_values(array_unique($processIds)),
                'rule_process_ids' => array_values(array_unique($byNameRpIds[$nameId] ?? [])),
                'description'      => implode('; ', $notes), // per-process notes; empty when none
                'sort_order'       => $this->sortCursor++,
                'phase'            => $phase,
            ];
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->processGroups);
    }
}
