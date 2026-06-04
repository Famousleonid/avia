<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRule;
use App\Models\MasterRulePhaseRule;
use App\Services\Measurements\PipelineContext;

class FinishStepHandler extends PhaseRuleHandler
{
    protected function phase(): string
    {
        return MasterRulePhaseRule::PHASE_FINISH;
    }

    public function resolve(PipelineContext $ctx, ?MasterRule $masterRule): void
    {
        // EC: the part is held pending OEM concession — Finish (plating, final
        // inspection, …) is not formed until the EC is granted.
        if ($ctx->heldPendingEc) {
            return;
        }
        parent::resolve($ctx, $masterRule);
    }
}
