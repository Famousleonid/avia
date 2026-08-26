<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\ManualParameterCode;
use App\Models\ManualParameterRepairRule;
use App\Models\ManualParameterRuleProcess;
use App\Models\ManualParameterRuleTrigger;
use App\Models\ManualProcess;
use App\Models\MasterRule;
use App\Models\MasterRulePhaseRule;
use App\Models\MasterRulePhaseRuleProcess;
use App\Models\Necessary;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WoMeasurement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Multi-fault repair plan assembly and REBUILD (Update processes) — the
 * "part has measurement faults in different places" scenarios.
 *
 * Design reference: docs/repair-routes-and-gates.md §7 (fixes A/B/C).
 */
class RepairPlanRebuildTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private $admin = null;

    /** One admin per test — a second actingAs with another user gets logged out (AuthenticateSession). */
    private function admin()
    {
        return $this->admin ??= $this->createUserWithRole('Admin', ['stamp' => 'RB' . random_int(1, 9999)]);
    }

    /** ProcessName with scope (point|part) → Process → ManualProcess for the manual. */
    private function makeManualProcess($manual, string $name, string $scope): ManualProcess
    {
        $pn = ProcessName::create([
            'name'               => $name . ' ' . uniqid(),
            'scope'              => $scope,
            'process_sheet_name' => $name,
            'form_number'        => 'F-' . random_int(1, 9999),
        ]);
        $proc = Process::create(['process_names_id' => $pn->id, 'process' => 'P-' . uniqid()]);

        return ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $proc->id]);
    }

    /** Repair rule on a parameter with ordered processes and triggers. */
    private function makeRule($param, string $name, array $manualProcesses, array $triggers = [['trigger' => 'below_orig']]): ManualParameterRepairRule
    {
        $rule = ManualParameterRepairRule::create([
            'manual_parameter_id' => $param->id,
            'name'                => $name,
            'action'              => 'repair',
        ]);
        foreach ($triggers as $t) {
            ManualParameterRuleTrigger::create(['repair_rule_id' => $rule->id] + $t);
        }
        foreach ($manualProcesses as $i => $mp) {
            ManualParameterRuleProcess::create([
                'repair_rule_id'    => $rule->id,
                'manual_process_id' => $mp->id,
                'sort_order'        => $i,
            ]);
        }

        return $rule;
    }

    /**
     * Part (IC + component) with two dimensional points on the same part and a
     * shared process vocabulary:
     *   ruleA (point A): Machining(point) → NDT(part) → Plating(part)
     *   ruleB (point B): Machining(point) → NDT(part) → Plating(part)
     */
    private function makeTwoPointPart(): array
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id, // orig limits
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);

        $paramA = $this->createParameter($manual, $ic, [
            'description' => 'Bearing bore', 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1,
        ]);
        $paramB = $this->createParameter($manual, $ic, [
            'description' => 'Main surface', 'orig_dim_min' => 5.0, 'orig_dim_max' => 5.1,
        ]);

        $machining = $this->makeManualProcess($manual, 'Machining', 'point');
        $ndt       = $this->makeManualProcess($manual, 'NDT', 'part');
        $plating   = $this->makeManualProcess($manual, 'Plating', 'part');

        $ruleA = $this->makeRule($paramA, 'Bore repair', [$machining, $ndt, $plating]);
        $ruleB = $this->makeRule($paramB, 'Surface repair', [$machining, $ndt, $plating]);

        return compact('manual', 'wo', 'ic', 'component', 'paramA', 'paramB', 'ruleA', 'ruleB', 'machining', 'ndt', 'plating');
    }

    private function failMeasurement($wo, $param, ?int $ruleId, array $attributes = []): WoMeasurement
    {
        return WoMeasurement::create(array_merge([
            'workorder_id'                    => $wo->id,
            'manual_parameter_id'             => $param->id,
            'stage'                           => 'initial',
            'actual_value'                    => 1.0, // far below any orig_dim_min
            'result'                          => 'FAIL',
            'manual_parameter_repair_rule_id' => $ruleId,
        ], $attributes));
    }

    private function makeRepairTdr($wo, $component): Tdr
    {
        return Tdr::create([
            'tdr_type'          => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id'      => $wo->id,
            'component_id'      => $component->id,
            'serial_number'     => 'NSN',
            'description'       => $component->name,
            'necessaries_id'    => Necessary::firstOrCreate(['name' => 'Repair'])->id,
            'qty'               => 1,
            'use_tdr'           => true,
            'use_process_forms' => true,
        ]);
    }

    private function updateProcesses($wo, $ic, array $extra = [])
    {
        return $this->actingAs($this->admin())
            ->postJson(route('workorders.update-part-processes', $wo->id), array_merge([
                'inspection_component_id' => $ic->id,
            ], $extra));
    }

    private function previewProcesses($wo, $ic, array $extra = [])
    {
        return $this->actingAs($this->admin())
            ->postJson(route('workorders.update-part-processes.preview', $wo->id), array_merge([
                'inspection_component_id' => $ic->id,
            ], $extra));
    }

    /** Plan rows of a TDR in execution order, with their process-name id. */
    private function planRows(Tdr $tdr)
    {
        return TdrProcess::where('tdrs_id', $tdr->id)->orderBy('sort_order')->get();
    }

    // -------------------------------------------------------------------------

    /**
     * Two FAIL points → one merged plan: a Machining row PER point (scope=point),
     * NDT and Plating merged into single part-scope rows, ordered
     * machining → NDT → plating.
     */
    public function test_two_failed_points_merge_into_one_ordered_plan(): void
    {
        $d = $this->makeTwoPointPart();
        $this->failMeasurement($d['wo'], $d['paramA'], $d['ruleA']->id);
        $this->failMeasurement($d['wo'], $d['paramB'], $d['ruleB']->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $rows = $this->planRows($tdr);
        $machiningNameId = $d['machining']->process->process_names_id;
        $ndtNameId       = $d['ndt']->process->process_names_id;
        $platingNameId   = $d['plating']->process->process_names_id;

        $this->assertSame(
            [$machiningNameId, $machiningNameId, $ndtNameId, $platingNameId],
            $rows->pluck('process_names_id')->map(fn ($v) => (int) $v)->all(),
            'Expected Machining per point, then merged NDT, then merged Plating'
        );

        // The merged NDT row accumulates rule processes from BOTH rules.
        $ndtRow = $rows->first(fn ($r) => (int) $r->process_names_id === $ndtNameId);
        $this->assertCount(2, $ndtRow->rule_process_ids, 'Part-scope NDT must merge both rules');
    }

    /**
     * FIX A acceptance (docs/repair-routes-and-gates.md §7-A).
     * Point A machining already STARTED; then point B fails and the plan is
     * rebuilt. The new point-B Machining row must NOT be swallowed by the
     * name-based "already started" skip.
     */
    public function test_started_machining_does_not_swallow_new_points_machining(): void
    {
        $d = $this->makeTwoPointPart();
        $this->failMeasurement($d['wo'], $d['paramA'], $d['ruleA']->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $machiningNameId = $d['machining']->process->process_names_id;
        TdrProcess::where('tdrs_id', $tdr->id)
            ->where('process_names_id', $machiningNameId)
            ->firstOrFail()
            ->update(['date_start' => now()]);

        // New fault in ANOTHER place → rebuild
        $this->failMeasurement($d['wo'], $d['paramB'], $d['ruleB']->id);
        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $machiningRows = TdrProcess::where('tdrs_id', $tdr->id)
            ->where('process_names_id', $machiningNameId)
            ->get();
        $this->assertCount(
            2,
            $machiningRows,
            'Point B needs its own Machining row even though point A machining started'
        );
        $this->assertCount(1, $machiningRows->whereNotNull('date_start'), 'Started row is kept as history');
    }

    /** A FAIL saved before its rule existed is re-resolved during rebuild. */
    public function test_rule_added_after_measurement_is_resolved_on_update(): void
    {
        $d = $this->makeTwoPointPart();
        $this->failMeasurement($d['wo'], $d['paramA'], null); // no rule id stored
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $this->assertGreaterThan(0, $this->planRows($tdr)->count(), 'Rule must be re-resolved from triggers');
        $this->assertSame(
            $d['machining']->process->process_names_id,
            (int) $this->planRows($tdr)->first()->process_names_id
        );
    }

    /**
     * FIX C acceptance (docs/repair-routes-and-gates.md §7-C).
     * A Finish phase rule conditioned on has_defect must fire during rebuild:
     * the defect codes of the part's FAIL measurements must reach the pipeline.
     */
    public function test_defect_codes_reach_finish_conditions_on_update(): void
    {
        $d = $this->makeTwoPointPart();

        $code = Code::create(['name' => 'Corroded ' . uniqid()]);
        ManualParameterCode::create([
            'manual_parameter_id' => $d['paramA']->id,
            'codes_id'            => $code->id,
            'finding_context'     => 'inspection',
        ]);
        ManualParameterRuleTrigger::create([
            'repair_rule_id' => $d['ruleA']->id,
            'trigger'        => 'finding_inspection',
            'codes_id'       => $code->id,
        ]);

        // MasterRule: Finish process applies only when this defect is present.
        $cadPlate = $this->makeManualProcess($d['manual'], 'Cad plate', 'part');
        $master = MasterRule::create([
            'manual_id'               => $d['manual']->id,
            'inspection_component_id' => $d['ic']->id,
            'name'                    => 'Rod plan',
        ]);
        $phaseRule = MasterRulePhaseRule::create([
            'master_rule_id' => $master->id,
            'phase'          => MasterRulePhaseRule::PHASE_FINISH,
            'name'           => 'Re-protect if corroded',
            'condition'      => ['type' => 'has_defect', 'codes_ids' => [$code->id]],
            'sort_order'     => 0,
        ]);
        MasterRulePhaseRuleProcess::create([
            'phase_rule_id'     => $phaseRule->id,
            'manual_process_id' => $cadPlate->id,
            'sort_order'        => 0,
        ]);

        $this->failMeasurement($d['wo'], $d['paramA'], $d['ruleA']->id, ['codes_id' => $code->id]);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $this->assertNotNull(
            $this->planRows($tdr)->first(
                fn ($r) => (int) $r->process_names_id === $cadPlate->process->process_names_id
            ),
            'has_defect Finish rule must fire on rebuild — defect codes must reach the pipeline'
        );
    }

    /**
     * FIX B acceptance (docs/repair-routes-and-gates.md §7-B).
     * When the technician picks a NON-default rule in the TDR dialog, the choice
     * must be persisted on the measurement, so a later rebuild keeps it instead
     * of falling back to the auto-resolved (first matching) rule.
     */
    public function test_chosen_rule_persists_to_measurement_on_tdr_creation(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);
        $param = $this->createParameter($manual, $ic, [
            'description' => 'Bore', 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1,
        ]);

        // TWO rules match the same dimensional FAIL; auto-resolve picks rule1.
        $grind  = $this->makeManualProcess($manual, 'Grinding', 'point');
        $chrome = $this->makeManualProcess($manual, 'Chrome', 'part');
        $rule1 = $this->makeRule($param, 'Grind oversize', [$grind]);
        $rule2 = $this->makeRule($param, 'Chrome build-up', [$chrome]);

        Necessary::firstOrCreate(['name' => 'Repair']);

        $meas = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id,
                'stage'               => 'initial',
                'actual_value'        => 1.0,
            ])->assertCreated()->json();
        $this->assertSame($rule1->id, $meas['manual_parameter_repair_rule_id'], 'Auto-resolve picks the first rule');

        // Technician picks rule2 in the dialog.
        $this->actingAs($this->admin())
            ->postJson(route('workorders.tdr-from-measurement', $wo->id), [
                'wo_measurement_id' => $meas['id'],
                'pn'                => $component->ipl_num,
                'sn'                => null,
                'qty'               => 1,
                'rule_ids'          => [$rule2->id],
            ])->assertCreated();

        $this->assertSame(
            $rule2->id,
            (int) WoMeasurement::find($meas['id'])->manual_parameter_repair_rule_id,
            'The chosen rule must be persisted on the measurement'
        );

        // Rebuild must keep the technician's choice (rule2 → Chrome), not rule1.
        $this->updateProcesses($wo, $ic)->assertOk();
        $tdr = Tdr::where('workorder_id', $wo->id)->where('tdr_type', Tdr::TYPE_COMPONENT_TDR)->firstOrFail();
        $names = $this->planRows($tdr)->pluck('process_names_id')->map(fn ($v) => (int) $v)->all();
        $this->assertContains($chrome->process->process_names_id, $names, 'Plan must follow the chosen rule');
        $this->assertNotContains($grind->process->process_names_id, $names, 'Auto-resolved rule must not override the choice');
    }

    // -------------------------------------------------------------------------
    // Stage 2: Update preview (dry-run diff + rule overrides + action warnings)
    // -------------------------------------------------------------------------

    /** Preview shows the diff (added rows for the new fault) and writes NOTHING. */
    public function test_preview_returns_diff_without_mutating_plan(): void
    {
        $d = $this->makeTwoPointPart();
        $this->failMeasurement($d['wo'], $d['paramA'], $d['ruleA']->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);
        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $rowsBefore = $this->planRows($tdr)->pluck('id')->all();
        $syncBefore = $tdr->fresh()->last_synced_measurement_id;

        // New fault appears → preview only
        $this->failMeasurement($d['wo'], $d['paramB'], $d['ruleB']->id);
        $preview = $this->previewProcesses($d['wo'], $d['ic'])->assertOk()->json();

        $machiningNameId = $d['machining']->process->process_names_id;
        $addedNames = collect($preview['added'])->pluck('name')->all();
        $this->assertContains(
            \App\Models\ProcessName::find($machiningNameId)->name,
            $addedNames,
            'Preview must show the new point machining as added'
        );
        $this->assertNotEmpty($preview['unchanged'], 'Existing NDT/Plating stay in the plan');

        // Dry-run: nothing changed
        $this->assertSame($rowsBefore, $this->planRows($tdr)->pluck('id')->all(), 'Preview must not touch plan rows');
        $this->assertSame($syncBefore, $tdr->fresh()->last_synced_measurement_id, 'Preview must not move the sync point');
    }

    /** Preview lists all matching rules per point; apply with an override follows it and persists. */
    public function test_preview_rule_override_is_applied_and_persisted(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);
        $param = $this->createParameter($manual, $ic, [
            'description' => 'Bore', 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1,
        ]);

        $grind  = $this->makeManualProcess($manual, 'Grinding', 'point');
        $chrome = $this->makeManualProcess($manual, 'Chrome', 'part');
        $rule1 = $this->makeRule($param, 'Grind oversize', [$grind]);
        $rule2 = $this->makeRule($param, 'Chrome build-up', [$chrome]);

        $meas = $this->failMeasurement($wo, $param, null); // auto-resolve → rule1
        $tdr = $this->makeRepairTdr($wo, $component);

        $preview = $this->previewProcesses($wo, $ic)->assertOk()->json();
        $point = collect($preview['points'])->firstWhere('param_id', $param->id);
        $this->assertCount(2, $point['options'], 'Both matching rules are offered');
        $this->assertSame($rule1->id, $point['chosen_rule_id'], 'Auto-resolved rule preselected');

        // Preview with the override reflects the other route
        $preview2 = $this->previewProcesses($wo, $ic, ['rule_overrides' => [$param->id => $rule2->id]])
            ->assertOk()->json();
        $this->assertContains(
            \App\Models\ProcessName::find($chrome->process->process_names_id)->name,
            collect($preview2['added'])->pluck('name')->all()
        );
        $this->assertNull($meas->fresh()->manual_parameter_repair_rule_id, 'Preview must not persist the override');

        // Apply with the override → persisted + plan follows it
        $this->updateProcesses($wo, $ic, ['rule_overrides' => [$param->id => $rule2->id]])->assertOk();
        $this->assertSame($rule2->id, (int) $meas->fresh()->manual_parameter_repair_rule_id);
        $names = $this->planRows($tdr)->pluck('process_names_id')->map(fn ($v) => (int) $v)->all();
        $this->assertContains($chrome->process->process_names_id, $names);
        $this->assertNotContains($grind->process->process_names_id, $names);
    }

    /**
     * Threshold triggers (§8a): the exceedance past the limit picks the route —
     * up to the band → Silver, beyond it → Chrome. No radio needed: exactly one
     * rule matches each measurement.
     */
    public function test_threshold_trigger_routes_by_exceedance(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);

        $silver = $this->makeManualProcess($manual, 'Silver', 'point');
        $chrome = $this->makeManualProcess($manual, 'ChromeHole', 'point');

        // Three identical bores measured at different exceedances
        $mk = function (string $desc) use ($manual, $ic, $silver, $chrome) {
            $param = $this->createParameter($manual, $ic, [
                'description' => $desc, 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1,
            ]);
            $silverRule = ManualParameterRepairRule::create([
                'manual_parameter_id' => $param->id, 'name' => 'Silver restoration', 'action' => 'repair',
            ]);
            ManualParameterRuleTrigger::create([
                'repair_rule_id' => $silverRule->id, 'trigger' => 'above_orig', 'max_delta' => 0.010,
            ]);
            ManualParameterRuleProcess::create([
                'repair_rule_id' => $silverRule->id, 'manual_process_id' => $silver->id, 'sort_order' => 0,
            ]);
            $chromeRule = ManualParameterRepairRule::create([
                'manual_parameter_id' => $param->id, 'name' => 'Chrome in hole', 'action' => 'repair',
            ]);
            ManualParameterRuleTrigger::create([
                'repair_rule_id' => $chromeRule->id, 'trigger' => 'above_orig', 'min_delta' => 0.010,
            ]);
            ManualParameterRuleProcess::create([
                'repair_rule_id' => $chromeRule->id, 'manual_process_id' => $chrome->id, 'sort_order' => 0,
            ]);

            return [$param, $silverRule, $chromeRule];
        };
        [$pSmall, $silverSmall]      = $mk('Bore small wear');
        [$pBig, , $chromeBig]        = $mk('Bore big wear');
        [$pBoundary, $silverBound]   = $mk('Bore boundary wear');

        // store() auto-resolve: exceedance 0.005 → Silver
        $saved = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $pSmall->id, 'stage' => 'initial', 'actual_value' => 10.105,
            ])->assertCreated()->json();
        $this->assertSame('FAIL', $saved['result']);
        $this->assertSame($silverSmall->id, $saved['manual_parameter_repair_rule_id']);

        // rebuild re-resolve: exceedance 0.05 → Chrome; boundary 0.010 (≤) → Silver
        $this->failMeasurement($wo, $pBig, null, ['actual_value' => 10.150]);
        $this->failMeasurement($wo, $pBoundary, null, ['actual_value' => 10.110]);
        $this->makeRepairTdr($wo, $component);

        $preview = $this->previewProcesses($wo, $ic)->assertOk()->json();
        $byParam = collect($preview['points'])->keyBy('param_id');

        $this->assertSame($silverSmall->id, $byParam[$pSmall->id]['chosen_rule_id']);
        $this->assertSame($chromeBig->id, $byParam[$pBig->id]['chosen_rule_id']);
        $this->assertSame($silverBound->id, $byParam[$pBoundary->id]['chosen_rule_id'], 'Exceedance equal to the threshold stays in the band');

        // The band makes the choice unambiguous — exactly one option, no radio
        foreach ([$pSmall->id, $pBig->id, $pBoundary->id] as $pid) {
            $this->assertCount(1, $byParam[$pid]['options'], 'Banded triggers must offer a single route');
        }
    }

    /**
     * FIX D acceptance: a point whose chosen rule has action=order_new is NOT
     * silently merged into the repair plan — it is surfaced as a warning.
     */
    public function test_order_new_rule_is_warned_and_not_merged(): void
    {
        $d = $this->makeTwoPointPart();

        // Point B's route becomes an Order New rule WITH processes (worst case)
        $d['ruleB']->update(['action' => 'order_new']);

        $this->failMeasurement($d['wo'], $d['paramA'], $d['ruleA']->id);
        $this->failMeasurement($d['wo'], $d['paramB'], $d['ruleB']->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $preview = $this->previewProcesses($d['wo'], $d['ic'])->assertOk()->json();
        $warning = collect($preview['warnings'])->firstWhere('param_id', $d['paramB']->id);
        $this->assertNotNull($warning, 'order_new rule must be surfaced as a warning');
        $this->assertSame('order_new', $warning['action']);

        $apply = $this->updateProcesses($d['wo'], $d['ic'])->assertOk()->json();
        $this->assertNotEmpty($apply['warnings']);

        // Plan contains only point A's machining (one row), not B's
        $machiningNameId = $d['machining']->process->process_names_id;
        $machiningRows = $this->planRows($tdr)->where('process_names_id', $machiningNameId);
        $this->assertCount(1, $machiningRows, 'Order New point must not contribute repair processes');
    }
}
