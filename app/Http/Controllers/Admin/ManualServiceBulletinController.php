<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualServiceBulletin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualServiceBulletinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manuals.update');
    }

    public function store(Request $request, Manual $manual): RedirectResponse
    {
        $this->ensureManualAccess($manual);

        $data = $this->validateBulletin($request);
        $data['sort_order'] = $this->nextSortOrder($manual);
        $data['is_active'] = $request->boolean('is_active', true);

        $manual->serviceBulletins()->create($data);

        return $this->backToSbTab($manual)->with('success', 'Service Bulletin row created.');
    }

    public function update(Request $request, Manual $manual, ManualServiceBulletin $serviceBulletin): RedirectResponse|JsonResponse
    {
        $this->ensureManualAccess($manual);
        abort_unless((int) $serviceBulletin->manual_id === (int) $manual->id, 404);

        $data = $this->validateBulletin($request);
        unset($data['sort_order']); // Ordering is saved independently from row content.
        $data['is_active'] = $request->boolean('is_active');

        $serviceBulletin->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Service Bulletin row updated.']);
        }

        return $this->backToSbTab($manual)->with('success', 'Service Bulletin row updated.');
    }

    public function destroy(Manual $manual, ManualServiceBulletin $serviceBulletin): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_unless((int) $serviceBulletin->manual_id === (int) $manual->id, 404);

        DB::transaction(function () use ($manual, $serviceBulletin): void {
            $rows = $manual->serviceBulletins()->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get();
            $serviceBulletin->delete();
            foreach ($rows->reject(fn ($row) => $row->id === $serviceBulletin->id)->values() as $index => $row) {
                $row->update(['sort_order' => $index + 1]);
            }
        });

        return $this->backToSbTab($manual)->with('success', 'Service Bulletin row deleted.');
    }

    private function validateBulletin(Request $request): array
    {
        return $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'year_introduced' => ['nullable', 'string', 'max:255'],
            'ac_mfg_service_bulletin_no' => ['nullable', 'string', 'max:255'],
            'oem_service_bulletin_no' => ['nullable', 'string', 'max:255'],
            'awd_no' => ['nullable', 'string', 'max:255'],
            'identification_method' => ['nullable', 'string', 'max:600'],
            'description' => ['nullable', 'string'],
            'default_requirement' => ['nullable', 'in:optional,recommended,mandatory'],
        ]);
    }

    public function reorder(Request $request, Manual $manual): JsonResponse
    {
        $this->ensureManualAccess($manual);
        $data = $request->validate(['ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct']]);
        $ids = array_map('intval', $data['ids']);
        DB::transaction(function () use ($manual, $ids): void {
            $existing = $manual->serviceBulletins()->lockForUpdate()->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (count($existing) !== count($ids) || array_diff($existing, $ids)) {
                throw ValidationException::withMessages(['ids' => 'The SB list changed. Reload the page before reordering.']);
            }
            foreach ($ids as $index => $id) {
                $manual->serviceBulletins()->whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });
        return response()->json(['ids' => $ids]);
    }

    private function nextSortOrder(Manual $manual): int
    {
        return ((int) $manual->serviceBulletins()->withTrashed()->max('sort_order')) + 1;
    }

    private function backToSbTab(Manual $manual): RedirectResponse
    {
        return redirect()->route('manuals.show', ['manual' => $manual->id, 'tab' => 'sb']);
    }

    private function ensureManualAccess(Manual $manual): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->roleIs('Admin') || $user->hasFullManualsAccess()) {
            return;
        }

        abort_unless($manual->permittedUsers()->where('users.id', $user->id)->exists(), 403);
    }
}
