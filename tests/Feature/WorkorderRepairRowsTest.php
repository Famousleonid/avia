<?php

namespace Tests\Feature;

use App\Models\{Necessary, RmReport, Tdr};
use App\Services\WorkorderRepairRows;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class WorkorderRepairRowsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_technical_notes_allow_256_characters_but_reject_257(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $payload = ['workorder_id' => $wo->id, 'selected_records' => '[]', 'notes' => [str_repeat('a', 256)]];
        $this->actingAs($admin)->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $payload['notes'][0] .= 'b';
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertUnprocessable()->assertJsonValidationErrors('notes.0');
        $this->assertSame(str_repeat('a', 256), json_decode($wo->fresh()->rm_report, true)['technical_notes'][0]);
    }

    public function test_custom_repair_row_and_inline_notes_persist_and_print_only_for_their_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $other = $this->createWorkorder();
        $row = ['key' => 'custom:'.\Illuminate\Support\Str::uuid(), 'ipl' => '1-65',
            'part_description' => 'Manual terminal QA', 'mod_repair' => 'Repair',
            'description' => 'Custom repair QA', 'ident_method' => 'Inkmark', 'ident_number' => 'TEST-123', 'deleted' => false];
        $payload = ['workorder_id' => $wo->id, 'selected_records' => '[]', 'editor_rows' => [$row],
            'notes' => ['Note    with    spaces', '']];
        $this->actingAs($admin)->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $saved = app(WorkorderRepairRows::class)->rows($wo->fresh(), collect());
        $this->assertCount(1, $saved);
        $this->assertSame($row['key'], $saved[0]['key']);
        $this->assertSame('Manual terminal QA', $saved[0]['part_description']);
        $this->assertSame('Note    with    spaces', json_decode($wo->fresh()->rm_report, true)['technical_notes'][0]);
        $this->assertCount(0, app(WorkorderRepairRows::class)->rows($other, collect()));
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()
            ->assertSee('Manual terminal QA')->assertSee('Custom repair QA')->assertSee('Inkmark TEST-123');
        $this->get(route('rm_reports.partial', $wo->id))->assertOk()->assertSee('rmAddRow')->assertDontSee('technicalNoteModal');
        $payload['editor_rows'][0]['ident_method'] = 'Custom marking TEST-123';
        $payload['editor_rows'][0]['ident_number'] = '';
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()
            ->assertSee('Custom marking TEST-123')->assertDontSee('TEST-123 TEST-123');
        $payload['editor_rows'][0]['deleted'] = true;
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()->assertDontSee('Manual terminal QA');
        $payload['editor_rows'][0]['key'] = 'custom:invalid';
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertUnprocessable();
    }

    public function test_repair_rows_are_scoped_editable_and_survive_reload_and_print(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $part = $this->createComponent($manual, ['name' => 'PISTON QA', 'ipl_num' => '3-425']);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $order = Necessary::firstOrCreate(['name' => 'Order New']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'necessaries_id' => $repair->id, 'qty' => 1, 'use_tdr' => true]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'necessaries_id' => $repair->id, 'qty' => 1, 'use_tdr' => false]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'necessaries_id' => $order->id, 'qty' => 1]);
        $other = $this->createWorkorder();
        $foreign = Tdr::create(['workorder_id' => $other->id, 'component_id' => $part->id, 'necessaries_id' => $repair->id, 'qty' => 1]);
        $template = RmReport::create(['manual_id' => $manual->id, 'part_description' => 'Template part', 'mod_repair' => 'Repair', 'description' => 'Original description']);
        $rows = app(WorkorderRepairRows::class)->rows($wo, collect([$template]));
        $this->assertCount(2, $rows);
        $payload = ['workorder_id' => $wo->id, 'selected_records' => json_encode([$template->id]), 'notes' => ['QA note'], 'editor_rows' => [
            ['key' => 'tdr:'.$tdr->id, 'mod_repair' => 'Modification', 'description' => 'Repair 4-4 QA', 'ident_method' => 'Vibropeen', 'ident_number' => '52103-1101', 'deleted' => '0'],
        ]];
        $this->actingAs($admin)->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $resolved = app(WorkorderRepairRows::class)->rows($wo->fresh(), collect([$template]));
        $this->assertFalse($resolved[0]['deleted']);
        $this->assertSame('52103-1101', $resolved[0]['ident_number']);
        $this->assertSame('Modification', $resolved[0]['mod_repair']);
        $this->assertSame('Original description', $template->fresh()->description);
        $this->get(route('rm_reports.partial', $wo->id))->assertOk()->assertSee('PISTON QA')->assertSee('editorRows', false);
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()->assertSee('(3-425) PISTON QA')->assertSee('Vibropeen 52103-1101')->assertSee('Repair 4-4 QA');
        $payload['editor_rows'][0]['key'] = 'tdr:'.$foreign->id;
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertUnprocessable();
        $payload['editor_rows'][0]['key'] = 'tdr:'.$tdr->id;
        $payload['editor_rows'][0]['deleted'] = '1';
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()->assertDontSee('PISTON QA');
        $this->assertDatabaseHas('tdrs', ['id' => $tdr->id]);
    }

    public function test_ipl_and_part_description_are_workorder_overrides_for_both_row_sources(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder();
        $part = $this->createComponent($wo->unit->manual, ['name' => 'Original part', 'ipl_num' => '13-70']);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
            'necessaries_id' => $repair->id, 'qty' => 1, 'use_tdr' => true]);
        $template = RmReport::create(['manual_id' => $part->manual_id, 'part_description' => 'Template part',
            'mod_repair' => 'Repair', 'description' => 'Unchanged repair']);
        $rows = app(WorkorderRepairRows::class)->rows($wo, collect([$template]))->all();
        $rows[0]['ipl'] = '13-70RS';
        $rows[0]['part_description'] = 'Repair Sleeve (EC)';
        $rows[1]['ipl'] = '14-210B';
        $rows[1]['part_description'] = 'Main Fitting RH';
        $payload = ['workorder_id' => $wo->id, 'selected_records' => json_encode([$template->id]), 'editor_rows' => $rows];
        $this->actingAs($admin)->putJson(route('rm_reports.update', $wo), $payload)->assertOk();
        $resolved = app(WorkorderRepairRows::class)->rows($wo->fresh(), collect([$template]));
        $this->assertSame('13-70RS', $resolved[0]['ipl']);
        $this->assertSame('Main Fitting RH', $resolved[1]['part_description']);
        $this->assertSame('Original part', $part->fresh()->name);
        $this->assertSame('13-70', $part->fresh()->ipl_num);
        $this->assertSame('Template part', $template->fresh()->part_description);
        $this->assertSame($part->id, $tdr->fresh()->component_id);
        $print = $this->get(route('rm_reports.rmRecordForm', $wo));
        $print->assertOk()->assertSee('(13-70RS) Repair Sleeve (EC)')->assertSee('(14-210B) Main Fitting RH');
        $payload['editor_rows'][0]['ipl'] = str_repeat('x', 101);
        $this->putJson(route('rm_reports.update', $wo), $payload)->assertUnprocessable();
        $this->assertSame('13-70RS', app(WorkorderRepairRows::class)->rows($wo->fresh(), collect([$template]))[0]['ipl']);
        $payload['editor_rows'][0]['ipl'] = '';
        $payload['editor_rows'][1]['part_description'] = '';
        $this->putJson(route('rm_reports.update', $wo), $payload)->assertOk();
        $cleared = app(WorkorderRepairRows::class)->rows($wo->fresh(), collect([$template]));
        $this->assertSame('', $cleared[0]['ipl']);
        $this->assertSame('', $cleared[1]['part_description']);
        $printRows = $this->get(route('rm_reports.rmRecordForm', $wo))->assertOk()->viewData('rmRecords');
        $this->assertSame('Repair Sleeve (EC)', $printRows->first()->part_description);
    }
    public function test_print_continues_notes_seven_per_sheet_without_repeating_them(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $wo = $this->createWorkorder();
        $records = collect(range(1, 46))->map(fn ($i) => (object) [
            'part_description' => 'Fixture part '.$i, 'mod_repair' => 'Repair',
            'description' => 'Repair fixture '.$i, 'ident_method' => 'Mark',
        ]);
        foreach ([0, 7, 8, 15, 29] as $count) {
            $notes = $count ? array_map(fn ($i) => 'Unique note '.$i, range(1, $count)) : [];
            $html = view('admin.rm_reports.rmRecordForm', ['current_wo' => $wo, 'rmRecords' => $records, 'technicalNotes' => $notes])->render();
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $pages = $xpath->query('//*[@data-rm-print-page]');
            $notesPages = max(1, (int) ceil($count / 7));
            $this->assertCount(max(4, $notesPages), $pages);
            $found = [];
            foreach ($pages as $index => $page) {
                $this->assertCount(1, $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " title ")]', $page));
                $this->assertCount(1, $xpath->query('.//footer', $page));
                $cells = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " rm-technical-note ")]', $page);
                $this->assertCount($index < $notesPages ? 7 : 0, $cells);
                foreach ($cells as $cell) if (trim($cell->textContent) !== '') $found[] = trim($cell->textContent);
            }
            $this->assertSame($notes, $found);
            $this->assertCount(46, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " div11 ") and contains(concat(" ", normalize-space(@class), " "), " data-row ")]'));
        }
    }

}
