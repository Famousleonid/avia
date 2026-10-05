<?php

return [
    // Rules for calculated ECD only; the manually entered ECD is independent.
    'fallback_days' => ['traveler' => 60],
    'between_process_days' => 2,
    'excluded_processes' => ['paint', 'stdpaintlist', 'stressrelief', 'stdstressrelieflist', 'inspect'],
];
