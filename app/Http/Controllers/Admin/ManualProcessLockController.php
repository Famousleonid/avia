<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ManualProcessNameLock;
use App\Models\ProcessName;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualProcessLockController extends Controller
{
    public function lockProcessName(Request $request, Manual $manual, ProcessName $processName): JsonResponse
    {
        $decision = $this->guard()->canLockProcessName($request->user(), $manual, $processName);
        if (! $decision->allowed) {
            return $this->denyDecision($decision);
        }

        $lock = ManualProcessNameLock::query()->updateOrCreate(
            [
                'manual_id' => $manual->id,
                'process_name_id' => $processName->id,
            ],
            [
                'locked_by_user_id' => $request->user()->id,
                'locked_at' => now(),
            ]
        );

        $lock->load('lockedBy');

        return $this->lockResponse(true, 'Process group locked successfully.', $lock->lockedBy?->selection_name);
    }

    public function unlockProcessName(Request $request, Manual $manual, ProcessName $processName): JsonResponse
    {
        $decision = $this->guard()->canUnlockProcessName($request->user(), $manual, $processName);
        if (! $decision->allowed) {
            return $this->denyDecision($decision);
        }

        ManualProcessNameLock::query()
            ->where('manual_id', $manual->id)
            ->where('process_name_id', $processName->id)
            ->delete();

        return $this->lockResponse(false, 'Process group unlocked successfully.');
    }

    public function lockManualProcess(Request $request, Manual $manual, ManualProcess $manualProcess): JsonResponse
    {
        abort_unless((int) $manualProcess->manual_id === (int) $manual->id, 404);
        $decision = $this->guard()->canLockManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($decision);
        }

        $manualProcess->update([
            'is_locked' => true,
            'locked_by_user_id' => $request->user()->id,
            'locked_at' => now(),
        ]);
        $manualProcess->load('lockedBy');

        return $this->lockResponse(true, 'Process locked successfully.', $manualProcess->lockedBy?->selection_name);
    }

    public function unlockManualProcess(Request $request, Manual $manual, ManualProcess $manualProcess): JsonResponse
    {
        abort_unless((int) $manualProcess->manual_id === (int) $manual->id, 404);
        $decision = $this->guard()->canUnlockManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($decision);
        }

        $manualProcess->update([
            'is_locked' => false,
            'locked_by_user_id' => null,
            'locked_at' => null,
        ]);

        return $this->lockResponse(false, 'Process unlocked successfully.');
    }

    private function lockResponse(bool $locked, string $message, ?string $lockedBy = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'locked' => $locked,
            'locked_by' => $lockedBy,
        ]);
    }

    private function denyDecision(ProcessAccessDecision $decision): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $decision->message,
            'reason' => $decision->reason,
            'contacts' => $decision->contacts,
        ], 403);
    }

    private function guard(): ProcessAccessGuard
    {
        return app(ProcessAccessGuard::class);
    }
}
