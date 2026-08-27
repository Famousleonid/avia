<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\ManualFit;
use App\Models\ManualInspectionComponent;
use App\Models\ManualInspectionComponentVariant;
use App\Models\ManualParameter;
use App\Models\ManualParameterRepairRule;
use App\Models\ManualParameterRuleTrigger;
use App\Models\Necessary;
use App\Models\ProcessName;
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
                    'is_fc'       => (bool) $pt->is_fits_clearance,
                    'child_ic_id' => $pt->child_ic_id,
                    'x_pct'       => $pt->x_pct,
                    'y_pct'       => $pt->y_pct,
                    'x2_pct'      => $pt->x2_pct,
                    'y2_pct'      => $pt->y2_pct,
                    'width_pct'   => $pt->width_pct,
                    'height_pct'  => $pt->height_pct,
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
                'in_process'              => (bool) $p->in_process,
                'requires_value'          => $p->requires_value,
                'qty'                     => $p->qty,
                'orig_dim_min'            => $p->orig_dim_min,
                'orig_dim_max'            => $p->orig_dim_max,
                'wear_dim_min'            => $p->wear_dim_min,
                'wear_dim_max'            => $p->wear_dim_max,
                'repair_dim_min'          => $p->repair_dim_min,
                'repair_dim_max'          => $p->repair_dim_max,
                'flange_clearance_min'    => $p->flange_clearance_min,
                'flange_clearance_max'    => $p->flange_clearance_max,
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
                    'pivot_id' => (int) $pt->pivot->id, // pivot id has no model cast
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
                'new_part'                     => (bool) $m->new_part,
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
                'user'                         => $m->user ? ['name' => $m->user->selection_name] : null,
            ]);

        $codes = Code::orderBy('name')->get(['id', 'name', 'code']);
        $missingCodeId = optional(Code::missing())->id;

        $tdrComponentIds = Tdr::where('workorder_id', $workorder->id)
            ->pluck('component_id')->unique()->filter()->values()->all();

        // Build ic_id → tdr label map
        // Uses ipl_num bridge: TDR and IC variant may reference different component
        // records in the components table even for the same physical part (same ipl_num).
        $icsTdrLabel = [];
        $priority    = ['missing' => 3, 'order new' => 2, 'repair' => 1, 'tdr' => 0];

        $icsSyncedMeas = []; // ic_id → last_synced_measurement_id (Update button state)

        if (count($tdrComponentIds) > 0) {
            $tdrs = Tdr::where('workorder_id', $workorder->id)
                ->whereIn('component_id', $tdrComponentIds)
                ->get(['tdr_type', 'codes_id', 'component_id', 'last_synced_measurement_id']);

            $codeNamesById = Code::whereIn('id', $tdrs->pluck('codes_id')->filter()->unique())
                ->pluck('name', 'id');

            // Step 1: label per component_id (highest priority wins per comp)
            $tdrLabelByComponent = [];
            $tdrSyncByComponent  = [];
            foreach ($tdrs as $tdr) {
                $tdrSyncByComponent[$tdr->component_id] = max(
                    $tdrSyncByComponent[$tdr->component_id] ?? 0,
                    (int) $tdr->last_synced_measurement_id
                );
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
            $tdrSyncByIpl  = [];
            foreach ($tdrLabelByComponent as $compId => $label) {
                $ipl = $iplByTdrComp[$compId] ?? null;
                if (!$ipl) continue;
                if (!isset($tdrLabelByIpl[$ipl]) ||
                    ($priority[$label] ?? 0) > ($priority[$tdrLabelByIpl[$ipl]] ?? 0)) {
                    $tdrLabelByIpl[$ipl] = $label;
                }
                $tdrSyncByIpl[$ipl] = max($tdrSyncByIpl[$ipl] ?? 0, $tdrSyncByComponent[$compId] ?? 0);
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
                $icsSyncedMeas[$icId] = max($icsSyncedMeas[$icId] ?? 0, $ipl ? ($tdrSyncByIpl[$ipl] ?? 0) : 0);
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
            'ics_synced_meas'      => $icsSyncedMeas, // ic_id → last measurement id processes were built from
        ]);
    }

    public function store(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'manual_parameter_id' => 'required|exists:manual_parameters,id',
            'stage'               => 'required|in:initial,final',
            'new_part'            => 'nullable|boolean',
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

        // New-part verification (Order New position): a replacement part must
        // meet the ORIGINAL factory limits — wear/repair limits don't apply.
        $isNewPart = (bool) ($data['new_part'] ?? false);
        if ($isNewPart) {
            $useWear = false;
            $limits  = $parameter->effectiveLimits(false);
        }

        $data['limits_source'] = $limits['source'];
        // Rule resolution uses the orig/wear result. The STORED result for a FINAL
        // measurement also accepts oversize repair steps (PASS + which step).
        $dimensionalResult = $this->computeResult($data['actual_value'] ?? null, $limits);
        $storedResult      = $dimensionalResult;
        $stepNo            = null;
        if (!$isNewPart && ($data['stage'] ?? null) === 'final' && $storedResult === 'FAIL' && $data['actual_value'] !== null) {
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
        if (!$isNewPart
            && ($data['stage'] ?? null) === 'final'
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
        // A failed NEW part is not "repaired" — it's rejected/reordered manually.
        $data['manual_parameter_repair_rule_id'] = $isNewPart ? null : $this->resolveRepairRule(
            $parameter,
            $dimensionalResult,
            $data['codes_id'] ?? null,
            $useWear,
            $findingContext,
            ($data['actual_value'] ?? null) !== null ? (float) $data['actual_value'] : null
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

        // F&C pairs come from the explicit fit registry (is_fc) — one source of
        // truth across the manual table, the WO F&C document and this grid;
        // supports cross-point pairs that the old shared-point logic could not.
        $pairedParamIds = [];
        $fcFits = ManualFit::where('manual_id', $manual->id)
            ->where('is_fc', true)
            ->with([
                'odParam.points.figure', 'odParam.inspectionComponent.variants.component', 'odParam.repairSteps',
                'idParam.points.figure', 'idParam.inspectionComponent.variants.component', 'idParam.repairSteps',
            ])
            ->orderBy('sort_order')
            ->get();

        foreach ($fcFits as $fit) {
            $pA = $fit->idParam;  // ID = bore
            $pB = $fit->odParam;  // OD = shaft

            // Single-member row (mate in another manual / Between-Across Faces):
            // one F&C line with its ref and limits, no clearances.
            if (! $pA || ! $pB) {
                $p = $pA ?? $pB;
                if (! $p) {
                    continue;
                }
                $pairedParamIds[$p->id] = true;
                $pt  = $p->points->first();
                $meas = $measByParam[$p->id] ?? null;
                $lim  = $p->effectiveLimits($useWear);
                $fcRows[] = [
                    'single'       => true,
                    // stored (manual) clearances — nothing derivable without a mate
                    'clearOrigMin' => $fit->assembly_clearance_min !== null ? (float) $fit->assembly_clearance_min : null,
                    'clearOrigMax' => $fit->assembly_clearance_max !== null ? (float) $fit->assembly_clearance_max : null,
                    'permClearMax' => $fit->permitted_clearance !== null ? (float) $fit->permitted_clearance : null,
                    'fig'          => $pt?->figure,
                    'pt'           => $pt,
                    'ref'          => trim((string) $fit->ref_no) ?: (trim((string) $fit->id_ref_no) ?: ($pt?->code ?? '—')),
                    'refOd'        => '', 'refId' => '', 'refSplit' => false,
                    'sortRef'      => trim((string) $fit->ref_no) ?: (trim((string) $fit->id_ref_no) ?: (string) ($pt?->code ?? '')),
                    'pA'           => $p,
                    'compA'        => $p->inspectionComponent?->variants->first()?->component,
                    'measA'        => $meas,
                    'aWearMin'     => $p->wear_dim_min ?? $p->orig_dim_min,
                    'aWearMax'     => $p->wear_dim_max ?? $p->orig_dim_max,
                    'findingA'     => $findingByParam[$p->id] ?? null,
                    'resultA'      => $meas?->result ?? $this->computeResult($meas?->actual_value, $lim),
                ];
                continue;
            }
            $pairedParamIds[$pA->id] = true;
            $pairedParamIds[$pB->id] = true;

            // Display anchor: the point shared by both members, else either member's.
            $odPts = $pB->points;
            $pt = $pA->points->first(fn($p) => $odPts->contains('id', $p->id))
                ?? $pA->points->first() ?? $odPts->first();
            $fig = $pt?->figure;

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

            // Per-member Ref.No (FIGURE 8001 NUMBER): ref_no = OD, id_ref_no = ID.
            // id_ref_no empty or == ref_no → merged single-ref cell (legacy look);
            // different → each member its own numbered row. Empty refs fall back to
            // the shared point code so the column is never blank.
            $odRef     = trim((string) $fit->ref_no);
            $idRef     = trim((string) $fit->id_ref_no);
            $ptCode    = $pt?->code;
            $refSplit  = $idRef !== '' && $idRef !== $odRef;
            $refOd     = $odRef !== '' ? $odRef : (string) ($ptCode ?? '');
            $refId     = $idRef !== '' ? $idRef : (string) ($ptCode ?? '');
            $mergedRef = $odRef !== '' ? $odRef : ($ptCode ?? '—');
            $refCands  = array_values(array_filter([$refOd, $refId], fn ($s) => $s !== ''));
            usort($refCands, 'strnatcasecmp');

            $fcRows[] = [
                'fig'          => $fig,
                'pt'           => $pt,
                'ref'          => $mergedRef,
                'refOd'        => $refOd,
                'refId'        => $refId,
                'refSplit'     => $refSplit,
                'sortRef'      => $refCands[0] ?? (string) $mergedRef,
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
        }

        // Order F&C pairs by Ref.No (FIGURE 8001 NUMBER), natural sort.
        usort($fcRows, fn ($a, $b) => strnatcasecmp((string) $a['sortRef'], (string) $b['sortRef']));

        // Every other measured parameter → standalone row (not part of an F&C pair).
        foreach ($figures as $fig) {
            foreach ($fig->points as $pt) {
                foreach ($pt->parameters->sortBy('sort_order')->values() as $param) {
                    if (isset($pairedParamIds[$param->id])) {
                        continue;
                    }
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

        return view('admin.measurements._fc-table', compact('fcRows', 'extraRows', 'workorder', 'useWear'));
    }

    /**
     * Required Bushings report: every bushing position with the P/N to install,
     * derived from the bore measurements. At overhaul ALL bushings are renewed —
     * the P/N depends on the bore state:
     *   bore final in an oversize step → the step's component P/N
     *   bore initial PASS (no repair)  → the initial (standard) P/N
     *   bore final, continuous fit     → manufacture per sketch (req OD shown)
     *   bore not measured              → pending inspection
     */
    public function requiredBushings(Workorder $workorder)
    {
        $manual  = $workorder->unit->manuals;
        $useWear = $workorder->usesWearLimits();

        $bushIcs = ManualInspectionComponent::where('manual_id', $manual->id)
            ->with('variants.component')
            ->get()
            ->filter(fn($ic) => $ic->variants->contains(fn($v) => $v->component?->is_bush));

        $params = ManualParameter::where('manual_id', $manual->id)
            ->with(['points:id,code', 'repairSteps.component'])
            ->get();

        $measByParam = WoMeasurement::where('workorder_id', $workorder->id)
            ->get()
            ->groupBy('manual_parameter_id');

        // Mating registry: the bushing OD ↔ housing bore pair is an explicit
        // fit (od_param_id = bushing OD). Replaces the old shared-point guess.
        $mateByOd = ManualFit::where('manual_id', $manual->id)->get()->keyBy('od_param_id');

        $rows = [];
        foreach ($bushIcs as $ic) {
            // One row per bushing POSITION = its OD parameter (the bore side
            // determines the P/N); the bushing's own ID (pin side) is skipped.
            $odParams = $params->where('inspection_component_id', $ic->id)
                ->filter(fn($p) =>
                    ($p->orig_dim_min !== null || $p->repairSteps->isNotEmpty())
                    && preg_match('/\bOD\b/i', (string) $p->description) === 1);
            $stdComp = $ic->variants->first()?->component;

            foreach ($odParams as $od) {
                $ptIds  = $od->points->pluck('id')->all();
                $codes  = $od->points->pluck('code')->filter()->implode(', ');
                $fit    = $mateByOd->get($od->id);
                $mating = $fit ? $params->firstWhere('id', $fit->id_param_id) : null;
                if (!$mating && $ptIds === []) continue;

                $row = [
                    'point'   => $codes,
                    'bushing' => $ic->label,
                    'param'   => $od->description,
                    'qty'     => max(1, (int) ($od->qty ?? 1)),
                    'bore'    => 'not inspected',
                    'pn'      => null,
                    'ipl'     => null,
                    'note'    => null,
                    'ic_id'    => $ic->id,
                    'param_id' => $od->id,
                    'sketch'   => false, // true when the bore final exists → drawing is renderable
                ];

                $ms = $mating ? ($measByParam[$mating->id] ?? collect()) : collect();
                $final = $ms->where('stage', 'final')->sortBy('id')->last();
                $init  = $ms->where('stage', 'initial')->sortBy('id')->last();

                if ($final && $final->repair_step_no) {
                    // bore repaired into an oversize step
                    $step = $od->repairSteps->first(fn($s) => $s->step_no === $final->repair_step_no);
                    $row['bore']   = $final->repair_step_no;
                    $row['pn']     = $step?->component?->part_number ?? '— step P/N not configured —';
                    $row['ipl']    = $step?->component?->ipl_num;
                    $row['sketch'] = true;
                } elseif ($final && $final->actual_value !== null) {
                    // continuous repair → bushing is manufactured to fit
                    $row['bore'] = 'machined ' . number_format((float) $final->actual_value, 4);
                    if ($od->orig_dim_min !== null && $od->orig_dim_max !== null
                        && $mating->orig_dim_min !== null && $mating->orig_dim_max !== null) {
                        $fitMin = (float) $od->orig_dim_min - (float) $mating->orig_dim_max;
                        $fitMax = (float) $od->orig_dim_max - (float) $mating->orig_dim_min;
                        $row['note'] = 'manufacture per sketch · req OD '
                            . number_format((float) $final->actual_value + $fitMin, 4) . '–'
                            . number_format((float) $final->actual_value + $fitMax, 4);
                    } else {
                        $row['note'] = 'manufacture per sketch';
                    }
                    $row['pn']     = $stdComp?->part_number;
                    $row['ipl']    = $stdComp?->ipl_num;
                    $row['sketch'] = true;
                } elseif ($init && $init->result === 'PASS') {
                    // bore within limits — standard (initial) bushing
                    $row['bore'] = 'OK (initial)';
                    $row['pn']   = $stdComp?->part_number;
                    $row['ipl']  = $stdComp?->ipl_num;
                } elseif ($init && ($init->result === 'FAIL' || $init->codes_id !== null)) {
                    // bore goes to repair (out of limits OR a defect finding) →
                    // the bushing WILL be oversize. Order the LAST (largest)
                    // repair-step P/N; the exact step is known after machining.
                    $row['bore'] = 'repair → OVS';
                    $lastStep = $od->repairSteps->sortBy('sort_order')->last();
                    if ($lastStep?->component) {
                        $row['pn']   = $lastStep->component->part_number;
                        $row['ipl']  = $lastStep->component->ipl_num;
                        $row['note'] = 'last oversize (' . $lastStep->step_no . ') — exact step after machining';
                    } else {
                        $row['note'] = 'P/N after final machining';
                    }
                }

                $rows[] = $row;
            }
        }

        return view('admin.measurements._required-bushings', compact('rows', 'workorder'));
    }

    /**
     * Final Dimensional Report — bushing fits: per position the FINAL bore
     * size, the FINAL bushing OD, the resulting actual fit and the allowed
     * fit (step pair or derived from the orig limits). QA/package document.
     */
    public function finalFitReport(Workorder $workorder)
    {
        $manual = $workorder->unit->manuals;

        $bushIcs = ManualInspectionComponent::where('manual_id', $manual->id)
            ->with('variants.component')
            ->get()
            ->filter(fn($ic) => $ic->variants->contains(fn($v) => $v->component?->is_bush));

        $params = ManualParameter::where('manual_id', $manual->id)
            ->with(['points:id,code', 'repairSteps'])
            ->get();

        $measByParam = WoMeasurement::where('workorder_id', $workorder->id)
            ->get()
            ->groupBy('manual_parameter_id');

        $lastFinal = fn($pid) => ($measByParam[$pid] ?? collect())
            ->where('stage', 'final')->sortBy('id')->last();

        // Bushing OD ↔ housing bore pairs come from the explicit fit registry.
        $mateByOd = ManualFit::where('manual_id', $manual->id)->get()->keyBy('od_param_id');

        $rows = [];
        foreach ($bushIcs as $ic) {
            $pos = $ic->variants->first()?->component?->ipl_num ?? $ic->label;

            $odParams = $params->where('inspection_component_id', $ic->id)
                ->filter(fn($p) =>
                    ($p->orig_dim_min !== null || $p->repairSteps->isNotEmpty())
                    && preg_match('/\bOD\b/i', (string) $p->description) === 1);

            foreach ($odParams as $od) {
                $fit    = $mateByOd->get($od->id);
                $bore   = $fit ? $params->firstWhere('id', $fit->id_param_id) : null;
                if (!$bore) continue;

                $boreIcLabel = ManualInspectionComponent::find($bore->inspection_component_id)?->label;

                $boreFin = $lastFinal($bore->id);
                $odFin   = $lastFinal($od->id);

                $boreVal = $boreFin?->actual_value !== null ? (float) $boreFin->actual_value : null;
                $odVal   = $odFin?->actual_value   !== null ? (float) $odFin->actual_value   : null;

                // Allowed fit: matching oversize step pair when the bore landed in a
                // step, otherwise derived from the pair's orig limits.
                $allowMin = $allowMax = null;
                $allowSrc = null;
                if ($boreFin?->repair_step_no) {
                    $odStep   = $od->repairSteps->first(fn($s) => $s->step_no === $boreFin->repair_step_no);
                    $boreStep = $bore->repairSteps->first(fn($s) => $s->step_no === $boreFin->repair_step_no);
                    if ($odStep && $boreStep && $odStep->dim_min !== null && $boreStep->dim_min !== null) {
                        $allowMin = round((float) $odStep->dim_min - (float) $boreStep->dim_max, 4);
                        $allowMax = round((float) $odStep->dim_max - (float) $boreStep->dim_min, 4);
                        $allowSrc = $boreFin->repair_step_no;
                    }
                }
                if ($allowMin === null
                    && $od->orig_dim_min !== null && $od->orig_dim_max !== null
                    && $bore->orig_dim_min !== null && $bore->orig_dim_max !== null) {
                    $allowMin = round((float) $od->orig_dim_min - (float) $bore->orig_dim_max, 4);
                    $allowMax = round((float) $od->orig_dim_max - (float) $bore->orig_dim_min, 4);
                    $allowSrc = 'orig';
                }

                $fit = ($boreVal !== null && $odVal !== null) ? round($odVal - $boreVal, 4) : null;

                $result = null;
                if ($fit !== null && $allowMin !== null && $allowMax !== null) {
                    $result = ($fit >= $allowMin && $fit <= $allowMax) ? 'PASS' : 'FAIL';
                }

                $rows[] = [
                    'pos'       => $pos,
                    'bore_part' => trim(($boreIcLabel ? $boreIcLabel . ' ' : '') . $bore->description),
                    'bore_val'  => $boreVal,
                    'bore_step' => $boreFin?->repair_step_no,
                    'bushing'   => $ic->label,
                    'od_val'    => $odVal,
                    'fit'       => $fit,
                    'allow_min' => $allowMin,
                    'allow_max' => $allowMax,
                    'allow_src' => $allowSrc,
                    'result'    => $result,
                    'qty'       => max(1, (int) ($od->qty ?? 1)),
                ];
            }
        }

        // Identical positions (same bushing, same final sizes and fit) collapse
        // into one row with the quantities summed.
        $merged = [];
        foreach ($rows as $r) {
            $key = implode('|', [
                $r['pos'], $r['bore_val'], $r['bore_step'], $r['od_val'],
                $r['fit'], $r['allow_min'], $r['allow_max'], $r['result'],
            ]);
            if (isset($merged[$key])) {
                $merged[$key]['qty'] += $r['qty'];
            } else {
                $merged[$key] = $r;
            }
        }
        $rows = array_values($merged);

        return view('admin.measurements._final-fit-report', compact('rows', 'workorder'));
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
            $missingCode      = Code::missing();
            $necessary        = Necessary::where('name', 'Order New')->first();
            $missingCondition = Condition::partsMissing();

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

        // Persist the technician's explicit rule choice on the measurement, so a
        // later rebuild (Update processes) keeps it instead of falling back to
        // the auto-resolved first matching rule.
        if (!empty($ruleIds)) {
            $chosen = ManualParameterRepairRule::find($ruleIds[0]);
            if ($chosen
                && (int) $chosen->manual_parameter_id === (int) $measurement->manual_parameter_id
                && (int) $measurement->manual_parameter_repair_rule_id !== (int) $chosen->id) {
                $measurement->update(['manual_parameter_repair_rule_id' => $chosen->id]);
            }
        }

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
            if (! $this->isValidProcessNameId($group['process_names_id'] ?? null)) {
                continue;
            }

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

        // Gate decision consumes the current measurements — re-arm the Update
        // button only when newer measurements appear.
        $gateSyncParamIds = ManualParameter::where('inspection_component_id', (int) $data['inspection_component_id'])->pluck('id');
        $tdr->update(['last_synced_measurement_id' => WoMeasurement::where('workorder_id', $workorder->id)
            ->whereIn('manual_parameter_id', $gateSyncParamIds)->max('id')]);

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
            'rule_overrides'          => 'nullable|array',
            'rule_overrides.*'        => 'integer|exists:manual_parameter_repair_rules,id',
            'orders'                  => 'nullable|array',
            'orders.*.component_id'   => 'required|integer|exists:components,id',
            'orders.*.qty'            => 'nullable|integer|min:1|max:99',
            'orders.*.accept'         => 'boolean',
            'cancel_order_tdr_ids'    => 'nullable|array',
            'cancel_order_tdr_ids.*'  => 'integer',
            'scrap_accept'            => 'boolean',
            'scrap_keep_through'      => 'nullable|integer', // TdrProcess id: work completed through this row
        ]);

        $icId = (int) $data['inspection_component_id'];
        $ctx  = $this->rebuildContext($workorder, $icId, $data['rule_overrides'] ?? [], true);
        if (!$ctx) {
            return response()->json(['error' => 'No Repair TDR found for this part'], 404);
        }
        $tdr = $ctx['tdr'];

        // Scrap accepted: the plan is NOT rebuilt — the shop often runs without
        // per-process dates, so the current rows up to the verdict point ARE the
        // record of performed work. Rows after "completed through" are dropped
        // below; everything else stays untouched.
        $scrapAccept = !empty($data['scrap_accept'])
            && ($ctx['scrap']['status'] ?? null) === 'proposed';

        if (!$scrapAccept) {
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
        }

        // Mark the sync point: the Update button stays inactive until newer
        // measurements of this part appear.
        $syncId = WoMeasurement::where('workorder_id', $workorder->id)
            ->whereIn('manual_parameter_id', $ctx['paramIds'])
            ->max('id');
        $tdr->update(['last_synced_measurement_id' => $syncId]);

        // §5 linked part orders: raise accepted proposals, cancel confirmed
        // obsolete linked orders — also when no repair rules matched (the
        // cancel case is exactly the route-deactivated scenario).
        [$ordersCreated, $ordersCancelled, $orderWarnings] =
            $this->applyLinkedPartOrders($workorder, $tdr, $ctx['orders'], $data);

        // §4 scrap verdict accepted: order a replacement for the CARRIER part.
        $scrapTdrId = null;
        if ($scrapAccept) {
            $s = $ctx['scrap'];
            $code = $s['codes_id'] ? Code::find($s['codes_id']) : null;
            $scrapTdr = Tdr::create([
                'tdr_type'           => Tdr::TYPE_ORDER_NEW,
                'workorder_id'       => $workorder->id,
                'component_id'       => $s['component_id'],
                'order_component_id' => $s['component_id'],
                'serial_number'      => $tdr->serial_number ?? 'NSN',
                'description'        => $s['name'],
                'codes_id'           => $s['codes_id'],
                'conditions_id'      => $code ? Condition::where('name', $code->name)->first()?->id : $tdr->conditions_id,
                'necessaries_id'     => Necessary::firstOrCreate(['name' => 'Order New'])->id,
                'qty'                => (int) ($tdr->qty ?: 1),
                'use_tdr'            => true,
                'use_process_forms'  => false,
                'source_rule_id'     => $s['rule_id'],
                'source_tdr_id'      => $tdr->id,
            ]);
            $scrapTdrId = $scrapTdr->id;
        }

        // Condemned part: keep the record of performed work (rows up to the
        // "completed through" point — the shop runs without per-process dates),
        // drop everything after it, and skip the rebuild entirely.
        if ($scrapAccept) {
            $keep = TdrProcess::where('tdrs_id', $tdr->id)
                ->whereKey((int) ($data['scrap_keep_through'] ?? 0))
                ->first();
            $q = TdrProcess::where('tdrs_id', $tdr->id)->whereNull('date_start');
            if ($keep) {
                $q->where('sort_order', '>', $keep->sort_order);
            }
            $q->delete();

            return response()->json([
                'ok'               => true,
                'tdr_id'           => $tdr->id,
                'warnings'         => $ctx['warnings'],
                'orders_created'   => $ordersCreated,
                'orders_cancelled' => $ordersCancelled,
                'order_warnings'   => $orderWarnings,
                'scrap_tdr_id'     => $scrapTdrId,
            ]);
        }

        if (empty($ctx['mainRuleIds'])) {
            return response()->json([
                'ok'               => true,
                'message'          => 'No repair rules matched — processes cleared',
                'warnings'         => $ctx['warnings'],
                'orders_created'   => $ordersCreated,
                'orders_cancelled' => $ordersCancelled,
                'order_warnings'   => $orderWarnings,
                'scrap_tdr_id'     => $scrapTdrId,
            ]);
        }

        // Started rows are kept as history. A new group is skipped only when a
        // started row covers the SAME plan node (see coveredByStartedRow) — two
        // points repaired by different rules share the "Machining" name but are
        // different nodes and must both exist.
        $startedRows = TdrProcess::where('tdrs_id', $tdr->id)
            ->whereNotNull('date_start')
            ->get(['process_names_id', 'rule_process_ids', 'phase_rule_process_ids']);

        $existingSorts = TdrProcess::where('tdrs_id', $tdr->id)->pluck('sort_order')->toArray();

        foreach ($this->plannedGroups($icId, $ctx['mainRuleIds'], $ctx['defectCodeIds']) as $group) {
            if ($this->coveredByStartedRow($group, $startedRows)) continue;

            $sort = $baseSort + (int) $group['sort_order'];
            while (in_array($sort, $existingSorts)) { $sort++; }
            $existingSorts[] = $sort;

            TdrProcess::create([
                'tdrs_id'                => $tdr->id,
                'process_names_id'       => $group['process_names_id'],
                'plus_process'           => !empty($group['plus_process_name_ids'])
                    ? implode(',', $group['plus_process_name_ids'])
                    : null,
                'processes'              => $group['process_ids'],
                'rule_process_ids'       => $group['rule_process_ids'] ?? [],
                'phase_rule_process_ids' => $group['phase_rule_process_ids'] ?? [],
                'description'            => $group['description'] ?? '',
                'sort_order'             => $sort,
                'in_traveler'            => false,
            ]);
        }

        return response()->json([
            'ok'               => true,
            'tdr_id'           => $tdr->id,
            'warnings'         => $ctx['warnings'],
            'orders_created'   => $ordersCreated,
            'orders_cancelled' => $ordersCancelled,
            'order_warnings'   => $orderWarnings,
            'scrap_tdr_id'     => $scrapTdrId,
        ]);
    }

    /**
     * Dry-run of updatePartProcesses — the preview the technician confirms
     * before the plan is rebuilt. Nothing is written: no deletions, no sync
     * point move, rule overrides stay in memory.
     *
     * Returns the per-point route choices (radio options when several rules
     * match), action warnings (order_new / ec rules that cannot be merged into
     * a repair plan), and the diff vs the current TdrProcess rows:
     * kept (started history) / unchanged / added / removed.
     */
    public function previewPartProcesses(Request $request, Workorder $workorder)
    {
        $data = $request->validate([
            'inspection_component_id' => 'required|exists:manual_inspection_components,id',
            'rule_overrides'          => 'nullable|array',
            'rule_overrides.*'        => 'integer|exists:manual_parameter_repair_rules,id',
        ]);

        $icId = (int) $data['inspection_component_id'];
        $ctx  = $this->rebuildContext($workorder, $icId, $data['rule_overrides'] ?? [], false);
        if (!$ctx) {
            return response()->json(['error' => 'No Repair TDR found for this part'], 404);
        }

        $groups = $this->plannedGroups($icId, $ctx['mainRuleIds'], $ctx['defectCodeIds']);

        $rows      = TdrProcess::where('tdrs_id', $ctx['tdr']->id)->orderBy('sort_order')->get();
        $started   = $rows->filter(fn ($r) => $r->date_start !== null)->values();
        $unstarted = $rows->filter(fn ($r) => $r->date_start === null)->values();

        $rowPlusIds = fn ($r) => array_values(array_filter(array_map('intval',
            explode(',', (string) ($r->plus_process ?? '')))));
        $nameIds = collect($groups)->pluck('process_names_id')
            ->merge(collect($groups)->flatMap(fn ($g) => (array) ($g['plus_process_name_ids'] ?? [])))
            ->merge($rows->pluck('process_names_id'))
            ->merge($rows->flatMap($rowPlusIds))
            ->filter()->unique()->values();
        $names = ProcessName::whereIn('id', $nameIds)->pluck('name', 'id');
        // Combined NDT row → "NDT-1 / NDT-4"
        $combinedName = function (int $baseId, array $plusIds) use ($names) {
            $parts = array_merge([$baseId], $plusIds);

            return implode(' / ', array_map(fn ($id) => (string) ($names[(int) $id] ?? ('#' . $id)), $parts));
        };

        // Operation texts for every referenced process id (groups + rows)
        $procIds = collect($groups)->flatMap(fn ($g) => (array) ($g['process_ids'] ?? []))
            ->merge($rows->flatMap(fn ($r) => (array) ($r->processes ?? [])))
            ->map(fn ($v) => (int) $v)->filter()->unique()->values();
        $opTexts = \App\Models\Process::whereIn('id', $procIds)->pluck('process', 'id');
        $opsOf = fn (array $ids) => collect($ids)->map(fn ($id) => (string) ($opTexts[(int) $id] ?? ''))
            ->filter()->unique()->values()->all();

        $rowEntry = fn ($r) => [
            'name'        => $combinedName((int) $r->process_names_id, $rowPlusIds($r)),
            'description' => (string) ($r->description ?? ''),
            'ops'         => $opsOf((array) ($r->processes ?? [])),
        ];
        $groupEntry = fn ($g) => [
            'name'        => $combinedName((int) $g['process_names_id'], (array) ($g['plus_process_name_ids'] ?? [])),
            'description' => (string) ($g['description'] ?? ''),
            'phase'       => $g['phase'] ?? 'main',
            'ops'         => $opsOf((array) ($g['process_ids'] ?? [])),
        ];

        // Match planned groups against existing unstarted rows by plan-node
        // identity (greedy, each row consumed once) — what matches is
        // "unchanged", the rest is "added"; unmatched rows are "removed".
        // `plan` is the resulting traveler in ORDER: kept (started) rows first,
        // then the pipeline groups with their status.
        $consumed  = [];
        $added     = [];
        $unchanged = [];
        $plan      = $started->map(fn ($r) => $rowEntry($r) + ['status' => 'kept'])->values()->all();
        foreach ($groups as $g) {
            if ($this->coveredByStartedRow($g, $started)) continue;
            $match = null;
            foreach ($unstarted as $i => $row) {
                if (isset($consumed[$i])) continue;
                if ($this->rowCoversGroup($g, $row)) { $match = $i; break; }
            }
            if ($match !== null) {
                $consumed[$match] = true;
                $unchanged[] = $groupEntry($g);
                $plan[] = $groupEntry($g) + ['status' => 'unchanged'];
            } else {
                $added[] = $groupEntry($g);
                $plan[] = $groupEntry($g) + ['status' => 'added'];
            }
        }
        $removed = [];
        foreach ($unstarted as $i => $row) {
            if (!isset($consumed[$i])) $removed[] = $rowEntry($row);
        }

        // Scrap proposed: the technician confirms how far the work physically
        // went (the shop runs without per-process dates) — send the CURRENT rows
        // and default "completed through" to the last NDT row.
        $scrapRows = null;
        if (($ctx['scrap']['status'] ?? null) === 'proposed') {
            $scrapRows = $rows->map(fn ($r) => $rowEntry($r) + [
                'id'         => (int) $r->id,
                'sort_order' => (int) $r->sort_order,
                'started'    => $r->date_start !== null,
            ])->values()->all();
            // Default: the FIRST NDT row — the crack is usually found at the
            // first NDT after strip/machining; work before it is done.
            $defaultKeep = collect($scrapRows)->first(fn ($e) => str_starts_with($e['name'], 'NDT'));
            $ctx['scrap']['default_keep_row_id'] = $defaultKeep['id'] ?? null;
        }

        return response()->json([
            'tdr_id'    => $ctx['tdr']->id,
            'points'    => $ctx['points'],
            'warnings'  => $ctx['warnings'],
            'orders'    => $ctx['orders'],
            'scrap'     => $ctx['scrap'],
            'scrap_rows'=> $scrapRows,
            'plan'      => $plan,
            'kept'      => $started->map($rowEntry)->values()->all(),
            'unchanged' => $unchanged,
            'added'     => $added,
            'removed'   => $removed,
        ]);
    }

    /**
     * Shared context for the rebuild (apply) and its preview: the part's Repair
     * TDR, current FAILs with their chosen rules (persisted / overridden /
     * auto-resolved), per-point rule options, defect codes.
     *
     * $ruleOverrides: param_id => rule_id — the technician's route choice for a
     * point; applied to EVERY current FAIL of that param, persisted only when
     * $persistOverrides (apply), in-memory for preview.
     *
     * Returns null when the part has no Repair TDR.
     */
    private function rebuildContext(Workorder $workorder, int $icId, array $ruleOverrides = [], bool $persistOverrides = false): ?array
    {
        // Find Repair TDR for this IC — bridge via ipl_num same as in data()
        $directComponentIds = ManualInspectionComponentVariant::where('inspection_component_id', $icId)
            ->pluck('component_id');

        $manual  = $workorder->unit->manuals;
        $useWear = $workorder->usesWearLimits();

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
            return null;
        }

        $paramIds = ManualParameter::where('inspection_component_id', $icId)->pluck('id');

        $allFails = WoMeasurement::where('workorder_id', $workorder->id)
            ->whereIn('manual_parameter_id', $paramIds)
            ->where('result', 'FAIL')
            ->orderBy('id')
            ->get(['id', 'manual_parameter_id', 'result', 'actual_value', 'codes_id', 'manual_parameter_repair_rule_id', 'stage']);

        $paramsById = ManualParameter::whereIn('id', $allFails->pluck('manual_parameter_id')->unique())
            ->with(['repairRules.triggers', 'repairRules.processes', 'codes', 'points'])
            ->get()->keyBy('id');

        // Technician's route choice — override every FAIL of the point. The rule
        // must belong to the point's parameter (foreign rules are ignored).
        foreach ($ruleOverrides as $paramId => $ruleId) {
            $param = $paramsById[(int) $paramId] ?? null;
            $rule  = $param?->repairRules->firstWhere('id', (int) $ruleId);
            if (!$rule) continue;
            foreach ($allFails->where('manual_parameter_id', (int) $paramId) as $m) {
                $m->manual_parameter_repair_rule_id = $rule->id;
                if ($persistOverrides) $m->save();
            }
        }

        // Chosen rule per FAIL: persisted/overridden, else auto-resolved
        // (the rule may have been added after the measurement was saved).
        // ROUTE REPLACEMENT (§4): a place has ONE active route — the rule of its
        // LATEST FAIL verdict wins. A gate finding recorded later (hole damage
        // after bearing removal, NDT crack) REPLACES the point's earlier route
        // instead of merging both routes into the plan.
        $chosenByParam   = [];
        $lastVerdictRule = [];
        foreach ($allFails as $m) {
            $rId = $m->manual_parameter_repair_rule_id;
            if (!$rId) {
                $param = $paramsById[$m->manual_parameter_id] ?? null;
                if ($param) {
                    $rId = $this->resolveRepairRule(
                        $param, $m->result, $m->codes_id, $useWear, null,
                        $m->actual_value !== null ? (float) $m->actual_value : null
                    );
                }
            }
            if ($rId) {
                $chosenByParam[$m->manual_parameter_id] = (int) $rId; // fails ordered by id → last wins
            }
            $lastVerdictRule[$m->manual_parameter_id] = $rId ? (int) $rId : null;
        }
        $ruleIds = array_values(array_unique(array_values($chosenByParam)));

        // Non-repair rules cannot be merged into the repair plan (order_new / ec
        // have their own flows) — surface them as warnings instead of silently
        // blending their processes in.
        $rulesById   = ManualParameterRepairRule::whereIn('id', $ruleIds)
            ->with('partOrders.component')->get()->keyBy('id');
        $mainRuleIds = [];
        $warnings    = [];
        foreach ($ruleIds as $rid) {
            $rule = $rulesById[$rid] ?? null;
            if (!$rule) continue;
            $action = $rule->action ?? ($rule->order_replacement ? 'order_new' : 'repair');
            if ($action === 'repair') {
                $mainRuleIds[] = $rid;
                continue;
            }
            $warnings[] = [
                'param_id'  => (int) $rule->manual_parameter_id,
                'param'     => (string) ($paramsById[(int) $rule->manual_parameter_id]?->description ?? ''),
                'rule_id'   => (int) $rule->id,
                'rule_name' => (string) ($rule->name ?? ''),
                'action'    => $action,
            ];
        }

        // Per-point info for the preview UI: latest FAIL + its matching rules.
        $points = [];
        foreach ($allFails->groupBy('manual_parameter_id') as $paramId => $ms) {
            $param = $paramsById[$paramId] ?? null;
            if (!$param) continue;
            $latest  = $ms->last();
            $options = $this->matchingRules($param, $latest, $useWear);
            $chosen  = $chosenByParam[$paramId] ?? null;
            if ($chosen && !collect($options)->contains(fn ($r) => (int) $r->id === $chosen)) {
                $extra = $param->repairRules->firstWhere('id', $chosen);
                if ($extra) $options[] = $extra;
            }
            $points[] = [
                'param_id'       => (int) $paramId,
                'description'    => (string) ($param->description ?? ''),
                'pt_codes'       => $param->points->pluck('code')->filter()->unique()->values()->implode(', '),
                'chosen_rule_id' => $chosen,
                'no_rule'        => $chosen === null,
                // The LATEST verdict matched no rule while an older route is
                // still active — silently keeping the old route hides the verdict
                'unmatched_verdict' => ($lastVerdictRule[$paramId] ?? null) === null && $chosen !== null,
                'options'        => collect($options)->map(fn ($r) => [
                    'id'            => (int) $r->id,
                    'name'          => (string) ($r->name ?? ''),
                    'action'        => $r->action ?? ($r->order_replacement ? 'order_new' : 'repair'),
                    'process_count' => $r->processes->count(),
                ])->values()->all(),
            ];
        }

        $orders = $this->linkedPartOrders($workorder, $tdr, $mainRuleIds, $rulesById);
        $scrap  = $this->scrapProposal($workorder, $tdr, $warnings, $allFails);

        // Scrap condemns the carrier: its linked orders (the bearing that is
        // part of the ordered assy) are offered for cancel too.
        if ($scrap) {
            foreach ($orders as &$o) {
                if (!empty($o['auto']) && ($o['status'] ?? '') === 'existing' && !empty($o['tdr_id'])) {
                    $o['status'] = 'obsolete';
                    $o['reason'] = 'scrap';
                }
            }
            unset($o);
        }

        return [
            'tdr'           => $tdr,
            'paramIds'      => $paramIds,
            'mainRuleIds'   => $mainRuleIds,
            'defectCodeIds' => $allFails->pluck('codes_id')->filter()
                ->map(fn ($v) => (int) $v)->unique()->values()->all(),
            'warnings'      => $warnings,
            'points'        => $points,
            'orders'        => $orders,
            'scrap'         => $scrap,
        ];
    }

    /**
     * §4: a scrap verdict (an active order_new-action rule on a point) means the
     * CARRIER part itself is condemned — propose ordering a replacement for it.
     * Dedup by IPL position against existing Order New TDRs of the WO.
     */
    private function scrapProposal(Workorder $workorder, Tdr $tdr, array $warnings, $allFails): ?array
    {
        $scrapRules = array_values(array_filter($warnings, fn ($w) => ($w['action'] ?? '') === 'order_new'));
        if (empty($scrapRules) || !$tdr->component) {
            return null;
        }

        // The shop orders the carrier's ASSY, not the bare part, when the Parts
        // assy grouping defines one (rod → rod assembly incl. its bearing).
        $carrier = $tdr->component;
        foreach ($carrier->assemblies as $a) {
            $assy = Component::where('manual_id', $carrier->manual_id)
                ->where(function ($q) use ($a) {
                    $q->where('ipl_num', $a->assy_ipl_num)
                      ->orWhere('part_number', $a->assy_part_number);
                })
                ->first();
            if ($assy) {
                $carrier = $assy;
                break;
            }
        }
        $baseIpl = fn (?string $ipl) => $ipl ? preg_replace('/[A-Za-z]+$/', '', trim($ipl)) : null;
        $carrierBase = $baseIpl($carrier->ipl_num);

        $existing = Tdr::where('workorder_id', $workorder->id)
            ->where('tdr_type', Tdr::TYPE_ORDER_NEW)
            ->get(['id', 'component_id', 'order_component_id'])
            ->first(function ($t) use ($carrier, $carrierBase, $baseIpl) {
                $cid = (int) ($t->order_component_id ?? $t->component_id);
                if ($cid === (int) $carrier->id) return true;
                $ipl = Component::find($cid)?->ipl_num;

                return $carrierBase !== null && $carrierBase !== '' && $baseIpl($ipl) === $carrierBase;
            });

        // Code of the scrap verdict: the latest FAIL that chose a scrap rule.
        // An NDT-context verdict orders the part as "Failed NDT" (shop code).
        $scrapRuleIds = array_map(fn ($w) => (int) $w['rule_id'], $scrapRules);
        $verdict = $allFails->last(fn ($m) => in_array((int) $m->manual_parameter_repair_rule_id, $scrapRuleIds, true));
        $verdictCodeId = $verdict?->codes_id !== null ? (int) $verdict->codes_id : null;
        if ($verdictCodeId !== null && $verdict) {
            $verdictCtx = ManualParameter::find($verdict->manual_parameter_id)
                ?->codes->firstWhere('codes_id', $verdictCodeId)?->finding_context;
            if ($verdictCtx === 'ndt') {
                $failedNdt = Code::where('name', 'Failed NDT')->first();
                if ($failedNdt) {
                    $verdictCodeId = (int) $failedNdt->id;
                }
            }
        }

        return [
            'component_id' => (int) $carrier->id,
            'ipl_num'      => $carrier->ipl_num,
            'part_number'  => $carrier->part_number,
            'name'         => $carrier->name,
            'rule_names'   => array_values(array_unique(array_map(fn ($w) => $w['rule_name'], $scrapRules))),
            'rule_id'      => $scrapRuleIds[0],
            'codes_id'     => $verdictCodeId ?? $tdr->codes_id,
            'status'       => $existing ? 'existing' : 'proposed',
            'tdr_id'       => $existing?->id,
        ];
    }

    /**
     * §5: raise accepted order proposals as linked Order New TDRs and cancel
     * confirmed obsolete linked orders (a PO number / receipt locks the TDR).
     *
     * @return array{0:array,1:array,2:array} [created, cancelled, warnings]
     */
    private function applyLinkedPartOrders(Workorder $workorder, Tdr $tdr, array $ctxOrders, array $data): array
    {
        $ordersCreated   = [];
        $ordersCancelled = [];
        $orderWarnings   = [];

        $declared = collect($ctxOrders)->keyBy('component_id');

        // Route switched but the new route still declares the component → the
        // linked order survives and re-binds to the new rule (§4).
        foreach ($ctxOrders as $co) {
            if (($co['status'] ?? '') !== 'existing' || empty($co['auto']) || empty($co['rule_ids'])) continue;
            Tdr::where('workorder_id', $workorder->id)
                ->whereKey((int) $co['tdr_id'])
                ->where('source_tdr_id', $tdr->id)
                ->whereNotIn('source_rule_id', $co['rule_ids'])
                ->update(['source_rule_id' => $co['rule_ids'][0]]);
        }

        foreach ((array) ($data['orders'] ?? []) as $o) {
            if (empty($o['accept'])) continue;
            $cid  = (int) $o['component_id'];
            $decl = $declared[$cid] ?? null;
            if (!$decl || $decl['status'] !== 'proposed') continue; // unknown / already ordered
            $component = Component::find($cid);
            if (!$component) continue;
            $orderTdr = Tdr::create([
                'tdr_type'           => Tdr::TYPE_ORDER_NEW,
                'workorder_id'       => $workorder->id,
                'component_id'       => $component->id,
                'order_component_id' => $component->id,
                'serial_number'      => 'NSN',
                'description'        => $component->name,
                // Inherit the carrier repair TDR's code/condition — an ordered
                // part without codes_id vanishes from Ordered Parts / PRL
                // (scopeOrderedParts filters codes_id != missing, NULL fails it).
                'codes_id'           => $tdr->codes_id,
                'conditions_id'      => $tdr->conditions_id,
                'necessaries_id'     => Necessary::firstOrCreate(['name' => 'Order New'])->id,
                'qty'                => max(1, (int) ($o['qty'] ?? $decl['qty'])),
                'use_tdr'            => true,
                'use_process_forms'  => false,
                'source_rule_id'     => $decl['rule_ids'][0] ?? null,
                'source_tdr_id'      => $tdr->id,
            ]);
            $ordersCreated[] = [
                'tdr_id'      => $orderTdr->id,
                'ipl_num'     => $component->ipl_num,
                'part_number' => $component->part_number,
                'name'        => $component->name,
                'qty'         => (int) $orderTdr->qty,
            ];
        }

        foreach ((array) ($data['cancel_order_tdr_ids'] ?? []) as $cancelId) {
            $t = Tdr::where('workorder_id', $workorder->id)
                ->whereKey((int) $cancelId)
                ->where('tdr_type', Tdr::TYPE_ORDER_NEW)
                ->where('source_tdr_id', $tdr->id)
                ->first();
            if (!$t) continue;
            if ($t->po_num !== null || $t->received !== null) {
                $orderWarnings[] = 'Order TDR #' . $t->id . ' has a PO / receipt — not cancelled';
                continue;
            }
            $t->delete();
            $ordersCancelled[] = (int) $cancelId;
        }

        return [$ordersCreated, $ordersCancelled, $orderWarnings];
    }

    /**
     * Pick the right VARIANT of the ordered position for this WO via the Parts
     * assy grouping (ComponentAssembly): among the position's variants (same
     * base IPL, letter suffix stripped) prefer the one whose assy list
     * intersects the CARRIER part's assy list (which rod → which bearing);
     * fall back to the WO unit's part number, then to the declared component.
     * Positions with unfilled assy data keep today's behavior.
     */
    private function resolveOrderVariant(Component $declared, Tdr $tdr, Workorder $workorder): Component
    {
        $base = preg_replace('/[A-Za-z]+$/', '', trim((string) $declared->ipl_num));
        if ($base === '' || $base === null) {
            return $declared;
        }

        $variants = Component::where('manual_id', $declared->manual_id)
            ->where('ipl_num', 'like', $base . '%')
            ->with('assemblies')
            ->get()
            ->filter(fn ($c) => preg_replace('/[A-Za-z]+$/', '', trim((string) $c->ipl_num)) === $base)
            ->values();
        if ($variants->count() <= 1) {
            return $declared;
        }

        $carrierAssyPns = $tdr->component
            ? $tdr->component->assemblies->map(fn ($a) => trim((string) $a->assy_part_number))->filter()->unique()
            : collect();
        if ($carrierAssyPns->isNotEmpty()) {
            $match = $variants->first(fn ($c) => $c->assemblies
                ->contains(fn ($a) => $carrierAssyPns->contains(trim((string) $a->assy_part_number))));
            if ($match) {
                return $match;
            }
        }

        $unitPn = trim((string) ($workorder->unit->part_number ?? ''));
        if ($unitPn !== '') {
            $match = $variants->first(fn ($c) => $c->assemblies
                ->contains(fn ($a) => trim((string) $a->assy_part_number) === $unitPn));
            if ($match) {
                return $match;
            }
        }

        return $declared;
    }

    /**
     * §5 linked part orders: components the ACTIVE routes declare for ordering,
     * matched against Order New TDRs already on the WO.
     *   proposed — declared, nothing ordered yet → offer to raise a linked TDR
     *   existing — an Order New TDR for this component already exists
     *              (auto=true when it is a linked one, false when manual)
     *   obsolete — a linked TDR raised by THIS part's plan whose component is
     *              no longer declared by any active route → offer to cancel
     *              (locked once a PO number / receipt exists)
     */
    private function linkedPartOrders(Workorder $workorder, Tdr $tdr, array $mainRuleIds, $rulesById): array
    {
        $declared = [];
        foreach ($mainRuleIds as $rid) {
            $rule = $rulesById[$rid] ?? null;
            foreach ($rule?->partOrders ?? [] as $po) {
                if (!$po->component) continue;
                // The declaration names A variant of the position; the actual
                // variant for THIS WO is resolved via the Parts assy grouping.
                $component = $this->resolveOrderVariant($po->component, $tdr, $workorder);
                $cid = (int) $component->id;
                $d = $declared[$cid] ?? [
                    'component_id' => $cid,
                    'ipl_num'      => $component->ipl_num,
                    'part_number'  => $component->part_number,
                    'name'         => $component->name,
                    'qty'          => 0,
                    'notes'        => [],
                    'rule_ids'     => [],
                    'rule_names'   => [],
                ];
                $d['qty'] = max($d['qty'], max(1, (int) $po->qty));
                if ($po->note) $d['notes'][] = $po->note;
                $d['rule_ids'][]   = (int) $rid;
                $d['rule_names'][] = (string) ($rule->name ?? '');
                $declared[$cid] = $d;
            }
        }

        $existing = Tdr::where('workorder_id', $workorder->id)
            ->where('tdr_type', Tdr::TYPE_ORDER_NEW)
            ->get(['id', 'component_id', 'order_component_id', 'qty', 'po_num', 'received', 'source_rule_id', 'source_tdr_id']);

        // Dedup is per IPL POSITION, not per part number: the same P/N lives at
        // different positions (1-380 rod / 1-400 cylinder) and BOTH must be
        // ordered. Variants of one position (1-380 / 1-380A) ARE the same order.
        $baseIpl = fn (?string $ipl) => $ipl ? preg_replace('/[A-Za-z]+$/', '', trim($ipl)) : null;
        $existingComponentIds = $existing->toBase()
            ->map(fn ($t) => (int) ($t->order_component_id ?? $t->component_id))->filter()->unique()->values();
        $iplByComponent = Component::whereIn('id', $existingComponentIds->merge(array_keys($declared)))
            ->pluck('ipl_num', 'id');

        $orders = [];
        foreach ($declared as $cid => $d) {
            $declBase = $baseIpl($iplByComponent[$cid] ?? $d['ipl_num']);
            $match = $existing->first(function ($t) use ($cid, $declBase, $baseIpl, $iplByComponent) {
                $ecid = (int) ($t->order_component_id ?? $t->component_id);
                if ($ecid === $cid) return true;

                return $declBase !== null && $declBase !== ''
                    && $baseIpl($iplByComponent[$ecid] ?? null) === $declBase;
            });
            $orders[] = [
                'component_id' => $cid,
                'ipl_num'      => $d['ipl_num'],
                'part_number'  => $d['part_number'],
                'name'         => $d['name'],
                'qty'          => $d['qty'],
                'note'         => implode('; ', array_unique($d['notes'])),
                'rule_names'   => array_values(array_unique($d['rule_names'])),
                'rule_ids'     => $d['rule_ids'],
                'status'       => $match ? 'existing' : 'proposed',
                'tdr_id'       => $match?->id,
                'auto'         => (bool) $match?->source_tdr_id,
                'locked'       => false,
            ];
        }

        // Linked orders of THIS plan whose component is no longer declared
        foreach ($existing as $t) {
            if ((int) $t->source_tdr_id !== (int) $tdr->id) continue;
            $cid = (int) ($t->order_component_id ?? $t->component_id);
            if (isset($declared[$cid])) continue;
            $comp = Component::find($cid);
            $orders[] = [
                'component_id' => $cid,
                'ipl_num'      => $comp?->ipl_num,
                'part_number'  => $comp?->part_number,
                'name'         => $comp?->name,
                'qty'          => (int) $t->qty,
                'note'         => '',
                'rule_names'   => [],
                'rule_ids'     => [],
                'status'       => 'obsolete',
                'tdr_id'       => (int) $t->id,
                'auto'         => true,
                'locked'       => $t->po_num !== null || $t->received !== null,
            ];
        }

        return $orders;
    }

    /**
     * ALL rules of the parameter matching this measurement — the preview's
     * radio options. Mirrors tdrMatchingRules() in measurements/tab.js.
     */
    private function matchingRules(ManualParameter $param, WoMeasurement $m, bool $useWear): array
    {
        $codesId = $m->codes_id ? (int) $m->codes_id : null;
        $findingContext = null;
        if ($codesId) {
            $pc = $param->codes->firstWhere('codes_id', $codesId);
            $findingContext = $pc?->finding_context ?? 'inspection';
        }

        $limits = $param->effectiveLimits($useWear);
        $v = $m->actual_value !== null ? (float) $m->actual_value : null;
        $dimFail = $limits['min'] !== null && $limits['max'] !== null && $v !== null
            && !($v >= (float) $limits['min'] && $v <= (float) $limits['max']);
        // Trigger family follows the limits actually applied (orig fallback in wear mode)
        $srcWear = ($limits['source'] ?? null) === 'wear';
        $failTriggers = $srcWear ? ['below_wear', 'above_wear'] : ['below_orig', 'above_orig'];

        return $param->repairRules->filter(function ($rule) use ($codesId, $findingContext, $dimFail, $failTriggers, $v, $limits) {
            $trigs = $rule->triggers;
            if ($codesId) {
                if ($findingContext === 'ndt') {
                    if ($trigs->contains(fn ($t) => $t->trigger === 'finding_ndt'
                        && ($t->codes_id === null || (int) $t->codes_id === $codesId))) {
                        return true;
                    }
                } elseif ($findingContext === 'measurement') {
                    if ($trigs->contains(fn ($t) => $t->trigger === 'finding_measurement'
                        && ($t->codes_id === null || (int) $t->codes_id === $codesId))) {
                        return true;
                    }
                } elseif ($trigs->contains(fn ($t) => in_array($t->trigger, ['finding_inspection', 'finding'], true)
                    && ($t->codes_id === null || (int) $t->codes_id === $codesId))) {
                    return true;
                }
            }

            return $dimFail && $trigs->contains(fn ($t) => in_array($t->trigger, $failTriggers, true)
                && $t->acceptsExceedance($this->triggerExceedance($t, $v, $limits)));
        })->values()->all();
    }

    /**
     * Run the repair pipeline for the part and return the valid ordered groups.
     */
    private function plannedGroups(int $icId, array $mainRuleIds, array $defectCodeIds): array
    {
        if (empty($mainRuleIds)) {
            return [];
        }

        $ctx = new PipelineContext();
        $ctx->inspectionComponentId = $icId;
        $ctx->mainRuleIds           = $mainRuleIds;
        $ctx->defectCodeIds         = $defectCodeIds;
        $ctx->heldPendingEc         = false;

        app(RepairPipeline::class)->run($ctx);

        return $this->foldConsecutiveNdt(array_values(array_filter(
            $ctx->orderedGroups(),
            fn ($g) => $this->isValidProcessNameId($g['process_names_id'] ?? null)
        )));
    }

    /**
     * Fold CONSECUTIVE NDT-x groups into one combined row (NDT-1 / NDT-4) —
     * the shop performs adjacent NDT together; TdrProcess.plus_process and the
     * NDT print forms already support a multi-NDT row. Non-adjacent NDT stay
     * separate (there is work between them).
     */
    private function foldConsecutiveNdt(array $groups): array
    {
        $nameOf = fn (int $id) => (string) (ProcessName::find($id)?->name ?? '');
        $isNdt  = fn (int $id) => str_starts_with($nameOf($id), 'NDT-');

        $out = [];
        foreach ($groups as $g) {
            $nameId = (int) $g['process_names_id'];
            $prev   = $out ? count($out) - 1 : null;
            if ($prev !== null
                && ($out[$prev]['phase'] ?? 'main') === ($g['phase'] ?? 'main')
                && $isNdt((int) $out[$prev]['process_names_id'])
                && $isNdt($nameId)
                && $nameId !== (int) $out[$prev]['process_names_id']) {
                $p = &$out[$prev];
                $p['plus_process_name_ids'] = array_values(array_unique(array_merge(
                    $p['plus_process_name_ids'] ?? [],
                    [$nameId],
                    (array) ($g['plus_process_name_ids'] ?? [])
                )));
                $p['process_ids'] = array_values(array_unique(array_merge(
                    (array) ($p['process_ids'] ?? []), (array) ($g['process_ids'] ?? [])
                )));
                $p['rule_process_ids'] = array_values(array_unique(array_merge(
                    (array) ($p['rule_process_ids'] ?? []), (array) ($g['rule_process_ids'] ?? [])
                )));
                $p['phase_rule_process_ids'] = array_values(array_unique(array_merge(
                    (array) ($p['phase_rule_process_ids'] ?? []), (array) ($g['phase_rule_process_ids'] ?? [])
                )));
                $p['description'] = implode('; ', array_unique(array_filter([
                    (string) ($p['description'] ?? ''), (string) ($g['description'] ?? ''),
                ])));
                unset($p);
                continue;
            }
            $out[] = $g;
        }

        return $out;
    }

    private function isValidProcessNameId(mixed $value): bool
    {
        $id = (int) ($value ?? 0);

        return $id > 0 && ProcessName::query()->whereKey($id)->exists();
    }

    /**
     * Does a STARTED TdrProcess row already cover this pipeline group (i.e. the
     * same plan node)? Covered = same process name AND overlapping source ids
     * (rule_process_ids for Main, phase_rule_process_ids for Start/Finish).
     * A row/group without any source ids falls back to name-only matching
     * (legacy rows, the EC row).
     */
    private function coveredByStartedRow(array $group, $startedRows): bool
    {
        foreach ($startedRows as $row) {
            if ($this->rowCoversGroup($group, $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this TdrProcess row the same plan node as the pipeline group?
     * Same process name AND overlapping source ids; a row/group without any
     * source ids falls back to name-only matching (legacy rows, the EC row).
     */
    private function rowCoversGroup(array $group, $row): bool
    {
        if ((int) $row->process_names_id !== (int) ($group['process_names_id'] ?? 0)) {
            return false;
        }

        $groupIds = array_map('intval', array_merge(
            (array) ($group['rule_process_ids'] ?? []),
            (array) ($group['phase_rule_process_ids'] ?? [])
        ));
        $rowIds = array_map('intval', array_merge(
            (array) ($row->rule_process_ids ?? []),
            (array) ($row->phase_rule_process_ids ?? [])
        ));

        if (empty($groupIds) || empty($rowIds)) {
            return true; // no node identity on either side → name match
        }

        return (bool) array_intersect($groupIds, $rowIds);
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

        // §5: linked auto-orders follow their repair TDR (unless purchasing started)
        Tdr::where('workorder_id', $workorder->id)
            ->whereIn('source_tdr_id', $tdrIds)
            ->whereNull('po_num')
            ->whereNull('received')
            ->delete();

        TdrProcess::whereIn('tdrs_id', $tdrIds)->delete();
        Tdr::whereIn('id', $tdrIds)->delete();

        return response()->json(['ok' => true, 'deleted_tdrs' => $tdrIds->count()]);
    }

    public function destroy(WoMeasurement $woMeasurement)
    {
        // Deleting a measurement can only LOWER the part's max measurement id,
        // so the "newer measurements exist" check would never re-arm the Update
        // button. Reset the sync point of the part's Repair TDR(s) instead.
        $icId = ManualParameter::find($woMeasurement->manual_parameter_id)?->inspection_component_id;
        $resyncIcId = null;
        if ($icId) {
            $componentIds = ManualInspectionComponentVariant::where('inspection_component_id', $icId)
                ->pluck('component_id');
            $affected = Tdr::where('workorder_id', $woMeasurement->workorder_id)
                ->where('tdr_type', Tdr::TYPE_COMPONENT_TDR)
                ->whereIn('component_id', $componentIds)
                ->where('last_synced_measurement_id', '>=', $woMeasurement->id)
                ->update(['last_synced_measurement_id' => null]);
            if ($affected) {
                $resyncIcId = (int) $icId;
            }
        }

        $woMeasurement->delete();

        return response()->json(['ok' => true, 'resync_ic_id' => $resyncIcId]);
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

    /**
     * Exceedance for a dimensional trigger: how far the value is past the limit
     * on the trigger's side (above max / below min). Null when unknown.
     */
    private function triggerExceedance(ManualParameterRuleTrigger $t, ?float $value, array $limits): ?float
    {
        if ($value === null) {
            return null;
        }
        if (in_array($t->trigger, ['above_orig', 'above_wear'], true)) {
            return $limits['max'] !== null ? $value - (float) $limits['max'] : null;
        }

        return $limits['min'] !== null ? (float) $limits['min'] - $value : null;
    }

    private function resolveRepairRule(ManualParameter $parameter, ?string $result, ?int $codesId, bool $useWear, ?string $findingContext = null, ?float $value = null): ?int
    {
        $rules = $parameter->repairRules;

        if ($codesId) {
            if ($findingContext === 'ndt') {
                // Gate verdict from NDT (§4): match finding_ndt triggers
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_ndt' && (int)$t->codes_id === $codesId)) {
                        return $rule->id;
                    }
                }
                foreach ($rules as $rule) {
                    if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding_ndt' && $t->codes_id === null)) {
                        return $rule->id;
                    }
                }
            } elseif ($findingContext === 'measurement') {
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

        // Dimensional FAIL (no explicit finding selected). Banded triggers
        // (min/max delta, §8a) match only when the exceedance falls in the band —
        // e.g. Silver up to 0.010" over max, Chrome-in-hole beyond that.
        if ($result === 'FAIL') {
            // Trigger family follows the limits ACTUALLY applied: a wear-mode WO
            // whose parameter has no wear limits falls back to orig limits, so
            // orig triggers must match (mirrors tdrMatchingRules in tab.js).
            $limits = $parameter->effectiveLimits($useWear);
            $srcWear = ($limits['source'] ?? null) === 'wear';
            $failTriggers = $srcWear ? ['below_wear', 'above_wear'] : ['below_orig', 'above_orig'];
            foreach ($rules as $rule) {
                $hit = $rule->triggers->contains(fn($t) => in_array($t->trigger, $failTriggers)
                    && $t->acceptsExceedance($this->triggerExceedance($t, $value, $limits)));
                if ($hit) {
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
