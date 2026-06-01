<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRule;
use App\Models\ManualParameterRepairRule;
use App\Services\Measurements\PipelineContext;

/**
 * Main phase: processes from the matched repair rules of failed points.
 * Independent of MasterRule — works even when the part has no repair plan.
 */
class MainStepHandler implements StepHandler
{
    public function resolve(PipelineContext $ctx, ?MasterRule $masterRule): void
    {
        $byNameId = $this->collectByNameId($ctx);
        if (!empty($byNameId)) {
            $ctx->addPhaseGroups('main', $byNameId);
        }
    }

    /**
     * Process_names_id list that Main will contribute — used to pre-fill the
     * context BEFORE Start runs, so Start/Finish conditions can reference Main.
     *
     * @return int[]
     */
    public function previewNameIds(PipelineContext $ctx): array
    {
        return array_keys($this->collectByNameId($ctx));
    }

    /**
     * @return array<int,int[]>  [process_names_id => [process_id, ...]]
     */
    private function collectByNameId(PipelineContext $ctx): array
    {
        if (empty($ctx->mainRuleIds)) {
            return [];
        }

        $rules = ManualParameterRepairRule::with('processes.manualProcess.process')
            ->whereIn('id', $ctx->mainRuleIds)
            ->get();

        $byNameId = [];
        foreach ($rules as $rule) {
            foreach ($rule->processes as $rp) {
                $process = $rp->manualProcess?->process;
                if (!$process) {
                    continue;
                }
                $byNameId[$process->process_names_id][] = $process->id;
            }
        }

        return $byNameId;
    }
}
