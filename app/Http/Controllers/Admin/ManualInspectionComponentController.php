<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualInspectionComponent;
use App\Models\ManualInspectionComponentVariant;
use Illuminate\Http\Request;

class ManualInspectionComponentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function format(ManualInspectionComponent $ic): array
    {
        return [
            'id'         => $ic->id,
            'label'      => $ic->label,
            'sort_order' => $ic->sort_order,
            'is_bush'    => $ic->variants->contains(fn($v) => $v->component?->is_bush),
            'variants'   => $ic->variants->map(fn($v) => [
                'id'           => $v->id,
                'component_id' => $v->component_id,
                'ipl_num'      => $v->component->ipl_num  ?? null,
                'name'         => $v->component->name     ?? null,
                'part_number'  => $v->component->part_number ?? null,
            ])->values()->all(),
        ];
    }

    public function index(Manual $manual)
    {
        $items = $manual->inspectionComponents;
        return response()->json($items->map(fn($ic) => $this->format($ic)));
    }

    public function store(Request $request, Manual $manual)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
        ]);

        $maxOrder = ManualInspectionComponent::where('manual_id', $manual->id)->max('sort_order') ?? -1;

        $ic = ManualInspectionComponent::create([
            'manual_id'  => $manual->id,
            'label'      => $data['label'],
            'sort_order' => $maxOrder + 1,
        ]);

        $ic->load('variants.component');

        return response()->json($this->format($ic), 201);
    }

    public function update(Request $request, ManualInspectionComponent $manualInspectionComponent)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
        ]);

        $manualInspectionComponent->update($data);

        return response()->json($this->format($manualInspectionComponent->fresh('variants.component')));
    }

    public function reorder(Request $request, Manual $manual)
    {
        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:manual_inspection_components,id',
        ]);

        foreach ($data['ids'] as $i => $id) {
            ManualInspectionComponent::where('id', $id)
                ->where('manual_id', $manual->id)
                ->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(ManualInspectionComponent $manualInspectionComponent)
    {
        $manualInspectionComponent->delete();
        return response()->json(['ok' => true]);
    }

    // --- Component search by IPL# (for repair steps) ---

    public function componentSearch(Request $request, Manual $manual)
    {
        $ipl = trim($request->query('ipl_num', ''));
        if ($ipl === '') {
            return response()->json([]);
        }

        $components = Component::where('manual_id', $manual->id)
            ->where('ipl_num', 'like', $ipl . '%')
            ->orderBy('ipl_num')
            ->get(['id', 'ipl_num', 'part_number', 'name'])
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
            ]);

        return response()->json($components);
    }

    // --- Variants ---

    public function storeVariant(Request $request, ManualInspectionComponent $manualInspectionComponent)
    {
        $data = $request->validate([
            'component_id' => 'required|exists:components,id',
        ]);

        $variant = ManualInspectionComponentVariant::firstOrCreate([
            'inspection_component_id' => $manualInspectionComponent->id,
            'component_id'            => $data['component_id'],
        ]);

        $variant->load('component');

        return response()->json([
            'id'           => $variant->id,
            'component_id' => $variant->component_id,
            'ipl_num'      => $variant->component->ipl_num  ?? null,
            'name'         => $variant->component->name     ?? null,
            'part_number'  => $variant->component->part_number ?? null,
        ], 201);
    }

    public function destroyVariant(ManualInspectionComponentVariant $manualInspectionComponentVariant)
    {
        $manualInspectionComponentVariant->delete();
        return response()->json(['ok' => true]);
    }
}
