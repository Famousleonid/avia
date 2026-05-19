<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Code;
use App\Models\Necessary;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\WorkorderStdProcessItem;
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

    public function test_workorder_std_snapshot_prefers_std_row_eff_code_over_component_eff_code(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => '9A',
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $matchingComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-20',
            'part_number' => 'PN-STD-EFF',
            'name' => 'STD Eff Part',
            'units_assy' => 1,
            'eff_code' => '',
            'ndt_list' => true,
        ]);

        $otherComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-30',
            'part_number' => 'PN-OTHER-STD-EFF',
            'name' => 'Other STD Eff Part',
            'units_assy' => 1,
            'eff_code' => '',
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'component_id' => $matchingComponent->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'manual_id' => $manual->id,
            'process' => '1',
            'qty' => 1,
            'eff_code' => '9A',
        ]);
        StdProcess::query()->updateOrCreate([
            'component_id' => $otherComponent->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'manual_id' => $manual->id,
            'process' => '1',
            'qty' => 1,
            'eff_code' => '9',
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame(['PN-STD-EFF'], array_column($rows, 'part_number'));
    }

    public function test_workorder_std_snapshot_includes_components_from_other_manuals_grouped_by_source_manual(): void
    {
        $manual = $this->createManual(['number' => 'MAIN-CMM']);
        $sourceManual = $this->createManual(['number' => 'SOURCE-CMM']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $localComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-10',
            'part_number' => 'PN-LOCAL-NDT',
            'name' => 'Local NDT Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $sourceComponent = Component::query()->create([
            'manual_id' => $sourceManual->id,
            'ipl_num' => '2-10',
            'part_number' => 'PN-SOURCE-NDT',
            'name' => 'Source NDT Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $localComponent->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $sourceComponent->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame(['MAIN-CMM', 'SOURCE-CMM'], array_column($rows, 'manual'));
        $this->assertSame(['PN-LOCAL-NDT', 'PN-SOURCE-NDT'], array_column($rows, 'part_number'));
    }

    public function test_workorder_std_snapshot_excludes_missing_code_tdr_qty(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $missing = Code::query()->firstOrCreate(['name' => 'Missing']);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-50',
            'part_number' => 'PN-MISSING-CODE',
            'name' => 'Missing Code Part',
            'units_assy' => 2,
            'ndt_list' => true,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $missing->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame(['PN-MISSING-CODE'], array_column($rows, 'part_number'));
        $this->assertSame([1], array_column($rows, 'qty'));
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
        $this->assertStringNotContainsString('tab-std-processes', $html);
        $this->assertStringNotContainsString('content-std-processes', $html);
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

    public function test_ndt_std_form_footer_uses_first_ndt_process_bucket_for_totals(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-NDT-14',
            'name' => 'NDT MPI FPI Part',
            'units_assy' => 2,
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-20',
            'part_number' => 'PN-NDT-4',
            'name' => 'NDT FPI Part',
            'units_assy' => 3,
            'ndt_list' => true,
        ]);
        $third = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-30',
            'part_number' => 'PN-NDT-146',
            'name' => 'NDT MPI FPI EC Part',
            'units_assy' => 5,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1 / 4',
            'qty' => 2,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '4',
            'qty' => 3,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $third->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1 / 4 / 6',
            'qty' => 5,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('Total QTY:', false);
        $response->assertSee('<strong>10</strong>', false);
        $response->assertSee('MPI:', false);
        $response->assertSee('<strong>7</strong>', false);
        $response->assertSee('FPI:', false);
        $response->assertSee('<strong>3</strong>', false);
    }

    public function test_spec_process_form_uses_qty_for_mpi_and_fpi_totals_based_on_first_process_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-SP-14',
            'name' => 'SP MPI FPI Part',
            'units_assy' => 2,
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-20',
            'part_number' => 'PN-SP-4',
            'name' => 'SP FPI Part',
            'units_assy' => 3,
            'ndt_list' => true,
        ]);
        $third = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-30',
            'part_number' => 'PN-SP-146',
            'name' => 'SP MPI FPI EC Part',
            'units_assy' => 5,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1 / 4',
            'qty' => 2,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '4',
            'qty' => 3,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $third->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1 / 4 / 6',
            'qty' => 5,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'qty' => 1,
            'serial_number' => 'SN-SP-1',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $second->id,
            'qty' => 1,
            'serial_number' => 'SN-SP-2',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $third->id,
            'qty' => 1,
            'serial_number' => 'SN-SP-3',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessForm', $workorder->id));

        $response->assertOk();
        $response->assertSee('Cat #1', false);
        $response->assertSeeInOrder(['Cat #1', '7', 'RO', '3'], false);
    }

    public function test_cad_std_form_total_uses_qty_for_all_rows_without_dedup_by_ipl(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'MAIN-CAD']);
        $sourceManual = $this->createManual(['number' => 'SRC-CAD']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-10',
            'part_number' => 'PN-CAD-LOCAL',
            'name' => 'Local CAD Part',
            'units_assy' => 2,
            'cad_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $sourceManual->id,
            'ipl_num' => '8-10',
            'part_number' => 'PN-CAD-SOURCE',
            'name' => 'Source CAD Part',
            'units_assy' => 3,
            'cad_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_CAD,
        ], [
            'process' => '2',
            'qty' => 2,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_CAD,
        ], [
            'process' => '2',
            'qty' => 3,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.cadStd', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['Total:', '5'], false);
    }

    public function test_stress_std_form_total_uses_qty_for_all_rows_without_dedup_by_ipl(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'MAIN-STRESS']);
        $sourceManual = $this->createManual(['number' => 'SRC-STRESS']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9-10',
            'part_number' => 'PN-STRESS-LOCAL',
            'name' => 'Local Stress Part',
            'units_assy' => 2,
            'stress_relief_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $sourceManual->id,
            'ipl_num' => '9-10',
            'part_number' => 'PN-STRESS-SOURCE',
            'name' => 'Source Stress Part',
            'units_assy' => 3,
            'stress_relief_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_STRESS,
        ], [
            'process' => '3',
            'qty' => 2,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_STRESS,
        ], [
            'process' => '3',
            'qty' => 3,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.stressStd', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['Total:', '5'], false);
    }

    public function test_ndt_std_form_collapses_letter_suffix_ipl_variants_into_one_row(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-MERGE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => '2801-0301',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => '2801-0304',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('8-240A', false);
        $response->assertSee('8-240B', false);
        $response->assertDontSee('8-240A / 8-240B', false);
        $response->assertSee('2801-0301', false);
        $response->assertDontSee('2801-0301 / 2801-0304', false);
        $this->assertSame(1, substr_count($response->getContent(), 'UPPER TORQUE LINK'));
        $response->assertSee('<strong>2</strong>', false);
    }

    public function test_paint_std_form_collapses_letter_suffix_ipl_variants_into_one_row(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'PAINT-MERGE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => '2801-0301',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'paint_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => '2801-0304',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'paint_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_PAINT,
        ], [
            'process' => '25',
            'qty' => 1,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_PAINT,
        ], [
            'process' => '25',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.paintStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('8-240A', false);
        $response->assertSee('8-240B', false);
        $response->assertDontSee('8-240A / 8-240B', false);
        $response->assertSee('2801-0301', false);
        $response->assertDontSee('2801-0301 / 2801-0304', false);
        $this->assertSame(1, substr_count($response->getContent(), 'UPPER TORQUE LINK'));
    }

    public function test_std_forms_sort_ipl_with_section_suffix_naturally(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        foreach ([
            '9A-300' => 'PN-SORT-300',
            '9A-30' => 'PN-SORT-030',
            '9A-290' => 'PN-SORT-290',
        ] as $ipl => $partNumber) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => $ipl,
                'part_number' => $partNumber,
                'name' => 'STD Part ' . $ipl,
                'units_assy' => 1,
                'ndt_list' => true,
                'cad_list' => true,
                'stress_relief_list' => true,
                'paint_list' => true,
            ]);
        }

        foreach ([
            route('tdrs.ndtStd', $workorder->id),
            route('tdrs.cadStd', $workorder->id),
            route('tdrs.stressStd', $workorder->id),
            route('tdrs.paintStd', $workorder->id),
        ] as $route) {
            $html = $this->actingAs($admin)->get($route)->assertOk()->getContent();

            $this->assertLessThan(strpos($html, 'PN-SORT-290'), strpos($html, 'PN-SORT-030'));
            $this->assertLessThan(strpos($html, 'PN-SORT-300'), strpos($html, 'PN-SORT-290'));
        }
    }

    public function test_paint_std_form_uses_component_flags_not_csv_snapshot(): void
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
            'ipl_num' => '4-10',
            'part_number' => 'PN-PAINT-FLAG',
            'name' => 'Paint Flag Part',
            'units_assy' => 2,
            'paint_list' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.paintStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('PN-PAINT-FLAG');
        $response->assertSee('Paint Flag Part');
        $response->assertDontSee('PN-FROM-CSV');
        $response->assertDontSee('CSV Paint Part');
    }

    public function test_ndt_cad_and_stress_std_forms_use_reduced_rows_not_empty_legacy_csv(): void
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
            'ipl_num' => '6-10',
            'part_number' => 'PN-NDT-REDUCED',
            'name' => 'NDT Reduced Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-20',
            'part_number' => 'PN-CAD-REDUCED',
            'name' => 'CAD Reduced Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-30',
            'part_number' => 'PN-STRESS-REDUCED',
            'name' => 'Stress Reduced Part',
            'units_assy' => 1,
            'stress_relief_list' => true,
        ]);

        $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id))
            ->assertOk()
            ->assertSee('PN-NDT-REDUCED');
        $this->actingAs($admin)->get(route('tdrs.cadStd', $workorder->id))
            ->assertOk()
            ->assertSee('PN-CAD-REDUCED');
        $this->actingAs($admin)->get(route('tdrs.stressStd', $workorder->id))
            ->assertOk()
            ->assertSee('PN-STRESS-REDUCED');
    }

    public function test_paint_std_form_says_when_no_component_has_paint_flag(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.paintStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('No Paint components with paint_list flag');
        $response->assertDontSee('PN-FROM-CSV');

        $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id))
            ->assertOk()
            ->assertSee('No NDT components with ndt_list flag');
        $this->actingAs($admin)->get(route('tdrs.cadStd', $workorder->id))
            ->assertOk()
            ->assertSee('No CAD components with cad_list flag');
        $this->actingAs($admin)->get(route('tdrs.stressStd', $workorder->id))
            ->assertOk()
            ->assertSee('No Stress Relief components with stress_relief_list flag');
    }

    public function test_workorder_std_items_subtract_and_restore_tdr_order_new_qty(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '5-10',
            'part_number' => 'PN-RESTORE',
            'name' => 'Restore Qty Part',
            'units_assy' => 2,
            'paint_list' => true,
        ]);

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);
        $this->assertSame(2, $initial[0]['qty']);
        $this->assertDatabaseHas('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'std_type' => StdProcess::STD_PAINT,
            'remaining_qty' => 2,
        ]);

        $tdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_ORDER_NEW,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $afterOrderNew = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('component_id', $component->id)
            ->where('std_type', StdProcess::STD_PAINT)
            ->first();

        $this->assertNotNull($afterOrderNew);
        $this->assertSame(1, $afterOrderNew->remaining_qty);
        $this->assertSame(1, $afterOrderNew->excluded_qty);

        $tdr->delete();

        $afterDelete = WorkorderStdProcessItem::query()
            ->where('workorder_id', $workorder->id)
            ->where('component_id', $component->id)
            ->where('std_type', StdProcess::STD_PAINT)
            ->first();

        $this->assertNotNull($afterDelete);
        $this->assertSame(2, $afterDelete->remaining_qty);
        $this->assertSame(0, $afterDelete->excluded_qty);
    }

    public function test_part_changes_invalidate_snapshot_and_rebuild_with_existing_tdr_exclusions(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '5-15',
            'part_number' => 'PN-INVALIDATE',
            'name' => 'Before Change',
            'units_assy' => 2,
            'paint_list' => true,
        ]);

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);
        $this->assertSame('Before Change', $initial[0]['description']);
        $this->assertSame(2, $initial[0]['qty']);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_ORDER_NEW,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $component->update([
            'name' => 'After Change',
            'units_assy' => 3,
        ]);

        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
        ]);

        $rebuilt = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_PAINT);

        $this->assertSame('After Change', $rebuilt[0]['description']);
        $this->assertSame(2, $rebuilt[0]['qty']);
        $this->assertDatabaseHas('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'std_type' => StdProcess::STD_PAINT,
            'base_qty' => 3,
            'excluded_qty' => 1,
            'remaining_qty' => 2,
        ]);
    }

    public function test_workorder_std_items_store_only_remaining_rows(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '5-20',
            'part_number' => 'PN-FULLY-EXCLUDED',
            'name' => 'Fully Excluded Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'std_type' => StdProcess::STD_CAD,
        ]);
    }

    public function test_workorder_std_items_cap_std_qty_to_units_assy_before_exclusions(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '5-25',
            'part_number' => 'PN-CAP-QTY',
            'name' => 'Cap Qty Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $component->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1 / 4',
            'qty' => 2,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame([], array_values(array_filter($rows, fn (array $row): bool => ($row['ipl_num'] ?? '') === '5-25')));
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
    }
}
