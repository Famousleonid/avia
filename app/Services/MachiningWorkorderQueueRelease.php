<?php

namespace App\Services;

use App\Models\MachiningWorkStep;
use App\Models\ProcessName;
use App\Models\TdrProcess;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;

final class MachiningWorkorderQueueRelease
{
    /**
     * Все отображаемые на экране Machining-линии WO закрыты (есть финиш у процесса / родителя шагов).
     */
    public function machiningFullyClosed(Workorder $workorder): bool
    {
        $workorder->loadMissing([
            'tdrs.component',
            'tdrs.tdrProcesses.processName',
            'woBushingProcesses.line',
            'woBushingProcesses.process.process_name',
            'woBushingProcesses.batch',
        ]);

        $hasAny = false;

        foreach ($workorder->tdrs as $tdr) {
            foreach ($tdr->tdrProcesses as $tp) {
                if ($tp->ignore_row ?? false) {
                    continue;
                }
                if (! ProcessName::isMachiningMachiningEcMergeMember($tp->processName)) {
                    continue;
                }
                if (trim((string) ($tdr->component?->part_number ?? '')) === '') {
                    continue;
                }
                $hasAny = true;
                if (! $this->machiningParentHasFinish($tp)) {
                    return false;
                }
            }
        }

        $bushingRows = MachiningBushingRowsBuilder::forWorkorder($workorder);
        foreach ($bushingRows as $row) {
            $parent = $row->bushing_batch ?? $row->bushing_process ?? null;
            if (! $parent instanceof WoBushingBatch && ! $parent instanceof WoBushingProcess) {
                continue;
            }
            $hasAny = true;
            if (! $this->machiningParentHasFinish($parent)) {
                return false;
            }
        }

        return $hasAny;
    }

    /**
     * @param  bool  $alreadyVerifiedFullyClosed  true — вызывающий уже проверил {@see machiningFullyClosed()} (избегаем двойной работы на списке Machining).
     */
    public function releaseIfFullyClosed(Workorder $workorder, bool $alreadyVerifiedFullyClosed = false): void
    {
        if (! $alreadyVerifiedFullyClosed && ! $this->machiningFullyClosed($workorder)) {
            return;
        }

        $queuedWo = Workorder::query()
            ->select(['id', 'machining_queue_order'])
            ->whereKey($workorder->id)
            ->first();

        if ($queuedWo === null || $queuedWo->machining_queue_order === null) {
            return;
        }

        $oldPosition = (int) $queuedWo->machining_queue_order;

        Workorder::query()
            ->whereKey($workorder->id)
            ->update(['machining_queue_order' => null]);

        Workorder::query()
            ->whereNotNull('approve_at')
            ->whereNull('done_at')
            ->where('is_draft', 0)
            ->whereNotNull('machining_queue_order')
            ->where('machining_queue_order', '>', $oldPosition)
            ->decrement('machining_queue_order');
    }

    private function machiningParentHasFinish(TdrProcess|WoBushingBatch|WoBushingProcess $parent): bool
    {
        $n = (int) ($parent->working_steps_count ?? 0);
        if ($n >= 1) {
            if ($this->machiningDateIsSet($parent->date_finish)) {
                return true;
            }
            $lastFinish = $this->lastStepFinish($parent, $n);

            return $this->machiningDateIsSet($lastFinish);
        }

        return $this->machiningDateIsSet($parent->date_finish);
    }

    private function lastStepFinish(TdrProcess|WoBushingBatch|WoBushingProcess $parent, int $n): mixed
    {
        $parent->loadMissing('machiningWorkSteps');
        $last = $parent->machiningWorkSteps->firstWhere('step_index', $n);
        if ($last !== null) {
            return $last->date_finish;
        }

        $q = MachiningWorkStep::query();
        if ($parent instanceof TdrProcess) {
            $q->where('tdr_process_id', $parent->id);
        } elseif ($parent instanceof WoBushingBatch) {
            $q->where('wo_bushing_batch_id', $parent->id);
        } else {
            $q->where('wo_bushing_process_id', $parent->id);
        }

        return $q->where('step_index', $n)->value('date_finish');
    }

    private function machiningDateIsSet(mixed $d): bool
    {
        if ($d === null) {
            return false;
        }
        if ($d instanceof \DateTimeInterface) {
            return true;
        }

        return trim((string) $d) !== '';
    }
}
