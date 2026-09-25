<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Models\WorkorderAssyConfigurationChoice;
use App\Services\WorkorderAssyConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkorderAssyConfigurationController extends Controller
{
    public function index(Request $request, Workorder $workorder, WorkorderAssyConfiguration $configuration): JsonResponse
    {
        abort_unless($request->user(), 403);

        return response()->json(['slots' => $configuration->slots($workorder)]);
    }

    public function update(Request $request, Workorder $workorder, WorkorderAssyConfiguration $configuration): JsonResponse
    {
        abort_unless($request->user(), 403);
        if ($workorder->done_at) {
            throw ValidationException::withMessages(['workorder' => 'Completed workorders cannot change ASSY configuration.']);
        }

        $data = $request->validate([
            'parent_option_id' => ['required', 'integer', 'exists:manual_part_group_options,id'],
            'choice_slot' => ['required', 'string', 'max:80'],
            'selected_coverage_id' => ['nullable', 'integer', 'exists:manual_part_group_coverages,id'],
        ]);
        $slot = collect($configuration->slots($workorder))->first(fn (array $candidate): bool =>
            $candidate['parent_option_id'] === (int) $data['parent_option_id']
                && $candidate['slot'] === $data['choice_slot']
        );
        if (! $slot) {
            throw ValidationException::withMessages(['choice_slot' => 'This ASSY choice is not available on this workorder.']);
        }

        $coverageId = (int) ($data['selected_coverage_id'] ?? 0);
        if ($coverageId && ! collect($slot['candidates'])->contains('coverage_id', $coverageId)) {
            throw ValidationException::withMessages(['selected_coverage_id' => 'This part does not belong to the selected ASSY choice.']);
        }

        $key = [
            'workorder_id' => $workorder->id,
            'parent_option_id' => (int) $data['parent_option_id'],
            'choice_slot' => $data['choice_slot'],
        ];
        DB::transaction(function () use ($key, $coverageId, $request, $workorder, $configuration): void {
            if ($coverageId) {
                WorkorderAssyConfigurationChoice::query()->updateOrCreate($key, [
                    'selected_coverage_id' => $coverageId,
                    'selected_by_user_id' => $request->user()->id,
                ]);
            } else {
                WorkorderAssyConfigurationChoice::query()->where($key)->delete();
            }

            // A changed parent can make an earlier axle/child choice unreachable.
            // Do not silently reactivate that stale choice if the parent returns.
            $reachable = collect($configuration->slots($workorder))->mapWithKeys(fn (array $slot): array => [
                $configuration::key($slot['parent_option_id'], $slot['slot']) => true,
            ]);
            WorkorderAssyConfigurationChoice::query()->where('workorder_id', $workorder->id)
                ->get()->each(function (WorkorderAssyConfigurationChoice $choice) use ($reachable, $configuration): void {
                    if (! $reachable->has($configuration::key((int) $choice->parent_option_id, (string) $choice->choice_slot))) {
                        $choice->delete();
                    }
                });
        });

        return response()->json(['success' => true, 'slots' => $configuration->slots($workorder)]);
    }
}
