<?php

namespace Tests\Feature;

use App\Models\{Process, ProcessName, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class UsMeasurementFormTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_selected_us_measurement_instruction_is_printed_in_process_cell(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $component = $this->createComponent($wo->unit->manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$component->id, 'qty'=>1]);
        $name = ProcessName::create(['name'=>'US Measurement', 'process_sheet_name'=>'NDT', 'form_number'=>'016', 'print_form'=>true]);
        $first = Process::create(['process_names_id'=>$name->id, 'process'=>'SUBTASK 32-11-14RM-350-113-A01 (27),(i,j).']);
        $second = Process::create(['process_names_id'=>$name->id, 'process'=>'Second selected <instruction>']);
        $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$name->id, 'processes'=>[$first->id,$second->id]]);
        $url = route('tdr-processes.show', $row);
        $response = $this->get($url.'?process_id='.$first->id)->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString($first->process, $this->processCell($html));
        $this->assertStringNotContainsString('Second selected', $html);
        file_put_contents(base_path('codex-test-runtime/us-measurement-form.html'), $html);
        $all = $this->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('Second selected &lt;instruction&gt;', $this->processCell($all));
        $this->assertStringNotContainsString('<instruction>', $all);
        $none = $this->get($url.'?process_id=99999999')->assertOk()->getContent();
        $this->assertSame('', trim(strip_tags($this->processCell($none))));

        // A regular NDT process must still print its method number.
        $ndt = ProcessName::firstOrCreate(['name'=>'NDT-1'], ['process_sheet_name'=>'NDT', 'form_number'=>'016', 'print_form'=>true]);
        $ndt->update(['process_sheet_name'=>'NDT','print_form'=>true]);
        $row->update(['process_names_id'=>$ndt->id]);
        $normal = $this->get($url)->assertOk()->getContent();
        $this->assertSame('1', trim(strip_tags($this->processCell($normal))));
    }

    private function processCell(string $html): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $cell = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," data-row-ndt ")][1]/div[4]')->item(0);
        $this->assertNotNull($cell);
        return $dom->saveHTML($cell);
    }
}
