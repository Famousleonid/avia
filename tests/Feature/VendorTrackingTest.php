<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Process;
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

    public function test_vendor_tracking_groups_tdr_traveler_processes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Vendor ' . uniqid()]);
        $firstProcessName = ProcessName::query()->create([
            'name' => 'Traveler Process A ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $secondProcessName = ProcessName::query()->create([
            'name' => 'Traveler Process B ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-PN-' . uniqid(),
            'name' => 'Traveler Component',
            'ipl_num' => '2-1',
            'eff_code' => 'ALL',
        ]);
        $firstCatalogProcess = Process::query()->create([
            'process_names_id' => $firstProcessName->id,
            'process' => 'Traveler Catalog A ' . uniqid(),
        ]);
        $secondCatalogProcess = Process::query()->create([
            'process_names_id' => $secondProcessName->id,
            'process' => 'Traveler Catalog B ' . uniqid(),
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstProcessName->id,
            'repair_order' => 'TR-OLD',
            'vendor_id' => $vendor->id,
            'date_start' => now()->toDateString(),
            'in_traveler' => true,
            'processes' => [$firstCatalogProcess->id],
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondProcessName->id,
            'repair_order' => 'TR-OLD',
            'vendor_id' => $vendor->id,
            'date_start' => null,
            'in_traveler' => true,
            'processes' => [$secondCatalogProcess->id],
        ]);
        $outsideTravelerProcessName = ProcessName::query()->create([
            'name' => 'Traveler Same Detail Outside ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $outsideTravelerProcessName->id,
            'repair_order' => 'TR-OUTSIDE',
            'vendor_id' => $vendor->id,
            'date_start' => now()->toDateString(),
            'in_traveler' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('vendor-tracking.index'))
            ->assertOk()
            ->assertSee('Traveler (2)')
            ->assertSee($firstProcessName->name)
            ->assertSee($secondProcessName->name)
            ->assertSee($component->part_number)
            ->assertSee('TR-OUTSIDE')
            ->assertSee($outsideTravelerProcessName->name);

        $this->actingAs($admin)
            ->patch(route('vendor-tracking.row.update'), [
                'source_key' => 'tdr_traveler',
                'id' => $tdr->id,
                'vendor_id' => $vendor->id,
                'repair_order' => 'TR-NEW',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'repair_order' => 'TR-NEW']);

        $this->assertDatabaseHas('tdr_processes', [
            'id' => $first->id,
            'repair_order' => 'TR-NEW',
        ]);
        $this->assertDatabaseHas('tdr_processes', [
            'id' => $second->id,
            'repair_order' => 'TR-NEW',
        ]);
    }

    public function test_mains_show_interleaves_traveler_groups_by_parent_process_order(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Order Vendor ' . uniqid()]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-ORDER-' . uniqid(),
            'name' => 'Traveler Order Component',
            'ipl_num' => '2-2',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-ORDER-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $names = collect([
            'QA First Single',
            'QA Second Single',
            'QA Traveler One A',
            'QA Traveler One B',
            'QA Traveler Two A',
            'QA Traveler Two B',
            'QA Last Single',
        ])->mapWithKeys(function (string $name) {
            $processName = ProcessName::query()->create([
                'name' => $name . ' ' . uniqid(),
                'process_sheet_name' => 'QA',
                'form_number' => 'QA',
            ]);

            return [$name => $processName];
        });

        $makeProcess = function (string $name, int $sortOrder, bool $inTraveler = false, ?int $travelerGroup = null) use ($tdr, $names, $vendor): void {
            TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $names[$name]->id,
                'sort_order' => $sortOrder,
                'in_traveler' => $inTraveler,
                'traveler_group' => $travelerGroup,
                'vendor_id' => $vendor->id,
                'date_start' => '2026-04-10',
            ]);
        };

        $makeProcess('QA First Single', 1);
        $makeProcess('QA Second Single', 2);
        $makeProcess('QA Traveler One A', 3, true, 1);
        $makeProcess('QA Traveler One B', 4, true, 1);
        $makeProcess('QA Traveler Two A', 5, true, 2);
        $makeProcess('QA Traveler Two B', 6, true, 2);
        $makeProcess('QA Last Single', 7);

        $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSeeInOrder([
                $names['QA First Single']->name,
                $names['QA Second Single']->name,
                'Traveler 1',
                'Traveler 2',
                $names['QA Last Single']->name,
            ]);

        $this->actingAs($admin)
            ->get(route('vendor-tracking.index', [
                'workorder' => $workorder->number,
                'part_number' => $component->part_number,
                'sources' => ['part'],
                'sort' => 'wo',
                'direction' => 'desc',
                'sort_user' => 1,
            ]))
            ->assertOk()
            ->assertSeeInOrder([
                $names['QA First Single']->name,
                $names['QA Second Single']->name,
                'Traveler (2)',
                'Traveler 2 (2)',
                $names['QA Last Single']->name,
            ]);

        $this->actingAs($admin)
            ->get(route('vendor-tracking.index', [
                'workorder' => $workorder->number,
                'part_number' => $component->part_number,
                'sources' => ['part'],
                'sort' => 'process',
                'direction' => 'asc',
                'sort_user' => 1,
            ]))
            ->assertOk()
            ->assertSeeInOrder([
                $names['QA First Single']->name,
                $names['QA Last Single']->name,
                $names['QA Second Single']->name,
                'Traveler (2)',
                'Traveler 2 (2)',
            ]);
    }

    public function test_traveler_group_requires_confirmation_and_clears_conflicting_dates_and_ro(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Cleanup Vendor ' . uniqid()]);
        $otherVendor = Vendor::query()->create(['name' => 'Traveler Cleanup Other Vendor ' . uniqid()]);
        $firstProcessName = ProcessName::query()->create([
            'name' => 'Traveler Cleanup A ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $secondProcessName = ProcessName::query()->create([
            'name' => 'Traveler Cleanup B ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-CLEAN-' . uniqid(),
            'name' => 'Traveler Cleanup Component',
            'ipl_num' => '3-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-CLEAN-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $firstProcessName->id,
            'repair_order' => 'RO-A',
            'vendor_id' => $vendor->id,
            'date_start' => '2026-04-01',
            'date_finish' => null,
            'date_promise' => '2026-04-05',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $secondProcessName->id,
            'repair_order' => 'RO-B',
            'vendor_id' => $otherVendor->id,
            'date_start' => '2026-04-02',
            'date_finish' => null,
            'date_promise' => '2026-04-06',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tdr-processes.traveler-group', ['tdrId' => $tdr->id]), [
                'process_ids' => [$first->id, $second->id],
            ])
            ->assertStatus(409)
            ->assertJson(['success' => false, 'requires_confirmation' => true]);

        $this->assertDatabaseHas('tdr_processes', [
            'id' => $first->id,
            'in_traveler' => false,
            'repair_order' => 'RO-A',
            'date_start' => '2026-04-01',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tdr-processes.traveler-group', ['tdrId' => $tdr->id]), [
                'process_ids' => [$first->id, $second->id],
                'clear_conflicting_values' => true,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ([$first, $second] as $process) {
            $this->assertDatabaseHas('tdr_processes', [
                'id' => $process->id,
                'in_traveler' => true,
                'vendor_id' => null,
                'repair_order' => null,
                'date_start' => null,
                'date_finish' => null,
                'date_promise' => null,
            ]);
        }
    }

    public function test_traveler_ungroup_clears_group_sent_dates(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $processName = ProcessName::query()->create([
            'name' => 'Traveler Ungroup A ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $otherProcessName = ProcessName::query()->create([
            'name' => 'Traveler Ungroup B ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-UNGROUP-' . uniqid(),
            'name' => 'Traveler Ungroup Component',
            'ipl_num' => '5-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-UNGROUP-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'in_traveler' => true,
            'traveler_group' => 2,
            'date_start' => '2026-04-10',
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $otherProcessName->id,
            'in_traveler' => true,
            'traveler_group' => 2,
            'date_start' => '2026-04-10',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tdr-processes.traveler-ungroup', ['tdrId' => $tdr->id]), [
                'traveler_group' => 2,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ([$first, $second] as $process) {
            $this->assertDatabaseHas('tdr_processes', [
                'id' => $process->id,
                'in_traveler' => false,
                'traveler_group' => null,
                'date_start' => null,
            ]);
        }
    }

    public function test_individual_traveler_process_updates_apply_to_whole_group(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Shared Vendor ' . uniqid()]);
        $processName = ProcessName::query()->create([
            'name' => 'Traveler Shared A ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $otherProcessName = ProcessName::query()->create([
            'name' => 'Traveler Shared B ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-SHARED-' . uniqid(),
            'name' => 'Traveler Shared Component',
            'ipl_num' => '4-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-SHARED-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $first = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'in_traveler' => true,
        ]);
        $second = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $otherProcessName->id,
            'in_traveler' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateRepairOrder', $first), [
                'repair_order' => 'RO-SHARED',
                'vendor_id' => $vendor->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'vendor_id' => $vendor->id]);

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $first), [
                'date_start' => '2026-04-10',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'date_start' => '2026-04-10']);

        $this->actingAs($admin)
            ->patchJson(route('tdrprocesses.updateDate', $second), [
                'date_finish' => '2026-04-12',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'date_finish' => '2026-04-12']);

        foreach ([$first, $second] as $process) {
            $this->assertDatabaseHas('tdr_processes', [
                'id' => $process->id,
                'repair_order' => 'RO-SHARED',
                'vendor_id' => $vendor->id,
                'date_start' => '2026-04-10',
                'date_finish' => '2026-04-12',
            ]);
        }
    }
}
