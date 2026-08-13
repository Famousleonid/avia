<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\Workorder;
use App\Models\WorkorderPartGroupSelection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkorderPartGroupSelectionController extends Controller
{
    public function update(Request $request, Workorder $workorder, ManualPartGroup $partGroup): JsonResponse
    {
        $data = $request->validate([
            'option_id' => ['nullable', 'integer', 'exists:manual_part_group_options,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        abort_unless($request->user(), 403);
        $workorder->loadMissing('unit');
        if (! in_array((int) $partGroup->manual_id, $workorder->usedManualIds(), true)) {
            throw ValidationException::withMessages(['part_group' => 'This group does not belong to the workorder manuals.']);
        }

        if (empty($data['option_id'])) {
            WorkorderPartGroupSelection::query()
                ->where('workorder_id', $workorder->id)
                ->where('manual_part_group_id', $partGroup->id)
                ->delete();

            return response()->json(['success' => true, 'selected' => false]);
        }

        $option = ManualPartGroupOption::query()
            ->whereKey((int) $data['option_id'])
            ->where('manual_part_group_id', $partGroup->id)
            ->first();
        if (! $option) {
            throw ValidationException::withMessages(['option_id' => 'The selected option does not belong to this group.']);
        }

        $selection = WorkorderPartGroupSelection::query()->updateOrCreate(
            ['workorder_id' => $workorder->id, 'manual_part_group_id' => $partGroup->id],
            [
                'manual_part_group_option_id' => $option->id,
                'qty' => max(1, (int) ($data['qty'] ?? 1)),
                'selected_by_user_id' => $request->user()->id,
            ]
        );

        return response()->json([
            'success' => true,
            'selected' => true,
            'option_id' => (int) $selection->manual_part_group_option_id,
            'qty' => (int) $selection->qty,
        ]);
    }
}
