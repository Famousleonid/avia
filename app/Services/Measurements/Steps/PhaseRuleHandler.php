<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRule;
use App\Models\MasterRulePhaseRule;
use App\Services\Measurements\PipelineContext;

/**
 * Shared handler for Start / Finish phases — both pull MasterRule phase rules,
 * check the (optional) condition, and append their processes.
 * Subclasses only differ by which phase they handle.
 */
abstract class PhaseRuleHandler implements StepHandler
{
    abstract protected function phase(): string;

    public function resolve(PipelineContext $ctx, ?MasterRule $masterRule): void
    {
        if (!$masterRule) {
            return; // no repair plan → no Start/Finish processes
        }

        $rules = $masterRule->phaseRules
            ->where('phase', $this->phase())
            ->sortBy('sort_order');

        foreach ($rules as $rule) {
            if (!$this->conditionMet($rule, $ctx)) {
                continue;
            }
            $byNameId = [];
            foreach ($rule->processes as $rp) {
                $process = $rp->manualProcess?->process;
                if (!$process) {
                    continue;
                }
                $byNameId[$process->process_names_id][] = $process->id;
            }
            $ctx->addPhaseGroups($this->phase(), $byNameId);
        }
    }

    /**
     * Evaluate the optional condition JSON on a phase rule.
     * No condition (null) => always applies.
     *
     * Supported condition types:
     *   ['type' => 'always']
     *   ['type' => 'has_main_process', 'process_name_ids' => [int, ...]]
     *   ['type' => 'any_point_fail']
     *   ['type' => 'has_defect', 'codes_ids' => [int, ...]]
     */
    protected function conditionMet(MasterRulePhaseRule $rule, PipelineContext $ctx): bool
    {
        $cond = $rule->condition;
        if (empty($cond) || empty($cond['type']) || $cond['type'] === 'always') {
            return true;
        }

        switch ($cond['type']) {
            case 'has_main_process':
                $need = array_map('intval', $cond['process_name_ids'] ?? []);
                return (bool) array_intersect($need, $ctx->mainProcessNameIds);

            case 'any_point_fail':
                return !empty($ctx->mainRuleIds);

            case 'has_defect':
                $need = array_map('intval', $cond['codes_ids'] ?? []);
                return (bool) array_intersect($need, $ctx->defectCodeIds);

            default:
                return true; // unknown condition → don't block
        }
    }
}
