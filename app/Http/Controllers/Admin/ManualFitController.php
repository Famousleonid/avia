<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualFit;
use App\Models\ManualParameter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ManualFitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Manual $manual)
    {
        $fits = $manual->fits()
            ->with([
                'odParam.inspectionComponent.variants.component',
                'idParam.inspectionComponent.variants.component',
            ])
            ->get()
            ->map(fn ($f) => $this->payload($f));

        return response()->json($fits);
    }

    public function store(Request $request, Manual $manual)
    {
        $data = $this->validateData($request, $manual);

        $fit = ManualFit::create([
            'manual_id'              => $manual->id,
            'od_param_id'            => $data['od_param_id'],
            'id_param_id'            => $data['id_param_id'],
            'ref_no'                 => $data['ref_no'] ?? null,
            'assembly_clearance_min' => $data['assembly_clearance_min'] ?? null,
            'assembly_clearance_max' => $data['assembly_clearance_max'] ?? null,
            'permitted_clearance'    => $data['permitted_clearance'] ?? null,
            'sort_order'             => $data['sort_order'] ?? ($manual->fits()->max('sort_order') + 1),
        ]);

        return response()->json($this->payload($this->reload($fit)), 201);
    }

    public function update(Request $request, ManualFit $manualFit)
    {
        $data = $this->validateData($request, $manualFit->manual, true, $manualFit->id);

        $manualFit->update($data);

        return response()->json($this->payload($this->reload($manualFit->fresh())));
    }

    public function destroy(ManualFit $manualFit)
    {
        $manualFit->delete();

        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, Manual $manual, bool $partial = false, ?int $ignoreId = null): array
    {
        $req = $partial ? 'sometimes|' : 'required|';

        $data = $request->validate([
            'od_param_id'            => $req . 'integer|exists:manual_parameters,id',
            'id_param_id'            => $req . 'integer|exists:manual_parameters,id',
            'ref_no'                 => 'nullable|string|max:40',
            'assembly_clearance_min' => 'nullable|numeric',
            'assembly_clearance_max' => 'nullable|numeric',
            'permitted_clearance'    => 'nullable|numeric',
            'sort_order'             => 'nullable|integer',
        ]);

        // Both members must belong to this manual and be distinct.
        foreach (['od_param_id', 'id_param_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $belongs = ManualParameter::where('id', $data[$key])
                    ->where('manual_id', $manual->id)
                    ->exists();
                if (! $belongs) {
                    throw ValidationException::withMessages([$key => 'Parameter does not belong to this manual.']);
                }
            }
        }

        $od = $data['od_param_id'] ?? null;
        $id = $data['id_param_id'] ?? null;
        if ($od !== null && $id !== null && (int) $od === (int) $id) {
            throw ValidationException::withMessages(['id_param_id' => 'OD and ID members must be different parameters.']);
        }

        return $data;
    }

    private function reload(ManualFit $fit): ManualFit
    {
        return $fit->load([
            'odParam.inspectionComponent.variants.component',
            'idParam.inspectionComponent.variants.component',
        ]);
    }

    private function payload(ManualFit $fit): array
    {
        return [
            'id'                     => $fit->id,
            'ref_no'                 => $fit->ref_no,
            'sort_order'             => $fit->sort_order,
            'od_param_id'            => $fit->od_param_id,
            'id_param_id'            => $fit->id_param_id,
            'od_label'               => $this->memberLabel($fit->odParam),
            'id_label'               => $this->memberLabel($fit->idParam),
            // Stored manual values (null = not entered → derived is used).
            'assembly_clearance_min' => $fit->assembly_clearance_min,
            'assembly_clearance_max' => $fit->assembly_clearance_max,
            'permitted_clearance'    => $fit->permitted_clearance,
            // Effective (stored else derived) + derived, for display and the mismatch flag.
            'eff_assembly_min'       => $fit->effectiveAssemblyClearanceMin(),
            'eff_assembly_max'       => $fit->effectiveAssemblyClearanceMax(),
            'eff_permitted'          => $fit->effectivePermittedClearance(),
            'derived_assembly_min'   => $fit->derivedAssemblyClearanceMin(),
            'derived_assembly_max'   => $fit->derivedAssemblyClearanceMax(),
            'derived_permitted'      => $fit->derivedPermittedClearance(),
            'mismatch'               => $fit->hasClearanceMismatch(),
        ];
    }

    private function memberLabel(?ManualParameter $param): ?string
    {
        if (! $param) {
            return null;
        }

        $ipl = optional($param->inspectionComponent?->variants?->first()?->component)->ipl_num;

        return trim($param->description . ($ipl ? " ({$ipl})" : ''));
    }
}
