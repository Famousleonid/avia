<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Component, Manual, ManualProcess, Tdr, TravelerProcessTemplate};
use App\Services\TravelerProcessTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TravelerProcessTemplateController extends Controller
{
    private function authorizeManual(Request $request, Manual $manual): void
    {
        abort_unless(TravelerProcessTemplates::canManage($request->user(), $manual), 403);
    }

    public function index(Request $request, Manual $manual)
    {
        $this->authorizeManual($request, $manual);
        return response()->json([
            'templates' => TravelerProcessTemplate::where('manual_id', $manual->id)->with('steps')->orderBy('part_number')->orderBy('name')->get(),
            'parts' => Component::where('manual_id', $manual->id)->orderBy('part_number')->get(['part_number', 'name', 'ipl_num']),
            'processes' => ManualProcess::where('manual_id', $manual->id)->with('process.process_name')->get()
                ->filter(fn ($row) => $row->process?->process_name)->map(fn ($row) => [
                    'id' => $row->process->id, 'name' => $row->process->process_name->name,
                    'specification' => $row->process->process,
                    'comment' => $row->process_comment,
                    'requires_fig' => $row->requires_fig, 'requires_zone' => $row->requires_zone,
                ])->unique('id')->values(),
        ]);
    }

    public function store(Request $request, Manual $manual)
    {
        return $this->save($request, $manual, null);
    }

    public function update(Request $request, Manual $manual, TravelerProcessTemplate $template)
    {
        abort_unless((int) $template->manual_id === (int) $manual->id, 404);
        return $this->save($request, $manual, $template);
    }

    private function save(Request $request, Manual $manual, ?TravelerProcessTemplate $template)
    {
        $this->authorizeManual($request, $manual);
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
            'revision' => [$template ? 'required' : 'nullable', 'integer', 'min:1'],
            'steps' => ['required', 'array', 'min:1', 'max:100'],
            'steps.*.process_id' => ['required', 'integer', Rule::exists('manual_processes', 'processes_id')->where('manual_id', $manual->id), Rule::exists('processes', 'id')->whereNotNull('process_names_id')],
            'steps.*.description' => ['nullable', 'string', 'max:10000'],
            'steps.*.traveler_group' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['steps'] = TravelerProcessTemplates::additionalDescriptions($data['steps']);
        TravelerProcessTemplates::validateDescriptions($manual->id, $data['steps']);
        $data['part_number'] = TravelerProcessTemplates::normalizePartNumbers($manual->id, $data['part_number']);
        $saved = DB::transaction(function () use ($data, $manual, $template) {
            Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            if ($data['active']) TravelerProcessTemplates::assertUnique($manual->id, $data['part_number'], $template?->id);
            if ($template) {
                $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
                abort_if($template->revision !== (int) $data['revision'], 409, 'The template was edited by another user. Reload before saving.');
                $template->revision++;
            } else {
                $template = new TravelerProcessTemplate(['manual_id' => $manual->id, 'revision' => 1]);
            }
            $template->fill(collect($data)->only(['part_number', 'name', 'active'])->all())->save();
            $template->steps()->delete();
            foreach (array_values($data['steps']) as $index => $step) {
                $template->steps()->create([
                    'process_id' => $step['process_id'], 'description' => $step['description'] ?? null,
                    'traveler_group' => $step['traveler_group'] ?? null, 'sort_order' => $index + 1,
                ]);
            }
            activity('traveler_templates')->performedOn($template)->causedBy(auth()->user())
                ->withProperties($template->load('steps')->toArray())->log('Template saved');
            return $template;
        });
        return response()->json(['template' => $saved]);
    }

    public function metadata(Request $request, Manual $manual, ?TravelerProcessTemplate $template = null)
    {
        $this->authorizeManual($request, $manual);
        abort_if($template && (int) $template->manual_id !== (int) $manual->id, 404);
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'], 'active' => ['required', 'boolean'],
            'revision' => [$template ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);
        $data['part_number'] = TravelerProcessTemplates::normalizePartNumbers($manual->id, $data['part_number']);
        $saved = DB::transaction(function () use ($template, $manual, $data) {
            Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            if ($data['active']) TravelerProcessTemplates::assertUnique($manual->id, $data['part_number'], $template?->id);
            if ($template) {
                $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
                abort_if($template->revision !== (int) $data['revision'], 409, 'The template was edited by another user. Reload before saving.');
                $template->revision++;
            } else {
                $template = new TravelerProcessTemplate(['manual_id' => $manual->id, 'revision' => 1]);
            }
            $template->fill(collect($data)->only(['part_number', 'name', 'active'])->all())->save();
            activity('traveler_templates')->performedOn($template)->causedBy(auth()->user())
                ->withProperties($data)->log('Template details saved');
            return $template->load('steps');
        });
        return response()->json(['template' => $saved]);
    }

    public function step(Request $request, Manual $manual, TravelerProcessTemplate $template)
    {
        $this->authorizeManual($request, $manual);
        abort_unless((int) $template->manual_id === (int) $manual->id, 404);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:save,delete,up,down'],
            'in_traveler' => ['sometimes', 'boolean'],
            'id' => ['nullable', 'integer'],
            'process_id' => ['required_if:action,save', 'integer', Rule::exists('manual_processes', 'processes_id')->where('manual_id', $manual->id), Rule::exists('processes', 'id')->whereNotNull('process_names_id')],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
        if ($data['action'] === 'save') {
            $data = TravelerProcessTemplates::additionalDescriptions([$data])[0];
            TravelerProcessTemplates::validateDescriptions($manual->id, [$data]);
        }
        $saved = DB::transaction(function () use ($template, $data) {
            $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
            abort_if($template->revision !== (int) $data['revision'], 409, 'The template was edited by another user. Reload before saving.');
            $step = !empty($data['id']) ? $template->steps()->findOrFail($data['id']) : null;
            abort_if(!$step && $data['action'] !== 'save', 422, 'Save this step first.');
            if ($data['action'] === 'save') {
                abort_if(!$step && $template->steps()->count() >= 100, 422, 'Maximum 100 steps.');
                $step ??= $template->steps()->make(['sort_order' => (int) $template->steps()->max('sort_order') + 1]);
                // Editing text/process must not erase the imported Traveler membership.
                if (array_key_exists('in_traveler', $data)) {
                    $step->traveler_group = $data['in_traveler'] ? ($step->traveler_group ?: 1) : null;
                }
                $step->fill(['process_id' => $data['process_id'], 'description' => $data['description'] ?? null])->save();
            } elseif ($data['action'] === 'delete') {
                $step->delete();
            } else {
                $ordered = $template->steps()->get();
                $index = $ordered->search(fn ($item) => $item->id === $step->id);
                $other = $ordered->get($index + ($data['action'] === 'up' ? -1 : 1));
                if ($other) {
                    $position = $step->sort_order;
                    $step->update(['sort_order' => $other->sort_order]);
                    $other->update(['sort_order' => $position]);
                }
            }
            foreach ($template->steps()->get() as $index => $item) $item->update(['sort_order' => $index + 1]);
            $template->increment('revision');
            activity('traveler_templates')->performedOn($template)->causedBy(auth()->user())
                ->withProperties(['action' => $data['action'], 'step_id' => $step->id])->log('Template step changed');
            return ['template' => $template->fresh('steps'), 'step_id' => $step->id];
        });
        return response()->json($saved);
    }

    public function apply(Request $request, Tdr $tdr, TravelerProcessTemplates $service)
    {
        // The normal TDR workspace is available to these existing WO roles.
        abort_unless($request->user()->can('workorders.view', $tdr->workorder), 403);
        $data = $request->validate(['template_id' => ['required', 'integer'], 'revision' => ['required', 'integer', 'min:1']]);
        $ids = $service->apply($tdr, $data['template_id'], $data['revision'], $request->user());
        return response()->json(['success' => true, 'process_ids' => $ids]);
    }
}
