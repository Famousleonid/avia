<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Tdr;
use App\Models\WorkorderUnitInspection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class BackfillWorkorderUnitInspectionsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_backfill_workorder_unit_inspections_copies_legacy_tdr_rows(): void
    {
        $workorder = $this->createWorkorder();
        $condition = Condition::query()->create([
            'name' => 'QA Unit Inspection ' . uniqid(),
            'unit' => true,
        ]);

        $source = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'description' => 'bearing worn note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'codes_id' => null,
            'necessaries_id' => null,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $this->artisan('unit-inspections:backfill-workorder-inspections', [
            '--workorder' => $workorder->id,
        ])
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();

        $this->assertDatabaseMissing('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
        ]);

        $this->artisan('unit-inspections:backfill-workorder-inspections', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'source_tdr_id' => $source->id,
            'notes' => 'bearing worn note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
    }

    public function test_backfill_workorder_unit_inspections_skips_duplicate_condition_sources(): void
    {
        $workorder = $this->createWorkorder();
        $condition = Condition::query()->create([
            'name' => 'QA Duplicate Inspection ' . uniqid(),
            'unit' => true,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'description' => 'first note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'description' => 'second note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $this->artisan('unit-inspections:backfill-workorder-inspections', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])
            ->expectsOutputToContain('duplicate source TDR')
            ->assertSuccessful();

        $this->assertSame(1, WorkorderUnitInspection::query()
            ->where('workorder_id', $workorder->id)
            ->where('condition_id', $condition->id)
            ->count());
        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => 'first note',
        ]);
    }

    public function test_delete_legacy_unit_inspection_tdrs_soft_deletes_after_new_row_exists(): void
    {
        $workorder = $this->createWorkorder();
        $condition = Condition::query()->create([
            'name' => 'QA Delete Legacy Inspection ' . uniqid(),
            'unit' => true,
        ]);

        $source = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_UNIT_INSPECTION,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => $condition->id,
            'description' => 'legacy note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $this->artisan('unit-inspections:delete-legacy-tdrs', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])
            ->expectsOutputToContain('skipped: no workorder_unit_inspections row')
            ->assertSuccessful();

        $this->assertDatabaseHas('tdrs', [
            'id' => $source->id,
            'deleted_at' => null,
        ]);

        $this->artisan('unit-inspections:backfill-workorder-inspections', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])->assertSuccessful();

        $this->artisan('unit-inspections:delete-legacy-tdrs', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])->assertSuccessful();

        $this->assertSoftDeleted('tdrs', ['id' => $source->id]);
        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'source_tdr_id' => $source->id,
            'notes' => 'legacy note',
        ]);
    }
}
