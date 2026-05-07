<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Necessary;
use App\Models\Tdr;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_admin_can_create_tdr_record_with_valid_payload(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'CMP-100',
            'name' => 'QA Component',
            'ipl_num' => '1-10',
        ]);
        $code = Code::query()->firstOrCreate(
            ['name' => 'Repairable'],
            ['code' => 'R']
        );
        $necessary = Necessary::query()->firstOrCreate([
            'name' => 'Repair',
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-TDR',
            'assy_serial_number' => 'ASSY-TDR',
            'codes_id' => $code->id,
            'conditions_id' => null,
            'necessaries_id' => $necessary->id,
            'qty' => 2,
            'description' => 'Created TDR',
            'order_component_id' => null,
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $code->id,
            'necessaries_id' => $necessary->id,
            'serial_number' => 'SN-TDR',
            'qty' => 2,
        ]);
    }

    public function test_update_part_field_updates_po_num(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $tdr = Tdr::query()->create([
            'workorder_id' => $this->createWorkorder(['user_id' => $admin->id])->id,
            'serial_number' => 'SN-1',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.updatePartField', $tdr->id), [
            'field' => 'po_num',
            'value' => 'PO-999',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $tdr->refresh();
        $this->assertSame('PO-999', $tdr->po_num);
    }

    public function test_store_unit_inspections_does_not_delete_blank_workorder_level_tdr(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $blankTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => null,
            'codes_id' => null,
            'necessaries_id' => null,
            'description' => null,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $condition = Condition::query()->create([
            'name' => 'BUSHINGS WORN BEYOND LIMITS AS INDICATED ON PARTS LIST',
            'unit' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $workorder->id,
            'conditions' => [
                $condition->id => [
                    'selected' => '1',
                    'notes' => '',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('tdrs', [
            'id' => $blankTdr->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'use_tdr' => true,
            'use_process_forms' => false,
            'deleted_at' => null,
        ]);
    }
}
