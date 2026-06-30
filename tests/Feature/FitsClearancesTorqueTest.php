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

    // ---- B1. F&C Document page exposes the torque fill UI (server scaffolding) ----
    // The actual Save-PDF JS gate (block until filled) needs a browser test (Dusk);
    // here we assert the server-rendered prerequisites only.

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
