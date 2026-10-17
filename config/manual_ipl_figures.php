<?php

// Verified against ELEB CMM 32-10-01, revision 30/Jan/2026:
// p.1051 FIG1001 sheet5: 2309-2002 ER; pp.1106-1107 FIG1012 sheets5-6:
// 2309-3002/3100 LR and 2309-4002 XR. Do not infer a branch for other P/Ns.
$eleb = [
    '2309-2002-' => [1001, 1011],
    '2309-3002-' => [1012, 1021],
    '2309-3100-' => [1012, 1021],
    '2309-4002-' => [1012, 1021],
];

return [
    '32-10-01 ELEB' => $eleb,
    // The same ELEB manual retains its original number in the local catalog.
    '32-10-01' => $eleb,
];
