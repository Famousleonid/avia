<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Code;
use App\Models\Necessary;
use App\Models\ProjectSetting;
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

    public function test_workorder_std_snapshot_refreshes_std_row_eff_code_from_component_flags(): void
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
            'eff_code' => '9A',
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

        $this->assertSame(['PN-STD-EFF', 'PN-OTHER-STD-EFF'], array_column($rows, 'part_number'));
        $this->assertSame('9A', StdProcess::query()->where('component_id', $matchingComponent->id)->value('eff_code'));
        $this->assertNull(StdProcess::query()->where('component_id', $otherComponent->id)->value('eff_code'));
    }

    public function test_workorder_std_snapshot_matches_space_separated_eff_codes(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => '1C',
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9A-520',
            'part_number' => 'PN-LH',
            'name' => 'MAIN FITTING, ASSY LH',
            'units_assy' => 1,
            'eff_code' => '1A 1C 1E 1G',
            'paint_list' => true,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9A-530',
            'part_number' => 'PN-RH',
            'name' => 'MAIN FITTING, ASSY RH',
            'units_assy' => 1,
            'eff_code' => '1B 1D 1F 1H',
            'paint_list' => true,
        ]);

        StdProcess::syncFromComponentFlagsForManual($manual);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);

        $this->assertSame(['PN-LH'], array_column($rows, 'part_number'));
        $this->assertSame(['1A, 1C, 1E, 1G'], array_column($rows, 'eff_code'));
    }

    public function test_std_ipl_sort_uses_first_line_for_collapsed_rows(): void
    {
        $values = [
            "9A-340",
            "10-70",
            "6-190A\n6-190B",
            "11-240A\n11-240B",
        ];

        usort($values, fn (string $left, string $right): int => StdProcess::compareIplValues($left, $right));

        $this->assertSame([
            "6-190A\n6-190B",
            "9A-340",
            "10-70",
            "11-240A\n11-240B",
        ], $values);
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

    public function test_tdr_show_ndt_std_badge_counts_collapsed_base_and_suffix_variant_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-BADGE-MERGE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $this->createInstruction()->id,
            'user_id' => $admin->id,
        ]);

        foreach ([
            ['1-10', 'PN-10', 'SINGLE 10', '1 / 4'],
            ['1-90', 'PN-90', 'SINGLE 90', '4'],
            ['1-100', 'PN-100', 'SINGLE 100', '1 / 4'],
            ['1-112', 'PN-112', 'PAIR 112', '4'],
            ['1-112A', 'PN-112A', 'PAIR 112', '4'],
            ['1-210', 'PN-210', 'SINGLE 210', '1'],
            ['1-272', 'PN-272', 'PAIR 272', '4'],
            ['1-272A', 'PN-272A', 'PAIR 272', '4'],
        ] as [$ipl, $partNumber, $name, $process]) {
            $component = Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => $ipl,
                'part_number' => $partNumber,
                'name' => $name,
                'units_assy' => 1,
                'ndt_list' => true,
            ]);

            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => $process,
                'qty' => 1,
            ]);
        }

        $formResponse = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));
        $formResponse->assertOk();
        $formResponse->assertSee("1-112\n1-112A", false);
        $formResponse->assertSee("1-272\n1-272A", false);

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $showResponse->assertOk();
        $html = $showResponse->getContent();
        $ndtStart = strpos($html, 'tdr-std-paper-ndt-wrap');
        $cadStart = strpos($html, 'tdr-std-paper-cad-wrap');

        $this->assertNotFalse($ndtStart);
        $this->assertNotFalse($cadStart);
        $ndtSegment = substr($html, $ndtStart, $cadStart - $ndtStart);

        $this->assertStringContainsString('NDT STD', $ndtSegment);
        $this->assertStringContainsString('>6</span>', $ndtSegment);
        $this->assertStringNotContainsString('>8</span>', $ndtSegment);
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

    public function test_ndt_std_form_displays_the_complete_manual_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => '32-11-15RM']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-158',
            'part_number' => '2821-2001',
            'name' => 'Bolt',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('<div class="std-manual-ref-box std-ndt-cmm-box">32-11-15RM</div>', false);
    }

    public function test_ndt_std_form_uses_fourteen_body_rows_per_page_by_default(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-ROWS']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        for ($i = 1; $i <= 16; $i++) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => '3-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'part_number' => 'PN-NDT-ROWS-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'name' => 'NDT Row Part ' . $i,
                'units_assy' => 1,
                'ndt_list' => true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $html = $response->getContent();
        $firstRowsStart = strpos($html, 'class="all-rows-container page-rows-container"');
        $firstRowsEnd = strpos($html, 'class="std-table-summary"', $firstRowsStart);
        $this->assertNotFalse($firstRowsStart);
        $this->assertNotFalse($firstRowsEnd);
        $firstPageRows = substr($html, $firstRowsStart, $firstRowsEnd - $firstRowsStart);

        $this->assertSame(2, substr_count($html, 'std-page std-page--ndt page data-page'));
        $this->assertSame(13, substr_count($firstPageRows, 'data-row-ndt std-grid-row" data-row-index'));
        $this->assertStringContainsString('manual-row std-grid-row std-grid-row--manual', $firstPageRows);
        $this->assertStringContainsString('NDT-ROWS', $firstPageRows);
        $this->assertStringContainsString('PN-NDT-ROWS-13', $firstPageRows);
        $this->assertStringNotContainsString('PN-NDT-ROWS-14', $firstPageRows);
    }

    public function test_ndt_std_form_keeps_approved_print_layout_parameters(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-LAYOUT']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-NDT-LAYOUT',
            'name' => 'NDT Layout Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $html = preg_replace('/\s+/', ' ', $response->getContent());

        $this->assertStringContainsString('.std-page--ndt .std-header-title--ndt { transform: translateX(-38px); }', $html);
        $this->assertStringContainsString('.std-page--ndt .std-ndt-title { transform: translate(12px, 4px); }', $html);
        $this->assertStringContainsString('.std-page--ndt .std-ndt-cmm-label { font-size: 16px; }', $html);
        $this->assertStringContainsString('.std-page--ndt .std-ndt-cmm-box { font-size: 16px; }', $html);
        $this->assertStringContainsString('.std-page--ndt .std-header { gap: 11px; }', $html);
        $this->assertStringContainsString('grid-template-rows: auto 24px auto 24px auto 44px;', $html);
        $this->assertStringContainsString('.std-page--ndt .std-table { margin-top: 8px; }', $html);
        $this->assertStringContainsString('--std-row-min-height: 35px;', $html);
        $this->assertStringContainsString('.std-page--ndt .std-footer-grid { font-size: 15px; }', $html);
        $this->assertStringContainsString('height: 40px;', $html);
        $this->assertStringContainsString('--std-table-columns: 1fr 2.4fr 3.2fr 2fr 0.9fr 1.25fr 1.25fr;', $html);
        $this->assertStringContainsString('white-space: nowrap;', $html);
        $this->assertStringNotContainsString('Settings saved successfully!', $html);
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

    public function test_spec_process_form_uses_one_cat_one_qty_when_unit_part_is_not_in_cat_two(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => 'PN-UNIT-AS-PART',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $unitPart = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN UNIT AS PART',
            'name' => 'Unit represented by its part',
            'units_assy' => 1,
            'ndt_list' => true,
            'cad_list' => true,
        ]);
        $catTwoDetail = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-20',
            'part_number' => 'PN-CAT-TWO-DETAIL',
            'name' => 'Separate Cat two detail',
            'units_assy' => 1,
            'ndt_list' => false,
            'cad_list' => false,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $unitPart->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $unitPart->id,
            'std' => StdProcess::STD_CAD,
        ], [
            'process' => 'CAD',
            'qty' => 1,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $catTwoDetail->id,
            'qty' => 1,
            'serial_number' => 'SN-CAT-TWO-DETAIL',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessForm', $workorder->id));

        $response->assertOk();
        $response->assertViewHas('ndtSums', ['total' => 1, 'mpi' => 1, 'fpi' => null]);
        $response->assertViewHas('cadSum', ['total_qty' => 1, 'total_components' => 1]);
        $response->assertViewHas('cadSum_ex', 0);
        $response->assertSeeInOrder(['Cat #1', '1', 'RO', 'RO No.', '1'], false);
    }

    public function test_spec_process_form_leaves_cat_one_blank_when_unit_part_is_already_in_cat_two(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => 'PN-CAT-TWO-ONLY',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $unitPart = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '12-500A',
            'part_number' => 'PN-CAT-TWO-ONLY',
            'name' => 'Cat two only part',
            'units_assy' => 1,
            'ndt_list' => true,
            'cad_list' => true,
        ]);
        $manualCatOnePart = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '12-510',
            'part_number' => 'PN-MANUAL-CAT-ONE',
            'name' => 'Unrelated manual Cat one part',
            'units_assy' => 7,
            'ndt_list' => true,
            'cad_list' => true,
        ]);

        foreach ([$unitPart, $manualCatOnePart] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1',
                'qty' => $component->units_assy,
            ]);
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_CAD,
            ], [
                'process' => 'CAD',
                'qty' => $component->units_assy,
            ]);
        }

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $unitPart->id,
            'qty' => 1,
            'serial_number' => 'SN-CAT-TWO-ONLY',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessForm', $workorder->id));

        $response->assertOk();
        $response->assertViewHas('ndtSums', ['total' => 0, 'mpi' => null, 'fpi' => null]);
        $response->assertViewHas('cadSum', ['total_qty' => 0, 'total_components' => 0]);
        $response->assertViewHas('cadSum_ex', 0);
    }

    public function test_empty_spec_process_form_uses_std_ndt_and_cad_totals_for_overhaul(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $mpi = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-SP-EMP-MPI',
            'name' => 'SP Emp MPI Part',
            'units_assy' => 2,
            'ndt_list' => true,
        ]);
        $fpi = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-20',
            'part_number' => 'PN-SP-EMP-FPI',
            'name' => 'SP Emp FPI Part',
            'units_assy' => 3,
            'ndt_list' => true,
        ]);
        $cad = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-30',
            'part_number' => 'PN-SP-EMP-CAD',
            'name' => 'SP Emp CAD Part',
            'units_assy' => 4,
            'cad_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $mpi->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 2,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $fpi->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '4',
            'qty' => 3,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $cad->id,
            'std' => StdProcess::STD_CAD,
        ], [
            'process' => 'CAD',
            'qty' => 4,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessFormEmp', $workorder->id));

        $response->assertOk();
        $response->assertDontSee('N/A', false);
        $response->assertSeeInOrder(['Cat #1', '2', 'RO No.', '3', 'RO No.', '4'], false);
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
        $response->assertSeeInOrder(['Total QTY:', '5'], false);
    }

    public function test_cad_std_form_keeps_screen_and_print_header_layout_aligned(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'CAD-LAYOUT']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-10',
            'part_number' => 'PN-CAD-LAYOUT',
            'name' => 'CAD Layout Part',
            'units_assy' => 1,
            'cad_list' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.cadStd', $workorder->id));

        $response->assertOk();
        $html = preg_replace('/\s+/', ' ', $response->getContent());

        $this->assertStringContainsString('<div class="std-instruction std-cad-instruction"> Perform the CAD plate as specified under Process No. and in accordance with SMM No. </div>', $html);
        $this->assertStringNotContainsString('<div class="std-instruction std-instruction-row std-instruction-row--cad">', $html);
        $this->assertStringContainsString('.std-page--cad .std-meta-row { grid-template-columns: 170px minmax(0, 1fr); min-height: 36px; }', $html);
        $this->assertStringContainsString('.std-page--cad .std-meta-label { white-space: nowrap; }', $html);
        $this->assertStringContainsString('.std-page--cad .std-cad-instruction { display: block; font-size: 17px; line-height: 1.2; margin-top: 10px; overflow: visible; white-space: nowrap; }', $html);
        $this->assertStringContainsString('.std-page--cad .std-meta-row { grid-template-columns: 150px minmax(0, 1fr); min-height: 30px; }', $html);
        $this->assertStringContainsString('.std-page--cad .std-cad-instruction { font-size: 15px; line-height: 1.15; margin-top: 8px; overflow: visible; white-space: nowrap; }', $html);
    }

    public function test_paint_std_form_renders_all_print_pages_on_server_when_rows_exceed_limit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'PAINT-PAGES']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => '9-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'part_number' => 'PN-PAINT-PAGE-' . $i,
                'name' => 'Paint Page Part ' . $i,
                'units_assy' => 1,
                'paint_list' => true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.paintStd', [
            'workorder_id' => $workorder->id,
            'paint_table_rows' => 3,
        ]));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'std-page page data-page'));
        $this->assertStringContainsString('PN-PAINT-PAGE-1', $html);
        $this->assertStringContainsString('PN-PAINT-PAGE-4', $html);
        $this->assertStringContainsString('tdr-primary-logical-page', $html);
        $this->assertStringContainsString('dynamic-page-wrapper', $html);
        $this->assertStringContainsString('data-tdr-footer-page>1</span>', $html);
        $this->assertStringContainsString('data-tdr-footer-page>2</span>', $html);
        $this->assertSame(2, substr_count($html, 'data-tdr-footer-total>2</span>'));
        $this->assertStringNotContainsString('cloneNode', $html);
        $this->assertStringNotContainsString('rowsToProcess', $html);
    }

    public function test_spec_process_form_uses_collapsed_cad_rows_for_cad_total(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'SP-CAD-MERGE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-380A',
            'part_number' => '1840-8402',
            'name' => 'LOWER TORQUE LINK',
            'units_assy' => 1,
            'cad_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-380B',
            'part_number' => '1840-8403',
            'name' => 'LOWER TORQUE LINK',
            'units_assy' => 1,
            'cad_list' => true,
        ]);

        foreach ([$first, $second] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_CAD,
            ], [
                'process' => 'SPM 20-00-10P05',
                'qty' => 1,
            ]);
        }

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'qty' => 1,
            'serial_number' => 'SN-SP-CAD',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessForm', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['CAD', 'qty', 'W'.$workorder->number, 'Cat #1', '1'], false);
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
        $response->assertSeeInOrder(['Total QTY:', '5'], false);
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
        $response->assertSee('2801-0304', false);
        $response->assertDontSee('2801-0301 / 2801-0304', false);
        $this->assertSame(1, substr_count($response->getContent(), 'UPPER TORQUE LINK'));
        $response->assertSee('<strong>1</strong>', false);
    }

    public function test_ndt_std_form_collapses_base_and_letter_suffix_ipl_variants_into_one_row(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-MERGE-BASE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $base = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-272',
            'part_number' => '170-70162-003',
            'name' => 'STAY, LOWER',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $suffix = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-272A',
            'part_number' => '170-70162-005',
            'name' => 'STAY, LOWER',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        foreach ([$base, $suffix] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1',
                'qty' => 1,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee("1-272\n1-272A", false);
        $response->assertSee("170-70162-003\n170-70162-005", false);
        $this->assertSame(1, substr_count($response->getContent(), 'STAY, LOWER'));
        $response->assertSee('<strong>1</strong>', false);
    }

    public function test_ndt_std_form_collapses_suffix_variants_even_when_descriptions_differ(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-MERGE-DESC']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9A-510A',
            'part_number' => '2801-0601',
            'name' => 'SLEEVE',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9A-510B',
            'part_number' => '2505-1401',
            'name' => 'VALVE, INSERT',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        foreach ([$first, $second] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1',
                'qty' => 1,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), '9A-510A'));
        $this->assertSame(1, substr_count($response->getContent(), '9A-510B'));
        $response->assertSee("9A-510A\n9A-510B", false);
        $response->assertSee("2801-0601\n2505-1401", false);
        $response->assertSee("SLEEVE\nVALVE, INSERT", false);
    }

    public function test_spec_process_form_uses_collapsed_ndt_rows_for_mpi_and_fpi_totals(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'SP-NDT-MERGE']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'instruction_id' => 1,
        ]);

        $mpiA = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => '2801-0301',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $mpiB = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => '2801-0304',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $fpiA = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9-90A',
            'part_number' => '2801-0008',
            'name' => 'RING',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $fpiB = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '9-90B',
            'part_number' => '2000A1045K01',
            'name' => 'RING',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        foreach ([$mpiA, $mpiB] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1',
                'qty' => 1,
            ]);
        }

        foreach ([$fpiA, $fpiB] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '4',
                'qty' => 1,
            ]);
        }

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $mpiA->id,
            'qty' => 1,
            'serial_number' => 'SN-SP-MPI',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.specProcessForm', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['Cat #1', '1', 'RO', '1'], false);
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
        $response->assertSee('2801-0304', false);
        $response->assertDontSee('2801-0301 / 2801-0304', false);
        $this->assertSame(1, substr_count($response->getContent(), 'UPPER TORQUE LINK'));
    }

    public function test_std_form_collapses_suffix_variants_after_eff_code_filtering(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'NDT-EFF-MERGE']);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'L2',
        ]);
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
            'eff_code' => 'L1, L2',
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => '2801-0304',
            'name' => 'UPPER TORQUE LINK',
            'units_assy' => 1,
            'eff_code' => 'L1, L2',
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
            'eff_code' => 'L1, L2',
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
            'eff_code' => 'L1, L2',
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.ndtStd', $workorder->id));

        $response->assertOk();
        $response->assertSee('8-240A', false);
        $response->assertSee('8-240B', false);
        $response->assertSee('2801-0301', false);
        $response->assertSee('2801-0304', false);
        $this->assertSame(1, substr_count($response->getContent(), 'UPPER TORQUE LINK'));
        $response->assertSee('<strong>1</strong>', false);
    }

    public function test_workorder_std_snapshot_prefers_eff_variant_over_universal_collapsed_variant(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'R2',
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        $left = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-190A',
            'part_number' => 'PN-LH',
            'name' => 'BRACKET',
            'units_assy' => 1,
            'eff_code' => 'L1, L2',
            'ndt_list' => true,
        ]);
        $right = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-190B',
            'part_number' => 'PN-RH',
            'name' => 'BRACKET',
            'units_assy' => 1,
            'eff_code' => 'R1, R2',
            'ndt_list' => true,
        ]);
        $universal = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => "6-190A\n6-190B",
            'part_number' => "PN-LH\nPN-RH",
            'name' => 'Support',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        foreach ([[$left, 'L1, L2'], [$right, 'R1, R2'], [$universal, null]] as [$component, $effCode]) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '4',
                'qty' => 1,
                'eff_code' => $effCode,
            ]);
        }

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);

        $this->assertSame(['6-190B'], array_column($rows, 'ipl_num'));
        $this->assertSame(['PN-RH'], array_column($rows, 'part_number'));
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

    public function test_std_qr_is_attached_to_sheet_without_table_anchor_for_screen_alignment(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true);

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        foreach ([
            route('tdrs.stressStd', $workorder->id),
            route('tdrs.paintStd', $workorder->id),
        ] as $route) {
            $response = $this->actingAs($admin)->get($route);

            $response->assertOk();
            $response->assertSee('data-screen-placement="page"', false);
            $response->assertSee('width: 40px;', false);
            $response->assertSee('top: 3mm;', false);
            $response->assertSee('right: 4mm;', false);
            $response->assertSee('.tdr-primary-sheet, .form-page-block, .std-sheet-container', false);
            $response->assertDontSee('anchorRect.right', false);
            $response->assertDontSee('anchorSelector', false);
            $response->assertDontSee('closestHost', false);
        }
    }

    public function test_ndt_and_cad_std_qr_have_std_label_and_use_the_sheet_as_screen_host(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true);

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        foreach ([
            route('tdrs.ndtStd', $workorder->id),
            route('tdrs.cadStd', $workorder->id),
        ] as $route) {
            $response = $this->actingAs($admin)->get($route);

            $response->assertOk();
            $response->assertSee(
                'system-print-qr__label">STD</span><span class="system-print-qr__code"',
                false
            );
            $response->assertSee(".split(',')", false);
            $response->assertSee('for (const selector of selectors)', false);
            $response->assertSee('if (host) return host;', false);
        }
    }

    public function test_prl_qr_is_shifted_up_from_workorder_number_box(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true);
        Necessary::query()->firstOrCreate(['name' => 'Order New']);
        Code::query()->firstOrCreate(['name' => Code::NAME_MISSING], ['code' => 'M']);

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $workorder->id));

        $response->assertOk();
        $response->assertSee('top: -8px;', false);
        $response->assertSee('top: -2mm;', false);
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

    public function test_workorder_std_items_subtract_tdr_from_all_collapsed_suffix_variants(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => 'PN-240A',
            'name' => 'Collapsed Repair Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => 'PN-240B',
            'name' => 'Collapsed Repair Part',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        foreach ([$first, $second] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1',
                'qty' => 1,
            ]);
        }

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
        $this->assertSame(['8-240A', '8-240B'], array_values(array_column($initial, 'ipl_num')));

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_NDT);

        $this->assertSame([], array_values(array_filter($rows, fn (array $row): bool => str_contains((string) ($row['ipl_num'] ?? ''), '8-240'))));
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $second->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
    }

    public function test_workorder_std_items_subtract_tdr_from_base_and_suffix_variants_by_ipl_only(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $base = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-272',
            'part_number' => '170-70162-003',
            'name' => 'STAY, LOWER BASE',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $suffix = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-272A',
            'part_number' => '170-70162-005',
            'name' => 'STAY, LOWER MOD',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $base->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);
        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $suffix->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '4',
            'qty' => 1,
        ]);

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
        $this->assertSame(['1-272', '1-272A'], array_values(array_column($initial, 'ipl_num')));

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $suffix->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_NDT);

        $this->assertSame([], array_values(array_filter($rows, fn (array $row): bool => str_contains((string) ($row['ipl_num'] ?? ''), '1-272'))));
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $base->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $suffix->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
    }

    public function test_workorder_std_items_do_not_subtract_same_base_ipl_from_other_manual(): void
    {
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $currentManualComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-272',
            'part_number' => 'CURRENT-PN',
            'name' => 'CURRENT MANUAL PART',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);
        $otherManualComponent = Component::query()->create([
            'manual_id' => $otherManual->id,
            'ipl_num' => '1-272A',
            'part_number' => 'OTHER-PN',
            'name' => 'OTHER MANUAL PART',
            'units_assy' => 1,
            'ndt_list' => true,
        ]);

        StdProcess::query()->updateOrCreate([
            'manual_id' => $manual->id,
            'component_id' => $currentManualComponent->id,
            'std' => StdProcess::STD_NDT,
        ], [
            'process' => '1',
            'qty' => 1,
        ]);

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
        $this->assertSame(['1-272'], array_values(array_column($initial, 'ipl_num')));

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $otherManualComponent->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_NDT);

        $this->assertSame(['1-272'], array_values(array_column($rows, 'ipl_num')));
        $this->assertDatabaseHas('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $currentManualComponent->id,
            'std_type' => StdProcess::STD_NDT,
            'remaining_qty' => 1,
        ]);
    }

    public function test_workorder_std_items_subtract_tdr_from_all_eff_filtered_collapsed_suffix_variants(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'L2',
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '11-240A',
            'part_number' => '2801-0121',
            'name' => 'MAIN FITTING LH',
            'units_assy' => 1,
            'eff_code' => 'L1, L2',
            'ndt_list' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '11-240B',
            'part_number' => '2801-0122',
            'name' => 'MAIN FITTING LH',
            'units_assy' => 1,
            'eff_code' => 'L1, L2',
            'ndt_list' => true,
        ]);

        foreach ([$first, $second] as $component) {
            StdProcess::query()->updateOrCreate([
                'manual_id' => $manual->id,
                'component_id' => $component->id,
                'std' => StdProcess::STD_NDT,
            ], [
                'process' => '1 / 4 / 6',
                'qty' => 1,
                'eff_code' => 'L1, L2',
            ]);
        }

        $initial = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
        $this->assertSame(['11-240A', '11-240B'], array_values(array_column($initial, 'ipl_num')));

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'necessaries_id' => $repair->id,
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $rows = StdProcess::snapshotComponentsForWorkorder($workorder->fresh(), StdProcess::STD_NDT);

        $this->assertSame([], array_values(array_filter($rows, fn (array $row): bool => str_contains((string) ($row['ipl_num'] ?? ''), '11-240'))));
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
        $this->assertDatabaseMissing('workorder_std_process_items', [
            'workorder_id' => $workorder->id,
            'component_id' => $second->id,
            'std_type' => StdProcess::STD_NDT,
        ]);
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
