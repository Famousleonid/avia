<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRulePhaseRule;

class StartStepHandler extends PhaseRuleHandler
{
    protected function phase(): string
    {
        return MasterRulePhaseRule::PHASE_START;
    }
}
