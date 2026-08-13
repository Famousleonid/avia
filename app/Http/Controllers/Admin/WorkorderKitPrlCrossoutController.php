<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Workorder;
use App\Models\WorkorderKitPrlCrossout;
use App\Services\ManualIplBranchRuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkorderKitPrlCrossoutController extends Controller
{
    public function update(
        Request $request,
        Workorder $workorder,
        Component $component,
        ManualIplBranchRuleResolver $branchRules
    ): JsonResponse {
        $validated = $request->validate([
            'crossed_out' => ['required', 'boolean'],
        ]);

        $workorder->loadMissing('unit');
        $belongsToKit = (bool) $component->kit
            && in_array((int) $component->manual_id, $workorder->usedManualIds(), true)
            && $branchRules->allowsComponentForUnit(
                $workorder->unit,
                (string) ($component->ipl_num ?? ''),
                (int) $component->manual_id
            );

        if (! $belongsToKit) {
            throw ValidationException::withMessages([
                'component' => ['This component is not available in the selected workorder KIT.'],
            ]);
        }

        $crossedOut = (bool) $validated['crossed_out'];

        if ($crossedOut) {
            WorkorderKitPrlCrossout::query()->updateOrCreate(
                [
                    'workorder_id' => $workorder->id,
                    'component_id' => $component->id,
                ],
                [
                    'created_by_user_id' => $request->user()?->id,
                ]
            );
        } else {
            WorkorderKitPrlCrossout::query()
                ->where('workorder_id', $workorder->id)
                ->where('component_id', $component->id)
                ->delete();
        }

        return response()->json([
            'component_id' => (int) $component->id,
            'crossed_out' => $crossedOut,
            'message' => $crossedOut
                ? 'KIT position crossed out.'
                : 'KIT position restored.',
        ]);
    }
}
