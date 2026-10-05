<?php

namespace Tests\Feature;

use App\Models\{ManualProcess, Process, ProcessName, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class ProcessFormCommentTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_manual_comment_is_absent_from_every_shared_form_family(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $component = $this->createComponent($wo->unit->manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$component->id, 'qty'=>1]);
        $comment = 'FUNKTIONAL FACE FROM 445mm TO 895mm FROM BOTTOM.';
        foreach (['NDT-1'=>'NDT', 'Stress Relief'=>'STRESS RELIEF', 'Cad plate'=>'CADMIUM PLATING'] as $name=>$sheet) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$sheet, 'print_form'=>true]);
            $type->update(['process_sheet_name'=>$sheet, 'print_form'=>true]);
            $process = Process::create(['process_names_id'=>$type->id, 'process'=>'MIL-HDBK-728/2']);
            $manualProcess = ManualProcess::create(['manual_id'=>$component->manual_id, 'processes_id'=>$process->id, 'process_comment'=>$comment]);
            $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$type->id, 'processes'=>[$process->id], 'description'=>'Fig. 6010 Zone 2']);
            $html = $this->get(route('tdr-processes.show', $row).'?process_id='.$process->id)->assertOk()->getContent();
            $this->assertStringNotContainsString($comment, $html);
            $this->assertStringNotContainsString('class="process-comment"', $html);
            $this->assertStringContainsString('MIL-HDBK-728/2', $html);
            $this->assertSame($comment, $manualProcess->fresh()->process_comment);
            file_put_contents(base_path('codex-test-runtime/no-comment-'.str_replace(' ', '-', $name).'.html'), $html);
        }
    }
}
