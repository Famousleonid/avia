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
    private function makeManualProcess($manual, string $name, string $scope, array $pnAttrs = []): ManualProcess
    {
        $pn = ProcessName::create(array_merge([
            'name'               => $name . ' ' . uniqid(),
            'scope'              => $scope,
            'process_sheet_name' => $name,
            'form_number'        => 'F-' . random_int(1, 9999),
        ], $pnAttrs));
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
        $addedMachining = collect($preview['added'])
            ->first(fn ($g) => $g['name'] === \App\Models\ProcessName::find($machiningNameId)->name);
        $this->assertContains(
            $d['machining']->process->process,
            $addedMachining['ops'],
            'Preview entries must carry the operation texts'
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
     * A wear-mode WO (instruction != Overhaul) whose parameter has NO wear
     * limits falls back to orig limits — orig triggers must match then.
     * Regression: backend used the WO flag for the trigger family while the
     * limits (and the frontend) had already fallen back to orig.
     */
    public function test_orig_triggers_match_on_wear_mode_wo_without_wear_limits(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createInstruction(['name' => 'Repair ' . uniqid()])->id, // wear mode
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);
        $param = $this->createParameter($manual, $ic, [
            'description' => 'Bore', 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1, // no wear limits
        ]);

        $silver = $this->makeManualProcess($manual, 'Silver', 'point');
        $rule = ManualParameterRepairRule::create([
            'manual_parameter_id' => $param->id, 'name' => 'Silver restoration', 'action' => 'repair',
        ]);
        ManualParameterRuleTrigger::create([
            'repair_rule_id' => $rule->id, 'trigger' => 'above_orig', 'max_delta' => 0.010,
        ]);
        ManualParameterRuleProcess::create([
            'repair_rule_id' => $rule->id, 'manual_process_id' => $silver->id, 'sort_order' => 0,
        ]);

        $saved = $this->actingAs($this->admin())
            ->postJson(route('workorders.measurements.store', $wo->id), [
                'manual_parameter_id' => $param->id, 'stage' => 'initial', 'actual_value' => 10.105,
            ])->assertCreated()->json();
        $this->assertSame('FAIL', $saved['result']);
        $this->assertSame($rule->id, $saved['manual_parameter_repair_rule_id'], 'orig trigger must match via limits-source fallback');

        $this->makeRepairTdr($wo, $component);
        $preview = $this->previewProcesses($wo, $ic)->assertOk()->json();
        $point = collect($preview['points'])->firstWhere('param_id', $param->id);
        $this->assertSame($rule->id, $point['chosen_rule_id']);
        $this->assertCount(1, $point['options']);
        $this->assertSame([], $preview['warnings']);
    }

    /**
     * Conditional rule processes (§8b) — the rod's NDT-4 case: the Silver route
     * runs NDT-4 BEFORE silver when chrome is not redone, but when a chrome
     * route is in the same plan, the shared NDT-4 goes AFTER silver.
     */
    public function test_conditional_rule_process_switches_position_by_plan_contents(): void
    {
        $manual = $this->createManual();
        $wo = $this->createWorkorder([
            'unit_id'        => $this->createUnit(['manual_id' => $manual->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $ic = $this->createInspectionComponent($manual, 'Rod');
        $component = $this->createComponent($manual, ['name' => 'Rod']);
        $this->attachComponentToIc($ic, $component);
        $paramOd = $this->createParameter($manual, $ic, [
            'description' => 'OD', 'orig_dim_min' => 1.0, 'orig_dim_max' => 1.1,
        ]);
        $paramId = $this->createParameter($manual, $ic, [
            'description' => 'ID', 'orig_dim_min' => 10.0, 'orig_dim_max' => 10.1,
        ]);

        $chrome = $this->makeManualProcess($manual, 'Chrome plating', 'point');
        $ndt4   = $this->makeManualProcess($manual, 'NDT-4', 'part');
        $silver = $this->makeManualProcess($manual, 'Silver', 'point');
        $paint  = $this->makeManualProcess($manual, 'Paint', 'part');
        $chromeNameId = $chrome->process->process_names_id;

        // Rechrome (OD): Chrome → NDT-4 → Paint
        $this->makeRule($paramOd, 'Rechrome', [$chrome, $ndt4, $paint]);
        // Silver (ID): NDT-4[no chrome in plan] → Silver → NDT-4[chrome in plan] → Paint
        $silverRule = $this->makeRule($paramId, 'Silver restoration', [$ndt4, $silver, $ndt4, $paint]);
        $rows = $silverRule->processes()->orderBy('sort_order')->get();
        $rows[0]->update(['condition' => ['type' => 'not_has_process', 'process_name_ids' => [$chromeNameId]]]);
        $rows[2]->update(['condition' => ['type' => 'has_process', 'process_name_ids' => [$chromeNameId]]]);

        $tdr = $this->makeRepairTdr($wo, $component);
        $nameOf = fn ($mp) => \App\Models\ProcessName::find($mp->process->process_names_id)->name;

        // Silver-only: NDT-4 BEFORE silver
        $this->failMeasurement($wo, $paramId, $silverRule->id);
        $this->updateProcesses($wo, $ic)->assertOk();
        $this->assertSame(
            [$nameOf($ndt4), $nameOf($silver), $nameOf($paint)],
            $this->planRows($tdr)->pluck('process_names_id')
                ->map(fn ($id) => \App\Models\ProcessName::find($id)->name)->all(),
            'Without chrome, NDT-4 runs before silver'
        );

        // Chrome route joins → single NDT-4 AFTER silver
        $this->failMeasurement($wo, $paramOd, null); // resolves to Rechrome
        $this->updateProcesses($wo, $ic)->assertOk();
        $names = $this->planRows($tdr)->pluck('process_names_id')
            ->map(fn ($id) => \App\Models\ProcessName::find($id)->name)->all();
        $this->assertSame(
            [$nameOf($chrome), $nameOf($silver), $nameOf($ndt4), $nameOf($paint)],
            $names,
            'With chrome in the plan, the single shared NDT-4 goes after silver'
        );
    }

    /**
     * plan_order priority (§2): among equally-ready nodes the merger prefers
     * lower plan_order — in-house machining opens the plan even when another
     * route's chain was inserted first.
     */
    public function test_plan_order_puts_machining_headed_chain_first(): void
    {
        $d = $this->makeTwoPointPart(); // ruleA/ruleB both start with Machining (plan_order null)
        $manual = $d['manual'];

        // Point A route starts with vendor Strip (default priority), point B with
        // in-house Blend (plan_order 10). B is created AFTER A — without the
        // priority the plan would open with Strip.
        $strip = $this->makeManualProcess($manual, 'Strip', 'point');
        $blend = $this->makeManualProcess($manual, 'Blend', 'point', ['plan_order' => 10]);
        $ndt   = $d['ndt'];

        $ruleA = $this->makeRule($d['paramA'], 'Vendor first', [$strip, $ndt]);
        $ruleB = $this->makeRule($d['paramB'], 'In-house first', [$blend, $ndt]);

        $this->failMeasurement($d['wo'], $d['paramA'], $ruleA->id);
        $this->failMeasurement($d['wo'], $d['paramB'], $ruleB->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $first = $this->planRows($tdr)->first();
        $this->assertSame(
            $blend->process->process_names_id,
            (int) $first->process_names_id,
            'In-house machining (plan_order 10) must open the plan'
        );
    }

    /**
     * Part-scope merging is per OPERATION: the same process name with the same
     * operation merges into one row across rules; a different operation under
     * the same name stays its own row, in sequence.
     */
    public function test_part_scope_merges_only_same_operation(): void
    {
        $d = $this->makeTwoPointPart();
        $manual = $d['manual'];

        // One Bake NAME, two different operations (AMS 2759/9 vs /11)
        $bakePn = ProcessName::create([
            'name' => 'Bake ' . uniqid(), 'scope' => 'part',
            'process_sheet_name' => 'Bake', 'form_number' => 'F-1',
        ]);
        $bake9  = Process::create(['process_names_id' => $bakePn->id, 'process' => 'AMS 2759/9']);
        $bake11 = Process::create(['process_names_id' => $bakePn->id, 'process' => 'AMS 2759/11']);
        $mp9  = ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $bake9->id]);
        $mp11 = ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $bake11->id]);

        $paramC = $this->createParameter($manual, $d['ic'], [
            'description' => 'Point C', 'orig_dim_min' => 3.0, 'orig_dim_max' => 3.1, 'sort_order' => 2,
        ]);
        $paramD = $this->createParameter($manual, $d['ic'], [
            'description' => 'Point D', 'orig_dim_min' => 4.0, 'orig_dim_max' => 4.1, 'sort_order' => 3,
        ]);
        // C: bake /9 ; D: bake /9 then bake /11 — /9 merges across rules, /11 is its own row
        $ruleC = $this->makeRule($paramC, 'C route', [$mp9]);
        $ruleD = $this->makeRule($paramD, 'D route', [$mp9, $mp11]);

        $this->failMeasurement($d['wo'], $paramC, $ruleC->id);
        $this->failMeasurement($d['wo'], $paramD, $ruleD->id);
        $tdr = $this->makeRepairTdr($d['wo'], $d['component']);

        $this->updateProcesses($d['wo'], $d['ic'])->assertOk();

        $bakeRows = $this->planRows($tdr)->filter(fn ($r) => (int) $r->process_names_id === $bakePn->id)->values();
        $this->assertCount(2, $bakeRows, 'Same name, different operations → two rows');
        $this->assertSame([$bake9->id], array_values($bakeRows[0]->processes), 'First row: /9 merged from both rules');
        $this->assertCount(2, $bakeRows[0]->rule_process_ids, '/9 accumulated from both rules');
        $this->assertSame([$bake11->id], array_values($bakeRows[1]->processes), 'Second row: /11 on its own, after /9');
    }

    /**
     * Consecutive NDT-x rows fold into one combined row (NDT-1 / NDT-4) via
     * plus_process; non-adjacent NDT stay separate.
     */
    public function test_consecutive_ndt_rows_fold_into_combined_row(): void
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

        $ndt1   = $this->makeManualProcess($manual, 'NDT-1', 'part');
        $ndt4   = $this->makeManualProcess($manual, 'NDT-4', 'part');
        $silver = $this->makeManualProcess($manual, 'Silver', 'point');
        $rule = $this->makeRule($param, 'Silver restoration', [$ndt1, $ndt4, $silver]);

        $this->failMeasurement($wo, $param, $rule->id);
        $tdr = $this->makeRepairTdr($wo, $component);

        $preview = $this->previewProcesses($wo, $ic)->assertOk()->json();
        $this->assertStringContainsString(' / ', $preview['added'][0]['name'], 'Preview shows the combined NDT name');

        $this->updateProcesses($wo, $ic)->assertOk();

        $rows = $this->planRows($tdr);
        $this->assertCount(2, $rows, 'NDT-1 and NDT-4 folded → combined NDT + Silver');
        $ndtRow = $rows->first();
        $this->assertSame($ndt1->process->process_names_id, (int) $ndtRow->process_names_id);
        $this->assertSame((string) $ndt4->process->process_names_id, (string) $ndtRow->plus_process);
        $this->assertEqualsCanonicalizing(
            [$ndt1->process->id, $ndt4->process->id],
            array_map('intval', $ndtRow->processes),
            'Combined row carries both NDT operations'
        );
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
