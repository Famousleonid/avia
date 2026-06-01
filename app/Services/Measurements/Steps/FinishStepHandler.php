<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRulePhaseRule;

class FinishStepHandler extends PhaseRuleHandler
{
    protected function phase(): string
    {
        return MasterRulePhaseRule::PHASE_FINISH;
    }
}
