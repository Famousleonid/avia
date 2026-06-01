<?php

namespace App\Services\Measurements\Steps;

use App\Models\MasterRule;
use App\Services\Measurements\PipelineContext;

interface StepHandler
{
    /**
     * Resolve this phase: read context, append process groups.
     * $masterRule is null when the part has no repair plan (Main still works).
     */
    public function resolve(PipelineContext $ctx, ?MasterRule $masterRule): void;
}
