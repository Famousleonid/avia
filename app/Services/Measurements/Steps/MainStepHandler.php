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
        $entries = $this->collectEntries($ctx);
        if (!empty($entries)) {
            $ctx->addPointGroups('main', $entries);
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
        return array_values(array_unique(array_map(
            fn ($e) => (int) $e['process_names_id'],
            $this->collectEntries($ctx)
        )));
    }

    /**
     * Flat list of Main process entries, each carrying its point identity
     * (the repair rule id). PipelineContext decides per-point vs per-part
     * grouping by ProcessName.scope.
     *
     * @return array<int,array{process_names_id:int,process_id:int,rule_process_id:int,description:?string,point_key:int}>
     */
    private function collectEntries(PipelineContext $ctx): array
    {
        if (empty($ctx->mainRuleIds)) {
            return [];
        }

        $rules = ManualParameterRepairRule::with('processes.manualProcess.process')
            ->whereIn('id', $ctx->mainRuleIds)
            ->get();

        $entries = [];
        foreach ($rules as $rule) {
            foreach ($rule->processes as $rp) {
                $process = $rp->manualProcess?->process;
                if (!$process) {
                    continue;
                }
                $entries[] = [
                    'process_names_id' => (int) $process->process_names_id,
                    'process_id'       => (int) $process->id,
                    'rule_process_id'  => (int) $rp->id, // ManualParameterRuleProcess id
                    'description'      => $rp->description,
                    'point_key'        => (int) $rule->id, // per-point identity
                ];
            }
        }

        return $entries;
    }
}
