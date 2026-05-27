<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\ManualParameter;
use App\Models\ManualParameterRepairRule;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\WoMeasurement;
use App\Models\Workorder;
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
        $useWear = $workorder->instruction?->name === 'Repair';

        $inspectionComponents = $manual->inspectionComponents()
            ->with('variants.component')
            ->get()
            ->map(fn($ic) => ['id' => $ic->id, 'label' => $ic->label]);

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
                    ->map(fn($c) => ['id' => $c->codes_id, 'name' => $c->code->name])
                    ->values(),
                'repair_rules'            => $p->repairRules->map(fn($r) => [
                    'id'               => $r->id,
                    'name'             => $r->name,
                    'order_replacement'=> $r->order_replacement,
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

        return response()->json([
            'use_wear'             => $useWear,
            'inspection_components'=> $inspectionComponents,
            'figures'              => $figures,
            'parameters'           => $parameters,
            'measurements'         => $measurements,
            'codes'                => $codes,
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

        $parameter = ManualParameter::with('repairRules.triggers')->findOrFail($data['manual_parameter_id']);
        $useWear   = $workorder->instruction?->name === 'Repair';
        $limits    = $parameter->effectiveLimits($useWear);

        $data['limits_source'] = $limits['source'];
        $data['result']        = $this->computeResult($data['actual_value'] ?? null, $limits);

        // Missing part → always FAIL
        $missingCode = Code::where('name', 'Missing')->first();
        if ($missingCode && (int)($data['codes_id'] ?? 0) === $missingCode->id) {
            $data['result'] = 'FAIL';
        }

        $data['user_id']       = auth()->id();
        $data['workorder_id']  = $workorder->id;

        // Auto-select repair rule
        $data['manual_parameter_repair_rule_id'] = $this->resolveRepairRule(
            $parameter,
            $data['result'],
            $data['codes_id'] ?? null,
            $useWear
        );

        $measurement = WoMeasurement::create($data);

        return response()->json($measurement->load(['user']), 201);
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

        $qty = $data['qty'] ?? 1;
        $sn  = $data['sn'] ?: 'NSN';

        // ── Missing Part ─────────────────────────────────────────────
        if (!empty($data['missing_meas_id'])) {
            $missingMeas      = WoMeasurement::findOrFail($data['missing_meas_id']);
            $missingCode      = Code::where('name', 'Missing')->first();
            $necessary        = Necessary::where('name', 'Order New')->firstOrFail();
            $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();

            $tdr = Tdr::create([
                'tdr_type'          => Tdr::TYPE_ORDER_NEW,
                'workorder_id'      => $workorder->id,
                'component_id'      => $component->id,
                'serial_number'     => $sn,
                'codes_id'          => $missingCode?->id,
                'conditions_id'     => $missingCondition?->id,
                'necessaries_id'    => $necessary->id,
                'qty'               => $qty,
                'use_tdr'           => true,
                'use_process_forms' => false,
            ]);

            return response()->json([
                'tdr_id'   => $tdr->id,
                'tdr_type' => $tdr->tdr_type,
                'component'=> $component->part_number . ' — ' . $component->name,
            ], 201);
        }

        $ruleIds = $data['rule_ids'] ?? [];

        // ── Order New override (no rule, user chose Order New manually) ─
        if (!empty($data['order_new_override'])) {
            $necessary = Necessary::where('name', 'Order New')->firstOrFail();
            $tdr = Tdr::create([
                'tdr_type'          => Tdr::TYPE_ORDER_NEW,
                'workorder_id'      => $workorder->id,
                'component_id'      => $component->id,
                'serial_number'     => $sn,
                'codes_id'          => $measurement->codes_id,
                'necessaries_id'    => $necessary->id,
                'qty'               => $qty,
                'use_tdr'           => true,
                'use_process_forms' => false,
            ]);
            return response()->json([
                'tdr_id'   => $tdr->id,
                'tdr_type' => $tdr->tdr_type,
                'component'=> $component->part_number . ' — ' . $component->name,
            ], 201);
        }

        // ── Order New (single rule, order_replacement=true) ──────────
        if (!empty($ruleIds)) {
            $firstRule = ManualParameterRepairRule::find($ruleIds[0]);
            if ($firstRule?->order_replacement) {
                $necessary = Necessary::where('name', 'Order New')->firstOrFail();
                $tdr = Tdr::create([
                    'tdr_type'          => Tdr::TYPE_ORDER_NEW,
                    'workorder_id'      => $workorder->id,
                    'component_id'      => $component->id,
                    'serial_number'     => $sn,
                    'codes_id'          => $measurement->codes_id,
                    'necessaries_id'    => $necessary->id,
                    'qty'               => $qty,
                    'use_tdr'           => true,
                    'use_process_forms' => false,
                ]);
                return response()->json([
                    'tdr_id'   => $tdr->id,
                    'tdr_type' => $tdr->tdr_type,
                    'component'=> $component->part_number . ' — ' . $component->name,
                ], 201);
            }
        }

        // ── Repair: combine processes from all selected rules ────────
        $necessary = Necessary::where('name', 'Repair')->firstOrFail();
        $tdr = Tdr::create([
            'tdr_type'          => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id'      => $workorder->id,
            'component_id'      => $component->id,
            'serial_number'     => $sn,
            'codes_id'          => $measurement->codes_id,
            'necessaries_id'    => $necessary->id,
            'qty'               => $qty,
            'use_tdr'           => true,
            'use_process_forms' => true,
        ]);

        if (!empty($ruleIds)) {
            $rules = ManualParameterRepairRule::with('processes.manualProcess.process')
                ->whereIn('id', $ruleIds)->get();

            $grouped   = [];
            $sortIndex = 0;
            foreach ($rules as $rule) {
                foreach ($rule->processes as $rp) {
                    $process = $rp->manualProcess?->process;
                    if (!$process) continue;
                    $nameId = $process->process_names_id;
                    if (!isset($grouped[$nameId])) {
                        $grouped[$nameId] = ['sort_order' => $sortIndex++, 'process_ids' => []];
                    }
                    if (!in_array($process->id, $grouped[$nameId]['process_ids'])) {
                        $grouped[$nameId]['process_ids'][] = $process->id;
                    }
                }
            }

            foreach ($grouped as $processNameId => $group) {
                TdrProcess::create([
                    'tdrs_id'          => $tdr->id,
                    'process_names_id' => $processNameId,
                    'processes'        => $group['process_ids'],
                    'sort_order'       => $group['sort_order'],
                    'in_traveler'      => true,
                ]);
            }
        }

        return response()->json([
            'tdr_id'     => $tdr->id,
            'tdr_type'   => $tdr->tdr_type,
            'component'  => $component->part_number . ' — ' . $component->name,
        ], 201);
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

    private function resolveRepairRule(ManualParameter $parameter, ?string $result, ?int $codesId, bool $useWear): ?int
    {
        $rules = $parameter->repairRules;

        // Finding-based: specific code match first
        if ($codesId) {
            foreach ($rules as $rule) {
                if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding' && (int)$t->codes_id === $codesId)) {
                    return $rule->id;
                }
            }
            // Any defect finding
            foreach ($rules as $rule) {
                if ($rule->triggers->contains(fn($t) => $t->trigger === 'finding' && $t->codes_id === null)) {
                    return $rule->id;
                }
            }
        }

        // Measurement FAIL
        if ($result === 'FAIL') {
            $failTriggers = $useWear ? ['below_wear', 'above_wear'] : ['below_orig', 'above_orig'];
            foreach ($rules as $rule) {
                if ($rule->triggers->contains(fn($t) => in_array($t->trigger, $failTriggers))) {
                    return $rule->id;
                }
            }
        }

        return null;
    }
}
