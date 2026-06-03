<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualInspectionComponent;
use App\Models\MasterRule;
use App\Models\MasterRulePhaseRule;
use App\Models\MasterRulePhaseRuleProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get (or lazily create) the repair plan for an inspection component.
     */
    public function show(ManualInspectionComponent $manualInspectionComponent)
    {
        $rule = MasterRule::firstOrCreate(
            ['inspection_component_id' => $manualInspectionComponent->id],
            ['manual_id' => $manualInspectionComponent->manual_id]
        );

        return response()->json($this->payload($rule));
    }

    public function storePhaseRule(Request $request, MasterRule $masterRule)
    {
        $data = $request->validate([
            'phase'                   => 'required|in:start,finish',
            'name'                    => 'nullable|string|max:100',
            'condition'               => 'nullable|array',
            'sort_order'              => 'nullable|integer',
            'processes'                       => 'nullable|array',
            'processes.*.manual_process_id'   => 'required|exists:manual_processes,id',
            'processes.*.description'         => 'nullable|string|max:255',
            'processes.*.sort_order'          => 'integer',
        ]);

        $phaseRule = MasterRulePhaseRule::create([
            'master_rule_id' => $masterRule->id,
            'phase'          => $data['phase'],
            'name'           => $data['name'] ?? null,
            'condition'      => $data['condition'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);

        $this->syncProcesses($phaseRule, $data['processes'] ?? []);

        return response()->json($this->phaseRulePayload($phaseRule->fresh('processes.manualProcess.process.process_name')), 201);
    }

    public function updatePhaseRule(Request $request, MasterRulePhaseRule $masterRulePhaseRule)
    {
        $data = $request->validate([
            'phase'         => 'sometimes|in:start,finish',
            'name'          => 'nullable|string|max:100',
            'condition'     => 'nullable|array',
            'sort_order'    => 'nullable|integer',
            'processes'     => 'nullable|array',
            'processes.*'   => 'integer|exists:manual_processes,id',
        ]);

        $masterRulePhaseRule->update([
            'phase'      => $data['phase']      ?? $masterRulePhaseRule->phase,
            'name'       => $data['name']       ?? $masterRulePhaseRule->name,
            'condition'  => array_key_exists('condition', $data) ? $data['condition'] : $masterRulePhaseRule->condition,
            'sort_order' => $data['sort_order'] ?? $masterRulePhaseRule->sort_order,
        ]);

        if (array_key_exists('processes', $data)) {
            $this->syncProcesses($masterRulePhaseRule, $data['processes']);
        }

        return response()->json($this->phaseRulePayload($masterRulePhaseRule->fresh('processes.manualProcess.process.process_name')));
    }

    public function destroyPhaseRule(MasterRulePhaseRule $masterRulePhaseRule)
    {
        $masterRulePhaseRule->delete();

        return response()->json(['ok' => true]);
    }

    private function syncProcesses(MasterRulePhaseRule $phaseRule, array $processes): void
    {
        $phaseRule->processes()->delete();
        foreach (array_values($processes) as $i => $p) {
            // tolerate both [{manual_process_id, description}, ...] and legacy [id, ...]
            $mpId = is_array($p) ? ($p['manual_process_id'] ?? null) : $p;
            if (!$mpId) {
                continue;
            }
            MasterRulePhaseRuleProcess::create([
                'phase_rule_id'     => $phaseRule->id,
                'manual_process_id' => $mpId,
                'description'       => is_array($p) ? (($p['description'] ?? null) ?: null) : null,
                'sort_order'        => $i,
            ]);
        }
    }

    private function payload(MasterRule $rule): array
    {
        $rule->load('phaseRules.processes.manualProcess.process.process_name', 'phaseRules.processes.documents.pages');

        return [
            'id'                      => $rule->id,
            'manual_id'               => $rule->manual_id,
            'inspection_component_id' => $rule->inspection_component_id,
            'name'                    => $rule->name,
            'phase_rules'             => $rule->phaseRules->map(fn($pr) => $this->phaseRulePayload($pr))->values(),
        ];
    }

    private function phaseRulePayload(MasterRulePhaseRule $pr): array
    {
        return [
            'id'         => $pr->id,
            'phase'      => $pr->phase,
            'name'       => $pr->name,
            'condition'  => $pr->condition,
            'sort_order' => $pr->sort_order,
            'processes'  => $pr->processes->map(function ($rp) {
                $mp    = $rp->manualProcess;
                $label = trim(($mp?->process?->process_name?->name ?? '') . ' — ' . ($mp?->process?->process ?? ''));
                $hasDrawing = $rp->documents->contains(fn($d) => $d->pages->contains(fn($p) => !empty($p->image_path)));
                return [
                    'id'                => $rp->id,
                    'manual_process_id' => $rp->manual_process_id,
                    'description'       => $rp->description,
                    'sort_order'        => $rp->sort_order,
                    'label'             => $label,
                    'has_drawing'       => $hasDrawing,
                ];
            })->values(),
        ];
    }
}
