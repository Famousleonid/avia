<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrintProcessFormFormattingTest extends TestCase
{
    public function test_process_form_prints_numeric_cmm_and_semicolons_as_line_breaks(): void
    {
        $manual = (object) [
            'id' => 21,
            'number' => '32-21-04 Goodrich',
        ];
        $component = (object) [
            'ipl_num' => '4-500B',
            'part_number' => '47105-105',
            'name' => 'Inner Cylinder NP',
        ];
        $process = (object) [
            'id' => 88,
            'process' => 'Remove Chrome Plating <test>; Bake as per MIL-STD-1501',
        ];

        $html = view('shared.process-forms.other._content', [
            'formConfig' => ['other_table_rows' => 1],
            'table_data' => [[
                'component' => $component,
                'process' => $process,
                'extra_process' => (object) ['serial_num' => null, 'qty' => 1],
            ]],
            'process_name' => (object) ['process_sheet_name' => 'CHROME PLATING'],
            'process_components' => collect(),
            'manuals' => collect([$manual]),
            'current_wo' => (object) [
                'unit' => (object) ['manual_id' => $manual->id],
            ],
        ])->render();

        $this->assertStringContainsString('32-21-04', $html);
        $this->assertStringNotContainsString('Goodrich', $html);
        $this->assertStringContainsString("Remove Chrome Plating &lt;test&gt;<br />\nBake as per MIL-STD-1501", $html);
        $this->assertStringNotContainsString('Remove Chrome Plating <test>', $html);
    }
}
