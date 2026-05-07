<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualPartLock;
use App\Services\ManualPartAccessGuard;
use App\Services\ProcessAccessDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManualPartLockController extends Controller
{
    public function lock(Request $request, Manual $manual): RedirectResponse
    {
        $decision = $this->guard()->canLockManualParts($request->user(), $manual);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        ManualPartLock::query()->updateOrCreate(
            ['manual_id' => $manual->id],
            [
                'locked_by_user_id' => auth()->id(),
                'locked_at' => now(),
                'notes' => $request->input('notes'),
            ]
        );

        return $this->redirectBackToParts($request, 'Manual parts locked successfully.');
    }

    public function unlock(Request $request, Manual $manual): RedirectResponse
    {
        $decision = $this->guard()->canUnlockManualParts($request->user(), $manual);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        ManualPartLock::query()
            ->where('manual_id', $manual->id)
            ->delete();

        return $this->redirectBackToParts($request, 'Manual parts unlocked successfully.');
    }

    private function redirectBackToParts(Request $request, string $message): RedirectResponse
    {
        $returnTo = (string) $request->input('return_to', '');

        if ($returnTo !== '') {
            return redirect($returnTo)->with('success', $message);
        }

        return redirect()->back()->with('success', $message);
    }

    private function denyDecision(Request $request, ProcessAccessDecision $decision): RedirectResponse
    {
        $returnTo = (string) $request->input('return_to', '');

        if ($returnTo !== '') {
            return redirect($returnTo)->with('error', $decision->message);
        }

        return redirect()->back()->with('error', $decision->message);
    }

    private function guard(): ManualPartAccessGuard
    {
        return app(ManualPartAccessGuard::class);
    }
}
