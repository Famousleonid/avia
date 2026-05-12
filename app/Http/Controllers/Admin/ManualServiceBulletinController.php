<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualServiceBulletin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder($manual);
        $data['is_active'] = $request->boolean('is_active', true);

        $manual->serviceBulletins()->create($data);

        return $this->backToSbTab($manual)->with('success', 'Service Bulletin row created.');
    }

    public function update(Request $request, Manual $manual, ManualServiceBulletin $serviceBulletin): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_unless((int) $serviceBulletin->manual_id === (int) $manual->id, 404);

        $data = $this->validateBulletin($request);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $serviceBulletin->update($data);

        return $this->backToSbTab($manual)->with('success', 'Service Bulletin row updated.');
    }

    public function destroy(Manual $manual, ManualServiceBulletin $serviceBulletin): RedirectResponse
    {
        $this->ensureManualAccess($manual);
        abort_unless((int) $serviceBulletin->manual_id === (int) $manual->id, 404);

        $serviceBulletin->delete();

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
            'identification_method' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_requirement' => ['nullable', 'in:optional,recommended,mandatory'],
        ]);
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
