<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualDimensionPoint;
use App\Models\ManualParameter;
use App\Models\ManualParameterCode;
use App\Models\ManualParameterRepairRule;
use App\Models\ManualParameterRuleProcess;
use App\Models\ManualParameterRuleTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualParameterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ── Parameters ────────────────────────────────────────────────

    public function index(Manual $manual)
    {
        $parameters = ManualParameter::where('manual_id', $manual->id)
            ->with([
                'codes.code',
                'repairRules.triggers.code',
                'repairRules.processes.manualProcess.process.process_name',
                'points',
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => $this->parameterPayload($p));

        return response()->json($parameters);
    }

    public function store(Request $request, ManualDimensionPoint $manualDimensionPoint)
    {
        $data = $request->validate([
            'manual_parameter_id'    => 'nullable|exists:manual_parameters,id',
            'description'            => 'required_without:manual_parameter_id|string|max:255',
            'inspection_component_id'=> 'nullable|exists:manual_inspection_components,id',
            'is_required'            => 'boolean',
            'requires_value'         => 'boolean',
            'qty'                    => 'nullable|integer|min:1|max:99',
            'orig_dim_min'           => 'nullable|numeric',
            'orig_dim_max'           => 'nullable|numeric',
            'wear_dim_min'           => 'nullable|numeric',
            'wear_dim_max'           => 'nullable|numeric',
            'repair_dim_min'         => 'nullable|numeric',
            'repair_dim_max'         => 'nullable|numeric',
            'interference_value'     => 'nullable|numeric',
            'flange_clearance_min'   => 'nullable|numeric',
            'flange_clearance_max'   => 'nullable|numeric',
            'repair_surface_side'    => 'nullable|in:A,B,both',
            'max_repair_depth_a'     => 'nullable|numeric',
            'max_repair_depth_b'     => 'nullable|numeric',
            'inspection'             => 'nullable|string',
            'sort_order'             => 'nullable|integer',
            'codes_ids'              => 'nullable|array',
            'codes_ids.*'            => 'exists:codes,id',
        ]);

        $manualId = $manualDimensionPoint->figure->manual_id;

        if (!empty($data['manual_parameter_id'])) {
            $parameter = ManualParameter::findOrFail($data['manual_parameter_id']);
        } else {
            $parameter = ManualParameter::create([
                'manual_id'               => $manualId,
                'inspection_component_id' => $data['inspection_component_id'] ?? null,
                'description'             => $data['description'],
                'is_required'             => $data['is_required'] ?? true,
                'qty'                     => $data['qty'] ?? 1,
                'orig_dim_min'            => $data['orig_dim_min'] ?? null,
                'orig_dim_max'            => $data['orig_dim_max'] ?? null,
                'wear_dim_min'            => $data['wear_dim_min'] ?? null,
                'wear_dim_max'            => $data['wear_dim_max'] ?? null,
                'repair_dim_min'          => $data['repair_dim_min'] ?? null,
                'repair_dim_max'          => $data['repair_dim_max'] ?? null,
                'interference_value'      => $data['interference_value'] ?? null,
                'flange_clearance_min'    => $data['flange_clearance_min'] ?? null,
                'flange_clearance_max'    => $data['flange_clearance_max'] ?? null,
                'inspection'              => $data['inspection'] ?? null,
                'sort_order'              => $data['sort_order'] ?? 0,
            ]);

            foreach ($data['codes_ids'] ?? [] as $codeId) {
                ManualParameterCode::firstOrCreate([
                    'manual_parameter_id' => $parameter->id,
                    'codes_id'            => $codeId,
                ]);
            }
        }

        $parameter->points()->syncWithoutDetaching([$manualDimensionPoint->id]);

        return response()->json($this->parameterPayload($parameter), 201);
    }

    public function update(Request $request, ManualParameter $manualParameter)
    {
        $data = $request->validate([
            'description'            => 'sometimes|string|max:255',
            'inspection_component_id'=> 'nullable|exists:manual_inspection_components,id',
            'is_required'            => 'boolean',
            'requires_value'         => 'boolean',
            'qty'                    => 'nullable|integer|min:1|max:99',
            'orig_dim_min'           => 'nullable|numeric',
            'orig_dim_max'           => 'nullable|numeric',
            'wear_dim_min'           => 'nullable|numeric',
            'wear_dim_max'           => 'nullable|numeric',
            'repair_dim_min'         => 'nullable|numeric',
            'repair_dim_max'         => 'nullable|numeric',
            'interference_value'     => 'nullable|numeric',
            'flange_clearance_min'   => 'nullable|numeric',
            'flange_clearance_max'   => 'nullable|numeric',
            'repair_surface_side'    => 'nullable|in:A,B,both',
            'max_repair_depth_a'     => 'nullable|numeric',
            'max_repair_depth_b'     => 'nullable|numeric',
            'inspection'             => 'nullable|string',
            'sort_order'             => 'nullable|integer',
        ]);

        $manualParameter->update($data);

        return response()->json($this->parameterPayload($manualParameter->fresh()));
    }

    public function destroy(ManualParameter $manualParameter)
    {
        $manualParameter->delete();

        return response()->json(['ok' => true]);
    }

    public function detachPoint(ManualParameter $manualParameter, ManualDimensionPoint $manualDimensionPoint)
    {
        $manualParameter->points()->detach($manualDimensionPoint->id);

        if ($manualParameter->points()->count() === 0) {
            $manualParameter->delete();
            return response()->json(['ok' => true, 'deleted' => true]);
        }

        return response()->json(['ok' => true, 'deleted' => false]);
    }

    // ── Codes ─────────────────────────────────────────────────────

    public function storeCode(Request $request, ManualParameter $manualParameter)
    {
        $data = $request->validate([
            'codes_id'        => 'required|exists:codes,id',
            'finding_context' => 'nullable|in:measurement,inspection',
        ]);

        $code = ManualParameterCode::firstOrCreate(
            [
                'manual_parameter_id' => $manualParameter->id,
                'codes_id'            => $data['codes_id'],
            ],
            [
                'finding_context' => $data['finding_context'] ?? 'inspection',
            ]
        );

        return response()->json($code->load('code'), 201);
    }

    public function destroyCode(ManualParameterCode $manualParameterCode)
    {
        $manualParameterCode->delete();

        return response()->json(['ok' => true]);
    }

    // ── Repair Rules ──────────────────────────────────────────────

    public function storeRule(Request $request, ManualParameter $manualParameter)
    {
        $data = $request->validate([
            'name'              => 'nullable|string|max:100',
            'order_replacement' => 'boolean',
            'action'            => 'nullable|in:repair,order_new,ec',
            'notes'             => 'nullable|string',
            'processes'                     => 'nullable|array',
            'processes.*.id'                => 'nullable|integer',
            'processes.*.is_gate'           => 'boolean',
            'processes.*.manual_process_id' => 'required|exists:manual_processes,id',
            'processes.*.description'       => 'nullable|string|max:255',
            'processes.*.sort_order'        => 'integer',
            'triggers'                      => 'required|array|min:1',
            'triggers.*.trigger'            => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,finding_measurement,finding_inspection,manual',
            'triggers.*.codes_id'           => 'nullable|exists:codes,id',
        ]);

        $action = $this->resolveAction($data);
        $rule = ManualParameterRepairRule::create([
            'manual_parameter_id' => $manualParameter->id,
            'name'                => $data['name'] ?? null,
            'action'              => $action,
            'order_replacement'   => $action === 'order_new', // keep legacy bool in sync
            'notes'               => $data['notes'] ?? null,
        ]);

        $this->syncRuleProcesses($rule, $data['processes'] ?? []);
        $this->syncRuleTriggers($rule, $data['triggers']);

        return response()->json($this->rulePayload($rule), 201);
    }

    public function updateRule(Request $request, ManualParameterRepairRule $manualParameterRepairRule)
    {
        $data = $request->validate([
            'name'              => 'nullable|string|max:100',
            'order_replacement' => 'boolean',
            'action'            => 'nullable|in:repair,order_new,ec',
            'notes'             => 'nullable|string',
            'processes'                     => 'nullable|array',
            'processes.*.id'                => 'nullable|integer',
            'processes.*.is_gate'           => 'boolean',
            'processes.*.manual_process_id' => 'required|exists:manual_processes,id',
            'processes.*.description'       => 'nullable|string|max:255',
            'processes.*.sort_order'        => 'integer',
            'triggers'                      => 'required|array|min:1',
            'triggers.*.trigger'            => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,finding_measurement,finding_inspection,manual',
            'triggers.*.codes_id'           => 'nullable|exists:codes,id',
        ]);

        $action = $this->resolveAction($data);
        $manualParameterRepairRule->update([
            'name'              => $data['name'] ?? null,
            'action'            => $action,
            'order_replacement' => $action === 'order_new', // keep legacy bool in sync
            'notes'             => $data['notes'] ?? null,
        ]);

        $this->syncRuleProcesses($manualParameterRepairRule, $data['processes'] ?? []);
        $this->syncRuleTriggers($manualParameterRepairRule, $data['triggers']);

        return response()->json($this->rulePayload($manualParameterRepairRule));
    }

    public function destroyRule(ManualParameterRepairRule $manualParameterRepairRule)
    {
        $manualParameterRepairRule->delete();

        return response()->json(['ok' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Rule outcome: prefer explicit `action`, fall back to the legacy order_replacement bool. */
    private function resolveAction(array $data): string
    {
        if (!empty($data['action']) && in_array($data['action'], ['repair', 'order_new', 'ec'], true)) {
            return $data['action'];
        }
        return !empty($data['order_replacement']) ? 'order_new' : 'repair';
    }

    private function syncRuleProcesses(ManualParameterRepairRule $rule, array $processes): void
    {
        // Upsert (NOT delete-all+recreate): keep existing rule-process ids so their
        // attached documents survive a rule edit (adding a description, reordering…).
        // Only ONE gate anchor per rule — keep the first flagged process.
        $gateSeen = false;
        $keepIds = [];
        foreach ($processes as $i => $p) {
            $isGate = !empty($p['is_gate']) && !$gateSeen;
            if ($isGate) {
                $gateSeen = true;
            }
            $attrs = [
                'manual_process_id' => $p['manual_process_id'],
                'description'       => $p['description'] ?? null,
                'is_gate'           => $isGate,
                'sort_order'        => $p['sort_order'] ?? $i,
            ];
            $existing = !empty($p['id']) ? $rule->processes()->whereKey($p['id'])->first() : null;
            if ($existing) {
                $existing->update($attrs);
                $keepIds[] = $existing->id;
            } else {
                $keepIds[] = $rule->processes()->create($attrs)->id;
            }
        }
        $rule->processes()->whereNotIn('id', $keepIds ?: [0])->delete();
    }

    private function syncRuleTriggers(ManualParameterRepairRule $rule, array $triggers): void
    {
        $rule->triggers()->delete();
        $findingTypes = ['finding', 'finding_measurement', 'finding_inspection'];
        foreach ($triggers as $t) {
            ManualParameterRuleTrigger::create([
                'repair_rule_id' => $rule->id,
                'trigger'        => $t['trigger'],
                'codes_id'       => in_array($t['trigger'], $findingTypes) ? ($t['codes_id'] ?? null) : null,
            ]);
        }
    }

    private function rulePayload(ManualParameterRepairRule $rule): array
    {
        $rule->load(['triggers.code', 'processes.manualProcess.process.process_name', 'processes.documents.pages']);
        $data = $rule->toArray();
        // always expose a usable action (fallback from legacy bool if column not yet present)
        $data['action'] = $rule->action ?? ($rule->order_replacement ? 'order_new' : 'repair');
        $data['triggers'] = $rule->triggers->map(fn($t) => [
            'id'        => $t->id,
            'trigger'   => $t->trigger,
            'codes_id'  => $t->codes_id,
            'code_name' => $t->code?->name,
        ])->values()->all();
        $data['processes'] = $rule->processes->map(function ($rp) {
            $mp    = $rp->manualProcess;
            $pn    = $mp?->process?->process_name;
            $label = trim(($pn?->name ?? '') . ' — ' . ($mp?->process?->process ?? ''));
            // has_drawing = at least one document with at least one page that has an image
            $hasDrawing = $rp->documents->contains(fn($d) =>
                $d->pages->contains(fn($p) => !empty($p->image_path)));
            return [
                'id'                => $rp->id,
                'manual_process_id' => $rp->manual_process_id,
                'description'       => $rp->description,
                'is_gate'           => (bool) $rp->is_gate,
                'sort_order'        => $rp->sort_order,
                'label'             => $label,
                'has_drawing'       => $hasDrawing,
                'process_names_id'  => $pn?->id,
                'scope'             => $pn?->scope,
            ];
        })->values()->all();
        return $data;
    }

    private function parameterPayload(ManualParameter $parameter): array
    {
        $parameter->load([
            'codes.code',
            'repairRules.triggers.code',
            'repairRules.processes.manualProcess.process.process_name',
            'points',
        ]);

        return [
            'id'                      => $parameter->id,
            'manual_id'               => $parameter->manual_id,
            'inspection_component_id' => $parameter->inspection_component_id,
            'description'             => $parameter->description,
            'is_required'             => $parameter->is_required,
            'requires_value'          => $parameter->requires_value,
            'qty'                     => $parameter->qty,
            'orig_dim_min'            => $parameter->orig_dim_min,
            'orig_dim_max'            => $parameter->orig_dim_max,
            'wear_dim_min'            => $parameter->wear_dim_min,
            'wear_dim_max'            => $parameter->wear_dim_max,
            'repair_dim_min'          => $parameter->repair_dim_min,
            'repair_dim_max'          => $parameter->repair_dim_max,
            'interference_value'      => $parameter->interference_value,
            'flange_clearance_min'    => $parameter->flange_clearance_min,
            'flange_clearance_max'    => $parameter->flange_clearance_max,
            'repair_surface_side'     => $parameter->repair_surface_side,
            'max_repair_depth_a'      => $parameter->max_repair_depth_a,
            'max_repair_depth_b'      => $parameter->max_repair_depth_b,
            'inspection'              => $parameter->inspection,
            'sort_order'              => $parameter->sort_order,
            'codes'                   => $parameter->codes->map(fn($c) => [
                'id'              => $c->id,
                'codes_id'        => $c->codes_id,
                'name'            => $c->code?->name,
                'finding_context' => $c->finding_context,
            ])->filter(fn($c) => $c['name'] !== null)->values(),
            'repair_rules'            => $parameter->repairRules->map(fn($r) => $this->rulePayload($r)),
            'points'                  => $parameter->points->map(fn($pt) => [
                'id'       => $pt->id,
                'pivot_id' => $pt->pivot->id,
            ])->values(),
        ];
    }
}
