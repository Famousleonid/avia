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
        $savedIds = \App\Models\TravelerProcessTemplateStep::whereHas('template', fn ($q) => $q->where('manual_id', $manual->id))->pluck('process_id')->all();
        $activeIds = ManualProcess::where('manual_id', $manual->id)->pluck('processes_id')->all();
        $catalog = \App\Services\ManualProcessVersions::catalog($manual->id, array_unique([...$savedIds, ...$activeIds]));
        $catalog->load('process.process_name');
        return response()->json([
            'templates' => TravelerProcessTemplate::where('manual_id', $manual->id)->with(['steps', 'images'])->orderBy('part_number')->orderBy('name')->get(),
            'parts' => Component::where('manual_id', $manual->id)->orderBy('part_number')->get(['part_number', 'name', 'ipl_num']),
            'processes' => $catalog
                ->filter(fn ($row) => $row->process?->process_name)->map(fn ($row) => [
                    'id' => $row->process->id, 'name' => $row->process->process_name->name,
                    'specification' => $row->process->process,
                    'comment' => $row->process_comment,
                    'historical' => !in_array($row->processes_id, $activeIds),
                    'requires_fig' => $row->requires_fig, 'requires_zone' => $row->requires_zone,
                ])->unique('id')->values(),
        ]);
    }

    public function preview(Request $request, Manual $manual, TravelerProcessTemplate $template)
    {
        $this->authorizeManual($request, $manual);
        abort_unless((int) $template->manual_id === (int) $manual->id, 404);
        $template->load(['steps.process.process_name', 'images']);
        $component = Component::where('manual_id', $manual->id)->where('part_number', trim(explode(';', $template->part_number)[0]))->first()
            ?? new Component(['name' => $template->name, 'part_number' => $template->part_number, 'ipl_num' => '']);
        $wo = new \App\Models\Workorder(['number' => '']);
        $tdr = new Tdr(['qty' => 1, 'serial_number' => '']);
        $tdr->setRelation('component', $component)->setRelation('workorder', $wo);
        $rows = $template->steps->filter(fn ($step) => $step->process?->process_name)->map(function ($step) {
            $row = new \App\Models\TdrProcess([
                'process_names_id' => $step->process->process_names_id, 'processes' => [$step->process_id],
                'description' => $step->description, 'in_traveler' => (bool) $step->traveler_group,
                'traveler_group' => $step->traveler_group, 'sort_order' => $step->sort_order,
                'ec' => $step->process->process_name->hasIdentity(['EC', 'Machining (EC)']),
            ]);
            $row->setRelation('processName', $step->process->process_name)->setRelation('vendor', null);
            return $row;
        });
        return view('admin.tdr-processes.travelForm', [
            'templatePreview' => true, 'previewTemplateName' => $template->name,
            'current_tdr' => $tdr, 'current_wo' => $wo, 'manual' => $manual,
            'tdrProcesses' => $rows, 'proces' => $template->steps->pluck('process')->filter()->unique('id'),
            'travelerImages' => $template->images, 'figPagesHtml' => [], 'vendorName' => '', 'repairNum' => '',
            'travelerTemplateNotes' => \App\Models\TravelerNoteTemplate::where('manual_id', $manual->id)
                ->where('part_number', $component->part_number)->pluck('notes', 'process_names_id'),
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
            'steps.*.process_id' => ['required', 'integer', Rule::exists('processes', 'id')->whereNotNull('process_names_id')],
            'steps.*.description' => ['nullable', 'string', 'max:10000'],
            'steps.*.traveler_group' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['steps'] = TravelerProcessTemplates::additionalDescriptions($data['steps']);
        TravelerProcessTemplates::validateDescriptions($manual->id, $data['steps']);
        $data['part_number'] = TravelerProcessTemplates::normalizePartNumbers($manual->id, $data['part_number']);
        $saved = DB::transaction(function () use ($data, $manual, $template) {
            Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            $retained = $template ? $template->steps()->pluck('process_id')->map(fn ($id) => (int) $id)->all() : [];
            foreach ($data['steps'] as $step) \App\Services\ManualProcessVersions::assertSelectable($manual->id, (int) $step['process_id'], $retained);
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
            return $template->load('images');
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
            return $template->load(['steps', 'images']);
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
            'step_number' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'in_traveler' => ['sometimes', 'boolean'],
            'id' => ['nullable', 'integer'],
            'process_id' => ['required_if:action,save', 'integer', Rule::exists('processes', 'id')->whereNotNull('process_names_id')],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
        if ($data['action'] === 'save') {
            $data = TravelerProcessTemplates::additionalDescriptions([$data])[0];
        }
        $saved = DB::transaction(function () use ($template, $data) {
            $template = TravelerProcessTemplate::lockForUpdate()->findOrFail($template->id);
            abort_if($template->revision !== (int) $data['revision'], 409, 'The template was edited by another user. Reload before saving.');
            $step = !empty($data['id']) ? $template->steps()->findOrFail($data['id']) : null;
            abort_if(!$step && $data['action'] !== 'save', 422, 'Save this step first.');
            if ($data['action'] === 'save') {
                $orderedIds = $template->steps()->pluck('id');
                $stepNumber = $step ? $orderedIds->search($step->id) + 1
                    : (int) ($data['step_number'] ?? ($orderedIds->count() + 1));
                TravelerProcessTemplates::validateDescriptions($template->manual_id, [$data], firstStepNumber: $stepNumber);
                \App\Services\ManualProcessVersions::assertSelectable($template->manual_id, (int) $data['process_id'], $step ? [(int) $step->process_id] : []);
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
            return ['template' => $template->fresh(['steps', 'images']), 'step_id' => $step->id];
        });
        return response()->json($saved);
    }

    public function apply(Request $request, Tdr $tdr, TravelerProcessTemplates $service)
    {
        // The normal TDR workspace is available to these existing WO roles.
        abort_unless($request->user()->can('workorders.view', $tdr->workorder), 403);
        $data = $request->validate(['template_id' => ['required', 'integer'], 'revision' => ['required', 'integer', 'min:1']]);
        $ids = $service->apply($tdr, $data['template_id'], $data['revision'], $request->user());
        $warnings = TravelerProcessTemplates::figureWarningsForProcesses(
            \App\Models\TdrProcess::whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')->get()
        );
        return response()->json(['success' => true, 'process_ids' => $ids, 'warnings' => $warnings]);
    }
}
