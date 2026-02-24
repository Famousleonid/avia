<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TDR Forms Print Settings Configuration
    |--------------------------------------------------------------------------
    | Настройки печати для форм ndtFormStd, cadFormStd, stressFormStd, paintFormStd.
    | Используется shared modal и scripts как у processesForm.
    */

    'ndtFormStd' => [
        'storage_key' => 'ndtFormStd_print_settings',
        'tooltip_lang_key' => 'ndtFormStd_tooltip_lang',
        'table_rows_key' => 'ndtTableRows',
        'table_rows_default' => 16,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 10,
        'container_margin_right' => 10,
        'body_width' => 98,
        'body_height' => 99,
        'page_margin' => 1,
        'body_margin_left' => 2,
        'footer_width' => 800,
        'footer_font_size' => 10,
        'footer_padding' => '3px 3px',
    ],

    'cadFormStd' => [
        'storage_key' => 'cadFormStd_print_settings',
        'tooltip_lang_key' => 'cadFormStd_tooltip_lang',
        'table_rows_key' => 'cadTableRows',
        'table_rows_default' => 19,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 10,
        'container_margin_right' => 10,
        'body_width' => 98,
        'body_height' => 99,
        'page_margin' => 1,
        'body_margin_left' => 2,
        'footer_width' => 800,
        'footer_font_size' => 10,
        'footer_padding' => '2px 2px',
    ],

    'stressFormStd' => [
        'storage_key' => 'stressFormStd_print_settings',
        'tooltip_lang_key' => 'stressFormStd_tooltip_lang',
        'table_rows_key' => 'stressTableRows',
        'table_rows_default' => 21,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 10,
        'container_margin_right' => 10,
        'body_width' => 98,
        'body_height' => 99,
        'page_margin' => 1,
        'body_margin_left' => 2,
        'footer_width' => 800,
        'footer_font_size' => 10,
        'footer_padding' => '2px 2px',
    ],

    'paintFormStd' => [
        'storage_key' => 'paintFormStd_print_settings',
        'tooltip_lang_key' => 'paintFormStd_tooltip_lang',
        'table_rows_key' => 'paintTableRows',
        'table_rows_default' => 19,
        'component_name_font_size' => 12,
        'container_max_width' => 920,
        'container_padding' => 5,
        'container_margin_left' => 5,
        'container_margin_right' => 5,
        'body_width' => 98,
        'body_height' => 95,
        'page_margin' => 1,
        'body_margin_left' => 2,
        'footer_width' => 800,
        'footer_font_size' => 10,
        'footer_padding' => '2px 2px',
    ],
];
