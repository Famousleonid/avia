<?php

namespace App\Services;

use App\Models\MachiningWorkStep;
use App\Models\ProcessName;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Support\WoBushingProcessColumnKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MachiningWorkStepsService
{
    /**
     * @throws ValidationException
     */
    public function syncWorkingStepsCount(TdrProcess|WoBushingBatch|WoBushingProcess $parent, int $n): void
    {
        if ($n < 1 || $n > 50) {
            throw ValidationException::withMessages(['working_steps_count' => ['Must be between 1 and 50']]);
        }

        if (! $parent->date_start) {
            throw ValidationException::withMessages(['working_steps_count' => ['Set send date first']]);
        }

        $this->assertParentEligible($parent);

        DB::transaction(function () use ($parent, $n): void {
            $parent->working_steps_count = $n;
            $parent->save();

            $this->stepsQuery($parent)->where('step_index', '>', $n)->delete();

            for ($i = 1; $i <= $n; $i++) {
                $this->firstOrCreateStep($parent, $i);
            }

            $this->validateFullChain($parent);
            $this->syncParentFinishFromSteps($parent);
        });
    }

    /**
     * @throws ValidationException
     */
    public function updateStepFromRequest(
        MachiningWorkStep $step,
        bool $machinistPresent,
        mixed $machinistUserId,
        bool $dateFinishPresent,
        mixed $dateFinish,
        bool $dateStartPresent = false,
        mixed $dateStart = null,
        bool $descriptionPresent = false,
        mixed $description = null,
    ): void {
        $this->assertUserCanEditStep(auth()->user(), $step);

        if ($descriptionPresent) {
            $step->description = ($description === null || $description === '')
                ? null
                : trim((string) $description);
        }

        if ($machinistPresent) {
            if ($machinistUserId !== null && $machinistUserId !== '' && (int) $machinistUserId > 0) {
                $mid = (int) $machinistUserId;
                $u = User::query()->find($mid);
                if (! $u || ! $u->roleIs(['Machining'])) {
                    throw ValidationException::withMessages(['machinist_user_id' => ['Invalid machinist']]);
                }
                $step->machinist_user_id = $mid;
            } else {
                $step->machinist_user_id = null;
            }
        }

        if ($dateStartPresent) {
            if ((int) $step->step_index !== 1) {
                throw ValidationException::withMessages(['date_start' => ['Work start date applies only to step 1']]);
            }
            $step->date_start = ($dateStart === '' || $dateStart === null)
                ? null
                : Carbon::parse((string) $dateStart)->startOfDay();
        }

        if ($dateFinishPresent) {
            $step->date_finish = ($dateFinish === '' || $dateFinish === null)
                ? null
                : Carbon::parse((string) $dateFinish)->startOfDay();
        }

        DB::transaction(function () use ($step): void {
            $step->save();
            $parent = $this->resolveParent($step);
            if (! $parent) {
                return;
            }
            $this->validateFullChain($parent);
            $this->syncParentFinishFromSteps($parent);
        });
    }

    public function clearStepsForParent(TdrProcess|WoBushingBatch|WoBushingProcess $parent): void
    {
        $this->stepsQuery($parent)->delete();
        $parent->working_steps_count = null;
        $parent->save();
    }

    /**
     * @throws ValidationException
     */
    public function validateFullChain(TdrProcess|WoBushingBatch|WoBushingProcess $parent): void
    {
        $n = (int) ($parent->working_steps_count ?? 0);
        if ($n < 1) {
            return;
        }

        $parent->loadMissing('machiningWorkSteps');
        $steps = $parent->machiningWorkSteps->sortBy('step_index')->values();
        if ($steps->count() !== $n) {
            throw ValidationException::withMessages(['steps' => ['Step rows out of sync with count']]);
        }

        if (! $parent->date_start) {
            throw ValidationException::withMessages(['date_start' => ['Send date required on parent before steps']]);
        }

        $prevFinish = null;
        foreach ($steps as $idx => $step) {
            if ((int) $step->step_index !== $idx + 1) {
                throw ValidationException::withMessages(['steps' => ['Invalid step order']]);
            }

            $effStart = $idx === 0
                ? (
                    $step->date_start
                        ? $step->date_start->copy()->startOfDay()
                        : ($parent->date_start ? $parent->date_start->copy()->startOfDay() : null)
                )
                : ($prevFinish ? $prevFinish->copy()->startOfDay() : null);

            if ($step->date_finish) {
                if ($idx === 0 && ! $effStart) {
                    throw ValidationException::withMessages([
                        'date_finish' => ['Set send date and step 1 work start date before finish'],
                    ]);
                }
                if ($idx > 0 && ! $prevFinish) {
                    throw ValidationException::withMessages([
                        'date_finish' => ['Finish previous step before step '.($idx + 1)],
                    ]);
                }
                if (! $step->machinist_user_id) {
                    throw ValidationException::withMessages([
                        'machinist_user_id' => ['Assign machinist for step '.($idx + 1)],
                    ]);
                }
                if ($effStart && $step->date_finish->lt($effStart)) {
                    throw ValidationException::withMessages([
                        'date_finish' => ['Step '.($idx + 1).' finish cannot be before effective start'],
                    ]);
                }
            }

            $prevFinish = $step->date_finish;
        }

        $last = $steps->last();
        if ($last && $last->date_finish) {
            foreach ($steps as $s) {
                if (! $s->machinist_user_id || ! $s->date_finish) {
                    throw ValidationException::withMessages([
                        'steps' => ['All steps require machinist and finish before closing the block'],
                    ]);
                }
            }
        }
    }

    public function syncParentFinishFromSteps(TdrProcess|WoBushingBatch|WoBushingProcess $parent): void
    {
        $n = (int) ($parent->working_steps_count ?? 0);
        if ($n < 1) {
            return;
        }

        $last = $this->stepsQuery($parent)->where('step_index', $n)->first();
        $parent->date_finish = $last?->date_finish;
        $parent->save();

        $wo = $this->resolveWorkorderForMachiningParent($parent);
        if ($wo) {
            app(MachiningWorkorderQueueRelease::class)->releaseIfFullyClosed($wo);
        }
    }

    private function resolveWorkorderForMachiningParent(TdrProcess|WoBushingBatch|WoBushingProcess $parent): ?Workorder
    {
        if ($parent instanceof TdrProcess) {
            $parent->loadMissing('tdr.workorder');

            return $parent->tdr?->workorder;
        }
        if ($parent instanceof WoBushingBatch) {
            $parent->loadMissing('workorder');

            return $parent->workorder;
        }
        $parent->loadMissing('line.workorder');

        return $parent->line?->workorder;
    }

    public function resolveParent(MachiningWorkStep $step): TdrProcess|WoBushingBatch|WoBushingProcess|null
    {
        if ($step->tdr_process_id) {
            return TdrProcess::query()->find($step->tdr_process_id);
        }
        if ($step->wo_bushing_batch_id) {
            return WoBushingBatch::query()->find($step->wo_bushing_batch_id);
        }
        if ($step->wo_bushing_process_id) {
            return WoBushingProcess::query()->find($step->wo_bushing_process_id);
        }

        return null;
    }

    protected function firstOrCreateStep(TdrProcess|WoBushingBatch|WoBushingProcess $parent, int $i): MachiningWorkStep
    {
        $existing = $this->stepsQuery($parent)->where('step_index', $i)->first();
        if ($existing) {
            return $existing;
        }

        return MachiningWorkStep::query()->create(array_merge(
            $this->parentFkAttributes($parent),
            [
                'step_index' => $i,
                'machinist_user_id' => null,
                'date_start' => null,
                'date_finish' => null,
                'description' => null,
            ]
        ));
    }

    protected function assertParentEligible(TdrProcess|WoBushingBatch|WoBushingProcess $parent): void
    {
        if ($parent instanceof TdrProcess) {
            $parent->loadMissing('processName');
            if (! ProcessName::isMachiningMachiningEcMergeMember($parent->processName)) {
                throw ValidationException::withMessages(['parent' => ['Working steps only for Machining TDR process']]);
            }

            return;
        }

        if ($parent instanceof WoBushingBatch || $parent instanceof WoBushingProcess) {
            $parent->loadMissing('process.process_name');
            $key = WoBushingProcessColumnKey::fromProcess($parent->process);
            if ($key !== 'machining') {
                throw ValidationException::withMessages(['parent' => ['Working steps only for bushing Machining']]);
            }
        }
    }

    protected function stepsQuery(TdrProcess|WoBushingBatch|WoBushingProcess $parent): \Illuminate\Database\Eloquent\Builder
    {
        if ($parent instanceof TdrProcess) {
            return MachiningWorkStep::query()->where('tdr_process_id', $parent->id);
        }
        if ($parent instanceof WoBushingBatch) {
            return MachiningWorkStep::query()->where('wo_bushing_batch_id', $parent->id);
        }

        return MachiningWorkStep::query()->where('wo_bushing_process_id', $parent->id);
    }

    /**
     * @return array<string, int|null>
     */
    protected function parentFkAttributes(TdrProcess|WoBushingBatch|WoBushingProcess $parent): array
    {
        if ($parent instanceof TdrProcess) {
            return [
                'tdr_process_id' => $parent->id,
                'wo_bushing_batch_id' => null,
                'wo_bushing_process_id' => null,
            ];
        }
        if ($parent instanceof WoBushingBatch) {
            return [
                'tdr_process_id' => null,
                'wo_bushing_batch_id' => $parent->id,
                'wo_bushing_process_id' => null,
            ];
        }

        return [
            'tdr_process_id' => null,
            'wo_bushing_batch_id' => null,
            'wo_bushing_process_id' => $parent->id,
        ];
    }

    protected function assertUserCanEditStep(?User $user, MachiningWorkStep $step): void
    {
        if (! $user) {
            abort(403);
        }
        if ((int) ($step->machinist_user_id ?? 0) === (int) $user->id) {
            return;
        }
        if ($user->can('feature.machining')) {
            return;
        }
        abort(403);
    }
}
