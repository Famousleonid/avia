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
use Illuminate\Support\Facades\Log;

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

//        // code name по component_id (показываем все коды через запятую)
//        $codeNamesByComponent = $tdrsByComponent->map(function ($group) {
//            return $group->pluck('codes.name')->filter()->unique()->implode(', ');
//        });

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

        return view('mobile.pages.components', compact('workorder', 'components', 'manualComponents','manualId',
                            'component_conditions','codes','necessaries','manuals', 'tdrsDetailsByComponent' ));
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
            'is_bush'      => 'nullable|boolean',
            'log_card'     => 'nullable|boolean',
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
        $component->is_bush  = $request->boolean('is_bush');
        $component->log_card = $request->boolean('log_card');

        $component->save();

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return redirect()->back()->with('success', 'Component added successfully.');
    }

    public function quickStore(Request $request)
    {

        Log::info('quickStore request', $request->all());

        $validated = $request->validate([
            'workorder_id' => ['required', 'exists:workorders,id'],
            'ipl_num' => ['required', 'string', 'max:255'],
            'part_number' => ['required', 'string', 'max:255'],
            'eff_code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'is_bush' => ['nullable'],
            'log_card' => ['nullable'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $workorder = Workorder::with('unit')->findOrFail($validated['workorder_id']);
        $manualId = optional($workorder->unit)->manual_id;

        if (!$manualId) {
            return response()->json(['ok' => false, 'message' => 'Manual not found for selected workorder.'], 422);
        }

        $isBush = $request->boolean('is_bush');
        $logCard = $request->boolean('log_card');
        $bushIpl = $isBush ? ($validated['bush_ipl_num'] ?? null) : null;

        $component = Component::create([
            'manual_id' => $manualId,
            'ipl_num' => $validated['ipl_num'],
            'part_number' => $validated['part_number'],
            'eff_code' => $validated['eff_code'] ?? null,
            'name' => $validated['name'],
            'log_card' => $logCard,
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

    public function storeAttach(Request $request)
    {
        $validated = $request->validate([
            'workorder_id'   => ['required', 'exists:workorders,id'],
            'component_id'   => ['required', 'exists:components,id'],
            'code_id'        => ['required', 'exists:codes,id'],
            'necessaries_id' => ['nullable', 'exists:necessaries,id'],
            'qty'            => ['nullable', 'integer', 'min:1'],
            'serial_number'  => ['nullable', 'string', 'max:255'],
        ]);

        $workorderId = (int) $validated['workorder_id'];
        $componentId = (int) $validated['component_id'];
        $codeId      = (int) $validated['code_id'];

        $code = Code::find($codeId);
        $isMissing = $code && stripos((string)$code->name, 'missing') !== false;

        // -------- flags by rules --------
        if ($isMissing) {
            $useTdr = 0;
            $useProcessForms = 0;
        } else {
            $useTdr = 1;
            $useProcessForms = 1; // default for all non-missing
        }

        $tdrData = [
            'workorder_id'       => $workorderId,
            'component_id'       => $componentId,
            'codes_id'           => $codeId,
            'necessaries_id'     => $validated['necessaries_id'] ?? null,
            'qty'                => 1,
            'serial_number'      => null,
            'order_component_id' => null,
            'use_tdr'            => $useTdr,
            'use_process_forms'  => $useProcessForms,
        ];

        // Missing: qty optional
        if ($isMissing) {
            if (isset($validated['qty'])) {
                $tdrData['qty'] = (int) $validated['qty'];
            }
        }
        // Not missing: analyze necessary for special cases + your existing qty/serial logic
        else if (!empty($validated['necessaries_id'])) {

            $necessary = Necessary::find((int)$validated['necessaries_id']);
            $necessaryName = strtolower(trim((string) optional($necessary)->name));

            // SPECIAL: "Order new"
            if ($necessaryName === 'order new') {
                $tdrData['use_tdr'] = 1;
                $tdrData['use_process_forms'] = 0;
                $tdrData['order_component_id'] = $componentId;

                if (isset($validated['qty'])) {
                    $tdrData['qty'] = (int) $validated['qty'];
                }
            }
            // other necessaries: keep default flags (1/1), but keep your qty/serial rules
            else {
                if (str_contains($necessaryName, 'order') && str_contains($necessaryName, 'new')) {
                    if (isset($validated['qty'])) {
                        $tdrData['qty'] = (int) $validated['qty'];
                    }
                } elseif (str_contains($necessaryName, 'repair')) {
                    if (isset($validated['serial_number'])) {
                        $tdrData['serial_number'] = $validated['serial_number'];
                    }
                }
            }
        }

        Tdr::create($tdrData);

        return redirect()->back()->with('success', 'Parts attached.');
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

    public function update(Request $request, Component $component)
    {
        $data = $request->validate([
            'name'         => ['nullable', 'string', 'max:255'],
            'ipl_num'      => ['nullable', 'string', 'max:255'],
            'part_number'  => ['nullable', 'string', 'max:255'],
            'eff_code'     => ['nullable', 'string', 'max:255'],
            'is_bush'      => ['nullable', 'boolean'],
            'bush_ipl_num' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_bush'] = $request->boolean('is_bush');

        if (!$data['is_bush']) {
            $data['bush_ipl_num'] = null;
        }

        $component->update($data);

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'ok' => true,
                'item' => [
                    'id'          => $component->id,
                    'name'        => $component->name,
                    'ipl_num'     => $component->ipl_num,
                    'part_number' => $component->part_number,
                    'eff_code'    => $component->eff_code,
                    'is_bush'     => (bool)$component->is_bush,
                    'bush_ipl_num'=> $component->bush_ipl_num,
                ]
            ]);
        }

        return back()->with('success', 'Component updated');
    }

    public function updateAttach(Request $request, Tdr $tdr)
    {
        $validated = $request->validate([
            'code_id'          => ['required', 'exists:codes,id'],
            'necessaries_id'   => ['nullable', 'exists:necessaries,id'],
            'qty'              => ['nullable', 'integer', 'min:1'],
            'serial_number'    => ['nullable', 'string', 'max:255'],
        ]);

        $codeId = (int) $validated['code_id'];

        $code = Code::find($codeId);
        $isMissing = $code && stripos((string)$code->name, 'missing') !== false;

        // -------- flags by rules --------
        if ($isMissing) {
            $useTdr = 0;
            $useProcessForms = 0;
        } else {
            $useTdr = 1;
            $useProcessForms = 1; // default for all non-missing
        }

        $data = [
            'codes_id'           => $codeId,
            'use_log_card'       => $request->boolean('use_log_card'),

            'use_tdr'            => $useTdr,
            'use_process_forms'  => $useProcessForms,
            'order_component_id' => null,

            'necessaries_id'     => null,
            'qty'                => 1,     // qty всегда число
            'serial_number'      => null,
        ];

        if ($isMissing) {
            // Missing: qty можно менять
            $data['qty'] = (int) ($validated['qty'] ?? 1);
        } else {
            $necessariesId = !empty($validated['necessaries_id']) ? (int) $validated['necessaries_id'] : null;

            if ($necessariesId) {
                $data['necessaries_id'] = $necessariesId;

                $necessary = Necessary::find($necessariesId);
                $n = strtolower(trim((string)($necessary?->name ?? '')));

                // SPECIAL: "Order new" (строго)
                if ($n === 'order new') {
                    $data['use_tdr'] = 1;
                    $data['use_process_forms'] = 0;
                    $data['order_component_id'] = (int) $tdr->component_id;

                    $data['qty'] = (int) ($validated['qty'] ?? 1);
                }
                // other: keep default flags (1/1), but keep your qty/serial rules
                else {
                    if (str_contains($n, 'order') && str_contains($n, 'new')) {
                        $data['qty'] = (int) ($validated['qty'] ?? 1);
                    } elseif (str_contains($n, 'repair')) {
                        $data['serial_number'] = $validated['serial_number'] ?? null;
                        // qty остаётся 1
                    }
                }
            }
        }

        $tdr->update($data);

        return back()->with('success', 'Parts updated.');
    }



    public function destroyAttach(Request $request, Tdr $tdr)
    {

        $tdr->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $tdr->id,
            ]);
        }

        return back()->with('success', 'Parts removed.');
    }

}
