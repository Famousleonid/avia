<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\NdtCadCsv;
use App\Models\StdProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrStdFormsFromComponentFlagsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_workorder_std_snapshot_is_built_from_manual_component_flags(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'A',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => 1,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PN-NDT',
            'name' => 'NDT Part',
            'units_assy' => 2,
            'eff_code' => 'A',
            'ndt_list' => true,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-20',
            'part_number' => 'PN-CAD',
            'name' => 'CAD Part',
            'units_assy' => 3,
            'cad_list' => true,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-30',
            'part_number' => 'PN-STRESS',
            'name' => 'Stress Part',
            'units_assy' => 1,
            'stress_relief_list' => true,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-40',
            'part_number' => 'PN-PAINT',
            'name' => 'Paint Part',
            'units_assy' => 4,
            'paint_list' => true,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-50',
            'part_number' => 'PN-OTHER-EFF',
            'name' => 'Other Eff Part',
            'units_assy' => 5,
            'eff_code' => 'B',
            'ndt_list' => true,
        ]);

        $ndt = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
        $cad = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);
        $stress = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_STRESS);
        $paint = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);

        $this->assertSame(['PN-NDT'], array_column($ndt, 'part_number'));
        $this->assertSame(['PN-CAD'], array_column($cad, 'part_number'));
        $this->assertSame(['PN-STRESS'], array_column($stress, 'part_number'));
        $this->assertSame(['PN-PAINT'], array_column($paint, 'part_number'));
        $this->assertSame(4, $paint[0]['qty']);
    }

    public function test_empty_workorder_std_bucket_is_filled_from_component_flags(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-10',
            'part_number' => 'PN-FLAG',
            'name' => 'Flagged Part',
            'units_assy' => 2,
            'cad_list' => true,
        ]);

        $ndtCadCsv = NdtCadCsv::query()->create([
            'workorder_id' => $workorder->id,
            'ndt_components' => [],
            'cad_components' => [],
            'stress_components' => [],
            'paint_components' => [],
        ]);

        $ndtCadCsv = NdtCadCsv::ensureTypeLoadedForWorkorder($workorder, $ndtCadCsv, StdProcess::STD_CAD);

        $this->assertSame('PN-FLAG', $ndtCadCsv->cad_components[0]['part_number'] ?? null);
    }

    public function test_tdr_show_always_renders_all_four_std_paper_buttons(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $this->createInstruction()->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="tdr-std-paper-group"', $html);
        $this->assertStringContainsString('NDT STD', $html);
        $this->assertStringContainsString('CAD STD', $html);
        $this->assertStringContainsString('Stress STD', $html);
        $this->assertStringContainsString('Paint STD', $html);
        $this->assertStringNotContainsString('tdr-std-paper-ndt-wrap d-inline-block d-none', $html);
        $this->assertStringNotContainsString('tdr-std-paper-cad-wrap d-inline-block d-none', $html);
        $this->assertStringNotContainsString('tdr-std-paper-stress-wrap d-inline-block d-none', $html);
        $this->assertStringNotContainsString('tdr-std-paper-paint-wrap d-inline-block d-none', $html);
    }

    public function test_ndt_std_form_opens_from_component_flag_snapshot(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-NDT-ROUTE',
            'name' => 'NDT Route Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('PN-NDT-ROUTE');
    }
}
