<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRule;
use App\Models\ManualParameterRepairRule;
use App\Services\Measurements\PipelineContext;
use App\Services\Measurements\TopologicalProcessMerger;

/**
 * Main phase: processes from the matched repair rules of failed points.
 *
 * Uses TopologicalProcessMerger to produce a single ordered process list
 * from all matched rules. Ordering constraints are derived from each rule's
 * own process sequence (sort_order within the rule); the merger builds a
 * dependency graph and produces the correct interleaved order automatically —
 * no global sort_order coordination across rules is required.
 *
 * Independent of MasterRule — works even when the part has no repair plan.
 */
class MainStepHandler implements StepHandler
{
    public function resolve(PipelineContext $ctx, ?MasterRule $masterRule): void
    {
        $rules = $this->loadRules($ctx);
        if ($rules->isEmpty()) {
            return;
        }

        $entries = (new TopologicalProcessMerger())->merge($rules);
        $ctx->setMainGroups($entries);
    }

    /**
     * Process_names_id list that Main will contribute — used to pre-fill the
     * context BEFORE Start runs, so Start/Finish conditions can reference Main.
     *
     * @return int[]
     */
    public function previewNameIds(PipelineContext $ctx): array
    {
        $rules = $this->loadRules($ctx);
        $ids   = [];
        foreach ($rules as $rule) {
            foreach ($rule->processes as $rp) {
                $process = $rp->manualProcess?->process;
                if ($process) {
                    $ids[] = (int) $process->process_names_id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function loadRules(PipelineContext $ctx)
    {
        if (empty($ctx->mainRuleIds)) {
            return collect();
        }

        return ManualParameterRepairRule::with('processes.manualProcess.process')
            ->whereIn('id', $ctx->mainRuleIds)
            ->get();
    }
}
