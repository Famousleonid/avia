<?php

namespace Tests\Feature;

use App\Models\ManualFit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Covers the Fits & Clearances registry (manual_fit), the F&C detection/sync,
 * the Torque values endpoint and the fit-driven measurement reports.
 */
class FitsClearancesTorqueTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function admin()
    {
        return $this->createUserWithRole('Admin', ['stamp' => 'FC' . random_int(1, 9999)]);
    }

    // ---- ManualFit model: derived clearances + mismatch flag ----

    public function test_manual_fit_derives_clearances_and_flags_mismatch(): void
    {
        $manual = $this->createManual();
        $od = $this->createParameter($manual, null, [
            'description' => 'OD', 'orig_dim_min' => 0.9987, 'orig_dim_max' => 0.9993,
            'wear_dim_min' => 0.9980, 'wear_dim_max' => 0.9993,
        ]);
        $id = $this->createParameter($manual, null, [
            'description' => 'ID', 'orig_dim_min' => 1.0000, 'orig_dim_max' => 1.0008,
            'wear_dim_min' => 1.0000, 'wear_dim_max' => 1.0024,
        ]);
        $fit = $this->createFit($manual, $od, $id)->load('odParam', 'idParam');

        // assembly = ID_mfg - OD_mfg ; permitted = ID_wear_max - OD_wear_min
        $this->assertEqualsWithDelta(0.0007, $fit->derivedAssemblyClearanceMin(), 0.00001);
        $this->assertEqualsWithDelta(0.0021, $fit->derivedAssemblyClearanceMax(), 0.00001);
        $this->assertEqualsWithDelta(0.0044, $fit->derivedPermittedClearance(), 0.00001);

        // no stored values → effective falls back to derived, no mismatch
        $this->assertEqualsWithDelta(0.0007, $fit->effectiveAssemblyClearanceMin(), 0.00001);
        $this->assertFalse($fit->hasClearanceMismatch());

        // a stored value that disagrees with derived → flagged
        $fit->update(['assembly_clearance_min' => 0.0099]);
        $this->assertTrue($fit->fresh()->load('odParam', 'idParam')->hasClearanceMismatch());
    }

    // ---- detect: gated is_fc from the point, ref_no from the point code ----

    public function test_detect_sets_is_fc_from_point_and_ref_no_from_code(): void
    {
        $manual = $this->createManual();
        $icA = $this->createInspectionComponent($manual, 'Pin');
        $icB = $this->createInspectionComponent($manual, 'Bushing');

        $od = $this->createParameter($manual, $icA, ['description' => 'OD', 'orig_dim_min' => 0.50]);
        $id = $this->createParameter($manual, $icB, ['description' => 'ID', 'orig_dim_min' => 0.50]);
        $fcPoint = $this->createDimensionPoint($manual, '1', true);   // F&C point
        $this->attachParamToPoint($od, $fcPoint);
        $this->attachParamToPoint($id, $fcPoint);

        $od2 = $this->createParameter($manual, $icB, ['description' => 'OD', 'orig_dim_min' => 0.60]);
        $id2 = $this->createParameter($manual, $icA, ['description' => 'ID', 'orig_dim_min' => 0.60]);
        $plainPoint = $this->createDimensionPoint($manual, '7', false); // not F&C
        $this->attachParamToPoint($od2, $plainPoint);
        $this->attachParamToPoint($id2, $plainPoint);

        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.detect', $manual->id))
            ->assertOk()
            ->assertJson(['created' => 2, 'skipped' => 0]);

        $fcFit = ManualFit::where('manual_id', $manual->id)->where('ref_no', '1')->first();
        $this->assertNotNull($fcFit);
        $this->assertTrue((bool) $fcFit->is_fc);
        $this->assertSame($od->id, $fcFit->od_param_id);
        $this->assertSame($id->id, $fcFit->id_param_id);

        $plainFit = ManualFit::where('manual_id', $manual->id)->where('ref_no', '7')->first();
        $this->assertNotNull($plainFit);
        $this->assertFalse((bool) $plainFit->is_fc);

        // idempotent
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.detect', $manual->id))
            ->assertJson(['created' => 0, 'skipped' => 2]);
    }

    // ---- point F&C checkbox toggle syncs fit.is_fc (the bug fix) ----

    public function test_point_fc_toggle_syncs_fit_is_fc(): void
    {
        $manual = $this->createManual();
        $od = $this->createParameter($manual, null, ['description' => 'OD', 'orig_dim_min' => 0.5]);
        $id = $this->createParameter($manual, null, ['description' => 'ID', 'orig_dim_min' => 0.5]);
        $point = $this->createDimensionPoint($manual, '1', true);
        $this->attachParamToPoint($od, $point);
        $this->attachParamToPoint($id, $point);
        $fit = $this->createFit($manual, $od, $id, ['is_fc' => true]);

        $this->actingAs($this->admin())
            ->patchJson(route('dimension-points.update', $point->id), ['is_fits_clearance' => false])
            ->assertOk();
        $this->assertFalse((bool) $fit->fresh()->is_fc);

        $this->actingAs($this->admin())
            ->patchJson(route('dimension-points.update', $point->id), ['is_fits_clearance' => true])
            ->assertOk();
        $this->assertTrue((bool) $fit->fresh()->is_fc);
    }

    // ---- fit CRUD validation ----

    public function test_fit_store_rejects_same_member_and_foreign_manual(): void
    {
        $manual = $this->createManual();
        $p1 = $this->createParameter($manual, null, ['description' => 'OD']);
        $other = $this->createManual();
        $pOther = $this->createParameter($other, null, ['description' => 'ID']);

        // same param on both sides
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), ['od_param_id' => $p1->id, 'id_param_id' => $p1->id])
            ->assertStatus(422);

        // member from another manual
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), ['od_param_id' => $p1->id, 'id_param_id' => $pOther->id])
            ->assertStatus(422);
    }

    public function test_bushing_sketch_case_b_accepts_initial_pass_bore(): void
    {
        // A PASSing initial means the bore is within limits and will never get a
        // final (no machining) — the sketch must compute req OD from that value
        // instead of demanding a measurement that cannot exist.
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 1-540');
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 0.8012, 'orig_dim_max' => 0.8020]);
        $housingIc = $this->createInspectionComponent($manual, 'Lower Stay');
        $bore = $this->createParameter($manual, $housingIc, ['description' => 'ID 1-540', 'orig_dim_min' => 0.8000, 'orig_dim_max' => 0.8008]);
        $pt = $this->createDimensionPoint($manual, '2A', false);
        $this->attachParamToPoint($od, $pt);
        $this->attachParamToPoint($bore, $pt);

        $this->createMeasurement($wo, $bore, ['stage' => 'initial', 'result' => 'PASS', 'actual_value' => 0.8003]);

        $html = $this->actingAs($this->admin())
            ->get(route('inspection-components.bushing-sketch-view', [$wo->id, $bushIc->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Manufacture to fit', $html);
        $this->assertStringContainsString('0.8007', $html); // 0.8003 + fit_min 0.0004
        $this->assertStringContainsString('0.8023', $html); // 0.8003 + fit_max 0.0020
        $this->assertStringContainsString('(initial)', $html);
        $this->assertStringNotContainsString('mating not measured yet', $html);
    }

    public function test_bushing_sketch_case_a_initial_pass_renders_standard(): void
    {
        // OD has oversize steps, but the bore PASSed initial (within limits) —
        // the position takes the STANDARD bushing: no step, factory OD dims.
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 11-10');
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 1.3171, 'orig_dim_max' => 1.3180]);
        \App\Models\ManualRepairStep::query()->create([
            'manual_parameter_id' => $od->id, 'step_no' => 'R01',
            'dim_min' => 1.3271, 'dim_max' => 1.3280, 'sort_order' => 1,
        ]);
        $housingIc = $this->createInspectionComponent($manual, 'Main Fitting');
        $bore = $this->createParameter($manual, $housingIc, ['description' => 'ID 11-10', 'orig_dim_min' => 1.3134, 'orig_dim_max' => 1.3151]);
        $pt = $this->createDimensionPoint($manual, 'AA3', false);
        $this->attachParamToPoint($od, $pt);
        $this->attachParamToPoint($bore, $pt);

        $this->createMeasurement($wo, $bore, ['stage' => 'initial', 'result' => 'PASS', 'actual_value' => 1.3140]);

        $html = $this->actingAs($this->admin())
            ->get(route('inspection-components.bushing-sketch-view', [$wo->id, $bushIc->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('standard — bore within limits', $html);
        $this->assertStringContainsString('Standard (bore in limits)', $html);
        $this->assertStringContainsString('1.3171', $html); // factory OD dims, not the step
        $this->assertStringNotContainsString('R01', $html);
    }

    public function test_bushing_sketch_initial_fail_still_requires_final(): void
    {
        // Initial FAIL = bore out of limits, machining pending — the sketch must
        // NOT compute from a failed size; the position stays "not measured".
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 1-550');
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 0.8012, 'orig_dim_max' => 0.8020]);
        $housingIc = $this->createInspectionComponent($manual, 'Lower Stay');
        $bore = $this->createParameter($manual, $housingIc, ['description' => 'ID 1-550', 'orig_dim_min' => 0.8000, 'orig_dim_max' => 0.8008]);
        $pt = $this->createDimensionPoint($manual, 'BB-1', false);
        $this->attachParamToPoint($od, $pt);
        $this->attachParamToPoint($bore, $pt);

        $this->createMeasurement($wo, $bore, ['stage' => 'initial', 'result' => 'FAIL', 'actual_value' => 0.8031]);

        $html = $this->actingAs($this->admin())
            ->get(route('inspection-components.bushing-sketch-view', [$wo->id, $bushIc->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('mating not measured yet', $html);
    }

    public function test_deleting_parameter_removes_its_orphaned_points(): void
    {
        // Deleting a parameter drops its measurement points when no other
        // parameter uses them (ghost marks on the WO figure); a point shared
        // with another parameter survives.
        $manual = $this->createManual();
        $ic = $this->createInspectionComponent($manual, 'Pin');
        $own = $this->createParameter($manual, $ic, ['description' => 'OD']);
        $other = $this->createParameter($manual, $ic, ['description' => 'ID']);
        $ownPoint = $this->createDimensionPoint($manual, 'P1', false);
        $sharedPoint = $this->createDimensionPoint($manual, 'P2', false);
        $this->attachParamToPoint($own, $ownPoint);
        $this->attachParamToPoint($own, $sharedPoint);
        $this->attachParamToPoint($other, $sharedPoint);

        $this->actingAs($this->admin())
            ->deleteJson(route('parameters.destroy', $own->id))
            ->assertOk();

        $this->assertDatabaseMissing('manual_dimension_points', ['id' => $ownPoint->id]);
        $this->assertDatabaseHas('manual_dimension_points', ['id' => $sharedPoint->id]);
    }

    public function test_cleanup_unattached_points_removes_orphans_only(): void
    {
        $manual = $this->createManual();
        $ic = $this->createInspectionComponent($manual, 'Pin');
        $param = $this->createParameter($manual, $ic, ['description' => 'OD']);
        $linked = $this->createDimensionPoint($manual, 'L1', false);
        $this->attachParamToPoint($param, $linked);
        $orphanMeas = $this->createDimensionPoint($manual, 'O1', false); // measurement, no params
        // callout that lost its part (child_ic SET NULL) and has no text of its own
        $orphanCallout = \App\Models\ManualDimensionPoint::query()->create([
            'manual_dimension_figure_id' => $linked->manual_dimension_figure_id,
            'point_type' => 'text', 'code' => 'lbl_x', 'child_ic_id' => null,
            'x_pct' => 5, 'y_pct' => 5, 'label_x_pct' => 8, 'label_y_pct' => 8, 'sort_order' => 0,
        ]);
        // legit free-text annotation — must survive
        $freeText = \App\Models\ManualDimensionPoint::query()->create([
            'manual_dimension_figure_id' => $linked->manual_dimension_figure_id,
            'point_type' => 'text', 'code' => 'lbl_y', 'child_ic_id' => null, 'description' => 'Surface inspection',
            'x_pct' => 6, 'y_pct' => 6, 'label_x_pct' => 9, 'label_y_pct' => 9, 'sort_order' => 0,
        ]);

        $this->actingAs($this->admin())
            ->postJson(route('manuals.dimension-points.cleanup', $manual->id))
            ->assertOk()
            ->assertJsonPath('measurement', 1)
            ->assertJsonPath('callouts', 1);

        $this->assertDatabaseMissing('manual_dimension_points', ['id' => $orphanMeas->id]);
        $this->assertDatabaseMissing('manual_dimension_points', ['id' => $orphanCallout->id]);
        $this->assertDatabaseHas('manual_dimension_points', ['id' => $linked->id]);
        $this->assertDatabaseHas('manual_dimension_points', ['id' => $freeText->id]);
    }

    public function test_single_member_fit_stores_and_renders_in_fc_table(): void
    {
        // Real Table 8001 has single rows: the mate lives in another manual, or
        // the row is a linear Between/Across Faces dimension — a fit may carry
        // just ONE member (plus single_kind for faces).
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $ic = $this->createInspectionComponent($manual, 'Bushing');
        $od = $this->createParameter($manual, $ic, ['description' => 'OD ext-mate', 'orig_dim_min' => 1.0, 'orig_dim_max' => 1.001]);
        $faces = $this->createParameter($manual, $ic, ['description' => 'Across flanges', 'orig_dim_min' => 0.5, 'orig_dim_max' => 0.52]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, 'S1', true));
        $this->attachParamToPoint($faces, $this->createDimensionPoint($manual, 'S2', true));

        // no member at all → rejected
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), ['ref_no' => '9'])
            ->assertStatus(422);

        // single OD (mate in another manual) — the FORM posts the absent side as
        // an explicit null and may carry manual clearances; single_kind derived
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), [
                'od_param_id' => $od->id, 'id_param_id' => null, 'ref_no' => '7',
                'assembly_clearance_min' => 0.0002, 'permitted_clearance' => 0.006,
            ])
            ->assertStatus(201)
            ->assertJsonPath('single_kind', 'od')
            ->assertJsonPath('id_member', null)
            ->assertJsonPath('assembly_clearance_min', '0.0002')
            ->assertJsonPath('permitted_clearance', '0.0060');

        // Between/Across Faces — explicit kind, od slot
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), [
                'od_param_id' => $faces->id, 'single_kind' => 'faces', 'ref_no' => '8',
            ])
            ->assertStatus(201)
            ->assertJsonPath('single_kind', 'faces');

        // WO F&C print: both singles render as fc rows with their refs, no dup in extra
        $html = $this->actingAs($this->admin())
            ->get(route('workorders.measurements.fc-table', $wo->id))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-ref="7" data-type="fc"', $html);
        $this->assertStringContainsString('data-ref="8" data-type="fc"', $html);
        $this->assertStringContainsString('OD ext-mate', $html);
        $this->assertStringContainsString('Across flanges', $html);
        $this->assertSame(1, substr_count($html, 'OD ext-mate'), 'single member must not duplicate into Extra rows');
    }

    public function test_fit_stores_per_member_ref_no(): void
    {
        $manual = $this->createManual();
        $od = $this->createParameter($manual, null, ['description' => 'OD']);
        $id = $this->createParameter($manual, null, ['description' => 'ID']);

        // store with distinct OD/ID Ref.No (Table 8001 per-member numbering)
        $created = $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), [
                'od_param_id' => $od->id, 'id_param_id' => $id->id,
                'ref_no' => '1', 'id_ref_no' => '2',
            ])
            ->assertStatus(201)
            ->assertJsonPath('ref_no', '1')
            ->assertJsonPath('id_ref_no', '2')
            ->json();

        // update clears the ID ref → back to the merged/legacy look
        $this->actingAs($this->admin())
            ->patchJson(route('fits.update', $created['id']), ['id_ref_no' => null])
            ->assertOk()
            ->assertJsonPath('id_ref_no', null);

        // store without id_ref_no → null (legacy single-ref behaviour preserved)
        $this->actingAs($this->admin())
            ->postJson(route('manuals.fits.store', $manual->id), [
                'od_param_id' => $od->id, 'id_param_id' => $id->id, 'ref_no' => '5',
            ])
            ->assertStatus(201)
            ->assertJsonPath('id_ref_no', null);
    }

    // ---- Torque values endpoint ----

    public function test_torque_values_save_trims_and_drops_empty(): void
    {
        $wo = $this->createWorkorder();

        $this->actingAs($this->admin())
            ->postJson(route('workorders.torque-values.save', $wo->id), [
                'values' => ['10' => ' 3.60 ', '11' => 'N/A', '12' => ''],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 2]);

        $stored = $wo->fresh()->torque_values;
        $this->assertSame('3.60', $stored['10']);
        $this->assertSame('N/A', $stored['11']);
        $this->assertArrayNotHasKey('12', $stored);
    }

    // ---- Measurements: Final Fit Report finds the mate via the fit registry ----
    // Members are on DIFFERENT points (no shared point), so this only works
    // because the report reads manual_fit, not a shared-point guess.

    public function test_final_fit_report_uses_fit_registry_cross_point(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 11-10');
        $bushComp = $this->createComponent($manual, ['is_bush' => true, 'ipl_num' => 'B-99']);
        $this->attachComponentToIc($bushIc, $bushComp);
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 1.3171, 'orig_dim_max' => 1.3180]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, 'AA3', false));

        $housingIc = $this->createInspectionComponent($manual, 'Main Fitting');
        $id = $this->createParameter($manual, $housingIc, ['description' => 'ID 11-10', 'orig_dim_min' => 1.3134, 'orig_dim_max' => 1.3151]);
        $this->attachParamToPoint($id, $this->createDimensionPoint($manual, 'ZZ9', false)); // different point

        // Without a fit the report has no bore → no row for this bushing.
        $this->actingAs($this->admin())
            ->get(route('workorders.measurements.final-fit-report', $wo->id))
            ->assertOk()
            ->assertDontSee('B-99');

        // With the explicit fit, the mate is resolved → the row appears.
        $this->createFit($manual, $od, $id, ['is_fc' => false]);
        $this->actingAs($this->admin())
            ->get(route('workorders.measurements.final-fit-report', $wo->id))
            ->assertOk()
            ->assertSee('B-99');
    }

    // ---- F&C report rows: F&C badge only for members of an is_fc fit ----

    public function test_fc_report_flags_only_is_fc_members(): void
    {
        $manual = $this->createManual();
        $ic = $this->createInspectionComponent($manual, 'Pin');
        $od = $this->createParameter($manual, $ic, ['description' => 'OD', 'orig_dim_min' => 0.5, 'orig_dim_max' => 0.6]);
        $id = $this->createParameter($manual, $ic, ['description' => 'ID', 'orig_dim_min' => 0.7, 'orig_dim_max' => 0.8]);
        $solo = $this->createParameter($manual, $ic, ['description' => 'Lug', 'orig_dim_min' => 0.1, 'orig_dim_max' => 0.2]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, '1', true));
        $this->attachParamToPoint($id, $this->createDimensionPoint($manual, '2', true));
        $this->attachParamToPoint($solo, $this->createDimensionPoint($manual, '3', false));
        $this->createFit($manual, $od, $id, ['is_fc' => true]);

        $rows = $this->actingAs($this->admin())
            ->getJson(route('manuals.fits.report', $manual->id))
            ->assertOk()
            ->json();

        $byDesc = collect($rows)->keyBy('description');
        $this->assertTrue((bool) $byDesc['OD']['is_fc']);
        $this->assertTrue((bool) $byDesc['ID']['is_fc']);
        $this->assertFalse((bool) $byDesc['Lug']['is_fc']);
    }

    // ---- Required Bushings: P/N resolved via the fit mate's bore state ----

    public function test_required_bushings_resolves_pn_via_fit(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 11-10');
        $bushComp = $this->createComponent($manual, ['is_bush' => true, 'part_number' => 'STD-PN-11-10', 'ipl_num' => '11-10']);
        $this->attachComponentToIc($bushIc, $bushComp);
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 1.3171, 'orig_dim_max' => 1.3180]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, 'AA3', false));

        $housingIc = $this->createInspectionComponent($manual, 'Main Fitting');
        $bore = $this->createParameter($manual, $housingIc, ['description' => 'ID 11-10', 'orig_dim_min' => 1.3134, 'orig_dim_max' => 1.3151]);
        $this->attachParamToPoint($bore, $this->createDimensionPoint($manual, 'AA3b', false));

        $this->createFit($manual, $od, $bore, ['is_fc' => false]);
        // Bore measured initial PASS → standard (initial) bushing P/N.
        $this->createMeasurement($wo, $bore, ['stage' => 'initial', 'result' => 'PASS', 'actual_value' => 1.3140]);

        $this->actingAs($this->admin())
            ->get(route('workorders.measurements.required-bushings', $wo->id))
            ->assertOk()
            ->assertSee('STD-PN-11-10');
    }

    // ---- A. Torque renderer: value comes from workorder.torque_values ----

    public function test_torque_renderer_reads_workorder_values(): void
    {
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Torque', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/torque.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $el = $page->elements()->create(['element_type' => 'dimension', 'value_source' => 'torque', 'x_pct' => 50, 'y_pct' => 50, 'font_size' => 10, 'sort_order' => 0]);
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $wo->update(['torque_values' => [(string) $el->id => '3.60']]);

        $renderer = new \App\Services\Measurements\ProcessDocumentRenderer();

        // generate mode → plain text value, no input
        $gen = $renderer->renderSinglePageHtml($page->fresh()->load('elements'), $wo->fresh(), []);
        $this->assertStringContainsString('3.60', $gen);
        $this->assertStringNotContainsString('pdw-torque-input', $gen);

        // edit mode → inline input prefilled from torque_values
        $edit = $renderer->renderSinglePageHtml($page->fresh()->load('elements'), $wo->fresh(), ['torque_edit' => true]);
        $this->assertStringContainsString('pdw-torque-input', $edit);
        $this->assertStringContainsString('value="3.60"', $edit);
    }

    public function test_process_document_technician_placeholder_uses_selection_name(): void
    {
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Technician', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/technician.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $page->elements()->create([
            'element_type' => 'label',
            'placeholder' => '{technician_name}',
            'x_pct' => 50,
            'y_pct' => 50,
            'font_size' => 10,
            'sort_order' => 0,
        ]);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Lyfar Eduard',
            'selection_name_order' => 'last_first',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $this->createUnit(['manual_id' => $manual->id])->id,
            'user_id' => $technician->id,
        ]);

        $html = (new \App\Services\Measurements\ProcessDocumentRenderer())
            ->renderSinglePageHtml($page->fresh()->load('elements'), $workorder->fresh()->load('user'), []);

        $this->assertStringContainsString('Eduard Lyfar', $html);
        $this->assertStringNotContainsString('Lyfar Eduard', $html);
    }

    // ---- B1. F&C Document page exposes the torque fill UI (server scaffolding) ----
    // The actual Save-PDF JS gate (block until filled) needs a browser test (Dusk);
    // here we assert the server-rendered prerequisites only.

    public function test_torque_range_round_trips_and_enables_auto_fill(): void
    {
        // A torque mark may carry the CMM range (min/max). The fill page then
        // exposes it as data-tq-min/max on the input and offers the Auto-fill
        // button; marks without a range stay manual-only (no button).
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Torque', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/torque.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        // range round-trips via the element endpoint (editor save path)
        $created = $this->actingAs($this->admin())
            ->postJson(route('process-document-pages.elements.store', $page->id), [
                'element_type' => 'dimension', 'value_source' => 'torque',
                'x_pct' => 50, 'y_pct' => 50, 'torque_min' => 160, 'torque_max' => 190,
            ])
            ->assertStatus(201)
            ->assertJsonPath('torque_min', 160)
            ->assertJsonPath('torque_max', 190)
            ->json();

        // max < min is rejected
        $this->actingAs($this->admin())
            ->patchJson(route('process-document-elements.update', $created['id']), ['torque_min' => 200, 'torque_max' => 190])
            ->assertStatus(422);

        $resp = $this->actingAs($this->admin())->get(route('workorders.fc-document', $wo->id))->assertOk();
        $resp->assertSee('data-tq-min="160" data-tq-max="190"', false);
        $resp->assertSee('id="autoTorqueBtn"', false);
    }

    public function test_fc_document_without_range_has_no_auto_fill_button(): void
    {
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Torque', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/torque.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $page->elements()->create(['element_type' => 'dimension', 'value_source' => 'torque', 'x_pct' => 50, 'y_pct' => 50, 'sort_order' => 0]);
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $resp = $this->actingAs($this->admin())->get(route('workorders.fc-document', $wo->id))->assertOk();
        $resp->assertSee('FC_HAS_TORQUE = true', false);
        // the JS helper is always present; the BUTTON only renders with a range
        $resp->assertDontSee('id="autoTorqueBtn"', false);
        // an unfilled torque input must be truly empty — the show_missing "—"
        // placeholder would make auto-fill and the "all filled" gate see it as filled
        $resp->assertDontSee('value="—"', false);
    }

    public function test_fc_document_shows_torque_inputs_and_save_button(): void
    {
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Torque', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/torque.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $el = $page->elements()->create(['element_type' => 'dimension', 'value_source' => 'torque', 'x_pct' => 50, 'y_pct' => 50, 'sort_order' => 0]);
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $wo->update(['torque_values' => [(string) $el->id => '7.50']]);

        // Anchor on the gate flag (the server's decision) + the prefilled value,
        // not on incidental button markup.
        $resp = $this->actingAs($this->admin())->get(route('workorders.fc-document', $wo->id))->assertOk();
        $resp->assertSee('FC_HAS_TORQUE = true', false); // gate active
        $resp->assertSee('value="7.50"', false);         // input prefilled from torque_values
    }

    public function test_fc_document_has_no_torque_ui_without_torque_marks(): void
    {
        $manual = $this->createManual();
        $doc = $manual->documents()->create(['doc_type' => 'manual', 'title' => 'Doc', 'sort_order' => 0]);
        $page = $doc->pages()->create(['page_no' => 1, 'image_path' => 't/p.png', 'image_width' => 1000, 'image_height' => 800, 'sort_order' => 0]);
        $page->elements()->create(['element_type' => 'dimension', 'value_source' => 'static', 'static_value' => 1.0, 'x_pct' => 50, 'y_pct' => 50, 'sort_order' => 0]);
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        // Gate flag off = no torque UI (the literal "saveTorqueBtn" still appears
        // in the always-emitted JS, so we anchor on the flag, not the markup).
        $this->actingAs($this->admin())->get(route('workorders.fc-document', $wo->id))
            ->assertOk()
            ->assertSee('FC_HAS_TORQUE = false', false);
    }

    // ---- Measurements F&C grid pairs via the fit registry (cross-point) ----

    public function test_measurements_fc_table_pairs_via_fit_registry(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $ic = $this->createInspectionComponent($manual, 'Pin');
        $od = $this->createParameter($manual, $ic, ['description' => 'OD pin', 'orig_dim_min' => 0.99, 'orig_dim_max' => 1.00]);
        $id = $this->createParameter($manual, $ic, ['description' => 'ID bush', 'orig_dim_min' => 1.00, 'orig_dim_max' => 1.01]);
        // Members on DIFFERENT points — the old shared-point logic could not pair these.
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, '1', true));
        $this->attachParamToPoint($id, $this->createDimensionPoint($manual, '2', true));
        $this->createFit($manual, $od, $id, ['is_fc' => true]);

        $this->actingAs($this->admin())
            ->get(route('workorders.measurements.fc-table', $wo->id))
            ->assertOk()
            ->assertSee('OD pin')
            ->assertSee('ID bush');
    }

    public function test_measurements_data_returns_integer_parameter_id(): void
    {
        // The grid binds measurements to parameters with a strict JS check
        // (m.manual_parameter_id === param.id). Some PDO/PHP setups return the FK
        // as a string ("38"), which breaks the match. The model cast must force int.
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $ic = $this->createInspectionComponent($manual, 'Pin');
        $param = $this->createParameter($manual, $ic, ['description' => 'OD', 'orig_dim_min' => 0.99, 'orig_dim_max' => 1.00]);
        $this->createMeasurement($wo, $param, ['stage' => 'initial', 'actual_value' => 0.995]);

        $json = $this->actingAs($this->admin())
            ->getJson(route('workorders.measurements.data', $wo->id))
            ->assertOk()
            ->json();

        $meas = $json['measurements'][0];
        $this->assertIsInt($meas['manual_parameter_id']);   // int, not "38"
        $this->assertSame($param->id, $meas['manual_parameter_id']);
    }

    public function test_measurements_data_payload_has_expected_top_level_keys(): void
    {
        // Smoke contract: the grid boots from these keys (loadData in _tab).
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $d = $this->actingAs($this->admin())
            ->getJson(route('workorders.measurements.data', $wo->id))
            ->assertOk()
            ->json();

        foreach ([
            'use_wear', 'inspection_components', 'figures', 'parameters', 'measurements',
            'codes', 'missing_code_id', 'ics_with_tdr', 'ics_missing_tdr',
            'ics_tdr_label', 'ics_synced_meas',
        ] as $key) {
            $this->assertArrayHasKey($key, $d, "data() lost the '$key' key the grid depends on");
        }
    }

    public function test_required_bushings_case_b_derives_req_od_from_orig_fit(): void
    {
        // Case B (continuous, no oversize steps): the bushing is manufactured to
        // fit the machined bore. req OD = ID_final + [fit_min, fit_max], where
        // fit_min = OD_orig_min − ID_orig_max, fit_max = OD_orig_max − ID_orig_min.
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $bushIc = $this->createInspectionComponent($manual, 'Bushing 1-540');
        $bushComp = $this->createComponent($manual, ['is_bush' => true, 'part_number' => 'STD-PN-1-540', 'ipl_num' => '1-540']);
        $this->attachComponentToIc($bushIc, $bushComp);
        // OD 0.8012–0.8020 vs bore 0.8000–0.8008 → fit +0.0004…+0.0020 (натяг)
        $od = $this->createParameter($manual, $bushIc, ['description' => 'OD', 'orig_dim_min' => 0.8012, 'orig_dim_max' => 0.8020]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, 'B1', false));

        $housingIc = $this->createInspectionComponent($manual, 'Housing');
        $bore = $this->createParameter($manual, $housingIc, ['description' => 'ID 1-540', 'orig_dim_min' => 0.8000, 'orig_dim_max' => 0.8008]);
        $this->attachParamToPoint($bore, $this->createDimensionPoint($manual, 'B1b', false));

        $this->createFit($manual, $od, $bore, ['is_fc' => false]);
        // Bore machined continuously (final, no repair step) to 0.8040.
        $this->createMeasurement($wo, $bore, ['stage' => 'final', 'result' => 'PASS', 'actual_value' => 0.8040]);

        $html = $this->actingAs($this->admin())
            ->get(route('workorders.measurements.required-bushings', $wo->id))
            ->assertOk()
            ->getContent();

        // req OD = 0.8040 + [0.0004, 0.0020] = 0.8044–0.8060
        $this->assertStringContainsString('req OD 0.8044', $html);
        $this->assertStringContainsString('0.8060', $html);
        $this->assertStringContainsString('manufacture per sketch', $html);
    }

    public function test_measurements_data_payload_ids_are_integers_everywhere(): void
    {
        // Contract: every id/FK in the data() payload is an integer. The grid JS
        // matches ids strictly (===); a string id anywhere silently unbinds data
        // (seen on prod: measurements not shown, callouts rendered as "lbl_25",
        // defect codes duplicated on save).
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $ic = $this->createInspectionComponent($manual, 'Pin');
        $component = $this->createComponent($manual, ['ipl_num' => '1-40']);
        $this->attachComponentToIc($ic, $component);

        $param = $this->createParameter($manual, $ic, [
            'description' => 'OD', 'orig_dim_min' => 0.99, 'orig_dim_max' => 1.00,
        ]);
        $point = $this->createDimensionPoint($manual, 'A1', false, ['child_ic_id' => $ic->id]);
        $this->attachParamToPoint($param, $point);

        $damage = \App\Models\Code::query()->firstOrCreate(['name' => 'Damage'], ['code' => 'D']);
        \App\Models\ManualParameterCode::query()->create([
            'manual_parameter_id' => $param->id,
            'codes_id'            => $damage->id,
            'finding_context'     => 'inspection',
        ]);
        $rule = \App\Models\ManualParameterRepairRule::query()->create([
            'manual_parameter_id' => $param->id, 'name' => 'Rechrome', 'sort_order' => 1,
        ]);
        \App\Models\ManualParameterRuleTrigger::query()->create([
            'repair_rule_id' => $rule->id, 'trigger' => 'finding_inspection', 'codes_id' => $damage->id,
        ]);
        $this->createMeasurement($wo, $param, [
            'stage' => 'initial', 'actual_value' => 0.985, 'result' => 'FAIL', 'codes_id' => $damage->id,
        ]);

        $d = $this->actingAs($this->admin())
            ->getJson(route('workorders.measurements.data', $wo->id))
            ->assertOk()
            ->json();

        // inspection_components
        $icRow = collect($d['inspection_components'])->firstWhere('id', $ic->id);
        $this->assertIsInt($icRow['id']);
        foreach ($icRow['component_ids'] as $cid) $this->assertIsInt($cid);

        // parameters (+ nested codes / rules / triggers / points)
        $pRow = collect($d['parameters'])->firstWhere('id', $param->id);
        $this->assertIsInt($pRow['id']);
        $this->assertIsInt($pRow['inspection_component_id']);
        $this->assertIsInt($pRow['codes'][0]['id']);
        $this->assertIsInt($pRow['repair_rules'][0]['id']);
        $this->assertIsInt($pRow['repair_rules'][0]['triggers'][0]['codes_id']);
        $this->assertIsInt($pRow['points'][0]['id']);
        $this->assertIsInt($pRow['points'][0]['pivot_id']);

        // figures (+ points, child_ic_id)
        $fig = collect($d['figures'])->first(fn ($f) => collect($f['points'])->contains('id', $point->id));
        $this->assertIsInt($fig['id']);
        $ptRow = collect($fig['points'])->firstWhere('id', $point->id);
        $this->assertIsInt($ptRow['id']);
        $this->assertIsInt($ptRow['child_ic_id']);

        // measurements
        $mRow = $d['measurements'][0];
        $this->assertIsInt($mRow['manual_parameter_id']);
        $this->assertIsInt($mRow['codes_id']);
    }

    public function test_measurements_fc_table_numbers_members_when_refs_differ(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);

        $ic = $this->createInspectionComponent($manual, 'Pin');
        $od = $this->createParameter($manual, $ic, ['description' => 'OD pin', 'orig_dim_min' => 0.99, 'orig_dim_max' => 1.00]);
        $id = $this->createParameter($manual, $ic, ['description' => 'ID bush', 'orig_dim_min' => 1.00, 'orig_dim_max' => 1.01]);
        $this->attachParamToPoint($od, $this->createDimensionPoint($manual, 'P1', true));
        $this->attachParamToPoint($id, $this->createDimensionPoint($manual, 'P2', true));
        // Distinct OD/ID Ref.No → Table 8001 per-member numbering.
        $this->createFit($manual, $od, $id, ['is_fc' => true, 'ref_no' => '5', 'id_ref_no' => '4']);

        $html = $this->actingAs($this->admin())
            ->get(route('workorders.measurements.fc-table', $wo->id))
            ->assertOk()
            ->getContent();

        // Each member is its own numbered row (not one merged Ref.No cell).
        $this->assertStringContainsString('data-ref="4"', $html);
        $this->assertStringContainsString('data-ref="5"', $html);
    }
}
