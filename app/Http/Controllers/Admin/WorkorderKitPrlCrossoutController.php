<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Workorder;
use App\Models\WorkorderKitPrlCrossout;
use App\Services\ManualIplBranchRuleResolver;
use App\Services\WorkorderPartScopeResolver;
use App\Support\BushingPrlGrouping;
use App\Models\Unit;
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
        $available = app(WorkorderPartScopeResolver::class)->filterComponents(
            Component::whereIn('manual_id', $workorder->usedManualIds())->get(), $workorder
        )->filter(fn (Component $part): bool => $branchRules->allowsComponentForUnit(
            $workorder->unit, (string) $part->ipl_num, (int) $part->manual_id
        ));
        $familyInKit = BushingPrlGrouping::groups(
            BushingPrlGrouping::candidates($available)
        )->contains(fn ($family): bool => $family->contains('id', $component->id)
            && $family->contains(fn (Component $part): bool => (bool) $part->kit));
        $belongsToKit = $workorder->isOverhaul()
            && $workorder->scope_type !== Unit::SCOPE_COMPONENT
            && $available->contains('id', $component->id)
            && ((bool) $component->kit || $familyInKit)
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
