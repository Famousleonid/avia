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

    /**
     * Flat dimensions report rows (the "old report" rebuilt on the new data):
     * one row per parameter×point with orig/wear limits, an F&C flag driven by
     * manual_fit membership (NOT the legacy is_fits_clearance point flag).
     */
    public function report(Manual $manual)
    {
        $fitMemberIds = $manual->fits()->where('is_fc', true)->get(['od_param_id', 'id_param_id'])
            ->flatMap(fn ($f) => [$f->od_param_id, $f->id_param_id])
            ->filter()->unique()->flip();

        $figures = \App\Models\ManualDimensionFigure::where('manual_id', $manual->id)
            ->get(['id', 'title', 'parent_figure_id', 'sort_order']);
        $figLabel = [];
        $figSort = [];
        foreach ($figures as $f) {
            $parent = $f->parent_figure_id ? $figures->firstWhere('id', $f->parent_figure_id) : null;
            $figLabel[$f->id] = $parent ? ($parent->title . ': ' . $f->title) : $f->title;
            $figSort[$f->id] = $f->sort_order ?? 0;
        }

        $params = ManualParameter::where('manual_id', $manual->id)
            ->where(fn ($q) => $q->whereNotNull('orig_dim_min')->orWhereNotNull('orig_dim_max'))
            ->with(['points:id,code,manual_dimension_figure_id,sort_order', 'inspectionComponent:id,label'])
            ->get();

        $rows = [];
        foreach ($params as $p) {
            $comp = $p->inspectionComponent?->label;
            $isFc = $fitMemberIds->has($p->id);
            foreach ($p->points as $pt) {
                $figId = $pt->manual_dimension_figure_id;
                $rows[] = [
                    'figure'      => $figLabel[$figId] ?? '',
                    'fig_sort'    => $figSort[$figId] ?? 0,
                    'ref'         => $pt->code,
                    'component'   => $comp,
                    'description' => $p->description,
                    'orig_min'    => $p->orig_dim_min,
                    'orig_max'    => $p->orig_dim_max,
                    'wear_min'    => $p->wear_dim_min ?? $p->orig_dim_min,
                    'wear_max'    => $p->wear_dim_max ?? $p->orig_dim_max,
                    'is_fc'       => $isFc,
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return [$a['fig_sort'], (string) $a['ref']] <=> [$b['fig_sort'], (string) $b['ref']];
        });

        return response()->json($rows);
    }

    /**
     * On-demand detection: create fits for points that carry both an OD and an
     * ID parameter (same heuristic as fits:backfill, scoped to this manual).
     * Idempotent — existing (od, id) pairs are kept, not duplicated; deleted
     * fits are NOT resurrected silently because this runs only on click.
     */
    public function detect(Manual $manual)
    {
        [$created, $skipped] = $this->detectPairs($manual);

        return response()->json(['created' => $created, 'skipped' => $skipped]);
    }

    /**
     * Shared detection used by detect() and the fits:backfill command.
     * Only points flagged Fits & Clearances (is_fits_clearance) qualify; on such
     * a point the OD member is the param described "OD", the ID member the one
     * described "ID" (or the other of two). ref_no is taken from the point code.
     * Returns [created, skipped]. Idempotent on (od_param_id, id_param_id).
     */
    public function detectPairs(Manual $manual): array
    {
        $params = ManualParameter::where('manual_id', $manual->id)
            ->where(fn ($q) => $q->whereNotNull('orig_dim_min')->orWhereNotNull('orig_dim_max'))
            ->with('points:id,code,is_fits_clearance')
            ->get();

        // Group qualifying params by point. A fit is any mating point with an
        // OD and an ID member; is_fits_clearance only decides the is_fc flag
        // (whether it shows in the F&C table), not whether the fit exists.
        $byPoint = [];
        foreach ($params as $p) {
            foreach ($p->points as $pt) {
                $byPoint[$pt->id] ??= ['code' => $pt->code, 'is_fc' => (bool) $pt->is_fits_clearance, 'params' => collect()];
                $byPoint[$pt->id]['params']->push($p);
            }
        }

        $created = 0;
        $skipped = 0;
        $order = (int) $manual->fits()->max('sort_order');

        foreach ($byPoint as $info) {
            $ps = $info['params'];
            if ($ps->count() < 2) {
                continue;
            }
            $od = $ps->first(fn ($p) => preg_match('/\bOD\b/i', (string) $p->description) === 1);
            $id = $ps->first(fn ($p) => preg_match('/\bID\b/i', (string) $p->description) === 1);
            if (! $id && $od && $ps->count() === 2) {
                $id = $ps->first(fn ($p) => $p->id !== $od->id);
            }
            if (! $od || ! $id) {
                continue;
            }

            $exists = ManualFit::where('od_param_id', $od->id)
                ->where('id_param_id', $id->id)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            ManualFit::create([
                'manual_id'   => $manual->id,
                'od_param_id' => $od->id,
                'id_param_id' => $id->id,
                'ref_no'      => $info['code'],
                'is_fc'       => $info['is_fc'],
                'sort_order'  => ++$order,
            ]);
            $created++;
        }

        return [$created, $skipped];
    }

    public function store(Request $request, Manual $manual)
    {
        $data = $this->validateData($request, $manual);

        $fit = ManualFit::create([
            'manual_id'              => $manual->id,
            'od_param_id'            => $data['od_param_id'] ?? null,
            'id_param_id'            => $data['id_param_id'] ?? null,
            'single_kind'            => $data['single_kind'] ?? null,
            'ref_no'                 => $data['ref_no'] ?? null,
            'id_ref_no'              => $data['id_ref_no'] ?? null,
            'is_fc'                  => $data['is_fc'] ?? true,
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
        $data = $request->validate([
            'od_param_id'            => 'nullable|integer|exists:manual_parameters,id',
            'id_param_id'            => 'nullable|integer|exists:manual_parameters,id',
            'single_kind'            => 'nullable|in:od,id,faces',
            'ref_no'                 => 'nullable|string|max:40',
            'id_ref_no'              => 'nullable|string|max:40',
            'is_fc'                  => 'nullable|boolean',
            'assembly_clearance_min' => 'nullable|numeric',
            'assembly_clearance_max' => 'nullable|numeric',
            'permitted_clearance'    => 'nullable|numeric',
            'sort_order'             => 'nullable|integer',
        ]);

        // A fit is a pair OR a single-member row (mate in another manual /
        // Between-Across Faces linear dimension) — at least one member always.
        if (! $partial && empty($data['od_param_id']) && empty($data['id_param_id'])) {
            throw ValidationException::withMessages(['od_param_id' => 'Select at least one member (OD, ID or Between/Across Faces).']);
        }

        // single_kind bookkeeping: derive for one-member saves, clear for pairs
        if (array_key_exists('od_param_id', $data) || array_key_exists('id_param_id', $data)) {
            $od = $data['od_param_id'] ?? null;
            $id = $data['id_param_id'] ?? null;
            if ($od !== null && $id !== null) {
                $data['single_kind'] = null;
            } elseif (($data['single_kind'] ?? null) !== 'faces') {
                $data['single_kind'] = $od !== null ? 'od' : ($id !== null ? 'id' : null);
            }
        }

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
            'id_ref_no'              => $fit->id_ref_no,
            'single_kind'            => $fit->single_kind,
            'is_fc'                  => (bool) $fit->is_fc,
            'sort_order'             => $fit->sort_order,
            'od_param_id'            => $fit->od_param_id,
            'id_param_id'            => $fit->id_param_id,
            'od_label'               => $this->memberLabel($fit->odParam),
            'id_label'               => $this->memberLabel($fit->idParam),
            'od_member'              => $this->member($fit->odParam),
            'id_member'              => $this->member($fit->idParam),
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

    private function member(?ManualParameter $param): ?array
    {
        if (! $param) {
            return null;
        }

        return [
            'id'          => $param->id,
            'description' => $param->description,
            'ipl'         => optional($param->inspectionComponent?->variants?->first()?->component)->ipl_num,
            'orig_min'    => $param->orig_dim_min,
            'orig_max'    => $param->orig_dim_max,
            // Wear falls back to orig when not set (same as the manual reading).
            'wear_min'    => $param->wear_dim_min ?? $param->orig_dim_min,
            'wear_max'    => $param->wear_dim_max ?? $param->orig_dim_max,
        ];
    }
}
