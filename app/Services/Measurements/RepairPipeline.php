<?php

namespace App\Services\Measurements;

use App\Models\MasterRule;
use App\Services\Measurements\Steps\FinishStepHandler;
use App\Services\Measurements\Steps\MainStepHandler;
use App\Services\Measurements\Steps\StartStepHandler;

/**
 * Orchestrates a part's repair plan: runs Start -> Main -> Finish, each handler
 * appending its process groups to the context in execution order.
 *
 * Phase order is fixed in code (Start, Main, Finish). New phases (e.g. Choice)
 * are added here as new handlers — no schema change required.
 */
class RepairPipeline
{
    /** @return \App\Services\Measurements\Steps\StepHandler[] */
    private function handlers(): array
    {
        return [
            new StartStepHandler(),
            new MainStepHandler(),
            new FinishStepHandler(),
        ];
    }

    /**
     * Resolve the full ordered list of process groups for a part.
     * Returns processGroups: each ['process_names_id', 'process_ids', 'sort_order', 'phase'].
     */
    public function run(PipelineContext $ctx): PipelineContext
    {
        $masterRule = $ctx->inspectionComponentId
            ? MasterRule::with('phaseRules.processes.manualProcess.process')
                ->where('inspection_component_id', $ctx->inspectionComponentId)
                ->first()
            : null;

        foreach ($this->handlers() as $handler) {
            $handler->resolve($ctx, $masterRule);
        }

        return $ctx;
    }
}
