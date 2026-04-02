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
                    ->with('error', 'Для этого типа STD нет процессов на вкладке Processes текущего CMM. Добавьте процесс (Cad plate / Stress / Paint) в Processes, затем повторите добавление строки.');
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
                ->with('error', 'У выбранной детали не заполнен IPL. Укажите IPL на вкладке Parts.');
        }

        $partNum = trim((string) ($component->part_number ?? ''));
        if (StdProcess::rowExistsForManualStdPart($manual->id, $std, $ipl, $partNum)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Эта деталь (IPL и Part №) уже есть в таблице для выбранного типа STD. Добавление отменено.');
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
        ])->with('success', 'Строка STD добавлена');
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
            ->sortBy(fn (Component $p) => StdProcess::iplNumSortRank($p->ipl_num))
            ->values();

        return response()->json(
            $parts->map(static fn (Component $p) => [
                'id' => $p->id,
                'ipl_num' => $p->ipl_num,
                'part_number' => $p->part_number,
                'name' => $p->name,
                'units_assy' => $p->units_assy,
            ])->all()
        );
    }

    public function update(Request $request, Manual $manual, StdProcess $stdProcess): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_if($stdProcess->manual_id !== $manual->id, 404);

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
        ])->with('success', 'Строка STD обновлена');
    }

    public function destroy(Manual $manual, StdProcess $stdProcess): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_if($stdProcess->manual_id !== $manual->id, 404);
        $stdProcess->delete();

        return redirect()->route('manuals.show', ['manual' => $manual->id, 'tab' => 'std'])
            ->with('success', 'Строка STD удалена');
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
            ])->with('error', 'Не удалось импортировать из CSV: '.$e->getMessage());
        }

        return redirect()->route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => 'csv',
        ])->with('success', 'Таблицы NDT / CAD / Stress / Paint обновлены из загруженных CSV-файлов.');
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
