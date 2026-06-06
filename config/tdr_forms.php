<?php

$stdPrintSettings = [
    'table_rows_key' => 'stdTableRows',
    'table_rows_default' => 18,
    'component_name_font_size' => 16,
    'header_data_font_size' => 16,
    'table_data_font_size' => 13,
    'simple_print_settings' => true,
    'locked_settings' => [
        'pageMargin' => '8mm',
        'componentNameFontSize' => '16',
        'headerDataFontSize' => '16',
        'footerFontSize' => '12px',
    ],
    'container_max_width' => 920,
    'container_padding' => 5,
    'container_margin_left' => 10,
    'container_margin_right' => 10,
    'body_width' => 98,
    'body_height' => 99,
    'page_margin' => 8,
    'body_margin_left' => 2,
    'footer_width' => 800,
    'footer_font_size' => 12,
    'footer_padding' => '3px 3px',
];

return [
    /*
    |--------------------------------------------------------------------------
    | TDR Forms Print Settings Configuration
    |--------------------------------------------------------------------------
    | Настройки печати для форм ndtFormStd, cadFormStd, stressFormStd, paintFormStd.
    | Используется shared modal и scripts как у processesForm.
    */

    'ndtFormStd' => [
        ...$stdPrintSettings,
        'storage_key' => 'ndtFormStd_print_settings_v4',
        'tooltip_lang_key' => 'ndtFormStd_tooltip_lang',
        'table_rows_default' => 14,
        'table_data_font_size' => 14,
    ],

    'cadFormStd' => [
        ...$stdPrintSettings,
        'storage_key' => 'cadFormStd_print_settings_v3',
        'tooltip_lang_key' => 'cadFormStd_tooltip_lang',
    ],

    'stressFormStd' => [
        ...$stdPrintSettings,
        'storage_key' => 'stressFormStd_print_settings_v3',
        'tooltip_lang_key' => 'stressFormStd_tooltip_lang',
    ],

    'paintFormStd' => [
        ...$stdPrintSettings,
        'storage_key' => 'paintFormStd_print_settings_v3',
        'tooltip_lang_key' => 'paintFormStd_tooltip_lang',
    ],

    'trainingForm112' => [
        'storage_key' => 'trainingForm112_print_settings',
        'tooltip_lang_key' => 'trainingForm112_tooltip_lang',
        'table_rows_key' => 'trainingTableRows',
        'table_rows_default' => 1,
        'show_table_settings' => false,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 10,
        'container_margin_right' => 10,
        'body_width' => 105,
        'body_height' => 85,
        'page_margin' => 2,
        'body_margin_left' => 10,
        'footer_width' => 920,
        'footer_font_size' => 12,
        'footer_padding' => '5px 10px',
    ],

    'trainingForm132' => [
        'storage_key' => 'trainingForm132_print_settings',
        'tooltip_lang_key' => 'trainingForm132_tooltip_lang',
        'table_rows_key' => 'trainingTableRows',
        'table_rows_default' => 1,
        'show_table_settings' => false,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 10,
        'container_margin_right' => 10,
        'body_width' => 107,
        'body_height' => 99,
        'page_margin' => 6,
        'body_margin_left' => 1,
        'footer_width' => 920,
        'footer_font_size' => 12,
        'footer_padding' => '1px 20px',
    ],
];
