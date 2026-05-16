<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\LogCard;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WorkorderUnitInspection;
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

    public function test_log_card_partial_has_include_checkbox_only_before_creation(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-100',
            'name' => 'Log Card Component',
            'ipl_num' => '1-10',
            'log_card' => true,
        ]);
        $componentTwo = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-101',
            'name' => 'Second Log Card Component',
            'ipl_num' => '1-10A',
            'log_card' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-200',
            'name' => 'Standalone Log Card Component',
            'ipl_num' => '2-10',
            'log_card' => true,
        ]);

        $draft = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $draft->assertOk();
        $draft->assertSee('lc-include-toggle-all', false);
        $draft->assertDontSee('type="radio"', false);
        $draft->assertSee('name="lc_include[1-10]"', false);
        $draft->assertSee('name="lc_include[2-10]"', false);
        $draft->assertSee('value="'.$component->id.'"', false);
        $draft->assertSee('value="'.$componentTwo->id.'"', false);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'component_id' => $component->id,
                    'included' => '1',
                    'serial_number' => 'SN-LC',
                ],
            ]),
        ]);

        $saved = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $saved->assertOk();
        $saved->assertDontSee('name="included"', false);
        $saved->assertDontSee('lc-include-toggle-all', false);
    }

    public function test_log_card_inline_include_checkbox_state_is_saved(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-200',
            'name' => 'Included Component',
            'ipl_num' => '1-20',
            'log_card' => true,
        ]);
        $logCard = LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'component_id' => $component->id,
                    'included' => '1',
                    'serial_number' => 'SN-200',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->patch(route('log_card.inline_field.update', $logCard), [
            'row' => 0,
            'field' => 'included',
            'value' => '0',
        ]);

        $response->assertOk();
        $rows = json_decode($logCard->fresh()->component_data, true);
        $this->assertSame('0', $rows[0]['included']);
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
        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => '',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $this->assertSame(0, Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->unitInspections()
            ->count());
    }

    public function test_legacy_unit_inspection_store_writes_to_workorder_unit_inspections(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $condition = Condition::query()->create([
            'name' => 'Legacy Single Unit Inspection ' . uniqid(),
            'unit' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => '',
            'order_component_id' => '',
            'serial_number' => ' ',
            'assy_serial_number' => ' ',
            'codes_id' => '',
            'conditions_id' => $condition->id,
            'necessaries_id' => ' ',
            'qty' => 1,
            'description' => 'legacy unit note',
            'use_tdr' => '1',
            'use_process_forms' => '0',
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => 'legacy unit note',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $this->assertSame(0, Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->unitInspections()
            ->count());
        $this->assertSame(1, WorkorderUnitInspection::query()
            ->where('workorder_id', $workorder->id)
            ->where('condition_id', $condition->id)
            ->count());
    }

    public function test_unit_inspection_form_excludes_existing_workorder_unit_inspections(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $existingCondition = Condition::query()->create([
            'name' => 'Already Selected Unit Condition ' . uniqid(),
            'unit' => 1,
        ]);
        $availableCondition = Condition::query()->create([
            'name' => 'Available Unit Condition ' . uniqid(),
            'unit' => 1,
        ]);

        WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'condition_id' => $existingCondition->id,
            'notes' => 'already there',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.inspection.unit', ['workorder_id' => $workorder->id]));

        $response->assertOk();
        $response->assertDontSee($existingCondition->name);
        $response->assertSee($availableCondition->name);
    }

    public function test_deleting_last_missing_part_does_not_delete_explicit_std_list_carrier(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'part_missing' => true]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'CMP-MISSING-' . uniqid(),
            'name' => 'Missing Component',
            'ipl_num' => '1-20',
        ]);
        $missingCode = Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $missingCondition = Condition::query()->firstOrCreate([
            'name' => 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST',
        ], [
            'unit' => 1,
        ]);

        $missingTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $missingCode->id,
            'conditions_id' => $missingCondition->id,
            'necessaries_id' => $orderNew->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $stdCarrier = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'codes_id' => null,
            'conditions_id' => $missingCondition->id,
            'necessaries_id' => null,
            'description' => 'STD List carrier',
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $stdProcess = TdrProcess::query()->create([
            'tdrs_id' => $stdCarrier->id,
            'repair_order' => 'RO-KEEP',
        ]);

        $response = $this->actingAs($admin)->delete(route('tdrs.destroy', $missingTdr->id));

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $this->assertSoftDeleted('tdrs', ['id' => $missingTdr->id]);
        $this->assertDatabaseHas('tdrs', [
            'id' => $stdCarrier->id,
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('tdr_processes', [
            'id' => $stdProcess->id,
            'tdrs_id' => $stdCarrier->id,
            'repair_order' => 'RO-KEEP',
        ]);
    }
}
