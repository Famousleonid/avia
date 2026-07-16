<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\ProjectSetting;
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

    public function test_part_traveler_form_prints_traveler_group_marker_before_qr(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true);

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $suffix = uniqid();

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'TR-FORM-PN-' . $suffix,
            'name' => 'Traveler Form Component',
            'ipl_num' => '11-240A',
            'eff_code' => 'ALL',
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-FORM-SN-' . $suffix,
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Machining'],
            ['process_sheet_name' => 'MACHINING', 'form_number' => 'MACH', 'print_form' => true]
        );

        $groupTwoProcess = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Traveler group two process ' . $suffix,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$groupTwoProcess->id],
            'in_traveler' => true,
            'traveler_group' => 2,
            'ignore_row' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdr-processes.travelForm', [
            'id' => $tdr->id,
            'traveler_group' => 2,
        ]));

        $response
            ->assertOk()
            ->assertSee($groupTwoProcess->process)
            ->assertSeeInOrder([$groupTwoProcess->process, 'Receiving inspection'])
            ->assertSee('data-screen-placement="viewport"', false)
            ->assertSee('system-print-qr__label">T2</span><span class="system-print-qr__code"', false)
            ->assertSee('332px 108px', false)
            ->assertSee('right: 2mm;', false)
            ->assertSee('top: 1mm;', false);

        $this->actingAs($admin)
            ->get(route('tdr-processes.processesBody', ['tdrId' => $tdr->id]))
            ->assertOk()
            ->assertSee('Form traveler')
            ->assertDontSee('Form standart');
    }

    public function test_part_traveler_places_receiving_inspection_immediately_before_ndt_6(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $suffix = uniqid();

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'TR-NDT6-PN-' . $suffix,
            'name' => 'Traveler NDT-6 Component',
            'ipl_num' => '11-240B',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'TR-NDT6-SN-' . $suffix,
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $preNdtName = ProcessName::query()->create([
            'name' => 'Traveler before NDT-6 ' . $suffix,
            'process_sheet_name' => 'TRAVELER',
            'form_number' => 'TRV',
            'print_form' => false,
            'show_in_process_picker' => true,
        ]);
        $ndt6Name = ProcessName::query()->firstOrCreate(
            ['name' => 'NDT-6 (Eddy Current)'],
            [
                'process_sheet_name' => 'NDT',
                'form_number' => 'NDT-6',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $beforeProcess = Process::query()->create([
            'process_names_id' => $preNdtName->id,
            'process' => 'Traveler process before receiving ' . $suffix,
        ]);
        $ndt6Process = Process::query()->create([
            'process_names_id' => $ndt6Name->id,
            'process' => 'Traveler NDT-6 instruction ' . $suffix,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $preNdtName->id,
            'processes' => [$beforeProcess->id],
            'in_traveler' => true,
            'traveler_group' => 1,
            'ignore_row' => false,
            'sort_order' => 1,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ndt6Name->id,
            'processes' => [$ndt6Process->id],
            'in_traveler' => true,
            'traveler_group' => 1,
            'ignore_row' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($admin)->get(route('tdr-processes.travelForm', [
            'id' => $tdr->id,
            'traveler_group' => 1,
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                $beforeProcess->process,
                'Receiving inspection',
                'NDT-6 (Eddy Current)',
                $ndt6Process->process,
            ]);

        $this->assertSame(1, substr_count((string) $response->getContent(), 'Receiving inspection'));
    }
}
