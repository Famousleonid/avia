<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Necessary, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManufacturePairEditTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    private function pair(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Sleeve',
            'part_number' => 'QA-MFG-SLEEVE', 'ipl_num' => '14-5', 'units_assy' => 2]);
        $shared = ['workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 2,
            'codes_id' => Code::firstOrCreate(['name' => 'Manufacture'])->id,
            'manufacture_pair_id' => (string) \Illuminate\Support\Str::uuid()];
        $order = Tdr::create($shared + ['tdr_type' => Tdr::TYPE_MANUFACTURE_ORDER,
            'order_component_id' => $part->id, 'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id,
            'use_process_forms' => false]);
        $repair = Tdr::create($shared + ['tdr_type' => Tdr::TYPE_MANUFACTURE_REPAIR,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Repair'])->id, 'use_process_forms' => true]);
        return [$order->fresh(), $repair->fresh(), $part];
    }

    public function test_updates_from_either_side_keep_pair_roles_and_processes(): void
    {
        [$order, $repair] = $this->pair();
        $process = TdrProcess::create(['tdrs_id' => $repair->id, 'description' => 'Preserve this route'])->fresh();
        foreach ([$order, $repair] as $index => $source) {
            $this->get(route('tdrs.editForm', $source->id))->assertOk()
                ->assertSee('Changes are saved to both Manufacture entries.');
            $values = ['qty' => $index + 1, 'description' => 'Updated '.$index,
                'serial_number' => 'SN-'.$index, 'assy_serial_number' => 'ASSY-'.$index];
            $this->putJson(route('tdrs.update', $source->id), $values)->assertOk()->assertJsonPath('success', true);
            foreach ([$order, $repair] as $row) {
                $this->assertDatabaseHas('tdrs', ['id' => $row->id] + $values);
                $this->assertSame($row->tdr_type, $row->fresh()->tdr_type);
                $this->assertSame($row->necessaries_id, $row->fresh()->necessaries_id);
                $this->assertSame($row->manufacture_pair_id, $row->fresh()->manufacture_pair_id);
                $this->assertSame($row->use_process_forms, $row->fresh()->use_process_forms);
            }
            $this->assertSame($process->getAttributes(), $process->fresh()->getAttributes());
        }
    }

    public function test_invalid_quantity_and_role_change_do_not_partially_update_pair(): void
    {
        [$order, $repair] = $this->pair();
        $this->putJson(route('tdrs.update', $order->id), ['qty' => 3, 'description' => 'Invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('qty');
        $this->putJson(route('tdrs.update', $repair->id), ['qty' => 1, 'codes_id' => Code::firstOrCreate(['name' => 'Missing'], ['code' => 'P'])->id])
            ->assertUnprocessable()->assertJsonValidationErrors('codes_id');
        foreach ([$order, $repair] as $row) {
            $this->assertSame($row->getAttributes(), $row->fresh()->getAttributes());
        }
        $repair->delete();
        $this->putJson(route('tdrs.update', $order->id), ['qty' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('tdr');
        $this->assertSame(2, (int) $order->fresh()->qty);
    }

    public function test_component_replacement_updates_both_and_order_reference_with_existing_permission(): void
    {
        [$order, $repair, $part] = $this->pair();
        $this->grantFeatureAccess(auth()->user(), 'tdrs.replace_component');
        $replacement = $part->replicate();
        $replacement->part_number = 'QA-MFG-REPLACEMENT';
        $replacement->ipl_num = '14-6';
        $replacement->save();
        $this->putJson(route('tdrs.update', $repair->id), ['component_id' => $replacement->id])->assertOk();
        $this->assertEquals($replacement->id, $order->fresh()->component_id);
        $this->assertEquals($replacement->id, $order->fresh()->order_component_id);
        $this->assertEquals($replacement->id, $repair->fresh()->component_id);
        $this->assertNull($repair->fresh()->order_component_id);
    }
}
