<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Process Forms Configuration
    |--------------------------------------------------------------------------
    | Настройки печатных форм процессов для модулей tdr-processes,
    | extra_processes, wo_bushings. Используется shared layout и partials.
    */

    'tdr-processes' => [
        'container_max_width' => 1200,
        'ndt_table_rows' => 17,
        'stress_table_rows' => 21,
        'other_table_rows' => 21,
        'component_name_font_size' => 12,
        'header_title' => 'PROCESS SHEET',
        'storage_key' => 'processesForm_print_settings',
    ],

    'extra_processes' => [
        'container_max_width' => 920,
        'ndt_table_rows' => 16,
        'stress_table_rows' => 19,
        'other_table_rows' => 19,
        'component_name_font_size' => 12,
        'header_title' => 'EXTRA PROCESS',
        'storage_key' => 'extraProcessesForm_print_settings',
    ],

    'wo_bushings' => [
        'container_max_width' => 920,
        'ndt_table_rows' => 20,
        'stress_table_rows' => 21,
        'other_table_rows' => 20,
        'component_name_font_size' => 12,
        'header_title' => 'WO BUSHING',
        'storage_key' => 'woBushingsProcessesForm_print_settings',
    ],
];
