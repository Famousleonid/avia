<?php

namespace Tests\Feature;

use App\Models\{Component, LogCard, Transfer, Workorder};
use App\Services\WorkorderPartsList;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class LogCardPartTransferTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $to = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $from = $this->createWorkorder(['unit_id' => $to->unit_id]);
        $part = Component::create(['manual_id' => $to->unit->manual_id, 'ipl_num' => '1-10', 'part_number' => 'TRANSFER-PN', 'name' => 'Pin', 'kit' => true, 'log_card' => true, 'units_assy' => 1]);
        $card = LogCard::create(['workorder_id' => $from->id, 'component_data' => json_encode([
            ['component_id' => (string) $part->id, 'included' => '1', 'ipl_group' => '1-10', 'serial_number' => 'SOURCE-SN', 'units_assy' => '1'],
        ])]);
        $targetCard = LogCard::create(['workorder_id' => $to->id, 'component_data' => '[]']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $row = app(WorkorderPartsList::class)->rows($to)->first();
        return compact('admin', 'to', 'from', 'part', 'card', 'targetCard', 'row');
    }

    private function choices(array $f)
    {
        return $this->getJson(route('workorders.log-card-transfer.sources', $f['to']).'?'.http_build_query([
            'row_key' => $f['row']->id, 'source_number' => 'w'.$f['from']->number,
        ]))->assertOk()->json('parts');
    }

    public function test_generated_kit_transfer_uses_log_card_sn_without_changing_log_cards_or_creating_tdrs(): void
    {
        $f = $this->fixture();
        $original = $f['card']->fresh()->component_data;
        $choice = $this->choices($f)[0];
        $this->assertSame('SOURCE-SN', $choice['serial_number']);
        $payload = ['row_key' => $f['row']->id, 'source_number' => (string) $f['from']->number, 'source_token' => $choice['token']];
        $url = route('workorders.log-card-transfer.store', $f['to']);
        $response = $this->postJson($url, $payload)->assertOk()->assertJsonPath('serial_number', 'SOURCE-SN');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('transfer_id', $response->json('transfer_id'));
        $transfer = Transfer::findOrFail($response->json('transfer_id'));
        $this->assertSame(1, Transfer::where('workorder_id', $f['to']->id)->count());
        $this->assertNull($transfer->tdr_id);
        $this->assertNull($transfer->cloned_tdr_id);
        $this->assertSame($original, $f['card']->fresh()->component_data);
        $this->assertSame('[]', $f['targetCard']->fresh()->component_data);
        $this->assertSame('Transfer from WO '.$f['from']->number, app(WorkorderPartsList::class)->rows($f['to'])->first()->po_num);
        $this->assertNull(app(WorkorderPartsList::class)->rows($f['to'])->first()->received);
        $this->get(route('transfers.transferForm', $transfer))->assertOk()->assertSee('SOURCE-SN')->assertSee('TRANSFER-PN');
        $this->assertSame(0, $this->choices($f)[0]['available']);
        $other = $this->createWorkorder(['unit_id' => $f['to']->unit_id, 'instruction_id' => $f['to']->instruction_id]);
        $this->postJson(route('workorders.log-card-transfer.store', $other), $payload)->assertStatus(422);
        $this->deleteJson(route('workorders.log-card-transfer.destroy', $f['to']), ['row_key' => $f['row']->id])->assertOk();
        $this->assertSame(1, $this->choices($f)[0]['available']);
        $this->assertNull(app(WorkorderPartsList::class)->rows($f['to'])->first()->po_num);
        $this->assertSame($original, $f['card']->fresh()->component_data);
    }

    public function test_source_requires_same_manual_real_log_card_part_and_current_snapshot(): void
    {
        $f = $this->fixture();
        $choice = $this->choices($f)[0];
        $url = route('workorders.log-card-transfer.sources', $f['to']);
        $other = $this->createWorkorder();
        $this->getJson($url.'?'.http_build_query(['row_key' => $f['row']->id, 'source_number' => $other->number]))->assertStatus(422);
        $this->getJson($url.'?'.http_build_query(['row_key' => $f['row']->id, 'source_number' => $f['to']->number]))->assertStatus(422);
        $f['card']->update(['component_data' => json_encode([['component_id' => $f['part']->id, 'serial_number' => 'CHANGED-SN']])]);
        $this->postJson(route('workorders.log-card-transfer.store', $f['to']), ['row_key' => $f['row']->id, 'source_number' => (string) $f['from']->number, 'source_token' => $choice['token']])->assertStatus(422);
        $this->assertDatabaseCount('transfers', 0);
        $f['card']->update(['component_data' => '[]']);
        $this->assertSame([], $this->choices($f));
    }

    public function test_completed_source_is_allowed_but_missing_or_insufficient_parts_are_not(): void
    {
        $f = $this->fixture();
        $f['from']->forceFill(['done_at' => '2026-09-01'])->save();
        $this->assertTrue($this->choices($f)[0]['can_transfer']);
        $f['part']->update(['units_assy' => 2]);
        $f['row'] = app(WorkorderPartsList::class)->rows($f['to'])->first();
        $choice = $this->choices($f)[0];
        $this->assertFalse($choice['can_transfer']);
        $this->postJson(route('workorders.log-card-transfer.store', $f['to']), ['row_key' => $f['row']->id, 'source_number' => (string) $f['from']->number, 'source_token' => $choice['token']])->assertStatus(422);
        $missing = \App\Models\Code::firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $f['card']->update(['component_data' => json_encode([['component_id' => $f['part']->id, 'reason' => $missing->id, 'serial_number' => 'MISSING-SN']])]);
        $this->assertSame([], $this->choices($f));
    }
}
