<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkorderStdProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class BackfillWorkorderStdProcessesTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_backfill_workorder_std_processes_copies_preferred_legacy_row(): void
    {
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'STD-BF-' . uniqid(),
            'name' => 'STD Backfill Component',
            'ipl_num' => '1-30',
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'STD Paint List',
            'process_sheet_name' => 'STD LIST',
            'form_number' => 'STD',
            'show_in_process_picker' => false,
        ]);
        $vendor = Vendor::query()->create(['name' => 'STD Vendor ' . uniqid()]);
        $dateUser = User::factory()->create([
            'name' => 'Date User',
            'email' => 'date.user.' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
        ]);
        $updateUser = User::factory()->create([
            'name' => 'Update User',
            'email' => 'update.user.' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $legacyTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'STD-SN',
            'qty' => 1,
        ]);
        $carrierTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'description' => 'STD List carrier',
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $legacyTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-04-01',
        ]);
        $preferred = TdrProcess::query()->create([
            'tdrs_id' => $carrierTdr->id,
            'process_names_id' => $processName->id,
            'repair_order' => 'RO-STD',
            'vendor_id' => $vendor->id,
            'date_start' => '2026-04-02',
            'date_start_user_id' => $dateUser->id,
            'date_finish' => '2026-04-05',
            'date_finish_user_id' => $dateUser->id,
            'date_promise' => '2026-04-08',
            'ignore_row' => true,
            'user_id' => $updateUser->id,
        ]);

        $this->artisan('std-list:backfill-workorder-processes', [
            '--workorder' => $workorder->id,
        ])
            ->expectsOutputToContain('Dry run only')
            ->assertSuccessful();

        $this->assertDatabaseMissing('workorder_std_processes', [
            'workorder_id' => $workorder->id,
            'process_name_id' => $processName->id,
        ]);

        $this->artisan('std-list:backfill-workorder-processes', [
            '--workorder' => $workorder->id,
            '--write' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('workorder_std_processes', [
            'workorder_id' => $workorder->id,
            'std_type' => 'paint',
            'process_name_id' => $processName->id,
            'source_tdr_id' => $carrierTdr->id,
            'source_tdr_process_id' => $preferred->id,
            'repair_order' => 'RO-STD',
            'vendor_id' => $vendor->id,
            'date_start' => '2026-04-02',
            'date_start_user_id' => $dateUser->id,
            'date_finish' => '2026-04-05',
            'date_finish_user_id' => $dateUser->id,
            'date_promise' => '2026-04-08',
            'ignore_row' => true,
            'user_id' => $updateUser->id,
        ]);
    }

    public function test_backfill_reports_orphan_std_rows_without_copying_them(): void
    {
        $processName = ProcessName::query()->create([
            'name' => 'STD NDT List',
            'process_sheet_name' => 'STD LIST',
            'form_number' => 'STD',
            'show_in_process_picker' => false,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => null,
            'process_names_id' => $processName->id,
            'date_start' => '2026-04-01',
        ]);

        $this->artisan('std-list:backfill-workorder-processes')
            ->expectsOutputToContain('1 STD List tdr_process rows have NULL tdrs_id')
            ->assertSuccessful();

        $this->assertSame(0, WorkorderStdProcess::query()->count());
    }
}
