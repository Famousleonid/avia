<?php

namespace Tests\Feature;

use App\Models\{Necessary, RmReport, Tdr};
use App\Services\WorkorderRepairRows;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class WorkorderRepairRowsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

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
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()->assertSee('3-425 PISTON QA')->assertSee('Vibropeen 52103-1101')->assertSee('Repair 4-4 QA');
        $payload['editor_rows'][0]['key'] = 'tdr:'.$foreign->id;
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertUnprocessable();
        $payload['editor_rows'][0]['key'] = 'tdr:'.$tdr->id;
        $payload['editor_rows'][0]['deleted'] = '1';
        $this->putJson(route('rm_reports.update', $wo->id), $payload)->assertOk();
        $this->get(route('rm_reports.rmRecordForm', $wo->id))->assertOk()->assertDontSee('PISTON QA');
        $this->assertDatabaseHas('tdrs', ['id' => $tdr->id]);
    }
}
