<?php

namespace Tests\Feature;

use App\Models\{Component, Necessary, Tdr};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class MainPartsListTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_repair_parts_counter_uses_prl_rows_in_print_order(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $necessary = Necessary::firstOrCreate(['name' => 'Order New']);
        $ids = [];
        foreach ([['1-2', false, 12, null], ['9-10', true, 5, '2026-09-23'], ['9-2', true, 2, null]] as [$ipl, $kit, $qty, $received]) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Test part',
                'part_number' => 'PART-'.$ipl, 'ipl_num' => $ipl, 'kit' => $kit]);
            $ids[] = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'order_component_id' => $part->id,
                'necessaries_id' => $necessary->id, 'qty' => $qty, 'received' => $received])->id;
        }
        $response = $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->get(route('mains.show', $wo))->assertOk();
        $this->assertSame(3, $response->viewData('orderedQty'));
        $this->assertSame(0, $response->viewData('receivedQty'));
        Tdr::whereKey($ids[1])->update(['po_num' => '123']);
        $url = route('workorders.part-receipt.update', $wo);
        $payload = ['row_key' => 'tdr:'.$ids[1], 'field' => 'received_qty'];
        $this->postJson($url, $payload + ['value' => '6'])->assertStatus(422);
        $this->postJson($url, $payload + ['value' => '-1'])->assertStatus(422);
        $this->postJson($url, $payload + ['value' => '1.5'])->assertStatus(422);
        $this->postJson($url, $payload + ['value' => '2'])->assertOk();
        $this->assertSame(0, $this->get(route('mains.show', $wo))->assertOk()->viewData('receivedQty'));
        $this->postJson($url, $payload + ['value' => '5'])->assertOk();
        $this->assertSame(1, $this->get(route('mains.show', $wo))->assertOk()->viewData('receivedQty'));
        Tdr::whereKey($ids[1])->update(['received' => null]);
        $this->assertSame(0, $this->get(route('mains.show', $wo))->assertOk()->viewData('receivedQty'));
        $this->assertSame(['tdr:'.$ids[0], 'tdr:'.$ids[2], 'tdr:'.$ids[1]], $response->viewData('prl_parts')->pluck('id')->all());
        $response->assertSee('Part Replacement List')->assertSee('main-parts-summary-paint');
    }

    public function test_main_uses_kit_and_prl_generators_and_generated_receipts_survive_reload(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'KIT seal',
            'part_number' => 'KIT-TEST', 'ipl_num' => '1-10', 'kit' => true, 'units_assy' => 2]);
        $bush = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Bushing',
            'part_number' => 'BUSH-TEST', 'ipl_num' => '1-20', 'bush_ipl_num' => '1-20', 'is_bush' => true, 'units_assy' => 2]);
        $header = \App\Models\WoBushing::create(['workorder_id' => $wo->id]);
        \App\Models\WoBushingLine::create(['wo_bushing_id' => $header->id, 'workorder_id' => $wo->id,
            'component_id' => $bush->id, 'qty' => 2, 'qty_remaining' => 2, 'do_not_order' => false]);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $service = app(\App\Services\WorkorderPartsList::class);
        $generator = app(\App\Http\Controllers\Admin\TdrPrintFormController::class);
        $rows = $service->rows($wo);
        $this->assertCount(2, $rows);
        $this->assertSame($generator->kitRows($wo)->concat($generator->prlRows($wo))->map(fn ($r) => $service->key($r))->all(), $rows->pluck('id')->all());
        $this->assertTrue($rows->first()->component->kit);
        foreach ($rows as $row) {
            $this->postJson(route('workorders.part-receipt.update', $wo), ['row_key' => $row->id, 'field' => 'po_num', 'value' => 'PO-123'])->assertOk();
            $this->postJson(route('workorders.part-receipt.update', $wo), ['row_key' => $row->id, 'field' => 'received', 'value' => '2026-09-23'])->assertOk();
            $this->postJson(route('workorders.part-receipt.update', $wo), ['row_key' => $row->id, 'field' => 'received_qty', 'value' => (string) $row->qty])->assertOk();
        }
        $this->assertSame(0, Tdr::where('workorder_id', $wo->id)->count());
        $this->assertSame(['PO-123', 'PO-123'], $service->rows($wo)->pluck('po_num')->all());
        $this->assertSame(['2026-09-23', '2026-09-23'], $service->rows($wo)->pluck('received')->all());
        $this->assertSame([2, 2], $service->rows($wo)->pluck('received_qty')->map(fn ($q) => (int) $q)->all());
        $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertSee('PO-123');
        $this->get(route('tdrs.prlForm', $wo))->assertOk()->assertSee('PO-123');
        $this->get(route('mains.show', $wo))->assertOk()->assertSee('PO-123')->assertSee('2026-09-23', false);
    }

    public function test_receipt_rejects_removed_changed_and_foreign_rows_and_keeps_other_field(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Seal',
            'part_number' => 'KIT-STABLE', 'ipl_num' => '1-10', 'kit' => true, 'units_assy' => 2]);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $service = app(\App\Services\WorkorderPartsList::class);
        $key = $service->rows($wo)->first()->id;
        $url = route('workorders.part-receipt.update', $wo);
        $this->postJson($url, ['row_key' => $key, 'field' => 'po_num', 'value' => '123'])->assertOk();
        $this->postJson($url, ['row_key' => $key, 'field' => 'received', 'value' => '2026-09-23'])->assertOk();
        $this->postJson($url, ['row_key' => $key, 'field' => 'received', 'value' => ''])->assertOk();
        $this->assertSame('123', $service->rows($wo)->first()->po_num);
        $this->assertNull($service->rows($wo)->first()->received);
        $this->postJson($url, ['row_key' => 'tdr:999999', 'field' => 'po_num', 'value' => 'x'])->assertStatus(422);
        $this->postJson($url, ['row_key' => $key, 'field' => 'received', 'value' => 'invalid'])->assertStatus(422);
        $part->update(['units_assy' => 3]);
        $this->postJson($url, ['row_key' => $key, 'field' => 'po_num', 'value' => 'x'])->assertStatus(422);
        $this->assertEmpty($service->rows($wo)->first()->po_num);
        $part->update(['kit' => false]);
        $this->assertCount(0, $service->rows($wo));
    }

    public function test_legacy_receipt_is_preserved_for_kit_and_missing_tdr_is_included(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Seal',
            'part_number' => 'OLD-KIT', 'ipl_num' => '1-10', 'kit' => true, 'units_assy' => 2]);
        $old = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'order_component_id' => $part->id,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id, 'qty' => 2, 'po_num' => 'OLD-123', 'received' => '2026-09-16']);
        $missingPart = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Pin', 'part_number' => 'MISSING', 'ipl_num' => '1-30']);
        $code = \App\Models\Code::firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $missing = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $missingPart->id, 'codes_id' => $code->id, 'qty' => 1]);
        $service = app(\App\Services\WorkorderPartsList::class);
        $rows = $service->rows($wo);
        $this->assertCount(2, $rows);
        $this->assertSame('OLD-123', $rows->first()->po_num);
        $this->assertSame($old->id, $rows->first()->tdr_id);
        $this->assertSame('tdr:'.$missing->id, $rows->last()->id);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertSee('OLD-123');
        $this->postJson(route('workorders.part-receipt.update', $wo), ['row_key' => $rows->first()->id, 'field' => 'po_num', 'value' => 'NEW-123'])->assertOk();
        $this->assertSame('NEW-123', $old->fresh()->po_num);
    }

    public function test_excluded_kit_row_is_visible_but_cannot_be_received(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Seal', 'part_number' => 'EXCLUDED', 'ipl_num' => '1-10', 'kit' => true, 'units_assy' => 1]);
        \App\Models\WorkorderKitPrlCrossout::create(['workorder_id' => $wo->id, 'component_id' => $part->id]);
        $row = app(\App\Services\WorkorderPartsList::class)->rows($wo)->first();
        $this->assertTrue($row->crossed_out);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $this->get(route('mains.show', $wo))->assertOk()->assertSee('Excluded');
        $this->postJson(route('workorders.part-receipt.update', $wo), ['row_key' => $row->id, 'field' => 'received', 'value' => '2026-09-23'])->assertStatus(422);
        $this->assertDatabaseCount('workorder_part_receipts', 0);
    }
}
