<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workorder role-based visibility
    |--------------------------------------------------------------------------
    |
    | These rules keep shop-floor visibility separate from the global workorder
    | completion rules. Add another role here when that role should use the
    | same restricted view.
    |
    */

    'roles_hide_general_task_positions' => [
        'Technician',
        'Team Leader',
    ],

    'hidden_general_task_positions' => [
        5,
    ],

    'roles_hide_submitted_final_inspection_from_active' => [
        'Technician',
        'Team Leader',
    ],

    'roles_hide_task_names_in_mains' => [
        'Technician',
        'Team Leader',
    ],

    'hidden_task_names_in_mains' => [
        'Approved',
        'Completed',
    ],

    'completed_task_names' => [
        'Completed',
    ],

    'submitted_final_inspection_task_names' => [
        'WO Submitted for Final Inspection',
        'Submitted for Final Inspection',
    ],

    'submitted_final_inspection_task_contains_all' => [
        ['submitted', 'final'],
    ],
];
