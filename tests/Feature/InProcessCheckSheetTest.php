<?php

namespace Tests\Feature;

use App\Models\ManualInProcessCheckSheet;
use App\Services\InProcessCheckSheetImporter;
use App\Services\InProcessCheckSheetSql;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\BuildsDomainData;
use Tests\TestCase;

class InProcessCheckSheetTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function source(): array
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('IN PROCESS CHECK SHEET');
        $sheet->setCellValue('D2', 'IN-PROCESS CHECK SHEET');
        $sheet->setCellValue('K3', '=Title!E6');
        $sheet->setCellValue('E5', 'SAMPLE-PN-NOT-FOR-WO');
        $sheet->setCellValue('A7', 'Inspect and stamp.');
        $sheet->setCellValue('A10', 'INPROCESS STAGE');
        $sheet->setCellValue('A12', '# 5');
        $sheet->setCellValue('C10', "Inspect lock keys.\nDo not back off the nut.");
        $sheet->setCellValue('M9', 'Tech. Stamp');
        $sheet->setCellValue('M12', '2nd Tech Stamp');
        $sheet->setCellValue('A15', 'Reference:');
        $sheet->setCellValue('F15', 'CMM page 5001');
        for ($i = 1; $i <= 8; $i++) {
            $sheet->setCellValue('A'.(45+$i), '#'.$i.' Stage '.$i);
        }
        return [$book, $sheet];
    }

    private function content(): array
    {
        [$book, $sheet] = $this->source();
        $content = (new InProcessCheckSheetImporter())->extractSheet($sheet);
        $book->disconnectWorksheets();
        return $content + ['source_file'=>'reviewed.xlsx', 'source_sha256'=>str_repeat('a',64)];
    }

    public function test_parser_preserves_tasks_references_and_stages_without_sample_identity(): void
    {
        $data = $this->content();
        $this->assertSame([], $data['issues']);
        $this->assertSame('5', $data['rows'][0]['stage']);
        $this->assertSame("Inspect lock keys.\nDo not back off the nut.", $data['rows'][0]['task']);
        $this->assertSame('CMM page 5001', $data['rows'][0]['reference']);
        $this->assertStringNotContainsString('SAMPLE-PN', json_encode($data));
    }

    public function test_unmapped_cells_and_task_formulas_block_sql(): void
    {
        [$book, $sheet] = $this->source();
        $sheet->setCellValue('M10', 'SIGNED SAMPLE');
        $sheet->setCellValue('C10', '=Other!A1');
        $data = (new InProcessCheckSheetImporter())->extractSheet($sheet);
        $this->assertNotEmpty($data['issues']);
        $this->expectException(\InvalidArgumentException::class);
        (new InProcessCheckSheetSql())->build($data, 1, '32-11-01RM');
    }

    public function test_xls_and_xlsx_are_supported_and_missing_sheet_is_not_a_default(): void
    {
        foreach (['Xls'=>'xls', 'Xlsx'=>'xlsx'] as $writer=>$extension) {
            [$book, $sheet] = $this->source();
            $sheet->setTitle(' in-process_check_sheet ');
            $path = base_path('codex-test-runtime/check-sheet-fixture.'.$extension);
            $export = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, $writer);
            $export->setPreCalculateFormulas(false);
            $export->save($path);
            $data = (new InProcessCheckSheetImporter())->extract($path);
            $this->assertSame([], $data['issues']);
            $this->assertSame('CMM page 5001', $data['rows'][0]['reference']);
            $this->assertSame(hash_file('sha256',$path),$data['source_sha256']);
        }
        $sheet->setTitle('Unrelated');
        $export->save($path);
        $this->expectException(\RuntimeException::class);
        (new InProcessCheckSheetImporter())->extract($path);
    }

    public function test_authenticated_form_uses_only_primary_manual_and_current_wo_identity(): void
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder();
        $other = $this->createManual();
        $data = $this->content();
        $template = ManualInProcessCheckSheet::create([
            'manual_id'=>$other->id, 'source_file'=>'other.xlsx', 'source_sheet'=>'IN PROCESS CHECK SHEET',
            'source_sha256'=>str_repeat('a',64), 'content_sha256'=>str_repeat('b',64), 'content'=>$data,
        ]);
        $url = route('tdrs.inProcessCheckSheet', $wo);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($user)->get($url)->assertOk()->assertSee('has not been imported')->assertDontSee('Inspect lock keys');
        $template->update(['manual_id'=>$wo->unit->manual_id]);
        $this->get($url)->assertOk()->assertSee('Inspect lock keys')->assertSee('CMM page 5001')
            ->assertSee((string) $wo->number)->assertSee($wo->unit->part_number)->assertDontSee('SAMPLE-PN');
        $this->assertDatabaseCount('manual_in_process_check_sheets', 1);
        $this->get(route('tdrs.inProcessCheckSheet', 999999999))->assertNotFound();
    }

    public function test_sql_is_guarded_idempotent_and_select_verification_only(): void
    {
        $wo = $this->createWorkorder();
        $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $data = $this->content();
        $sql = (new InProcessCheckSheetSql())->build($data, $wo->unit->manual_id, $wo->unit->manual->number);
        // Use rollback-only DML within the test transaction; do not execute generated COMMIT.
        $executeDml = function ($text) use ($db) {
            preg_match('/SET @ipcs_ready = (.*?);\n/s', $text, $guard);
            $db->exec($guard[0]);
            preg_match('/INSERT INTO.*?;\n/s', $text, $insert);
            $db->exec($insert[0]);
        };
        $wrongManual = (new InProcessCheckSheetSql())->build($data, $wo->unit->manual_id, 'WRONG-MANUAL');
        $executeDml($wrongManual['import']);
        $this->assertDatabaseCount('manual_in_process_check_sheets',0);
        $executeDml($sql['import']);
        $first = ManualInProcessCheckSheet::where('manual_id',$wo->unit->manual_id)->firstOrFail();
        $executeDml($sql['import']);
        $this->assertSame($first->getAttributes(), $first->fresh()->getAttributes());
        $data['rows'][0]['task'] = 'Changed task';
        $changed = (new InProcessCheckSheetSql())->build($data,$wo->unit->manual_id,$wo->unit->manual->number);
        $executeDml($changed['import']);
        $this->assertSame($first->content, $first->fresh()->content);
        $approved = (new InProcessCheckSheetSql())->build($data,$wo->unit->manual_id,$wo->unit->manual->number,$sql['content_sha256']);
        $executeDml($approved['import']);
        $this->assertSame('Changed task', $first->fresh()->content['rows'][0]['task']);
        // Native JSON formatting must not make SELECT verification falsely fail.
        preg_match('/SELECT IF\(.*? AS check_sheet_verification;/s', $approved['verify'], $verification);
        $this->assertSame('PASS', $db->query($verification[0])->fetchColumn());
        $first->refresh()->update(['content'=>array_replace_recursive($data,['rows'=>[['stage'=>'7']]])]);
        $this->assertSame('FAIL', $db->query($verification[0])->fetchColumn());
        $executeDml($approved['import']);
        $this->assertSame('7', $first->fresh()->content['rows'][0]['stage']);
        $this->assertStringNotContainsString('UPDATE ', $sql['verify']);
        $this->assertStringNotContainsString('INSERT ', $sql['verify']);
    }

    public function test_original_form_layout_preserves_order_and_paginates_five_slots_per_sheet(): void
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder();
        $data = $this->content();
        $row = $data['rows'][0];
        $data['rows'] = [];
        for ($i = 1; $i <= 6; $i++) {
            $data['rows'][] = array_replace($row, ['task'=>'Task '.$i.' must be checked.']);
        }
        ManualInProcessCheckSheet::create([
            'manual_id'=>$wo->unit->manual_id, 'source_file'=>'layout.xlsx', 'source_sheet'=>'IN PROCESS CHECK SHEET',
            'source_sha256'=>str_repeat('a',64), 'content_sha256'=>str_repeat('b',64), 'content'=>$data,
        ]);
        $response = $this->actingAs($user)->get(route('tdrs.inProcessCheckSheet',$wo));
        $response->assertOk()->assertSee('Times New Roman')->assertSee('Library Man#:')
            ->assertSee('Form # 004')->assertSee('Rev # 0, 15/Dec/2012')
            ->assertSee('1 of 2')->assertSee('2 of 2')->assertSee('<u>must</u>',false);
        $this->assertSame(2,substr_count($response->getContent(),'<main class="sheet">'));
        $this->assertSame(6,substr_count($response->getContent(),'<td class="task-cell"'));
        $response->assertSeeInOrder(['Task 1','Task 2','Task 3','Task 4','Task 5','Task 6']);
    }
}
