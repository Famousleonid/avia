<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\ManualParameterCode;
use App\Models\ManualParameterRepairRule;
use App\Models\ManualParameterRuleTrigger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Final stage on an inspection-only point (no dimensional limits, only findings)
 * is a RE-INSPECTION verdict:
 *   - no finding code selected  → PASS (defect eliminated)
 *   - a finding code selected   → FAIL (defect persists → EC / Order New gate)
 */
class WoMeasurementFindingFinalTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function admin()
    {
        return $this->createUserWithRole('Admin', ['stamp' => 'FF' . random_int(1, 9999)]);
    }

    /** Inspection-only param (no dims) with a Corroded code + a repair rule triggered by it. */
    private function makeFindingPoint(): array
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $ic = $this->createInspectionComponent($manual, 'Lug');
        $param = $this->createParameter($manual, $ic, ['description' => 'Surface at pt 5']);

        $code = Code::create(['name' => 'Corroded ' . uniqid()]);
        ManualParameterCode::create([
            'manual_parameter_id' => $param->id,
            'codes_id'            => $code->id,
            'finding_context'     => 'inspection',
        ]);

        $rule = ManualParameterRepairRule::create([
            'manual_parameter_id' => $param->id,
            'name'                => 'Blend & re-treat',
            'action'              => 'repair',
        ]);
        ManualParameterRuleTrigger::create([
            'repair_rule_id' => $rule->id,
            'trigger'        => 'finding_inspection',
            'codes_id'       => $code->id,
        ]);

        return [$wo, $param, $code, $rule];
    }

    public function test_final_without_code_on_finding_point_records_pass(): void
    {
        [$wo, $param, $code] = $this->makeFindingPoint();

        // initial: finding recorded → FAIL
        $init = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id,
                'stage'               => 'initial',
                'codes_id'            => $code->id,
            ])->assertCreated()->json();
        $this->assertSame('FAIL', $init['result']);

        // final re-inspection, no code → defect eliminated → PASS
        $fin = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id,
                'stage'               => 'final',
                'replaces_id'         => $init['id'],
                'notes'               => 'Blended, re-inspected — clean',
            ])->assertCreated()->json();

        $this->assertSame('final', $fin['stage']);
        $this->assertSame('PASS', $fin['result']);
        $this->assertNull($fin['actual_value'] ?? null);
    }

    public function test_final_with_code_on_finding_point_records_fail(): void
    {
        [$wo, $param, $code, $rule] = $this->makeFindingPoint();

        $this->createMeasurement($wo, $param, [
            'stage' => 'initial', 'result' => 'FAIL', 'codes_id' => $code->id,
        ]);

        // final re-inspection, defect still present → FAIL + rule chip resolved
        $fin = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id,
                'stage'               => 'final',
                'codes_id'            => $code->id,
            ])->assertCreated()->json();

        $this->assertSame('FAIL', $fin['result']);
        $this->assertSame($rule->id, $fin['manual_parameter_repair_rule_id']);
    }

    public function test_final_with_code_on_dimensional_point_fails_even_when_value_in_limits(): void
    {
        // Uniform semantics: a finding at final overrides a passing dimension.
        $manual = $this->createManual();
        $wo = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $manual->id])->id]);
        $ic = $this->createInspectionComponent($manual, 'Pin');
        $param = $this->createParameter($manual, $ic, [
            'description' => 'OD', 'orig_dim_min' => 0.5000, 'orig_dim_max' => 0.5010,
        ]);
        $code = Code::create(['name' => 'Cracked ' . uniqid()]);
        ManualParameterCode::create([
            'manual_parameter_id' => $param->id,
            'codes_id'            => $code->id,
            'finding_context'     => 'inspection',
        ]);

        $fin = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id,
                'stage'               => 'final',
                'actual_value'        => 0.5005, // in tolerance
                'codes_id'            => $code->id,
            ])->assertCreated()->json();

        $this->assertSame('FAIL', $fin['result']);
    }
}
