<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\StdProcess;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManualStdProcessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Manual $manual): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        $data = $request->validate([
            'std' => 'required|in:ndt,cad,stress,paint',
            'component_id' => 'required|integer|exists:components,id',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $component = Component::query()->with('manual')->findOrFail($data['component_id']);
        $this->ensureComponentAllowedForStdAdd($manual, $component);

        $std = $data['std'];
        $allowed = StdProcess::processPicklistValuesForManual($manual->id, $std);
        if ($allowed === []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'There are no processes for this STD type on the current CMM Processes tab. Add a matching process in Processes, then try again.');
        }
        $processVal = $this->validatedProcessValue($request, $std, $allowed);

        if (trim((string) ($component->ipl_num ?? '')) === '') {
            return redirect()->back()
                ->withInput()
            ->with('error', 'The selected part has no IPL. Set the IPL on the Parts tab.');
        }

        if (StdProcess::rowExistsForComponentStd((int) $manual->id, (int) $component->id, $std)) {
            return redirect()->back()
                ->withInput()
            ->with('error', 'This part (IPL and Part No.) already exists in the table for the selected STD type. Adding was canceled.');
        }

        $manual->stdProcesses()->create([
            'component_id' => $component->id,
            'std' => $std,
            'process' => $processVal,
            'qty' => $data['qty'],
            'eff_code' => StdProcess::normalizeEffCodeForStorage($data['eff_code'] ?? null),
        ]);

        $flagColumn = StdProcess::componentFlagColumnForStd($std);
        if (! (bool) $component->{$flagColumn}) {
            $component->updateQuietly([$flagColumn => true]);
        }
        $this->invalidateWorkorderStdSnapshots($manual);

        return redirect()->route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => $std,
        ])->with('success', 'STD row added');
    }

    public function componentsForAdd(Request $request, Manual $manual): JsonResponse
    {
        $this->ensureManualAccess($manual);
        $sourceId = (int) $request->query('source_manual_id', 0);
        abort_unless($sourceId > 0, 422);
        $source = Manual::query()->findOrFail($sourceId);
        $this->ensureManualAccess($source);
        $this->ensureSamePlaneBuilder($manual, $source);

        $parts = Component::query()
            ->where('manual_id', $source->id)
            ->get()
            ->sortBy(function (Component $p) {
                return StdProcess::iplNumSortRank($p->ipl_num);
            })
            ->values();

        return response()->json(
            $parts->map(function (Component $p) {
                return [
                    'id' => $p->id,
                    'ipl_num' => $p->ipl_num,
                    'part_number' => $p->part_number,
                    'name' => $p->name,
                    'units_assy' => $p->units_assy,
                ];
            })->all()
        );
    }

    public function update(Request $request, Manual $manual, StdProcess $stdProcess): RedirectResponse|JsonResponse
    {
        $this->ensureManualAccess($manual);

        abort_if($stdProcess->manual_id != $manual->id, 404);

        $allowed = StdProcess::processPicklistValuesForManual($manual->id, $stdProcess->std);
        if ($allowed === []) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'There are no processes for this STD type on the current CMM Processes tab. Add a matching process in Processes, then try again.');
        }

        $data = $request->validate([
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);
        $processVal = $this->validatedProcessValue($request, $stdProcess->std, $allowed);

        $stdProcess->update([
            'process' => $processVal,
            'qty' => $data['qty'],
            'eff_code' => StdProcess::normalizeEffCodeForStorage($data['eff_code'] ?? null),
        ]);
        $this->invalidateWorkorderStdSnapshots($manual);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'STD row updated',
                'row' => [
                    'id' => $stdProcess->id,
                    'std' => $stdProcess->std,
                    'process' => (string) $stdProcess->process,
                    'qty' => (int) $stdProcess->qty,
                    'eff_code' => StdProcess::normalizeEffCodeForStorage($stdProcess->eff_code) ?? '',
                ],
            ]);
        }

        return redirect()->route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => $stdProcess->std,
        ])->with('success', 'STD row updated');
    }

    public function destroy(Manual $manual, StdProcess $stdProcess): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_if($stdProcess->manual_id !== $manual->id, 404);
        $flagColumn = StdProcess::componentFlagColumnForStd($stdProcess->std);
        $component = $stdProcess->component;

        if ($component) {
            $component->update([$flagColumn => false]);
        } else {
            $stdProcess->delete();
        }
        $this->invalidateWorkorderStdSnapshots($manual);

        return redirect()->route('manuals.show', ['manual' => $manual->id, 'tab' => 'std'])
            ->with('success', 'STD row deleted');
    }

    protected function ensureManualAccess(Manual $manual): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->roleIs('Admin') || $user->hasFullManualsAccess()) {
            return;
        }

        $allowed = $manual->permittedUsers()->where('users.id', $user->id)->exists();
        abort_unless($allowed, 403);
    }

    protected function invalidateWorkorderStdSnapshots(Manual $manual): void
    {
        app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manual->id);
    }

    /**
     * @param  array<int, string>  $allowed
     */
    protected function validatedProcessValue(Request $request, string $std, array $allowed): string
    {
        if ($std === StdProcess::STD_NDT) {
            $data = $request->validate([
                'process' => ['required', 'array', 'min:1'],
                'process.*' => ['required', 'string', 'max:32', Rule::in($allowed)],
            ]);

            $values = array_values(array_unique(array_map('strval', $data['process'])));

            return implode(' / ', $values);
        }

        $data = $request->validate([
            'process' => ['required', 'string', 'max:255', Rule::in($allowed)],
        ]);

        return (string) $data['process'];
    }

    protected function ensureSamePlaneBuilder(Manual $page, Manual $other): void
    {
        if ((int) $page->planes_id !== (int) $other->planes_id
            || (int) $page->builders_id !== (int) $other->builders_id) {
            abort(403, 'Выбранный CMM должен совпадать по Plane и Builder с текущим.');
        }
    }

    protected function ensureComponentAllowedForStdAdd(Manual $pageManual, Component $component): void
    {
        $compManual = $component->manual;
        if (! $compManual) {
            abort(422, 'У детали не указан мануал.');
        }
        $this->ensureManualAccess($compManual);
        $this->ensureSamePlaneBuilder($pageManual, $compManual);
    }
}
