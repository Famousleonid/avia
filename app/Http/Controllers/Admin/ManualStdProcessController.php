<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\StdProcess;
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
        $processRules = ['nullable', 'string', 'max:255'];
        if (in_array($std, ['cad', 'stress', 'paint'], true)) {
            $allowed = StdProcess::processPicklistValuesForManual($manual->id, $std);
            if ($allowed === []) {
                return redirect()->back()
                    ->withInput()
            ->with('error', 'There are no processes for this STD type on the current CMM Processes tab. Add a process (Cad plate / Stress / Paint) in Processes, then try adding the row again.');
            }
            $processRules = ['required', 'string', 'max:255', Rule::in($allowed)];
        }

        $processData = $request->validate(['process' => $processRules]);
        $processVal = $processData['process'] ?? null;
        if ($std === 'ndt') {
            $processVal = ($processVal !== null && $processVal !== '') ? $processVal : '1';
        } else {
            $processVal = (string) ($processData['process'] ?? '1');
        }

        $srcManual = $component->manual;
        $manualRef = null;
        if ($srcManual && (int) $component->manual_id !== (int) $manual->id) {
            $num = trim((string) ($srcManual->number ?? ''));
            $manualRef = $num !== '' ? $num : null;
        }

        $ipl = trim((string) ($component->ipl_num ?? ''));
        if ($ipl === '') {
            return redirect()->back()
                ->withInput()
            ->with('error', 'The selected part has no IPL. Set the IPL on the Parts tab.');
        }

        $partNum = trim((string) ($component->part_number ?? ''));
        if (StdProcess::rowExistsForManualStdPart($manual->id, $std, $ipl, $partNum)) {
            return redirect()->back()
                ->withInput()
            ->with('error', 'This part (IPL and Part No.) already exists in the table for the selected STD type. Adding was canceled.');
        }

        $manual->stdProcesses()->create([
            'std' => $std,
            'ipl_num' => $ipl,
            'part_number' => $partNum,
            'description' => $component->name,
            'process' => $processVal,
            'qty' => $data['qty'],
            'manual' => $manualRef,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($data['eff_code'] ?? null),
        ]);

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

    public function update(Request $request, Manual $manual, StdProcess $stdProcess): RedirectResponse
    {
        $this->ensureManualAccess($manual);

        abort_if($stdProcess->manual_id != $manual->id, 404);

        $data = $request->validate([
            'ipl_num' => 'required|string|max:64',
            'part_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'process' => 'nullable|string|max:255',
            'qty' => 'required|integer|min:1',
            'manual' => 'nullable|string|max:255',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $stdProcess->update([
            'ipl_num' => $data['ipl_num'],
            'part_number' => $data['part_number'] ?? '',
            'description' => $data['description'] ?? null,
            'process' => $data['process'] ?? '1',
            'qty' => $data['qty'],
            'manual' => isset($data['manual']) && $data['manual'] !== '' ? $data['manual'] : null,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($data['eff_code'] ?? null),
        ]);

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
        $stdProcess->delete();

        return redirect()->route('manuals.show', ['manual' => $manual->id, 'tab' => 'std'])
            ->with('success', 'STD row deleted');
    }

    public function reimportFromCsv(Manual $manual): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        try {
            StdProcess::reimportAllTypesFromMedia($manual);
        } catch (\Throwable $e) {
            \Log::error('ManualStdProcessController::reimportFromCsv failed', [
                'manual_id' => $manual->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('manuals.show', [
                'manual' => $manual->id,
                'tab' => 'std',
                'std_inner' => 'csv',
        ])->with('error', 'Failed to import from CSV: '.$e->getMessage());
        }

        return redirect()->route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => 'csv',
        ])->with('success', 'NDT / CAD / Stress / Paint tables were updated from uploaded CSV files.');
    }

    protected function ensureManualAccess(Manual $manual): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->roleIs('Admin')) {
            return;
        }

        $allowed = $manual->permittedUsers()->where('users.id', $user->id)->exists();
        abort_unless($allowed, 403);
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
