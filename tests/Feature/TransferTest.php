<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Tdr;
use App\Models\Transfer;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /** Origin component TDR in the receiving WO. */
    private function makeComponentTdr(Workorder $receiving, array $attributes = []): Tdr
    {
        $component = Component::query()->create([
            'manual_id' => $receiving->unit->manual_id,
            'part_number' => 'PN-' . uniqid(),
            'name' => 'Transfer Component',
            'ipl_num' => '1-' . random_int(10, 99),
        ]);

        return Tdr::query()->create(array_merge([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $receiving->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-' . uniqid(),
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ], $attributes));
    }

    public function test_create_stores_explicit_links_and_marks_clone_as_transfer_clone(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving, ['po_num' => 'SHOULD-NOT-CLONE']);

        $response = $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => (string) $source->number,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $transfer = Transfer::query()->where('tdr_id', $tdr->id)->firstOrFail();
        $this->assertSame($receiving->id, $transfer->workorder_id);
        $this->assertSame($source->id, $transfer->workorder_source);
        $this->assertSame($tdr->component_id, $transfer->component_id);
        $this->assertNotNull($transfer->cloned_tdr_id);

        $clone = Tdr::query()->findOrFail($transfer->cloned_tdr_id);
        $this->assertSame(Tdr::TYPE_TRANSFER_CLONE, $clone->tdr_type);
        $this->assertSame($source->id, $clone->workorder_id);
        // Parasitic fields are reset on the clone.
        $this->assertNull($clone->po_num);
    }

    public function test_create_is_idempotent_for_the_same_tdr(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving);

        $payload = [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => (string) $source->number,
        ];

        $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), $payload)->assertOk();
        $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), $payload)->assertOk();

        $this->assertSame(1, Transfer::query()->where('tdr_id', $tdr->id)->count());
        $this->assertSame(1, Tdr::query()->where('workorder_id', $source->id)
            ->where('tdr_type', Tdr::TYPE_TRANSFER_CLONE)->count());
    }

    public function test_create_rejects_self_transfer(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving);

        $response = $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => (string) $receiving->number,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertSame(0, Transfer::query()->where('tdr_id', $tdr->id)->count());
    }

    public function test_create_returns_422_for_unknown_source_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving);

        $response = $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => '999999999',
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Transfer::query()->where('tdr_id', $tdr->id)->count());
    }

    public function test_create_accepts_legacy_target_workorder_number_param(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving);

        $response = $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'target_workorder_number' => (string) $source->number, // old name
        ]);

        $response->assertOk();
        $this->assertSame($source->id, Transfer::query()->where('tdr_id', $tdr->id)->value('workorder_source'));
    }

    public function test_delete_removes_transfer_and_clone_via_explicit_link(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = $this->makeComponentTdr($receiving);

        $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => (string) $source->number,
        ])->assertOk();

        $transfer = Transfer::query()->where('tdr_id', $tdr->id)->firstOrFail();
        $cloneId = $transfer->cloned_tdr_id;

        $response = $this->actingAs($admin)->deleteJson(route('transfers.deleteByTdr', $tdr->id));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('transfers', ['id' => $transfer->id]);
        $this->assertDatabaseMissing('tdrs', ['id' => $cloneId]);
    }

    public function test_missing_code_sets_and_clears_source_part_missing_state(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id, 'part_missing' => false]);

        $missingCode = Code::query()->firstOrCreate(['name' => Code::NAME_MISSING], ['code' => 'M']);
        $missingCondition = Condition::query()->firstOrCreate(
            ['name' => Condition::NAME_PARTS_MISSING],
            ['unit' => 1]
        );
        $tdr = $this->makeComponentTdr($receiving, ['codes_id' => $missingCode->id]);

        $this->actingAs($admin)->postJson(route('transfers.create', $tdr->id), [
            'workorder_number' => (string) $receiving->number,
            'source_workorder_number' => (string) $source->number,
        ])->assertOk();

        $this->assertTrue((bool) $source->fresh()->part_missing);
        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $source->id,
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'conditions_id' => $missingCondition->id,
            'component_id' => null,
        ]);

        $this->actingAs($admin)->deleteJson(route('transfers.deleteByTdr', $tdr->id))->assertOk();

        $this->assertFalse((bool) $source->fresh()->part_missing);
        $this->assertDatabaseMissing('tdrs', [
            'workorder_id' => $source->id,
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'conditions_id' => $missingCondition->id,
        ]);
    }

    public function test_update_sn_persists_component_sn(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $component = $this->createComponent($this->createManual());
        $transfer = Transfer::query()->create([
            'workorder_id' => $receiving->id,
            'workorder_source' => $source->id,
            'component_id' => $component->id,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('transfers.updateSn', $transfer->id), [
            'component_sn' => 'SN-NEW-123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('component_sn', 'SN-NEW-123');
        $this->assertSame('SN-NEW-123', $transfer->fresh()->component_sn);
    }

    public function test_update_unit_on_po_persists_value(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);
        $component = $this->createComponent($this->createManual());
        $transfer = Transfer::query()->create([
            'workorder_id' => $receiving->id,
            'workorder_source' => $source->id,
            'component_id' => $component->id,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('transfers.updateUnitOnPo', $transfer->id), [
            'unit_on_po' => 'PO-7788',
        ]);

        $response->assertOk();
        $response->assertJsonPath('unit_on_po', 'PO-7788');
        $this->assertSame('PO-7788', $transfer->fresh()->unit_on_po);
    }

    public function test_transfers_form_paginates_into_sheets_of_five(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $receiving = $this->createWorkorder(['user_id' => $admin->id]);
        $source = $this->createWorkorder(['user_id' => $admin->id]);

        for ($i = 1; $i <= 6; $i++) {
            $component = Component::query()->create([
                'manual_id' => $receiving->unit->manual_id,
                'part_number' => 'TPN-' . $i,
                'name' => 'Transfer Part ' . $i,
                'ipl_num' => '3-' . $i,
            ]);
            $tdr = $this->makeComponentTdr($receiving);
            Transfer::query()->create([
                'tdr_id' => $tdr->id,
                'workorder_id' => $receiving->id,
                'workorder_source' => $source->id,
                'component_id' => $component->id,
                'component_sn' => 'SN-' . $i,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('transfers.transfersForm', $source->id));

        $response->assertOk();
        $response->assertSee('Sheet 1 of 2');
        $response->assertSee('Sheet 2 of 2');
        $response->assertSee('TPN-1');
        $response->assertSee('TPN-6');
    }
}
