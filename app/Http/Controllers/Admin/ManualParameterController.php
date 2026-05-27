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
            'orig_dim_min'           => 'nullable|numeric',
            'orig_dim_max'           => 'nullable|numeric',
            'wear_dim_min'           => 'nullable|numeric',
            'wear_dim_max'           => 'nullable|numeric',
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
                'orig_dim_min'            => $data['orig_dim_min'] ?? null,
                'orig_dim_max'            => $data['orig_dim_max'] ?? null,
                'wear_dim_min'            => $data['wear_dim_min'] ?? null,
                'wear_dim_max'            => $data['wear_dim_max'] ?? null,
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
            'orig_dim_min'           => 'nullable|numeric',
            'orig_dim_max'           => 'nullable|numeric',
            'wear_dim_min'           => 'nullable|numeric',
            'wear_dim_max'           => 'nullable|numeric',
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
            'codes_id' => 'required|exists:codes,id',
        ]);

        $code = ManualParameterCode::firstOrCreate([
            'manual_parameter_id' => $manualParameter->id,
            'codes_id'            => $data['codes_id'],
        ]);

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
            'notes'             => 'nullable|string',
            'processes'                     => 'nullable|array',
            'processes.*.manual_process_id' => 'required|exists:manual_processes,id',
            'processes.*.sort_order'        => 'integer',
            'triggers'                      => 'required|array|min:1',
            'triggers.*.trigger'            => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,manual',
            'triggers.*.codes_id'           => 'nullable|exists:codes,id',
        ]);

        $rule = ManualParameterRepairRule::create([
            'manual_parameter_id' => $manualParameter->id,
            'name'                => $data['name'] ?? null,
            'order_replacement'   => $data['order_replacement'] ?? false,
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
            'notes'             => 'nullable|string',
            'processes'                     => 'nullable|array',
            'processes.*.manual_process_id' => 'required|exists:manual_processes,id',
            'processes.*.sort_order'        => 'integer',
            'triggers'                      => 'required|array|min:1',
            'triggers.*.trigger'            => 'required|in:below_orig,above_orig,below_wear,above_wear,finding,manual',
            'triggers.*.codes_id'           => 'nullable|exists:codes,id',
        ]);

        $manualParameterRepairRule->update([
            'name'              => $data['name'] ?? null,
            'order_replacement' => $data['order_replacement'] ?? false,
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

    private function syncRuleProcesses(ManualParameterRepairRule $rule, array $processes): void
    {
        $rule->processes()->delete();
        foreach ($processes as $i => $p) {
            ManualParameterRuleProcess::create([
                'repair_rule_id'    => $rule->id,
                'manual_process_id' => $p['manual_process_id'],
                'sort_order'        => $p['sort_order'] ?? $i,
            ]);
        }
    }

    private function syncRuleTriggers(ManualParameterRepairRule $rule, array $triggers): void
    {
        $rule->triggers()->delete();
        foreach ($triggers as $t) {
            ManualParameterRuleTrigger::create([
                'repair_rule_id' => $rule->id,
                'trigger'        => $t['trigger'],
                'codes_id'       => ($t['trigger'] === 'finding') ? ($t['codes_id'] ?? null) : null,
            ]);
        }
    }

    private function rulePayload(ManualParameterRepairRule $rule): array
    {
        $rule->load(['triggers.code', 'processes.manualProcess.process.process_name']);
        $data = $rule->toArray();
        $data['triggers'] = $rule->triggers->map(fn($t) => [
            'id'        => $t->id,
            'trigger'   => $t->trigger,
            'codes_id'  => $t->codes_id,
            'code_name' => $t->code?->name,
        ])->values()->all();
        $data['processes'] = $rule->processes->map(function ($rp) {
            $mp    = $rp->manualProcess;
            $label = trim(($mp?->process?->process_name?->name ?? '') . ' — ' . ($mp?->process?->process ?? ''));
            return [
                'id'                => $rp->id,
                'manual_process_id' => $rp->manual_process_id,
                'sort_order'        => $rp->sort_order,
                'label'             => $label,
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
            'orig_dim_min'            => $parameter->orig_dim_min,
            'orig_dim_max'            => $parameter->orig_dim_max,
            'wear_dim_min'            => $parameter->wear_dim_min,
            'wear_dim_max'            => $parameter->wear_dim_max,
            'inspection'              => $parameter->inspection,
            'sort_order'              => $parameter->sort_order,
            'codes'                   => $parameter->codes->map(fn($c) => [
                'id'       => $c->id,
                'codes_id' => $c->codes_id,
                'name'     => $c->code?->name,
            ])->filter(fn($c) => $c['name'] !== null)->values(),
            'repair_rules'            => $parameter->repairRules->map(fn($r) => $this->rulePayload($r)),
            'point_ids'               => $parameter->points->pluck('id'),
        ];
    }
}
