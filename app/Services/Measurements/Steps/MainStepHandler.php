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
        if (empty($ctx->mainRuleIds)) {
            return;
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
                $nameId = $process->process_names_id;
                $byNameId[$nameId][] = $process->id;
            }
        }

        $ctx->addPhaseGroups('main', $byNameId);
    }
}
