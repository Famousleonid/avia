<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\Workorder;
use Illuminate\Http\Request;

class MobileComponentController extends Controller
{
    public function components(Workorder $workorder)
    {
        $workorder->load(['unit', 'media', 'tdrs.codes',  'tdrs.component.media',]);
        $manualId = optional($workorder->unit)->manual_id;
        $component_conditions = Condition::where('unit', false)->get();
        $codes = Code::all();
        $necessaries = Necessary::all();
        $manuals=Manual::all();

        // Собираем компоненты для этого воркордера
        $components = $workorder->tdrs
            ->filter(fn($tdr) => $tdr->component)       // только Tdr с компонентом
            ->groupBy('component_id')
            ->map(function ($group) {
                $first = $group->first();
                $component = $first->component;
                return $component;
            })
            ->values();

        // code name по component_id (если вдруг несколько tdr -> покажем уникальные через запятую)
        $codeNamesByComponent = $workorder->tdrs->filter(fn($tdr) => (bool)$tdr->component)
            ->groupBy('component_id')
            ->map(function ($group) {
                return $group->pluck('codes.name')->filter()->unique()->implode(', ');
            });

        $manualComponents = $manualId
            ? Component::query()
                ->where('manual_id', $manualId)
                ->orderBy('ipl_num')
                ->orderBy('part_number')
                ->orderBy('name')
                ->get()
            : collect();

        return view('mobile.pages.components', compact(
            'workorder', 'components', 'manualComponents',
        'manualId','component_conditions','codes','necessaries','manuals','codeNamesByComponent' ));
    }

    public function componentStore(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'ipl_num' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'eff_code' => 'nullable|string|max:100',
            'photo' => 'image|max:5120',
            'name' => 'required|string|max:255',
        ]);

        $workorder = Workorder::with('unit')->findOrFail($validated['workorder_id']);
        $manualId = optional($workorder->unit)->manual_id;

        if (!$manualId) {
            return redirect()->back()->withErrors(['manual' => 'Manual not found for selected workorder.']);
        }

        $component = new Component();
        $component->manual_id = $manualId;
        $component->ipl_num = $validated['ipl_num'];
        $component->part_number = $validated['part_number'];
        $component->eff_code = $validated['eff_code'];
        $component->name = $validated['name'];
        $component->save();

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return redirect()->back()->with('success', 'Component added successfully.');
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],

            'ipl_num' => ['required', 'string', 'max:255'],
            'part_number' => ['required', 'string', 'max:255'],
            'eff_code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],

            'is_bush' => ['nullable'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],

            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $workorder = Workorder::with('unit')->findOrFail($validated['workorder_id']);
        $manualId = optional($workorder->unit)->manual_id;

        if (!$manualId) {
            return response()->json(['ok' => false, 'message' => 'Manual not found for selected workorder.'], 422);
        }

        $isBush = $request->boolean('is_bush');
        $bushIpl = $isBush ? ($validated['bush_ipl_num'] ?? null) : null;

        $component = Component::create([
            'manual_id' => $manualId,
            'ipl_num' => $validated['ipl_num'],
            'part_number' => $validated['part_number'],
            'eff_code' => $validated['eff_code'] ?? null,
            'name' => $validated['name'],

            'is_bush' => $isBush,
            'bush_ipl_num' => $bushIpl,
        ]);

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $component->id,
                'name' => $component->name,
                'ipl_num' => $component->ipl_num,
                'part_number' => $component->part_number,
                'eff_code' => $component->eff_code,
                'is_bush' => (bool)$component->is_bush,
                'bush_ipl_num' => $component->bush_ipl_num,
                'text' => trim(($component->ipl_num ?: '—') . ' | ' . ($component->part_number ?: '—') . ' | ' . $component->name),
            ]
        ]);
    }

    public function attachToWorkorder(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
            'component_id' => ['required', 'exists:components,id'],

            'use_log_card' => ['nullable'],
            'use_tdr' => ['nullable'],
        ]);

        $workorderId = (int)$validated['workorder_id'];
        $componentId = (int)$validated['component_id'];

        $exists = Tdr::where('workorder_id', $workorderId)
            ->where('component_id', $componentId)
            ->exists();

        if (!$exists) {
            Tdr::create([
                'workorder_id' => $workorderId,
                'component_id' => $componentId,
                'use_log_card' => $request->boolean('use_log_card'),
                'use_tdr' => $request->boolean('use_tdr'),
            ]);
        }

        return redirect()->back()->with('success', 'Component attached.');
    }
}
