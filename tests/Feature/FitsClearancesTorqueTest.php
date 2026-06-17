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
}
