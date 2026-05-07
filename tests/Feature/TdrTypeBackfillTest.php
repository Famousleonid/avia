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

class TdrTypeBackfillTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_tdr_infers_core_row_types(): void
    {
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TYPE-CMP-' . uniqid(),
            'name' => 'Typed Component',
            'ipl_num' => '1-10',
        ]);
        $condition = Condition::query()->create([
            'name' => 'BUSHINGS WORN BEYOND LIMITS AS INDICATED ON PARTS LIST',
            'unit' => 1,
        ]);
        $manufactureCode = Code::query()->firstOrCreate(['name' => 'Manufacture'], ['code' => 'M']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $this->assertSame(Tdr::TYPE_STD_LIST_CARRIER, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'description' => 'STD List carrier',
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));

        $this->assertSame(Tdr::TYPE_UNIT_INSPECTION, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));

        $this->assertSame(Tdr::TYPE_MANUFACTURE_ORDER, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $manufactureCode->id,
            'necessaries_id' => $orderNew->id,
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));

        $this->assertSame(Tdr::TYPE_MANUFACTURE_REPAIR, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $manufactureCode->id,
            'necessaries_id' => $repair->id,
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));

        $this->assertSame(Tdr::TYPE_ORDER_NEW, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $orderNew->id,
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));

        $this->assertSame(Tdr::TYPE_COMPONENT_TDR, Tdr::query()->make([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
        ])->inferType((string) $manufactureCode->id, (string) $orderNew->id, (string) $repair->id));
    }

    public function test_backfill_tdr_types_is_dry_run_by_default_and_writes_with_flag(): void
    {
        $workorder = $this->createWorkorder();
        $condition = Condition::query()->create([
            'name' => 'UNIT INSPECTION TEST CONDITION',
            'unit' => 1,
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $this->artisan('tdrs:backfill-types')
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();

        $this->assertNull($tdr->fresh()->tdr_type);

        $this->artisan('tdrs:backfill-types --write')
            ->assertSuccessful();

        $this->assertSame(Tdr::TYPE_UNIT_INSPECTION, $tdr->fresh()->tdr_type);
    }
}
