<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\ManualInspectionComponentVariant;
use App\Models\ManualParameter;
use App\Models\ManualParameterRepairRule;
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
                'is_bush' => $ic->variants->contains(fn($v) => $v->component?->is_bush),
                'component_ids' => $ic->variants
                    ->map(fn($v) => $v->component_id)
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

        $rawParameters = ManualParameter::where('manual_id', $manual->id)
            ->with([
                'inspectionComponent',
                'codes.code',
                'repairRules.triggers.code',
                'repairRules.processes.manualProcess.process.process_name',
                'points',
                'repairSteps.component',
            ])
            ->get();

        $parameters = $rawParameters->map(fn($p) => [
                'id'                      => $p->id,
                'inspection_component_id' => $p->inspection_component_id,
                'description'             => $p->description,
                'is_required'             => $p->is_required,
                'requires_value'          => $p->requires_value,
                'qty'                     => $p->qty,
                'orig_dim_min'            => $p->orig_dim_min,
                'orig_dim_max'            => $p->orig_dim_max,
                'wear_dim_min'            => $p->wear_dim_min,
                'wear_dim_max'            => $p->wear_dim_max,
                'repair_dim_min'          => $p->repair_dim_min,
                'repair_dim_max'          => $p->repair_dim_max,
                'interference_value'      => $p->interference_value,
                'flange_clearance'        => $p->flange_clearance,
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
                'points'              => $p->points->map(fn($pt) => [
                    'id'       => $pt->id,
                    'pivot_id' => $pt->pivot->id,
                ])->values(),
                'repair_surface_side' => $p->repair_surface_side,
                'max_repair_depth_a'  => $p->max_repair_depth_a,
                'max_repair_depth_b'  => $p->max_repair_depth_b,
                'repair_steps'        => $p->repairSteps->map(fn($s) => [
                    'step_no'        => $s->step_no,
                    'dim_min'        => $s->dim_min,
                    'dim_max'        => $s->dim_max,
                    'after_dim_min'  => $s->after_dim_min,
                    'after_dim_max'  => $s->after_dim_max,
                    'component_id'   => $s->component_id,
                    'component_pn'   => $s->component?->part_number,
                    'component_ipl'  => $s->component?->ipl_num,
                ])->values(),
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
                'repair_step_no'               => $m->repair_step_no,
                'repair_depth_a'               => $m->repair_depth_a,
                'repair_depth_b'               => $m->repair_depth_b,
                'codes_id'                     => $m->codes_id,
                'manual_parameter_repair_rule_id' => $m->manual_parameter_repair_rule_id,
                'notes'                        => $m->notes,
                'user'                         => $m->user ? ['name' => $m->user->name] : null,
            ]);

        $codes = Code::orderBy('name')->get(['id', 'name', 'code']);
        $missingCodeId = Code::where('name', 'Missing')->value('id');

        $tdrComponentIds = Tdr::where('workorder_id', $workorder->id)
            ->pluck('component_id')->unique()->filter()->values()->all();

        // Build ic_id → tdr label map
        // Uses ipl_num bridge: TDR and IC variant may reference different component
        // records in the components table even for the same physical part (same ipl_num).
        $icsTdrLabel = [];
        $priority    = ['missing' => 3, 'order new' => 2, 'repair' => 1, 'tdr' => 0];

        if (count($tdrComponentIds) > 0) {
            $tdrs = Tdr::where('workorder_id', $workorder->id)
                ->whereIn('component_id', $tdrComponentIds)
                ->get(['tdr_type', 'codes_id', 'component_id']);

            $codeNamesById = Code::whereIn('id', $tdrs->pluck('codes_id')->filter()->unique())
                ->pluck('name', 'id');

            // Step 1: label per component_id (highest priority wins per comp)
            $tdrLabelByComponent = [];
            foreach ($tdrs as $tdr) {
                $isMissingTdr = $missingCodeId && (int)$tdr->codes_id === (int)$missingCodeId
                                && $tdr->tdr_type === Tdr::TYPE_ORDER_NEW;
                $codeName  = $tdr->codes_id ? ($codeNamesById[$tdr->codes_id] ?? null) : null;
                $typeLabel = $tdr->tdr_type === Tdr::TYPE_ORDER_NEW  ? 'Order New'
                           : ($tdr->tdr_type === Tdr::TYPE_COMPONENT_TDR ? 'Repair' : 'TDR');
                $label = $isMissingTdr ? 'missing'
                       : ($codeName ? $codeName . ', ' . $typeLabel : strtolower($typeLabel));
                if (!isset($tdrLabelByComponent[$tdr->component_id]) ||
                    ($priority[$label] ?? 0) > ($priority[$tdrLabelByComponent[$tdr->component_id]] ?? 0)) {
                    $tdrLabelByComponent[$tdr->component_id] = $label;
                }
            }

            // Step 2: bridge via ipl_num → build ipl → label map
            $iplByTdrComp = Component::whereIn('id', $tdrComponentIds)
                ->pluck('ipl_num', 'id');

            $tdrLabelByIpl = [];
            foreach ($tdrLabelByComponent as $compId => $label) {
                $ipl = $iplByTdrComp[$compId] ?? null;
                if (!$ipl) continue;
                if (!isset($tdrLabelByIpl[$ipl]) ||
                    ($priority[$label] ?? 0) > ($priority[$tdrLabelByIpl[$ipl]] ?? 0)) {
                    $tdrLabelByIpl[$ipl] = $label;
                }
            }

            // Step 3: find component_ids in THIS manual with those ipl_nums
            $manualCompsByIpl = Component::where('manual_id', $manual->id)
                ->whereIn('ipl_num', array_keys($tdrLabelByIpl))
                ->get(['id', 'ipl_num'])
                ->keyBy('id'); // id → Component

            // Step 4: find IC variants for those component_ids, build icsTdrLabel
            $variants = ManualInspectionComponentVariant::whereIn('component_id', $manualCompsByIpl->keys())
                ->get(['inspection_component_id', 'component_id']);

            foreach ($variants as $v) {
                $ipl   = $manualCompsByIpl[$v->component_id]?->ipl_num ?? null;
                $label = $ipl ? ($tdrLabelByIpl[$ipl] ?? 'tdr') : 'tdr';
                $icId  = $v->inspection_component_id;
                if (!isset($icsTdrLabel[$icId]) ||
                    ($priority[$label] ?? 0) > ($priority[$icsTdrLabel[$icId]] ?? 0)) {
                    $icsTdrLabel[$icId] = $label;
                }
            }
        }

        return response()->json([
            'use_wear'             => $useWear,
            'inspection_components'=> $inspectionComponents,
            'figures'              => $figures,
            'parameters'           => $parameters,
            'measurements'         => $measurements,
            'codes'                => $codes,
            'missing_code_id'      => $missingCodeId,
            'ics_with_tdr'         => array_keys($icsTdrLabel),
            'ics_missing_tdr'      => array_keys(array_filter($icsTdrLabel, fn($l) => $l === 'missing')),
            'ics_tdr_label'        => $icsTdrLabel,   // ic_id → 'repair'|'order new'|'missing'|'tdr'
        ]);
    }

    public function store(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'manual_parameter_id' => 'required|exists:manual_parameters,id',
            'stage'               => 'required|in:initial,final',
            'replaces_id'         => 'nullable|exists:wo_measurements,id',
            'actual_value'        => 'nullable|numeric',
            'repair_depth_a'      => 'nullable|numeric',
            'repair_depth_b'      => 'nullable|numeric',
            'codes_id'            => 'nullable|exists:codes,id',
            'notes'               => 'nullable|string',
        ]);

        $parameter = ManualParameter::with('repairRules.triggers', 'codes', 'repairSteps')->findOrFail($data['manual_parameter_id']);
        $useWear   = $workorder->usesWearLimits();
        $limits    = $parameter->effectiveLimits($useWear);

        $data['limits_source'] = $limits['source'];
        // Rule resolution uses the orig/wear result. The STORED result for a FINAL
        // measurement also accepts oversize repair steps (PASS + which step).
        $dimensionalResult = $this->computeResult($data['actual_value'] ?? null, $limits);
        $storedResult      = $dimensionalResult;
        $stepNo            = null;
        if (($data['stage'] ?? null) === 'final' && $storedResult === 'FAIL' && $data['actual_value'] !== null) {
            $v = (float) $data['actual_value'];
            foreach ($parameter->repairSteps as $s) {
                if ($s->dim_min !== null && $s->dim_max !== null
                    && $v >= (float) $s->dim_min && $v <= (float) $s->dim_max) {
                    $storedResult = 'PASS';
                    $stepNo = $s->step_no; // string label, e.g. "R05"
                    break;
                }
            }
            // Continuous repair (no steps): final within the repair limits
            // (machined size after repair, e.g. bore machined to fit) = PASS.
            if ($storedResult === 'FAIL'
                && ($parameter->repair_dim_min !== null || $parameter->repair_dim_max !== null)
                && ($parameter->repair_dim_min === null || $v >= (float) $parameter->repair_dim_min)
                && ($parameter->repair_dim_max === null || $v <= (float) $parameter->repair_dim_max)) {
                $storedResult = 'PASS';
            }
        }
        $data['repair_step_no'] = $stepNo;

        // Result of the saved record:
        //  - any finding code selected            → FAIL
        //  - dimensional point with a value        → PASS/FAIL by limits
        //  - inspection-only point, finding=None   → PASS (inspected, no defect)
        //  - dimensional point, no value yet       → null (incomplete)
        $isInspectionOnly = $limits['min'] === null && $limits['max'] === null;
        if (!empty($data['codes_id'])) {
            $data['result'] = 'FAIL';
        } elseif ($storedResult !== null) {
            $data['result'] = $storedResult;
        } elseif ($isInspectionOnly) {
            $data['result'] = 'PASS';
        } else {
            $data['result'] = null;
        }

        // Repair-surface (spotface) final control. The machined gap legitimately
        // exceeds the orig tolerance — the governing limits are:
        //   - repair_dim_min/max (max allowed TOTAL gap after repair), when set
        //   - max_repair_depth per endpoint (spotface depth)
        if (($data['stage'] ?? null) === 'final'
            && $parameter->repair_surface_side !== null
            && empty($data['codes_id'])) {
            $overA = ($data['repair_depth_a'] ?? null) !== null
                && $parameter->max_repair_depth_a !== null
                && (float) $data['repair_depth_a'] > (float) $parameter->max_repair_depth_a;
            $overB = ($data['repair_depth_b'] ?? null) !== null
                && $parameter->max_repair_depth_b !== null
                && (float) $data['repair_depth_b'] > (float) $parameter->max_repair_depth_b;

            $value   = ($data['actual_value'] ?? null) !== null ? (float) $data['actual_value'] : null;
            $widthOk = true;
            if ($value !== null) {
                if ($parameter->repair_dim_max !== null && $value > (float) $parameter->repair_dim_max) $widthOk = false;
                if ($parameter->repair_dim_min !== null && $value < (float) $parameter->repair_dim_min) $widthOk = false;
            }

            if ($overA || $overB || !$widthOk) {
                $data['result'] = 'FAIL';
            } elseif ($value !== null || ($data['repair_depth_a'] ?? null) !== null || ($data['repair_depth_b'] ?? null) !== null) {
                $data['result'] = 'PASS';
            }
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
                'points.parameters.repairSteps',
            ])
            ->orderBy('sort_order')
            ->get();

        $allMeas = WoMeasurement::where('workorder_id', $workorder->id)
            ->get()
            ->groupBy('manual_parameter_id');

        // Latest measurement per parameter (prefer final over initial)
        $measByParam = $allMeas->map(function ($ms) {
            $finals = $ms->where('stage', 'final');
            return $finals->isNotEmpty()
                ? $finals->sortBy('id')->last()
                : $ms->sortBy('id')->last();
        });

        // Recorded defect per parameter: latest finding code; a dimensional
        // out-of-limit initial without a code is implicitly "Worn".
        $codeNames      = Code::pluck('name', 'id');
        $findingByParam = $allMeas->map(function ($ms) use ($codeNames) {
            $m = $ms->filter(fn($x) => $x->codes_id !== null)->sortBy('id')->last();
            if ($m) {
                return $codeNames[$m->codes_id] ?? null;
            }
            $dimFail = $ms->first(fn($x) =>
                $x->stage === 'initial' && $x->result === 'FAIL' && $x->actual_value !== null);
            return $dimFail ? 'Worn' : null;
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
                        'findingA'     => $findingByParam[$pA->id] ?? null,
                        'findingB'     => $findingByParam[$pB->id] ?? null,
                        // stored result is stage-aware (final → repair steps/limits)
                        'resultA'      => $measA?->result ?? $this->computeResult($measA?->actual_value, $limA),
                        'resultB'      => $measB?->result ?? $this->computeResult($measB?->actual_value, $limB),
                    ];

                } elseif (!$pt->is_fits_clearance && $params->isNotEmpty()) {
                    foreach ($params as $param) {
                        $meas = $measByParam[$param->id] ?? null;
                        $lim  = $param->effectiveLimits($useWear);

                        // Repair limits column: explicit repair_dim, otherwise from
                        // oversize steps — the step the final landed in, or the
                        // full step span when nothing is machined yet.
                        $repMin = $param->repair_dim_min;
                        $repMax = $param->repair_dim_max;
                        $repLbl = null;
                        // Repair-surface param without explicit repair_dim: the min
                        // limit derives from orig min − allowed spotface depths.
                        if ($repMin === null && $repMax === null
                            && $param->repair_surface_side !== null
                            && $param->orig_dim_min !== null) {
                            $repMin = round((float) $param->orig_dim_min
                                - (float) ($param->max_repair_depth_a ?? 0)
                                - (float) ($param->max_repair_depth_b ?? 0), 4);
                        }
                        if ($repMin === null && $repMax === null && $param->repairSteps->isNotEmpty()) {
                            $step = $meas?->repair_step_no
                                ? $param->repairSteps->first(fn($s) => $s->step_no === $meas->repair_step_no)
                                : null;
                            if ($step) {
                                $repMin = $step->dim_min;
                                $repMax = $step->dim_max;
                                $repLbl = $step->step_no;
                            } else {
                                $repMin = $param->repairSteps->min('dim_min');
                                $repMax = $param->repairSteps->max('dim_max');
                            }
                        }

                        $extraRows[] = [
                            'fig'        => $fig,
                            'pt'         => $pt,
                            'param'      => $param,
                            'meas'       => $meas,
                            'comp'       => $param->inspectionComponent?->variants->first()?->component,
                            'lim'        => $lim,
                            'repair_min' => $repMin,
                            'repair_max' => $repMax,
                            'repair_lbl' => $repLbl,
                            'finding'    => $findingByParam[$param->id] ?? null,
                            // stored result is stage-aware (initial → orig/wear,
                            // final → repair limits / steps / spotface depths)
                            'result'     => $meas?->result ?? $this->computeResult($meas?->actual_value, $lim),
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
            $necessary        = Necessary::where('name', 'Order New')->first();
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
                'necessaries_id'     => $necessary?->id,
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
        foreach ($ctx->orderedGroups() as $group) {
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
                'phase_rule_process_ids' => $group['phase_rule_process_ids'] ?? [],
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

        return response()->json($this->evaluateGate($workorder, (int) $data['inspection_component_id']));
    }

    /**
     * Evaluate the EC gate for a part: per repaired point, compare the final
     * measurement against orig/wear tolerance or any repair-step range.
     *
     * @return array{ready:bool, all_pass:bool, points:array<int,array{
     *     param_id:int, rule_id:?int, description:?string, pt_codes:string,
     *     final_value:?float, pass:bool}>}
     */
    private function evaluateGate(Workorder $workorder, int $icId): array
    {
        $useWear = $workorder->usesWearLimits();
        $params  = ManualParameter::where('inspection_component_id', $icId)
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
            $initialFail = $ms->first(fn($m) => $m->stage === 'initial' && $m->result === 'FAIL');
            if (!$initialFail) {
                continue;
            }
            $final = $ms->where('stage', 'final')->last();
            if (!$final) {
                $ready = false; // a repaired point still has no final measurement
                continue;
            }
            $value  = $final->actual_value !== null ? (float) $final->actual_value : null;
            $limits = $param->effectiveLimits($useWear);
            // Stored final result already encodes repair-surface logic (repair_dim
            // limits + spotface depth control) — trust it when present.
            $pass = $final->result !== null
                ? $final->result === 'PASS'
                : $this->gatePass($value, $limits, $param->repairSteps);
            if (!$pass) {
                $allPass = false;
            }
            $points[] = [
                'param_id'    => $param->id,
                'rule_id'     => $final->manual_parameter_repair_rule_id ?? $initialFail->manual_parameter_repair_rule_id,
                'description' => $param->description,
                'pt_codes'    => $param->points->pluck('code')->filter()->unique()->values()->implode(', '),
                'final_value' => $value,
                'pass'        => $pass,
            ];
        }

        return [
            'ready'    => $ready && count($points) > 0,
            'all_pass' => $allPass,
            'points'   => $points,
        ];
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
            'ec_typical'              => 'boolean', // typical/pre-approved EC → don't hold post+finish
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
            $icId = (int) $data['inspection_component_id'];
            // Relabel the Machining row(s) of each FAILED point → Machining (EC),
            // so the concession is tied to the exact place that's out of limit.
            $eval   = $this->evaluateGate($workorder, $icId);
            $failed = array_values(array_filter($eval['points'], fn ($p) => !$p['pass']));
            $failedRuleIds = array_values(array_filter(array_map(fn ($p) => $p['rule_id'], $failed)));
            $ecProcesses   = []; // mirror of the relabelled Machining (EC) processes
            $ecRuleProcIds = [];
            if (!empty($failedRuleIds)) {
                $failedRpIds = \App\Models\ManualParameterRuleProcess::whereIn('repair_rule_id', $failedRuleIds)
                    ->pluck('id')->map(fn ($i) => (int) $i)->all();
                $machiningId   = \App\Models\ProcessName::where('name', 'Machining')->value('id');
                $machiningEcId = \App\Models\ProcessName::where('name', 'Machining (EC)')->value('id')
                    ?? \App\Models\ProcessName::where('name', 'Machining(EC)')->value('id');
                if ($machiningId && $machiningEcId) {
                    $rows = TdrProcess::where('tdrs_id', $tdr->id)
                        ->whereIn('process_names_id', [$machiningId, $machiningEcId])
                        ->get();
                    foreach ($rows as $row) {
                        $rp = array_map('intval', $row->rule_process_ids ?? []);
                        if (array_intersect($rp, $failedRpIds)) {
                            if ((int) $row->process_names_id === (int) $machiningId) {
                                $row->update(['process_names_id' => $machiningEcId]);
                            }
                            $ecProcesses   = array_values(array_unique(array_merge($ecProcesses, array_map('intval', $row->processes ?? []))));
                            $ecRuleProcIds = array_values(array_unique(array_merge($ecRuleProcIds, $rp)));
                        }
                    }
                }
            }

            // Reason note (auto from the failed checks) for the EC process.
            $reasons = array_filter(array_map(function ($p) {
                $code = trim((string) ($p['pt_codes'] ?? ''));
                $desc = trim((string) ($p['description'] ?? ''));
                $val  = $p['final_value'] !== null ? $this->fmtDim((float) $p['final_value']) : '';

                return trim(($code ? $code . ' ' : '') . $desc . ($val !== '' ? ' = ' . $val : ''));
            }, $failed));
            $reasonNote = $reasons ? ('Out of limit: ' . implode('; ', $reasons)) : 'EC';

            // Typical/pre-approved EC: processes are known and the concession is
            // routinely granted → keep working. Otherwise freeze everything AFTER the
            // gate anchor (is_gate process); fallback to stage post+finish when none set.
            // Freeze everything AFTER the gate anchor (is_gate process). No anchor → no
            // freeze (the engineer sets the anchor to mark the EC hold boundary).
            $typical = (bool) ($data['ec_typical'] ?? false);
            $gateSort = $typical ? null : $this->gateAnchorSort($icId, $tdr->id);
            if ($gateSort !== null) {
                TdrProcess::where('tdrs_id', $tdr->id)
                    ->whereNull('date_start')
                    ->where('sort_order', '>', $gateSort)
                    ->delete();
            }
            // Add the EC process (companion to Machining (EC); read by SP Form / TDR-print).
            $ecNameId = \App\Models\ProcessName::where('name', 'EC')->value('id');
            if ($ecNameId) {
                $existingEc = TdrProcess::where('tdrs_id', $tdr->id)->where('process_names_id', $ecNameId)->first();
                if ($existingEc) {
                    // keep the EC row mirroring its Machining (EC) processes
                    $existingEc->update([
                        'processes'        => $ecProcesses,
                        'rule_process_ids' => $ecRuleProcIds,
                    ]);
                } else {
                    $maxSort = TdrProcess::where('tdrs_id', $tdr->id)->max('sort_order') ?? 0;
                    TdrProcess::create([
                        'tdrs_id'            => $tdr->id,
                        'process_names_id'   => $ecNameId,
                        // mirror the Machining (EC) processes so SP Form / traveler
                        // show WHAT is being conceded, not an empty row
                        'processes'          => $ecProcesses,
                        'rule_process_ids'   => $ecRuleProcIds,
                        'description'        => $typical ? ('Typical EC — ' . $reasonNote) : $reasonNote,
                        'sort_order'         => $maxSort + 1,
                        'in_traveler'        => false,
                        'ec'                 => 1,
                        'standalone_ec_only' => false,
                    ]);
                }
            }
            return response()->json(['ok' => true, 'outcome' => 'ec', 'tdr_id' => $tdr->id, 'typical' => $typical]);
        }

        // order_new — part condemned at the gate (NDT crack / unsalvageable).
        // Drop the not-yet-started post+finish work (moot on a scrapped part) and
        // raise an Order New TDR for the same part. Repair TDR stays as history.
        // Condemn: drop not-started work after the gate anchor (no anchor → leave as is).
        $gateSort = $this->gateAnchorSort((int) $data['inspection_component_id'], $tdr->id);
        if ($gateSort !== null) {
            TdrProcess::where('tdrs_id', $tdr->id)
                ->whereNull('date_start')
                ->where('sort_order', '>', $gateSort)
                ->delete();
        }

        $existing = Tdr::where('workorder_id', $workorder->id)
            ->where('order_component_id', $tdr->component_id)
            ->where('tdr_type', Tdr::TYPE_ORDER_NEW)
            ->first();
        if ($existing) {
            return response()->json(['ok' => true, 'outcome' => 'order_new', 'tdr_id' => $existing->id, 'already' => true]);
        }

        $necessary = Necessary::where('name', 'Order New')->firstOrFail();
        $new = Tdr::create([
            'tdr_type'           => Tdr::TYPE_ORDER_NEW,
            'workorder_id'       => $workorder->id,
            'component_id'       => $tdr->component_id,
            'order_component_id' => $tdr->component_id,
            'serial_number'      => $tdr->serial_number,
            'description'        => $tdr->description,
            'codes_id'           => $tdr->codes_id,
            'conditions_id'      => $tdr->conditions_id,
            'necessaries_id'     => $necessary->id,
            'qty'                => $tdr->qty,
            'use_tdr'            => true,
            'use_process_forms'  => false,
        ]);

        return response()->json(['ok' => true, 'outcome' => 'order_new', 'tdr_id' => $new->id]);
    }

    /**
     * B1 — Revert the TDR(s) of a part so a new decision can be made.
     * Allowed ONLY while no work has started (no TdrProcess has date_start).
     * If work started, the caller must use scrap (B2) instead.
     */

    /**
     * Update (regenerate) repair processes for a part based on current measurements.
     * Replaces all unstarted TdrProcesses on the Repair TDR with a fresh pipeline run.
     */
    public function updatePartProcesses(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'inspection_component_id' => 'required|exists:manual_inspection_components,id',
        ]);

        $icId = (int) $data['inspection_component_id'];

        // Find Repair TDR for this IC — bridge via ipl_num same as in data()
        $directComponentIds = ManualInspectionComponentVariant::where('inspection_component_id', $icId)
            ->pluck('component_id');

        $manual  = $workorder->unit->manuals;
        $useWear = $workorder->usesWearLimits();

        // Extend lookup via ipl_num to handle different component records for same part
        $variantIplNums = Component::whereIn('id', $directComponentIds)->pluck('ipl_num')->filter()->unique();
        $allComponentIds = Component::where('manual_id', $manual->id)
            ->whereIn('ipl_num', $variantIplNums)
            ->pluck('id')
            ->merge($directComponentIds)
            ->unique()->values();

        $tdr = Tdr::where('workorder_id', $workorder->id)
            ->where('tdr_type', Tdr::TYPE_COMPONENT_TDR)
            ->whereIn('component_id', $allComponentIds)
            ->first();

        if (!$tdr) {
            return response()->json(['error' => 'No Repair TDR found for this part'], 404);
        }

        // Collect rule IDs from current FAIL measurements
        $paramIds = ManualParameter::where('inspection_component_id', $icId)->pluck('id');

        $allFails = WoMeasurement::where('workorder_id', $workorder->id)
            ->whereIn('manual_parameter_id', $paramIds)
            ->where('result', 'FAIL')
            ->get(['id', 'manual_parameter_id', 'result', 'codes_id', 'manual_parameter_repair_rule_id', 'stage']);

        // For FAILs without a rule_id, try to re-resolve now
        // (rule may have been added after the measurement was saved)
        $paramsById = ManualParameter::whereIn('id', $allFails->pluck('manual_parameter_id')->unique())
            ->with('repairRules.triggers')
            ->get()->keyBy('id');

        $ruleIds = [];
        foreach ($allFails as $m) {
            $rId = $m->manual_parameter_repair_rule_id;
            if (!$rId) {
                $param = $paramsById[$m->manual_parameter_id] ?? null;
                if ($param) {
                    $rId = $this->resolveRepairRule($param, $m->result, $m->codes_id, $useWear);
                }
            }
            if ($rId) $ruleIds[] = $rId;
        }
        $ruleIds = array_values(array_unique($ruleIds));

        // Delete only processes that haven't started
        $startedExists = TdrProcess::where('tdrs_id', $tdr->id)->whereNotNull('date_start')->exists();
        if ($startedExists) {
            // Only replace unstarted processes; keep started ones
            TdrProcess::where('tdrs_id', $tdr->id)->whereNull('date_start')->delete();
            $baseSort = TdrProcess::where('tdrs_id', $tdr->id)->max('sort_order') ?? 0;
        } else {
            TdrProcess::where('tdrs_id', $tdr->id)->delete();
            $baseSort = 0;
        }

        if (empty($ruleIds)) {
            return response()->json(['ok' => true, 'message' => 'No repair rules matched — processes cleared']);
        }

        // Re-run pipeline
        $ctx = new PipelineContext();
        $ctx->inspectionComponentId = $icId;
        $ctx->mainRuleIds           = $ruleIds;
        $ctx->defectCodeIds         = [];
        $ctx->heldPendingEc         = false;

        app(RepairPipeline::class)->run($ctx);

        // Skip process_names_id already covered by a started process (avoid duplicates on repeat)
        $startedNameIds = TdrProcess::where('tdrs_id', $tdr->id)
            ->whereNotNull('date_start')
            ->pluck('process_names_id')
            ->toArray();

        $existingSorts = TdrProcess::where('tdrs_id', $tdr->id)->pluck('sort_order')->toArray();

        foreach ($ctx->orderedGroups() as $group) {
            if (in_array($group['process_names_id'], $startedNameIds)) continue;

            $sort = $baseSort + (int) $group['sort_order'];
            while (in_array($sort, $existingSorts)) { $sort++; }
            $existingSorts[] = $sort;

            TdrProcess::create([
                'tdrs_id'                => $tdr->id,
                'process_names_id'       => $group['process_names_id'],
                'processes'              => $group['process_ids'],
                'rule_process_ids'       => $group['rule_process_ids'] ?? [],
                'phase_rule_process_ids' => $group['phase_rule_process_ids'] ?? [],
                'description'            => $group['description'] ?? '',
                'sort_order'             => $sort,
                'in_traveler'            => false,
            ]);
        }

        return response()->json(['ok' => true, 'tdr_id' => $tdr->id]);
    }

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
                'error' => 'Work has already started on this TDR — it cannot be reverted.',
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

        // One-sided limits are valid (e.g. lug thickness has min only,
        // bore has max only) — each bound is checked only when set.
        if ($limits['min'] === null && $limits['max'] === null) {
            return null; // inspection-only, no dimensional judgement
        }

        if ($limits['min'] !== null && $value < (float) $limits['min']) return 'FAIL';
        if ($limits['max'] !== null && $value > (float) $limits['max']) return 'FAIL';

        return 'PASS';
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
     * sort_order of the gate-anchor process (is_gate on a repair rule) in this TDR —
     * everything after it is frozen on EC. Null when no anchor is set (→ fallback to stage).
     */
    private function gateAnchorSort(int $icId, int $tdrId): ?int
    {
        $partParamIds = ManualParameter::where('inspection_component_id', $icId)->pluck('id');
        $partRuleIds  = \App\Models\ManualParameterRepairRule::whereIn('manual_parameter_id', $partParamIds)->pluck('id');
        $gateRp = \App\Models\ManualParameterRuleProcess::whereIn('repair_rule_id', $partRuleIds)
            ->where('is_gate', true)
            ->with('manualProcess.process')
            ->first();
        $gateNameId = $gateRp?->manualProcess?->process?->process_names_id;
        if (!$gateNameId) {
            return null;
        }
        $sort = TdrProcess::where('tdrs_id', $tdrId)->where('process_names_id', $gateNameId)->min('sort_order');

        return $sort === null ? null : (int) $sort;
    }

    private function fmtDim(float $v): string
    {
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
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
