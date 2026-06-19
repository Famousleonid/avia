<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrProcessTravelerGroupFormTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_traveler_group_standard_form_prints_only_grouped_ndt_processes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $suffix = uniqid();

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'TR-GRP-PN-' . $suffix,
            'name' => 'Traveler Group Component',
            'ipl_num' => '11-240A',
            'eff_code' => 'ALL',
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-GRP-SN-' . $suffix,
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $ndt1 = ProcessName::query()->firstOrCreate(
            ['name' => 'NDT-1'],
            ['process_sheet_name' => 'NDT', 'form_number' => 'NDT', 'print_form' => true]
        );
        $ndt1->forceFill(['print_form' => true])->save();
        $ndt4 = ProcessName::query()->firstOrCreate(
            ['name' => 'NDT-4'],
            ['process_sheet_name' => 'NDT', 'form_number' => 'NDT', 'print_form' => true]
        );
        $ndt4->forceFill(['print_form' => true])->save();

        $groupedMagnetic = Process::query()->create([
            'process_names_id' => $ndt1->id,
            'process' => 'Traveler grouped magnetic ' . $suffix,
        ]);
        $groupedPenetrant = Process::query()->create([
            'process_names_id' => $ndt4->id,
            'process' => 'Traveler grouped penetrant ' . $suffix,
        ]);
        $outsidePenetrant = Process::query()->create([
            'process_names_id' => $ndt4->id,
            'process' => 'Traveler outside penetrant ' . $suffix,
        ]);

        foreach ([$groupedMagnetic, $groupedPenetrant, $outsidePenetrant] as $process) {
            ManualProcess::query()->create([
                'manual_id' => $manualId,
                'processes_id' => $process->id,
            ]);
        }

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ndt1->id,
            'processes' => [$groupedMagnetic->id],
            'in_traveler' => true,
            'traveler_group' => 1,
            'ignore_row' => false,
            'sort_order' => 1,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ndt4->id,
            'processes' => [$groupedPenetrant->id],
            'in_traveler' => true,
            'traveler_group' => 1,
            'ignore_row' => false,
            'sort_order' => 2,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ndt4->id,
            'processes' => [$outsidePenetrant->id],
            'in_traveler' => true,
            'traveler_group' => 2,
            'ignore_row' => false,
            'sort_order' => 3,
        ]);

        $vendor = Vendor::query()->create(['name' => 'Traveler Form Vendor ' . $suffix]);

        $this->actingAs($admin)
            ->get(route('tdr-processes.travelerGroupStandardForm', [
                'tdrId' => $tdr->id,
                'traveler_group' => 1,
                'vendor_id' => $vendor->id,
            ]))
            ->assertOk()
            ->assertSee('NDT PROCESS SHEET')
            ->assertSee($groupedMagnetic->process)
            ->assertSee($groupedPenetrant->process)
            ->assertSee($vendor->name)
            ->assertDontSee($outsidePenetrant->process);
    }
}
