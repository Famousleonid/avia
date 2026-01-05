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

        // Собираем компоненты для этого воркордера с группировкой по component_id
        // Один компонент может иметь несколько TDR с разными кодами
        $components = $workorder->tdrs
            ->filter(fn($tdr) => $tdr->component)       // только Tdr с компонентом
            ->groupBy('component_id')
            ->map(function ($group) {
                $first = $group->first();
                $component = $first->component;
                return $component;
            })
            ->values();

        // Группируем TDR по component_id для отображения всех кодов
        $tdrsByComponent = $workorder->tdrs
            ->filter(fn($tdr) => (bool)$tdr->component)
            ->groupBy('component_id');

        // code name по component_id (показываем все коды через запятую)
        $codeNamesByComponent = $tdrsByComponent->map(function ($group) {
            return $group->pluck('codes.name')->filter()->unique()->implode(', ');
        });

        // Детальная информация по TDR для каждого компонента
        $tdrsDetailsByComponent = $tdrsByComponent->map(function ($group) {
            return $group->map(function ($tdr) {
                return [
                    'id' => $tdr->id,
                    'code_id' => $tdr->codes_id,
                    'code_name' => $tdr->codes?->name,
                    'necessaries_id' => $tdr->necessaries_id,
                    'necessaries_name' => $tdr->necessaries?->name,
                    'qty' => $tdr->qty,
                    'serial_number' => $tdr->serial_number,
                ];
            })->values();
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
        'manualId','component_conditions','codes','necessaries','manuals','codeNamesByComponent', 'tdrsDetailsByComponent' ));
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
            'code_id' => ['required', 'exists:codes,id'],
            
            'necessaries_id' => ['nullable', 'exists:necessaries,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'serial_number' => ['nullable', 'string', 'max:255'],

            'use_log_card' => ['nullable'],
            'use_tdr' => ['nullable'],
        ]);

        $workorderId = (int)$validated['workorder_id'];
        $componentId = (int)$validated['component_id'];
        $codeId = (int)$validated['code_id'];

        // Get code to check if it's Missing
        $code = Code::find($codeId);
        $isMissing = $code && stripos($code->name, 'missing') !== false;

        // Prepare data
        $tdrData = [
            'workorder_id' => $workorderId,
            'component_id' => $componentId,
            'codes_id' => $codeId,
            'use_log_card' => $request->boolean('use_log_card'),
            'use_tdr' => $request->boolean('use_tdr'),
        ];

        // If Missing - add qty
        if ($isMissing && isset($validated['qty'])) {
            $tdrData['qty'] = (int)$validated['qty'];
        }
        // For other codes - check necessaries
        else if (isset($validated['necessaries_id'])) {
            $tdrData['necessaries_id'] = (int)$validated['necessaries_id'];
            
            // Get necessary to check type
            $necessary = Necessary::find($tdrData['necessaries_id']);
            if ($necessary) {
                $necessaryName = strtolower($necessary->name);
                
                // If Order New - add qty
                if (stripos($necessaryName, 'order') !== false && stripos($necessaryName, 'new') !== false) {
                    if (isset($validated['qty'])) {
                        $tdrData['qty'] = (int)$validated['qty'];
                    }
                }
                // If Repair - add serial number
                else if (stripos($necessaryName, 'repair') !== false) {
                    if (isset($validated['serial_number'])) {
                        $tdrData['serial_number'] = $validated['serial_number'];
                    }
                }
            }
        }

        // Allow multiple TDRs for same component with different codes
        Tdr::create($tdrData);

        return redirect()->back()->with('success', 'Component attached.');
    }

    public function updatePhoto(Request $request, Component $component)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            // Удаляем старое фото (как аватар - одно фото)
            $component->clearMediaCollection('components');
            // Добавляем новое
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return response()->json([
            'ok' => true,
            'message' => 'Photo updated successfully',
            'thumb_url' => $component->getFirstMediaThumbnailUrl('components'),
            'big_url' => $component->getFirstMediaBigUrl('components'),
        ]);
    }
}
