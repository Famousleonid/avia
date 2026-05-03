<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\ProcessName;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManualProcessLockController extends Controller
{
    public function lockProcessName(Request $request, Manual $manual, ProcessName $processName): RedirectResponse
    {
        $decision = $this->guard()->canLockProcessName($request->user(), $manual, $processName);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        ManualProcessNameLock::query()->updateOrCreate(
            [
                'manual_id' => $manual->id,
                'process_name_id' => $processName->id,
            ],
            [
                'locked_by_user_id' => auth()->id(),
                'locked_at' => now(),
            ]
        );

        return $this->redirectBackToProcesses($request, 'Process group locked successfully.');
    }

    public function unlockProcessName(Request $request, Manual $manual, ProcessName $processName): RedirectResponse
    {
        $decision = $this->guard()->canUnlockProcessName($request->user(), $manual, $processName);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        ManualProcessNameLock::query()
            ->where('manual_id', $manual->id)
            ->where('process_name_id', $processName->id)
            ->delete();

        return $this->redirectBackToProcesses($request, 'Process group unlocked successfully.');
    }

    public function lockManualProcess(Request $request, Manual $manual, ManualProcess $manualProcess): RedirectResponse
    {
        abort_unless((int) $manualProcess->manual_id === (int) $manual->id, 404);
        $decision = $this->guard()->canLockManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        $manualProcess->update([
            'is_locked' => true,
            'locked_by_user_id' => auth()->id(),
            'locked_at' => now(),
        ]);

        return $this->redirectBackToProcesses($request, 'Process locked successfully.');
    }

    public function unlockManualProcess(Request $request, Manual $manual, ManualProcess $manualProcess): RedirectResponse
    {
        abort_unless((int) $manualProcess->manual_id === (int) $manual->id, 404);
        $decision = $this->guard()->canUnlockManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision);
        }

        $manualProcess->update([
            'is_locked' => false,
            'locked_by_user_id' => null,
            'locked_at' => null,
        ]);

        return $this->redirectBackToProcesses($request, 'Process unlocked successfully.');
    }

    private function redirectBackToProcesses(Request $request, string $message): RedirectResponse
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

    private function guard(): ProcessAccessGuard
    {
        return app(ProcessAccessGuard::class);
    }
}
