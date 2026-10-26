<?php

return [
    // Parallel WO camera trial. The existing native-camera buttons never depend on this flag.
    'pilot_enabled' => (bool) env('MOBILE_CAMERA_PILOT_ENABLED', true),
];
