<?php

namespace Tests\Feature;

use App\Models\ProcessName;
use App\Models\Unit;
use App\Models\Workorder;
use Tests\TestCase;

class PrintProcessFormFormattingTest extends TestCase
{
    public function test_machining_technician_and_library_are_printed_only_for_at_and_ec(): void
    {
        $this->actingAs((new \App\Models\User())->forceFill(['id'=>1, 'name'=>'Print Technician', 'selection_name_order'=>'first_last']));
        $wo = (new Workorder())->forceFill(['number'=>107736, 'serial_number'=>'00220']);
        $wo->setRelation('unit', (new Unit())->forceFill(['name'=>'Shock Strut', 'part_number'=>'2801A0000-03']));
        $preview = '';
        foreach (['Machining'=>true, 'Machining (AT)'=>true, 'Machining (EC)'=>true, 'Machining (OUT)'=>false, 'Machining (Blend)'=>false] as $name=>$visible) {
            $html = view('shared.process-forms._header', [
                'process_name'=>(new ProcessName())->forceFill(['name'=>$name, 'process_sheet_name'=>'MACHINING']),
                'current_wo'=>$wo, 'selectedVendor'=>null, 'machining_header_manual_libs'=>['294','286'],
            ])->render();
            if ($visible) {
                $this->assertStringContainsString('class="machining-sheet-header-meta', $html);
                $this->assertStringContainsString('Print Technician', $html);
                $this->assertStringContainsString('(lib: 294, 286)', $html);
            } else {
                $this->assertStringNotContainsString('class="machining-sheet-header-meta', $html);
                $this->assertStringNotContainsString('Print Technician', $html);
                $this->assertStringNotContainsString('(lib:', $html);
            }
            $this->assertStringContainsString('process-sheet-title-row--machining', $html);
            if (in_array($name, ['Machining (AT)','Machining (EC)','Machining (OUT)'], true)) {
                $preview .= '<section data-process="'.$name.'"><h3>'.$name.'</h3>'.$html.'</section>';
            }
        }
        file_put_contents(base_path('codex-test-runtime/machining-header.html'), '<!doctype html><html><head><style>'.file_get_contents(public_path('assets/Bootstrap 5/bootstrap.min.css')).'</style>'.view('shared.process-forms._styles')->render().'</head><body>'.$preview.'</body></html>');
    }

    public function test_process_header_shows_only_a_boxed_ro_marker_and_preserves_batch_labels(): void
    {
        $workorder = (new Workorder())->forceFill(['number' => 107736, 'serial_number' => '00220']);
        $workorder->setRelation('unit', (new Unit())->forceFill(['name' => 'Shock Strut', 'part_number' => '2801A0000-03']));
        foreach ([['tdr-processes', 'R9138', 'R'], ['tdr-processes', 'R9138, R9171', 'R'],
            ['tdr-processes', '  ', null], ['wo_bushings', 'B1', 'B1']] as [$module, $ro, $expected]) {
            $html = view('shared.process-forms._header', [
                'module' => $module,
                'current_wo' => $workorder,
                'process_name' => (new ProcessName())->forceFill(['name' => 'Passivation', 'process_sheet_name' => 'PASSIVATION']),
                'selectedVendor' => null,
                'formHeaderRepairOrder' => $ro,
            ])->render();
            if ($expected === null) {
                $this->assertStringNotContainsString('class="process-ro-box"', $html);
            } else {
                $this->assertStringContainsString('class="process-ro-box">'.$expected.'</strong>', $html);
            }
            $this->assertStringNotContainsString('R9138', $html);
            $this->assertStringNotContainsString('R9171', $html);
        }
    }

    public function test_process_form_preserves_specification_and_description_but_never_outputs_manual_comment(): void
    {
        $html = view('shared.process-forms._process-text', [
            'text' => "Specification <literal>\n11111\n222222",
            'comment' => "Steel parts.\n<Second line>",
            'description' => "Fig. 6010\nZone 2",
        ])->render();
        $this->assertStringContainsString("Specification &lt;literal&gt;<br />\n11111<br />\n222222", $html);
        $this->assertStringNotContainsString("Steel parts.", $html);
        $this->assertStringNotContainsString("process-comment", $html);
        $this->assertStringContainsString("class=\"process-description\">Fig. 6010\nZone 2", $html);
        $this->assertStringNotContainsString('<literal>', $html);
    }

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

    public function test_box_title_rows_do_not_extend_outside_the_watermark_card(): void
    {
        $html = view('admin.tdrs.wo_BoxTitle', [
            'current_wo' => (new Workorder())->forceFill([
                'number' => 107884,
                'unit_id' => 1,
                'customer_id' => 1,
                'user_id' => 1,
            ]),
            'units' => collect([(object) ['id' => 1, 'part_number' => '47200-29']]),
            'customers' => collect([(object) ['id' => 1, 'name' => 'AirStart Inc.']]),
            'users' => collect([(object) ['id' => 1, 'selection_name' => 'Test Technician']]),
        ])->render();

        $this->assertStringContainsString('.wo-box-title-card > .row {', $html);
        $this->assertStringContainsString('margin-left: 0;', $html);
        $this->assertStringContainsString('margin-right: 0;', $html);
        $this->assertStringContainsString('overflow: hidden;', $html);
    }
}
