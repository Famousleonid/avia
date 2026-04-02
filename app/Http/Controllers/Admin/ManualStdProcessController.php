<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\StdProcess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'ipl_num' => 'required|string|max:64',
            'part_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'process' => 'nullable|string|max:255',
            'qty' => 'required|integer|min:1',
            'manual' => 'nullable|string|max:255',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $manual->stdProcesses()->create([
            'std' => $data['std'],
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
            'std_inner' => $data['std'],
        ])->with('success', 'Строка STD добавлена');
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
}
