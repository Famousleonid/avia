<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class VendorTrackingTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_admin_can_assign_vendor_to_tdr_process_and_view_tracking(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'QA Vendor ' . uniqid()]);
        $processName = ProcessName::query()->create([
            'name' => 'QA Outside Process ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'QA-PN-' . uniqid(),
            'name' => 'QA Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'QA-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => 'RO-123',
            'date_start' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->patch(route('tdrprocesses.updateRepairOrder', $tdrProcess), [
                'vendor_id' => $vendor->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'vendor_id' => $vendor->id]);

        $this->assertDatabaseHas('tdr_processes', [
            'id' => $tdrProcess->id,
            'vendor_id' => $vendor->id,
            'repair_order' => 'RO-123',
        ]);

        $this->actingAs($admin)
            ->get(route('vendor-tracking.index'))
            ->assertOk()
            ->assertSee('Vendor Tracking')
            ->assertSee($vendor->name)
            ->assertSee('RO-123');

        $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSee('Vendor');
    }
}
