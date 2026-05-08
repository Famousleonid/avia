<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\ProcessName;
use App\Models\Vendor;
use App\Models\WorkorderStdProcess;
use App\Models\WorkorderUnitInspection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderDetachedRowsActivityLogTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_new_workorder_unit_inspections_and_std_processes_are_logged(): void
    {
        $user = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder();

        $condition = Condition::query()->create([
            'name' => 'Logged Unit Inspection ' . uniqid(),
            'unit' => true,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'STD Logged List',
            'process_sheet_name' => 'STD LIST',
            'form_number' => 'STD',
            'show_in_process_picker' => false,
        ]);
        $vendor = Vendor::query()->create(['name' => 'Logged Vendor ' . uniqid()]);

        $this->actingAs($user);

        $inspection = WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => 'initial note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $inspection->update(['notes' => 'updated note']);

        $stdProcess = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $processName->id,
            'repair_order' => null,
            'vendor_id' => null,
        ]);
        $stdProcess->update([
            'repair_order' => 'RO-LOG',
            'vendor_id' => $vendor->id,
            'date_start' => '2026-05-08',
            'date_start_user_id' => $user->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'workorder_unit_inspection',
            'subject_type' => WorkorderUnitInspection::class,
            'subject_id' => $inspection->id,
            'event' => 'updated',
            'causer_id' => $user->id,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'workorder_std_process',
            'subject_type' => WorkorderStdProcess::class,
            'subject_id' => $stdProcess->id,
            'event' => 'updated',
            'causer_id' => $user->id,
        ]);

        $stdActivity = Activity::query()
            ->where('subject_type', WorkorderStdProcess::class)
            ->where('subject_id', $stdProcess->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame('RO-LOG', $stdActivity->properties->get('attributes')['repair_order'] ?? null);
        $this->assertSame($vendor->id, $stdActivity->properties->get('attributes')['vendor_id'] ?? null);

        $this->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('workorder std process')
            ->assertSee('workorder unit inspection')
            ->assertSee('RO-LOG');
    }
}
