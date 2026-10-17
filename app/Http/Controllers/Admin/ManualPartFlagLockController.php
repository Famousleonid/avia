<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManualPartFlagLockController extends Controller
{
    public function update(Request $request, Manual $manual)
    {
        $user = $request->user();
        abort_unless($user && ($user->isSystemAdmin() || $user->hasExplicitFeatureAccess('manuals.flag_locks')), 403);
        abort_unless($user->roleIs('Admin') || $user->hasFullManualsAccess()
            || $user->permittedManuals()->where('manuals.id', $manual->id)->exists(), 403);
        $data = $request->validate([
            'field' => ['required', Rule::in(['kit', 'np', 'kit_e'])],
            'locked' => ['required', 'boolean'],
        ]);
        $locks = DB::transaction(function () use ($manual, $data, $user) {
            $record = Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            $locks = json_decode($record->getRawOriginal('part_flag_locks') ?? '[]', true) ?: [];
            if ($data['locked']) {
                $locks[$data['field']] = ['user_id' => $user->id, 'locked_at' => now()->toIso8601String()];
            } else {
                unset($locks[$data['field']]);
            }
            $record->part_flag_locks = json_encode($locks);
            $record->save();
            activity('manual_flag_lock')->performedOn($record)->causedBy($user)
                ->withProperties($data)->log($data['locked'] ? 'Column locked' : 'Column unlocked');
            return $locks;
        });
        return response()->json(['locks' => $locks]);
    }
}
