<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\ManualInspectionComponentVariant;
use App\Models\ManualParameter;
use App\Models\ManualParameterRepairRule;
use App\Models\MasterRule;
use App\Models\MasterRulePhaseRule;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WoMeasurement;
use App\Models\Workorder;
use App\Services\Measurements\PipelineContext;
use App\Services\Measurements\RepairPipeline;
use Illuminate\Http\Request;

class WoMeasurementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function data(Workorder $workorder)
    {
        $manual  = $workorder->unit->manuals;
        $useWear = $workorder->usesWearLimits();

        $inspectionComponents = $manual->inspectionComponents()
            ->with('variants.component')
            ->get()
            ->map(fn($ic) => [
                'id'          => $ic->id,
                'label'       => $ic->label,
                'ipl_nums'    => $ic->variants
                    ->map(fn($v) => $v->component?->ipl_num)
                    ->filter()
                    ->unique()
                    ->values(),
                'part_numbers' => $ic->variants
                    ->map(fn($v) => $v->component?->part_number)
                    ->filter()
                    ->unique()
                    ->values(),
            ]);

        $figures = $manual->dimensionFigures()
            ->with(['points'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($fig) => [
                'id'         => $fig->id,
                'title'      => $fig->title,
                'image_path' => $fig->image_path,
                'points'     => $fig->points->map(fn($pt) => [
                    'id'          => $pt->id,
                    'code'        => $pt->code,
                    'description' => $pt->description,
                    'point_type'  => $pt->point_type,
                    'child_ic_id' => $pt->child_ic_id,
                    'x_pct'       => $pt->x_pct,
                    'y_pct'       => $pt->y_pct,
                    'x2_pct'      => $pt->x2_pct,
                    'y2_pct'      => $pt->y2_pct,
                    'label_x_pct' => $pt->label_x_pct,
                    'label_y_pct' => $pt->label_y_pct,
                ])->values(),
            ]);

        $parameters = ManualParameter::where('manual_id', $manual->id)
            ->with([
                'inspectionComponent',
                'codes.code',
                'repairRules.triggers.code',
                'repairRules.processes.manualProcess.process.process_name',
                'points',
            ])
            ->get()
            ->map(fn($p) => [
                'id'                      => $p->id,
                'inspection_component_id' => $p->inspection_component_id,
                'description'             => $p->description,
                'is_required'             => $p->is_required,
                'orig_dim_min'            => $p->orig_dim_min,
                'orig_dim_max'            => $p->orig_dim_max,
                'wear_dim_min'            => $p->wear_dim_min,
                'wear_dim_max'            => $p->wear_dim_max,
                'codes'                   => $p->codes
                    ->filter(fn($c) => $c->code !== null)
                    ->map(fn($c) => [
                        'id'              => $c->codes_id,
                        'name'            => $c->code->name,
                        'finding_context' => $c->finding_context,
                    ])
                    ->values(),
                'repair_rules'            => $p->repairRules->map(fn($r) => [
                    'id'               => $r->id,
                    'name'             => $r->name,
                    'order_replacement'=> $r->order_replacement,
                    'action'           => $r->action ?? ($r->order_replacement ? 'order_new' : 'repair'),
                    'triggers'         => $r->triggers->map(fn($t) => [
                        'trigger'  => $t->trigger,
                        'codes_id' => $t->codes_id,
                    ])->values(),
                    'processes'        => $r->processes->map(fn($rp) => [
                        'label' => trim(
                            ($rp->manualProcess?->process?->process_name?->name ?? '') .
                            ' — ' .
                            ($rp->manualProcess?->process?->process ?? '')
                        ),
                    ])->values(),
                ])->values(),
                'point_ids' => $p->points->pluck('id')->values(),
            ]);

        $measurements = WoMeasurement::where('workorder_id', $workorder->id)
            ->with(['user'])
            ->get()
            ->map(fn($m) => [
                'id'                           => $m->id,
                'manual_parameter_id'          => $m->manual_parameter_id,
                'stage'                        => $m->stage,
                'replaces_id'                  => $m->replaces_id,
                'actual_value'                 => $m->actual_value,
                'limits_source'                => $m->limits_source,
                'result'                       => $m->result,
                'codes_id'                     => $m->codes_id,
                'manual_parameter_repair_rule_id' => $m->manual_parameter_repair_rule_id,
                'notes'                        => $m->notes,
                'user'                         => $m->user ? ['name' => $m->user->name] : null,
            ]);

        $codes = Code::orderBy('name')->get(['id', 'name', 'code']);
        $missingCodeId = Code::where('name', 'Missing')->value('id');

        $tdrComponentIds = Tdr::where('workorder_id', $workorder->id)
            ->pluck('component_id')->unique()->filter()->all();
        $icsWithTdr = count($tdrComponentIds) > 0
            ? ManualInspectionComponentVariant::whereIn('component_id', $tdrComponentIds)
                ->pluck('inspection_component_id')->unique()->values()->all()
            : [];

        return response()->json([
            'use_wear'             => $useWear,
            'inspection_components'=> $inspectionComponents,
            'figures'              => $figures,
            'parameters'           => $parameters,
            'measurements'         => $measurements,
            'codes'                => $codes,
            'missing_code_id'      => $missingCodeId,
            'ics_with_tdr'         => $icsWithTdr,
        ]);
    }

    public function store(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'manual_parameter_id' => 'required|exists:manual_parameters,id',
            'stage'               => 'required|in:initial,final',
            'replaces_id'         => 'nullable|exists:wo_measurements,id',
            'actual_value'        => 'nullable|numeric',
            'codes_id'            => 'nullable|exists:codes,id',
            'notes'               => 'nullable|string',
        ]);

        $parameter = ManualParameter::with('repairRules.triggers', 'codes')->findOrFail($data['manual_parameter_id']);
        $useWear   = $workorder->usesWearLimits();
        $limits    = $parameter->effectiveLimits($useWear);

        $data['limits_source'] = $limits['source'];
        $dimensionalResult     = $this->computeResult($data['actual_value'] ?? null, $limits);

        // Result of the saved record:
        //  - any finding code selected            → FAIL
        //  - dimensional point with a value        → PASS/FAIL by limits
        //  - inspection-only point, finding=None   → PASS (inspected, no defect)
        //  - dimensional point, no value yet       → null (incomplete)
        $isInspectionOnly = $limits['min'] === null && $limits['max'] === null;
        if (!empty($data['codes_id'])) {
            $data['result'] = 'FAIL';
        } elseif ($dimensionalResult !== null) {
            $data['result'] = $dimensionalResult;
        } elseif ($isInspectionOnly) {
            $data['result'] = 'PASS';
        } else {
            $data['result'] = null;
        }

        $data['user_id']       = auth()->id();
        $data['workorder_id']  = $workorder->id;

        // Determine finding context from the parameter code definition
        $findingContext = null;
        if (!empty($data['codes_id'])) {
            $paramCode = $parameter->codes->firstWhere('codes_id', $data['codes_id']);
            $findingContext = $paramCode?->finding_context;
        }

        // Auto-select repair rule — use dimensional result so inspection
        // findings on passing dimensions don't match dimensional-FAIL rules.
        $data['manual_parameter_repair_rule_id'] = $this->resolveRepairRule(
            $parameter,
            $dimensionalResult,
            $data['codes_id'] ?? null,
            $useWear,
            $findingContext
        );

        $measurement = WoMeasurement::create($data);

        return response()->json($measurement->load(['user']), 201);
    }

    public function fcTable(Workorder $workorder)
    {
        $manual  = $workorder->unit->manuals;
        $useWear = $workorder->usesWearLimits();

        $figures = $manual->dimensionFigures()
            ->with([
                'parentFigure',
                'points' => fn($q) => $q->where('point_type', 'measurement')->orderBy('sort_order'),
                'points.parameters.inspectionComponent.variants.component',
            ])
            ->orderBy('sort_order')
            ->get();

        // Latest measurement per parameter (prefer final over initial)
        $measByParam = WoMeasurement::where('workorder_id', $workorder->id)
            ->get()
            ->groupBy('manual_parameter_id')
            ->map(function ($ms) {
                $finals = $ms->where('stage', 'final');
                return $finals->isNotEmpty()
                    ? $finals->sortBy('id')->last()
                    : $ms->sortBy('id')->last();
            });

        $fcRows    = [];
        $extraRows = [];

        foreach ($figures as $fig) {
            foreach ($fig->points as $pt) {
                $params = $pt->parameters->sortBy('sort_order')->values();

                if ($pt->is_fits_clearance && $params->count() >= 2) {
                    // Clearance is ALWAYS ID(bore) − OD(shaft). Param order in the manual
                    // isn't guaranteed (a point may list OD first), so pick ID/OD explicitly
                    // by description; fall back to sort_order only if they can't be identified.
                    $idParam = $params->first(fn($p) => preg_match('/\bID\b/i', (string) ($p->description ?? '')) === 1);
                    $odParam = $params->first(fn($p) => preg_match('/\bOD\b/i', (string) ($p->description ?? '')) === 1);
                    if ($idParam && $odParam && $idParam->id !== $odParam->id) {
                        $pA = $idParam;
                        $pB = $odParam;
                    } else {
                        $pA = $params[0];
                        $pB = $params[1];
                    }

                    $measA = $measByParam[$pA->id] ?? null;
                    $measB = $measByParam[$pB->id] ?? null;
                    $limA  = $pA->effectiveLimits($useWear);
                    $limB  = $pB->effectiveLimits($useWear);

                    $clearOrigMin = ($pA->orig_dim_min !== null && $pB->orig_dim_max !== null)
                        ? round((float)$pA->orig_dim_min - (float)$pB->orig_dim_max, 4) : null;
                    $clearOrigMax = ($pA->orig_dim_max !== null && $pB->orig_dim_min !== null)
                        ? round((float)$pA->orig_dim_max - (float)$pB->orig_dim_min, 4) : null;

                    $aWearMin = $pA->wear_dim_min ?? $pA->orig_dim_min;
                    $aWearMax = $pA->wear_dim_max ?? $pA->orig_dim_max;
                    $bWearMin = $pB->wear_dim_min ?? $pB->orig_dim_min;
                    $bWearMax = $pB->wear_dim_max ?? $pB->orig_dim_max;

                    $permClearMax = ($aWearMax !== null && $bWearMin !== null)
                        ? round((float)$aWearMax - (float)$bWearMin, 4) : null;

                    $actualClear = ($measA?->actual_value !== null && $measB?->actual_value !== null)
                        ? round((float)$measA->actual_value - (float)$measB->actual_value, 4) : null;

                    $fcRows[] = [
                        'fig'          => $fig,
                        'pt'           => $pt,
                        'pA'           => $pA,
                        'pB'           => $pB,
                        'measA'        => $measA,
                        'measB'        => $measB,
                        'compA'        => $pA->inspectionComponent?->variants->first()?->component,
                        'compB'        => $pB->inspectionComponent?->variants->first()?->component,
                        'limA'         => $limA,
                        'limB'         => $limB,
                        'clearOrigMin' => $clearOrigMin,
                        'clearOrigMax' => $clearOrigMax,
                        'aWearMin'     => $aWearMin,
                        'aWearMax'     => $aWearMax,
                        'bWearMin'     => $bWearMin,
                        'bWearMax'     => $bWearMax,
                        'permClearMax' => $permClearMax,
                        'actualClear'  => $actualClear,
                        'resultA'      => $this->computeResult($measA?->actual_value, $limA),
                        'resultB'      => $this->computeResult($measB?->actual_value, $limB),
                    ];

                } elseif (!$pt->is_fits_clearance && $params->isNotEmpty()) {
                    foreach ($params as $param) {
                        $meas = $measByParam[$param->id] ?? null;
                        $lim  = $param->effectiveLimits($useWear);
                        $extraRows[] = [
                            'fig'    => $fig,
                            'pt'     => $pt,
                            'param'  => $param,
                            'meas'   => $meas,
                            'comp'   => $param->inspectionComponent?->variants->first()?->component,
                            'lim'    => $lim,
                            'result' => $this->computeResult($meas?->actual_value, $lim),
                        ];
                    }
                }
            }
        }

        return view('admin.measurements._fc-table', compact('fcRows', 'extraRows', 'workorder', 'useWear'));
    }

    public function componentByIpl(Request $request, Workorder $workorder)
    {
        $ipl = trim($request->query('ipl_num', ''));
        if ($ipl === '') {
            return response()->json([]);
        }

        $manualId   = $workorder->unit->manual_id;
        $components = Component::where('manual_id', $manualId)
            ->where('ipl_num', 'like', $ipl . '%')
            ->orderBy('ipl_num')
            ->get(['id', 'ipl_num', 'part_number', 'name', 'units_assy'])
            ->filter(function ($c) use ($ipl) {
                $suffix = substr($c->ipl_num, strlen($ipl));
                return $suffix === '' || ctype_alpha($suffix[0]);
            })
            ->values()
            ->map(fn($c) => [
                'id'          => $c->id,
                'ipl_num'     => $c->ipl_num,
                'part_number' => $c->part_number,
                'name'        => $c->name,
                'units_assy'  => $c->units_assy ?? 1,
            ]);

        return response()->json($components);
    }

    public function createTdrFromMeasurement(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'wo_measurement_id' => 'required|exists:wo_measurements,id',
            'missing_meas_id'   => 'nullable|exists:wo_measurements,id',
            'pn'                => 'required|string|max:100',
            'sn'                => 'nullable|string|max:100',
            'qty'               => 'nullable|integer|min:1',
            'rule_ids'           => 'nullable|array',
            'rule_ids.*'         => 'exists:manual_parameter_repair_rules,id',
            'no_rule'            => 'nullable|boolean',
            'order_new_override' => 'nullable|boolean',
        ]);

        $measurement = WoMeasurement::findOrFail($data['wo_measurement_id']);
        if ((int) $measurement->workorder_id !== $workorder->id) {
            return response()->json(['error' => 'Measurement does not belong to this workorder'], 422);
        }

        $manualId  = $workorder->unit->manual_id;
        $component = Component::where('ipl_num', $data['pn'])
            ->where('manual_id', $manualId)
            ->first();
        if (!$component) {
            return response()->json(['error' => "Component with IPL# '{$data['pn']}' not found in this manual"], 422);
        }

        $qty  = $data['qty'] ?? 1;
        $sn   = $data['sn'] ?: 'NSN';
        $desc = $component->name;

        // ── Missing Part ─────────────────────────────────────────────
        if (!empty($data['missing_meas_id'])) {
            $missingCode      = Code::where('name', 'Missing')->first();
            $necessary        = Necessary::where('name', 'Order New')->firstOrFail();
            $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();

            $tdr = Tdr::create([
                'tdr_type'           => Tdr::TYPE_ORDER_NEW,
                'workorder_id'       => $workorder->id,
                'component_id'       => $component->id,
                'order_component_id' => $component->id,   // Order New = same part ordered new
                'serial_number'      => $sn,
                'description'        => $desc,
                'codes_id'           => $missingCode?->id,
                'conditions_id'      => $missingCondition?->id,
                'necessaries_id'     => $necessary->id,
                'qty'                => $qty,
                'use_tdr'            => false,
                'use_process_forms'  => false,
            ]);

            if (!$workorder->part_missing) {
                $workorder->part_missing = true;
                $workorder->save();
            }

            return response()->json([
                'tdr_id'   => $tdr->id,
                'tdr_type' => $tdr->tdr_type,
                'component'=> $component->part_number . ' — ' . $component->name,
            ], 201);
        }

        $ruleIds = $data['rule_ids'] ?? [];

        // codes_id: use measurement's own finding code if set;
        // for dimensional FAILs (no finding selected) fall back to the parameter's
        // measurement-context finding code (e.g. "Worn" on an OD parameter).
        $codesId = $measurement->codes_id;
        if (!$codesId && $measurement->result === 'FAIL') {
            $parameter = ManualParameter::with('codes')->find($measurement->manual_parameter_id);
            $codesId = $parameter?->codes
                ->first(fn($c) => $c->finding_context === 'measurement')
                ?->codes_id;
        }

        // condition_id by defect: condition with the same name as the finding code
        // (Worn code -> Worn condition). Name match is case-insensitive in MySQL.
        $conditionId = null;
        if ($codesId) {
            $code = Code::find($codesId);
            if ($code) {
                $conditionId = Condition::where('name', $code->name)->first()?->id;
            }
        }

        // ── Order New override (no rule, user chose Order New manually) ─
        if (!empty($data['order_new_override'])) {
            $necessary = Necessary::where('name', 'Order New')->firstOrFail();
            $tdr = Tdr::create([
                'tdr_type'           => Tdr::TYPE_ORDER_NEW,
                'workorder_id'       => $workorder->id,
                'component_id'       => $component->id,
                'order_component_id' => $component->id,   // Order New = same part ordered new
                'serial_number'      => $sn,
                'description'        => $desc,
                'codes_id'           => $codesId,
                'conditions_id'      => $conditionId,
                'necessaries_id'     => $necessary->id,
                'qty'                => $qty,
                'use_tdr'            => true,
                'use_process_forms'  => false,
            ]);
            $tdr->update(['use_tdr' => true]);
            return response()->json([
                'tdr_id'   => $tdr->id,
                'tdr_type' => $tdr->tdr_type,
                'component'=> $component->part_number . ' — ' . $component->name,
            ], 201);
        }

        // ── Chosen rule's action: repair | order_new | ec ────────────
        $firstRule  = !empty($ruleIds) ? ManualParameterRepairRule::find($ruleIds[0]) : null;
        $ruleAction = $firstRule
            ? ($firstRule->action ?? ($firstRule->order_replacement ? 'order_new' : 'repair'))
            : 'repair';

        // ── Order New (action = order_new) ───────────────────────────
        if ($ruleAction === 'order_new') {
            $necessary = Necessary::where('name', 'Order New')->firstOrFail();
            $tdr = Tdr::create([
                'tdr_type'           => Tdr::TYPE_ORDER_NEW,
                'workorder_id'       => $workorder->id,
                'component_id'       => $component->id,
                'order_component_id' => $component->id,   // Order New = same part ordered new
                'serial_number'      => $sn,
                'description'        => $desc,
                'codes_id'           => $codesId,
                'conditions_id'      => $conditionId,
                'necessaries_id'     => $necessary->id,
                'qty'                => $qty,
                'use_tdr'            => true,
                'use_process_forms'  => false,
            ]);
            $tdr->update(['use_tdr' => true]);
            return response()->json([
                'tdr_id'   => $tdr->id,
                'tdr_type' => $tdr->tdr_type,
                'component'=> $component->part_number . ' — ' . $component->name,
            ], 201);
        }

        // ── Repair / EC: combine processes from selected rules ───────
        // EC = repair under OEM concession. The part is machined at ALL its points
        // (one machining covers the whole part), then submitted for concession:
        // the EC plan keeps ONLY Start + machining processes + the EC process;
        // everything else (plating, NDT, paint, Finish) is held until granted.
        $isEc = ($ruleAction === 'ec');
        $necessary = Necessary::where('name', 'Repair')->firstOrFail();
        $tdr = Tdr::create([
            'tdr_type'          => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id'      => $workorder->id,
            'component_id'      => $component->id,
            'serial_number'     => $sn,
            'description'       => $desc,
            'codes_id'          => $codesId,
            'conditions_id'     => $conditionId,
            'necessaries_id'    => $necessary->id,
            'qty'               => $qty,
            'use_tdr'           => true,
            'use_process_forms' => true,
        ]);
        $tdr->update(['use_tdr' => true]);

        // Run repair pipeline: Start (part rules) -> Main (point rules) -> Finish (part rules).
        // If the part has no MasterRule, only Main runs — same result as the previous flat merge.
        $parameter = ManualParameter::find($measurement->manual_parameter_id);

        // EC machines the WHOLE part: aggregate the matched rules of EVERY failed
        // point of this inspection component (+ the chosen EC rule). Regular repair
        // uses only the selected rule(s).
        $pipelineRuleIds = $ruleIds;
        if ($isEc) {
            $partParamIds = ManualParameter::where('inspection_component_id', $parameter?->inspection_component_id)
                ->pluck('id');
            $pipelineRuleIds = WoMeasurement::where('workorder_id', $workorder->id)
                ->whereIn('manual_parameter_id', $partParamIds)
                ->where('result', 'FAIL')
                ->whereNotNull('manual_parameter_repair_rule_id')
                ->pluck('manual_parameter_repair_rule_id')
                ->merge($ruleIds)
                ->unique()->values()->all();
        }

        $ctx = new PipelineContext();
        $ctx->inspectionComponentId = $parameter?->inspection_component_id;
        $ctx->mainRuleIds           = $pipelineRuleIds;
        $ctx->defectCodeIds         = array_values(array_filter([$codesId]));
        $ctx->heldPendingEc         = $isEc; // EC → hold the Finish phase until granted

        app(RepairPipeline::class)->run($ctx);

        // EC keeps only Start + machining-type Main processes (e.g. Machining, Machining (EC)).
        $machiningNameIds = $isEc
            ? \App\Models\ProcessName::where('name', 'like', '%Machining%')->pluck('id')->map(fn($id) => (int) $id)->all()
            : [];

        $maxSort = 0;
        foreach ($ctx->processGroups as $group) {
            if ($isEc) {
                $keep = ($group['phase'] ?? '') === 'start'
                    || (($group['phase'] ?? '') === 'main'
                        && in_array((int) $group['process_names_id'], $machiningNameIds, true));
                if (!$keep) {
                    continue; // drop plating/NDT/paint/etc. — held until concession granted
                }
            }
            // Description = per-process notes from the rule (Main + Start/Finish),
            // computed by the pipeline. Empty when the process has no note — no fallback.
            TdrProcess::create([
                'tdrs_id'          => $tdr->id,
                'process_names_id' => $group['process_names_id'],
                'processes'        => $group['process_ids'],
                'rule_process_ids' => $group['rule_process_ids'] ?? [],
                'description'      => $group['description'] ?? '',
                'sort_order'       => $group['sort_order'],
                'in_traveler'      => false,
            ]);
            $maxSort = max($maxSort, (int) $group['sort_order']);
        }

        // EC: add the EC process (tracked on /ec) after the machining processes.
        if ($isEc) {
            $ecNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
            if ($ecNameId) {
                TdrProcess::create([
                    'tdrs_id'            => $tdr->id,
                    'process_names_id'   => $ecNameId,
                    'processes'          => [],
                    'rule_process_ids'   => [],
                    'description'        => '',
                    'sort_order'         => $maxSort + 1,
                    'in_traveler'        => false,
                    'ec'                 => 1,     // EC-related (read by SP Form / TDR-print)
                    'standalone_ec_only' => false, // always a companion to Machining (EC), never standalone
                ]);
            }
        }

        return response()->json([
            'tdr_id'     => $tdr->id,
            'tdr_type'   => $tdr->tdr_type,
            'component'  => $component->part_number . ' — ' . $component->name,
        ], 201);
    }

    /**
     * EC gate (Path A) — evaluate the post-Main results of a part.
     * A repaired point = a parameter that FAILed at the initial measurement.
     * The gate is READY once every repaired point has a final (post-repair) measurement;
     * each is PASS if it landed in tolerance or an oversize repair step (gatePass).
     */
    public function gateEvaluate(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'inspection_component_id' => 'required|integer',
        ]);

        $useWear = $workorder->usesWearLimits();
        $params  = ManualParameter::where('inspection_component_id', $data['inspection_component_id'])
            ->with(['repairSteps', 'points'])
            ->get();

        $measByParam = WoMeasurement::where('workorder_id', $workorder->id)
            ->whereIn('manual_parameter_id', $params->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('manual_parameter_id');

        $points  = [];
        $allPass = true;
        $ready   = true;

        foreach ($params as $param) {
            $ms = $measByParam[$param->id] ?? collect();
            // repaired point = failed at initial inspection
            $hadFail = $ms->contains(fn($m) => $m->stage === 'initial' && $m->result === 'FAIL');
            if (!$hadFail) {
                continue;
            }
            $final = $ms->where('stage', 'final')->last();
            if (!$final) {
                $ready = false; // a repaired point still has no final measurement
                continue;
            }
            $value  = $final->actual_value !== null ? (float) $final->actual_value : null;
            $limits = $param->effectiveLimits($useWear);
            $pass   = $this->gatePass($value, $limits, $param->repairSteps);
            if (!$pass) {
                $allPass = false;
            }
            $points[] = [
                'param_id'    => $param->id,
                'description' => $param->description,
                'pt_codes'    => $param->points->pluck('code')->filter()->unique()->values()->implode(', '),
                'final_value' => $value,
                'pass'        => $pass,
            ];
        }

        return response()->json([
            'ready'    => $ready && count($points) > 0,
            'all_pass' => $allPass,
            'points'   => $points,
        ]);
    }

    /**
     * EC gate (Path A) — apply the technician's confirmed outcome.
     *   finish    → nothing (the plan, incl. post-NDT, proceeds as is)
     *   ec        → hold everything after NDT (stage post+finish removed) + add EC process
     *   order_new → (TODO) condemn → Order New
     */
    public function gateApply(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'inspection_component_id' => 'required|integer',
            'outcome'                 => 'required|in:finish,ec,order_new',
            'ndt_pass'                => 'boolean',
        ]);

        $componentIds = ManualInspectionComponentVariant::where('inspection_component_id', $data['inspection_component_id'])
            ->pluck('component_id');
        $tdr = Tdr::where('workorder_id', $workorder->id)
            ->whereIn('component_id', $componentIds)
            ->where('tdr_type', Tdr::TYPE_COMPONENT_TDR)
            ->latest('id')
            ->first();
        if (!$tdr) {
            return response()->json(['error' => 'No repair TDR found for this part'], 404);
        }

        if ($data['outcome'] === 'finish') {
            return response()->json(['ok' => true, 'outcome' => 'finish']);
        }

        if ($data['outcome'] === 'ec') {
            // Hold everything after the NDT gate: remove not-yet-started post+finish processes…
            TdrProcess::where('tdrs_id', $tdr->id)
                ->whereIn('process_names_id', $this->heldOnEcProcessNameIds())
                ->whereNull('date_start')
                ->delete();
            // …and add the EC process (companion to Machining (EC); read by SP Form / TDR-print).
            $ecNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
            if ($ecNameId && !TdrProcess::where('tdrs_id', $tdr->id)->where('process_names_id', $ecNameId)->exists()) {
                $maxSort = TdrProcess::where('tdrs_id', $tdr->id)->max('sort_order') ?? 0;
                TdrProcess::create([
                    'tdrs_id'            => $tdr->id,
                    'process_names_id'   => $ecNameId,
                    'processes'          => [],
                    'rule_process_ids'   => [],
                    'description'        => '',
                    'sort_order'         => $maxSort + 1,
                    'in_traveler'        => false,
                    'ec'                 => 1,
                    'standalone_ec_only' => false,
                ]);
            }
            return response()->json(['ok' => true, 'outcome' => 'ec', 'tdr_id' => $tdr->id]);
        }

        // order_new — condemn → Order New (TODO)
        return response()->json(['ok' => false, 'outcome' => 'order_new', 'message' => 'Order New from the gate is not implemented yet.'], 501);
    }

    /**
     * B1 — Revert the TDR(s) of a part so a new decision can be made.
     * Allowed ONLY while no work has started (no TdrProcess has date_start).
     * If work started, the caller must use scrap (B2) instead.
     */
    public function revertPartTdr(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'inspection_component_id' => 'required|exists:manual_inspection_components,id',
        ]);

        $componentIds = ManualInspectionComponentVariant::where('inspection_component_id', $data['inspection_component_id'])
            ->pluck('component_id');

        $tdrs = Tdr::where('workorder_id', $workorder->id)
            ->where(function ($q) use ($componentIds) {
                $q->whereIn('component_id', $componentIds)
                  ->orWhereIn('order_component_id', $componentIds);
            })
            ->get();

        if ($tdrs->isEmpty()) {
            return response()->json(['error' => 'No TDR found for this part'], 404);
        }

        $tdrIds = $tdrs->pluck('id');

        // Work started? any process with date_start
        $started = TdrProcess::whereIn('tdrs_id', $tdrIds)->whereNotNull('date_start')->exists();
        if ($started) {
            return response()->json([
                'error' => 'Work has already started on this TDR — it cannot be reverted. Use Scrap & Order New instead.',
            ], 422);
        }

        TdrProcess::whereIn('tdrs_id', $tdrIds)->delete();
        Tdr::whereIn('id', $tdrIds)->delete();

        return response()->json(['ok' => true, 'deleted_tdrs' => $tdrIds->count()]);
    }

    public function destroy(WoMeasurement $woMeasurement)
    {
        $woMeasurement->delete();

        return response()->json(['ok' => true]);
    }

    private function computeResult(?float $value, array $limits): ?string
    {
        if ($value === null) return null;

        if ($limits['min'] !== null && $limits['max'] !== null) {
            return ($value >= $limits['min'] && $value <= $limits['max']) ? 'PASS' : 'FAIL';
        }

        return null;
    }

    /**
     * EC gate (Path A) — a repaired point PASSes if its final measured value landed
     * within the orig/wear tolerance OR within any oversize repair step. Anything
     * outside both = FAIL → EC / Order New.
     *
     * @param \Illuminate\Support\Collection|\App\Models\ManualRepairStep[] $repairSteps
     */
    private function gatePass(?float $value, array $limits, $repairSteps): bool
    {
        if ($value === null) {
            return false;
        }
        // within the general tolerance (orig or wear, per the WO instruction)
        if ($limits['min'] !== null && $limits['max'] !== null
            && $value >= (float) $limits['min'] && $value <= (float) $limits['max']) {
            return true;
        }
        // within any allowed oversize repair step
        foreach ($repairSteps as $s) {
            if ($s->dim_min !== null && $s->dim_max !== null
                && $value >= (float) $s->dim_min && $value <= (float) $s->dim_max) {
                return true;
            }
        }
        return false;
    }

    /**
     * EC gate (Path A) — process_names held when a part goes to EC:
     * everything AFTER the NDT gate, i.e. stage `post` + `finish`.
     *
     * @return int[] process_names_id
     */
    private function heldOnEcProcessNameIds(): array
    {
        return \App\Models\ProcessName::whereIn('stage', ['post', 'finish'])
            ->pluck('id')->map(fn($id) => (int) $id)->all();
    }

    /**
     * EC gate (Path A) — Finish-phase process_names of the part's MasterRule.
     * Used to identify the Finish processes (pipeline OR manual — keyed by
     * process_names_id) that must be removed/held when a part goes to EC.
     * Returns [] when the part has no MasterRule (no Finish phase defined).
     *
     * @return int[] process_names_id
     */
    private function finishProcessNameIds(?int $inspectionComponentId): array
    {
        if (!$inspectionComponentId) {
            return [];
        }
        $masterRule = MasterRule::with('phaseRules.processes.manualProcess.process')
            ->where('inspection_component_id', $inspectionComponentId)
            ->first();
        if (!$masterRule) {
            return [];
        }
        $ids = [];
        foreach ($masterRule->phaseRules->where('phase', MasterRulePhaseRule::PHASE_FINISH) as $rule) {
            foreach ($rule->processes as $rp) {
                $pnId = $rp->manualProcess?->process?->process_names_id;
                if ($pnId) {
                    $ids[] = (int) $pnId;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    private function resolveRepairRule(ManualParameter $parameter, ?string $result, ?int $codesId, bool $useWear, ?string $findingContext = null): ?int
    {
        $rules = $parameter->repairRules;

        if ($codesId) {
            if ($findingContext === 'measurement') {
                // Finding came from measurement context → match finding_measurement triggers
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_measurement' && (int)$t->codes_id === $codesId)) {
                        return $rule->id;
                    }
                }
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_measurement' && $t->codes_id === null)) {
                        return $rule->id;
                    }
                }
            } else {
                // Finding came from inspection context → match finding_inspection / legacy finding
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_inspection' && (int)$t->codes_id === $codesId)) {
                        return $rule->id;
                    }
                }
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding' && (int)$t->codes_id === $codesId)) {
                        return $rule->id;
                    }
                }
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => in_array($t->trigger, ['finding_inspection', 'finding']) && $t->codes_id === null)) {
                        return $rule->id;
                    }
                }
            }
        }

        // Dimensional FAIL (no explicit finding selected)
        if ($result === 'FAIL') {
            $failTriggers = $useWear ? ['below_wear', 'above_wear'] : ['below_orig', 'above_orig'];
            foreach ($rules as $rule) {
                if ($rule->triggers->contains(fn($t) => in_array($t->trigger, $failTriggers))) {
                    return $rule->id;
                }
            }
            foreach ($rules as $rule) {
                if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_measurement')) {
                    return $rule->id;
                }
            }
        }

        return null;
    }
}
