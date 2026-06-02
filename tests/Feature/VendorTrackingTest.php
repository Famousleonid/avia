<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\UserUiSetting;
use App\Models\Vendor;
use App\Models\WoBushing;
use App\Models\WoBushingBatch;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use App\Models\WorkorderStdProcess;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class VendorTrackingTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_vendor_tracking_uses_saved_user_filters_when_query_is_empty(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $firstVendor = Vendor::query()->create(['name' => 'Saved Filter Vendor ' . uniqid()]);
        $secondVendor = Vendor::query()->create(['name' => 'Hidden Vendor ' . uniqid()]);
        $firstWorkorder = $this->createWorkorder(['user_id' => $admin->id]);
        $secondWorkorder = $this->createWorkorder(['user_id' => $admin->id]);

        $this->createVendorTrackingTdrProcess($firstWorkorder, $firstVendor, 'SAVED-RO-1');
        $this->createVendorTrackingTdrProcess($secondWorkorder, $secondVendor, 'HIDDEN-RO-2');

        UserUiSetting::query()->create([
            'user_id' => $admin->id,
            'scope' => 'vendor-tracking.index',
            'key' => 'filters',
            'value' => [
                'vendor_id' => (string) $firstVendor->id,
                'customer_id' => '0',
                'status' => 'all',
                'sources' => ['part', 'std', 'bushing'],
                'include_vendor_null' => false,
                'workorder' => '',
                'part_number' => '',
                'repair_order' => '',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index'));

        $response->assertOk();
        $response->assertSee($firstVendor->name);
        $response->assertSee('SAVED-RO-1');
        $response->assertDontSee('HIDDEN-RO-2');
    }

    public function test_vendor_tracking_accepts_legacy_saved_filter_key(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $firstVendor = Vendor::query()->create(['name' => 'Legacy Filter Vendor ' . uniqid()]);
        $secondVendor = Vendor::query()->create(['name' => 'Legacy Hidden Vendor ' . uniqid()]);
        $firstWorkorder = $this->createWorkorder(['user_id' => $admin->id]);
        $secondWorkorder = $this->createWorkorder(['user_id' => $admin->id]);

        $this->createVendorTrackingTdrProcess($firstWorkorder, $firstVendor, 'LEGACY-RO-1');
        $this->createVendorTrackingTdrProcess($secondWorkorder, $secondVendor, 'LEGACY-HIDDEN-RO-2');

        UserUiSetting::query()->create([
            'user_id' => $admin->id,
            'scope' => 'vendor-tracking.index',
            'key' => 'vendorTrackingFilters',
            'value' => [
                'vendor_id' => (string) $firstVendor->id,
                'customer_id' => '0',
                'status' => 'all',
                'sources' => ['part', 'std', 'bushing'],
                'include_vendor_null' => false,
                'workorder' => '',
                'part_number' => '',
                'repair_order' => '',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index'));

        $response->assertOk();
        $response->assertSee($firstVendor->name);
        $response->assertSee('LEGACY-RO-1');
        $response->assertDontSee('LEGACY-HIDDEN-RO-2');
    }

    public function test_quantum_modal_shows_latest_received_rows_with_all_apply_statuses(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $suffix = (string) random_int(100000, 999999);

        QuantumRoLine::query()->create([
            'source_uid' => 'latest-unresolved-' . $suffix,
            'ro_number' => 'R-OLD-' . $suffix,
            'wo_number' => 'W107616',
            'bom_ref' => 'BAD',
            'apply_status' => 'unresolved',
            'apply_message' => 'No target process',
            'qty_repair' => '1.0000',
            'qty_reserved' => '1.0000',
            'qty_repaired' => '0.0000',
            'source_hash' => str_repeat('a', 64),
            'last_seen_at' => Carbon::parse('2026-05-30 09:00:00'),
        ]);
        QuantumRoLine::query()->create([
            'source_uid' => 'latest-pending-' . $suffix,
            'ro_number' => 'R-PENDING-' . $suffix,
            'wo_number' => 'W107617',
            'bom_ref' => null,
            'apply_status' => null,
            'source_hash' => str_repeat('b', 64),
            'last_seen_at' => Carbon::parse('2026-05-31 09:00:00'),
        ]);
        QuantumRoLine::query()->create([
            'source_uid' => 'latest-applied-' . $suffix,
            'ro_number' => 'R-NEW-' . $suffix,
            'wo_number' => 'W107618',
            'bom_ref' => 'CP',
            'apply_status' => 'applied',
            'apply_message' => 'Applied to target',
            'applied_target_table' => 'tdr_processes',
            'applied_target_id' => 123,
            'source_hash' => str_repeat('c', 64),
            'last_seen_at' => Carbon::parse('2026-06-01 09:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index'));

        $response
            ->assertOk()
            ->assertSee('Quantum RO Buffer')
            ->assertSee('Latest received:')
            ->assertSee('Latest received from Quantum')
            ->assertSee('All statuses')
            ->assertSee('unresolved')
            ->assertSee('pending')
            ->assertSee('id="quantumBufferSplitter"', false)
            ->assertSee('quantum-buffer-splitter-lines', false)
            ->assertSee('quantumRoBufferSplitRatio', false)
            ->assertSee('window.UserUiSettings.set(settingsScope, quantumSplitRatioKey', false)
            ->assertSee('applied')
            ->assertSee('pending')
            ->assertSee('unresolved')
            ->assertSee('<td class="text-nowrap">', false)
            ->assertSee('qty - 1 / 1 / 0', false)
            ->assertDontSee('To Repair:')
            ->assertDontSee('Reserved:')
            ->assertDontSee('Repaired:')
            ->assertDontSee('<th>Source</th>', false)
            ->assertDontSee('1.0000')
            ->assertSee('tdr_processes #123')
            ->assertDontSee('Fix incorrect Ref values')
            ->assertDontSee('1. Ref code')
            ->assertSeeInOrder([
                'Unresolved / needs attention',
                'R-OLD-' . $suffix,
                'Latest received from Quantum',
                'R-NEW-' . $suffix,
                'R-PENDING-' . $suffix,
                'R-OLD-' . $suffix,
            ]);
    }

    public function test_quantum_recent_rows_endpoint_paginates_latest_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $suffix = (string) random_int(100000, 999999);
        $baseSeenAt = Carbon::parse('2030-01-01 00:00:00');

        for ($i = 0; $i < 201; $i++) {
            QuantumRoLine::query()->create([
                'source_uid' => sprintf('recent-page-%s-%03d', $suffix, $i),
                'ro_number' => sprintf('R-INF-%03d-%s', $i, $suffix),
                'wo_number' => 'W107616',
                'bom_ref' => $i % 2 === 0 ? 'CP' : null,
                'apply_status' => $i % 2 === 0 ? 'applied' : null,
                'source_hash' => hash('sha256', 'recent-page-' . $suffix . '-' . $i),
                'last_seen_at' => $baseSeenAt->copy()->addMinutes($i),
            ]);
        }

        $response = $this->actingAs($admin)->getJson(route('vendor-tracking.quantum-lines.recent', [
            'page' => 2,
        ]));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertStringContainsString('R-INF-000-' . $suffix, (string) $response->json('html'));
        $this->assertStringNotContainsString('R-INF-200-' . $suffix, (string) $response->json('html'));
    }

    public function test_quantum_not_applicable_rows_do_not_show_in_unparsed_section(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $suffix = (string) random_int(100000, 999999);

        QuantumRoLine::query()->create([
            'source_uid' => 'na-workorder-' . $suffix,
            'ro_number' => 'R-NA-' . $suffix,
            'wo_number' => 'W999999',
            'bom_ref' => 'CP',
            'apply_status' => 'N/A',
            'apply_message' => 'Workorder not found: W999999',
            'source_hash' => str_repeat('d', 64),
            'applied_source_hash' => str_repeat('d', 64),
            'last_seen_at' => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        QuantumRoLine::query()->create([
            'source_uid' => 'old-workorder-' . $suffix,
            'ro_number' => 'R-OLD-WO-' . $suffix,
            'wo_number' => 'W106999',
            'bom_ref' => 'CP',
            'apply_status' => 'unresolved',
            'apply_message' => 'Workorder not found: W106999',
            'source_hash' => str_repeat('f', 64),
            'applied_source_hash' => str_repeat('f', 64),
            'last_seen_at' => Carbon::parse('2026-06-01 11:00:00'),
        ]);

        QuantumRoLine::query()->create([
            'source_uid' => 'unresolved-target-' . $suffix,
            'ro_number' => 'R-UNRES-' . $suffix,
            'wo_number' => 'W107616',
            'bom_ref' => 'BAD',
            'apply_status' => 'unresolved',
            'apply_message' => 'No target process',
            'source_hash' => str_repeat('e', 64),
            'last_seen_at' => Carbon::parse('2026-06-01 09:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index'));

        $response->assertOk();
        $html = (string) $response->getContent();
        $unparsedStart = strpos($html, 'Unresolved / needs attention');
        $latestStart = strpos($html, 'Latest received from Quantum');

        $this->assertNotFalse($unparsedStart);
        $this->assertNotFalse($latestStart);

        $unparsedHtml = substr($html, $unparsedStart, $latestStart - $unparsedStart);
        $latestHtml = substr($html, $latestStart);

        $this->assertStringContainsString('R-UNRES-' . $suffix, $unparsedHtml);
        $this->assertStringNotContainsString('R-NA-' . $suffix, $unparsedHtml);
        $this->assertStringNotContainsString('R-OLD-WO-' . $suffix, $unparsedHtml);
        $this->assertStringContainsString('R-NA-' . $suffix, $latestHtml);
        $this->assertStringContainsString('R-OLD-WO-' . $suffix, $latestHtml);
        $this->assertStringContainsString('WO not found', $latestHtml);
        $this->assertStringContainsString('WO not found: old', $latestHtml);
    }

    private function createVendorTrackingTdrProcess($workorder, Vendor $vendor, string $repairOrder): TdrProcess
    {
        $processName = ProcessName::query()->create([
            'name' => 'Vendor Tracking Process ' . uniqid(),
            'process_sheet_name' => 'VT',
            'form_number' => 'VT',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'VT-PN-' . uniqid(),
            'name' => 'Vendor Tracking Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'VT-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        return TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => $repairOrder,
            'vendor_id' => $vendor->id,
            'date_start' => now()->toDateString(),
        ]);
    }

    public function test_vendor_tracking_can_sort_by_last_changed_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $vendor = Vendor::query()->create(['name' => 'Changed Sort Vendor ' . uniqid()]);

        $older = $this->createVendorTrackingTdrProcess(
            $this->createWorkorder(['user_id' => $admin->id]),
            $vendor,
            'OLDER-CHANGE-RO'
        );
        $newer = $this->createVendorTrackingTdrProcess(
            $this->createWorkorder(['user_id' => $admin->id]),
            $vendor,
            'NEWER-CHANGE-RO'
        );

        $older->timestamps = false;
        $older->forceFill(['updated_at' => Carbon::parse('2026-05-01 09:00:00')])->saveQuietly();
        $newer->timestamps = false;
        $newer->forceFill(['updated_at' => Carbon::parse('2026-06-01 11:30:00')])->saveQuietly();

        $this->actingAs($admin)
            ->get(route('vendor-tracking.index', [
                'sort' => 'changed_at',
                'direction' => 'desc',
                'sort_user' => 1,
            ]))
            ->assertOk()
            ->assertSee('Changed')
            ->assertSeeInOrder([
                'NEWER-CHANGE-RO',
                'OLDER-CHANGE-RO',
            ]);
    }

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
            'date_start' => '2026-05-25',
            'date_finish' => '2026-05-26',
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

        $response = $this->actingAs($admin)->get(route('mains.show', $workorder));

        $response
            ->assertOk()
            ->assertSee('RO')
            ->assertSee('RO-123')
            ->assertSee('25/may/2026')
            ->assertSee('26/may/2026')
            ->assertDontSee('Sent (edit)')
            ->assertDontSee('Returned (edit)')
            ->assertDontSee(route('tdrprocesses.updateDate', $tdrProcess), false);
    }

    public function test_vendor_tracking_shows_ro_and_sent_returned_dates_readonly(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Readonly Vendor ' . uniqid()]);
        $processName = ProcessName::query()->create([
            'name' => 'Readonly Vendor Process ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'VT-RO-READONLY-' . uniqid(),
            'name' => 'Readonly Component',
            'ipl_num' => '2-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'VT-RO-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'vendor_id' => $vendor->id,
            'repair_order' => 'RO-READ',
            'date_start' => '2026-05-25',
            'date_finish' => '2026-05-26',
            'date_promise' => '2026-05-30',
        ]);

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index', [
            'repair_order' => 'RO-READ',
        ]));

        $response
            ->assertOk()
            ->assertSee('RO-READ')
            ->assertSee('25/may/2026')
            ->assertSee('26/may/2026')
            ->assertSee('data-repair-order="RO-READ"', false)
            ->assertSee('js-vendor-tracking-ro-filter', false)
            ->assertSee('name="date_promise"', false)
            ->assertSee(route('tdrprocesses.updateDate', $tdrProcess), false)
            ->assertDontSee('Sent (edit)')
            ->assertDontSee('Returned (edit)')
            ->assertDontSee('js-vendor-tracking-repair-order', false)
            ->assertDontSee('name="date_start"', false)
            ->assertDontSee('name="date_finish"', false);
    }

    public function test_main_process_windows_show_ro_and_readonly_quantum_dates(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        GeneralTask::query()->create([
            'name' => 'QA Main Task',
            'sort_order' => 1,
            'has_start_date' => true,
        ]);

        $stdProcessName = ProcessName::query()->create([
            'name' => 'STD NDT List',
            'process_sheet_name' => 'NDT',
            'form_number' => 'NDT',
        ]);
        $stdProcess = WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $stdProcessName->id,
            'repair_order' => 'STD-RO-777',
            'date_start' => '2026-05-10',
            'date_finish' => '2026-05-11',
        ]);

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'MAIN-PN-' . uniqid(),
            'name' => 'Main Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $partProcessName = ProcessName::query()->create([
            'name' => 'Main Part Process ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'MAIN-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $partProcessName->id,
            'repair_order' => 'PART-RO-888',
            'date_start' => '2026-05-12',
            'date_finish' => '2026-05-13',
        ]);

        $bushingProcessName = ProcessName::query()->create([
            'name' => 'Machining',
            'process_sheet_name' => 'MACH',
            'form_number' => 'MACH',
        ]);
        $bushingProcess = Process::query()->create([
            'process_names_id' => $bushingProcessName->id,
            'process' => 'Machining',
        ]);
        $woBushing = WoBushing::query()->create([
            'workorder_id' => $workorder->id,
        ]);
        $woBushingLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
            'qty_remaining' => 0,
            'group_key' => 'qa',
            'sort_order' => 1,
        ]);
        $looseBushingLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
            'qty_remaining' => 0,
            'group_key' => 'qa-loose',
            'sort_order' => 2,
        ]);
        $woBushingBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $bushingProcess->id,
            'process_column_key' => 'machining',
            'repair_order' => 'BUSH-BATCH-RO-999',
            'date_start' => '2026-05-14',
            'date_finish' => '2026-05-15',
        ]);
        $woBushingProcess = WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $woBushingLine->id,
            'process_id' => $bushingProcess->id,
            'batch_id' => $woBushingBatch->id,
            'qty' => 1,
        ]);
        $looseWoBushingProcess = WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $looseBushingLine->id,
            'process_id' => $bushingProcess->id,
            'qty' => 1,
            'repair_order' => 'BUSH-LOOSE-RO-999',
            'date_start' => '2026-05-16',
            'date_finish' => '2026-05-17',
        ]);

        $response = $this->actingAs($admin)->get(route('mains.show', $workorder));

        $response
            ->assertOk()
            ->assertSee('RO')
            ->assertSee('STD-RO-777')
            ->assertSee('PART-RO-888')
            ->assertSee('BUSH-BATCH-RO-999')
            ->assertDontSee('BUSH-LOOSE-RO-999')
            ->assertSee('10/may/2026')
            ->assertSee('11/may/2026')
            ->assertSee('12/may/2026')
            ->assertSee('13/may/2026')
            ->assertSee('14/may/2026')
            ->assertSee('15/may/2026')
            ->assertDontSee('16/may/2026')
            ->assertDontSee('17/may/2026')
            ->assertDontSee('Sent (edit)')
            ->assertDontSee('Returned (edit)')
            ->assertDontSee(route('workorder_std_processes.updateDate', $stdProcess), false)
            ->assertDontSee(route('tdrprocesses.updateDate', $tdrProcess), false)
            ->assertDontSee(route('wo_bushing_processes.updateDate', $woBushingProcess), false)
            ->assertDontSee(route('wo_bushing_processes.updateDate', $looseWoBushingProcess), false);
    }

    public function test_mains_show_renders_date_edit_forms_for_manual_date_editable_process_names(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'MAIN-EDIT-' . uniqid(),
            'name' => 'Editable Main Component',
            'ipl_num' => '7-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'MAIN-EDIT-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $machiningEcName = ProcessName::query()->firstOrCreate(
            ['name' => 'Machining (EC)'],
            [
                'process_sheet_name' => 'MACHINING',
                'form_number' => '018',
                'show_in_process_picker' => true,
            ]
        );
        $regularName = ProcessName::query()->create([
            'name' => 'Readonly Main Process ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $editableTdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $machiningEcName->id,
            'sort_order' => 1,
            'date_start' => '2026-05-25',
        ]);
        $readonlyTdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $regularName->id,
            'sort_order' => 2,
            'date_start' => '2026-05-26',
        ]);
        $stdPaintName = ProcessName::query()->firstOrCreate(
            ['name' => 'STD Paint List'],
            [
                'process_sheet_name' => 'STD',
                'form_number' => 'PAINT',
                'show_in_process_picker' => false,
            ]
        );
        $stdPaint = WorkorderStdProcess::query()->updateOrCreate(
            [
                'workorder_id' => $workorder->id,
                'process_name_id' => $stdPaintName->id,
            ],
            [
                'std_type' => 'paint',
                'date_start' => '2026-05-27',
            ]
        );

        $response = $this->actingAs($admin)->get(route('mains.show', $workorder));

        $response
            ->assertOk()
            ->assertSee(route('tdrprocesses.updateDate', $editableTdrProcess), false)
            ->assertSee(route('workorder_std_processes.updateDate', $stdPaint), false)
            ->assertDontSee(route('tdrprocesses.updateDate', $readonlyTdrProcess), false);
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
            ->assertJson(['ok' => true, 'repair_order' => 'TR-OLD']);

        $this->assertDatabaseHas('tdr_processes', [
            'id' => $first->id,
            'repair_order' => 'TR-OLD',
            'vendor_id' => $vendor->id,
        ]);
        $this->assertDatabaseHas('tdr_processes', [
            'id' => $second->id,
            'repair_order' => 'TR-OLD',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_vendor_tracking_total_count_matches_displayed_traveler_groups(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Count Vendor ' . uniqid()]);
        $firstProcessName = ProcessName::query()->create([
            'name' => 'Traveler Count A ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $secondProcessName = ProcessName::query()->create([
            'name' => 'Traveler Count B ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-COUNT-' . uniqid(),
            'name' => 'Traveler Count Component',
            'ipl_num' => '7-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-COUNT-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $totalBeforeTravelerRows = $this->vendorTrackingTotalRowsCount();

        foreach ([$firstProcessName, $secondProcessName] as $processName) {
            TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'repair_order' => 'TR-COUNT',
                'vendor_id' => $vendor->id,
                'date_start' => now()->toDateString(),
                'in_traveler' => true,
                'traveler_group' => 1,
            ]);
        }

        $this->assertSame($totalBeforeTravelerRows + 1, $this->vendorTrackingTotalRowsCount());

        $response = $this->actingAs($admin)->get(route('vendor-tracking.index', [
            'vendor_id' => $vendor->id,
            'include_vendor_null' => 0,
            'sources' => ['part'],
        ]));

        $response->assertOk();
        $response->assertSee('Selected: &nbsp; <span class="vendor-tracking-count-number">1</span>', false);
        $response->assertSee('Traveler (2)');
    }

    private function vendorTrackingTotalRowsCount(): int
    {
        $controller = app(\App\Http\Controllers\Admin\VendorTrackingController::class);
        $method = new \ReflectionMethod($controller, 'totalRowsCount');
        $method->setAccessible(true);

        return (int) $method->invoke($controller);
    }

    public function test_vendor_tracking_traveler_group_one_update_does_not_touch_null_groups_from_other_tdrs(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $vendor = Vendor::query()->create(['name' => 'Traveler Scoped Vendor ' . uniqid()]);
        $otherVendor = Vendor::query()->create(['name' => 'Traveler Other Vendor ' . uniqid()]);
        $processName = ProcessName::query()->create([
            'name' => 'Traveler Scoped Process ' . uniqid(),
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-SCOPED-' . uniqid(),
            'name' => 'Traveler Scoped Component',
            'ipl_num' => '2-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-SCOPED-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $otherTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-OTHER-SN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $target = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => 'TR-OLD',
            'in_traveler' => true,
            'traveler_group' => 1,
        ]);
        $other = TdrProcess::query()->create([
            'tdrs_id' => $otherTdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => 'TR-OTHER',
            'vendor_id' => $otherVendor->id,
            'in_traveler' => true,
            'traveler_group' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('vendor-tracking.row.update'), [
                'source_key' => 'tdr_traveler',
                'id' => $tdr->id,
                'traveler_group' => 1,
                'vendor_id' => $vendor->id,
                'repair_order' => 'TR-NEW',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'repair_order' => 'TR-OLD']);

        $this->assertDatabaseHas('tdr_processes', [
            'id' => $target->id,
            'repair_order' => 'TR-OLD',
            'vendor_id' => $vendor->id,
        ]);
        $this->assertDatabaseHas('tdr_processes', [
            'id' => $other->id,
            'repair_order' => 'TR-OTHER',
            'vendor_id' => $otherVendor->id,
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
