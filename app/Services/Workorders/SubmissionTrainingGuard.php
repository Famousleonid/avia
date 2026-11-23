<?php

namespace App\Services\Workorders;

use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmissionTrainingGuard
{
    public function check(User $user, Workorder $workorder, Task $task, array $data): void
    {
        $name = strtolower(preg_replace('/^\d+[.)]\s*/', '', trim($task->name)));
        if (!$user->roleIs(['Technician', 'Team Leader'])
            || $name !== 'wo submitted to inspection'
            || $task->generalTask?->technician_stage !== 'disassembly'
            || empty($data['date_finish'])) {
            return;
        }

        $workorder->loadMissing('unit.manual');
        $manual = $workorder->unit?->manual;
        $days = (int) config('trainings.matrix_red_after_days', 350);
        $today = today();
        $valid = $manual && Training::where('user_id', $user->id)
            ->where('manuals_id', $manual->id)->where('form_type', 112)
            ->where('is_legacy', false)
            ->whereDate('date_training', '>=', $today->copy()->subDays($days)->toDateString())
            ->whereDate('date_training', '<=', $today->toDateString())->exists();
        if ($valid) return;

        $part = $manual?->unit_name_training ?: $workorder->unit?->part_number;
        $message = "A current Form 112 training (within {$days} days) is required for {$part} before submitting this WO to inspection. The date was not saved.";
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => ['date_finish' => [$message]],
            'training_required' => true,
            'training_url' => $manual ? route('trainings.index', [
                'manual_id' => $manual->id,
                'open_training' => $manual->id,
            ]) : null,
        ], 422));
    }
}
