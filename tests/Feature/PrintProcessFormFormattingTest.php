<?php

namespace Tests\Feature;

use App\Models\ProcessName;
use App\Models\Unit;
use App\Models\Workorder;
use Tests\TestCase;

class PrintProcessFormFormattingTest extends TestCase
{
    public function test_process_form_header_grows_for_a_wrapped_workorder_description(): void
    {
        $unit = (new Unit())->forceFill([
            'name' => 'Shock Strut Assy, Dressed, NLG',
            'part_number' => '52000-31',
        ]);
        $workorder = (new Workorder())->forceFill([
            'number' => 107849,
            'serial_number' => 'MA1713',
        ]);
        $workorder->setRelation('unit', $unit);

        $html = view('shared.process-forms._header', [
            'process_name' => (new ProcessName())->forceFill([
                'name' => 'Shot Peening',
                'process_sheet_name' => 'SHOT PEENING',
            ]),
            'current_wo' => $workorder,
            'selectedVendor' => null,
        ])->render();
        $styles = view('shared.process-forms._styles')->render();

        $this->assertStringContainsString('process-header-component-row', $html);
        $this->assertStringNotContainsString('style="height: 32px"', $html);
        $this->assertStringContainsString('.header-page .process-header-field-row { min-height: 32px; }', $styles);
        $this->assertStringContainsString('Shock Strut Assy, Dressed, NLG', $html);
        $this->assertStringContainsString('52000-31', $html);
    }

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
