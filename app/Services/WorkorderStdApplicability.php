<?php

namespace App\Services;

use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;

class WorkorderStdApplicability
{
    public function exclusionReason(Workorder $workorder, string $type, ?WorkorderStdProcess $process = null): ?string
    {
        if ($process?->ignore_row) {
            return app(WorkorderScopeService::class)->businessSelectionForWorkorder($workorder)['mode'] === WorkorderScopeService::MODE_PART_ASSEMBLY
                ? 'Ignored for Part / Assembly'
                : 'Ignored';
        }

        // Use the same quantity/alternative/KIT/ASSY filtering as the printed
        // sheet, freshly calculated without writing or trusting a stale snapshot.
        $rows = app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($workorder, $type, true);
        foreach ($rows as $row) {
            if (empty($row['group_crossed_out']) && (float) ($row['qty'] ?? 0) > 0) {
                return null;
            }
        }

        return 'Empty STD list';
    }

    public function appliesTo(WorkorderStdProcess|TdrProcess $process): bool
    {
        if ($process instanceof WorkorderStdProcess) {
            return $process->workorder !== null
                && $this->exclusionReason($process->workorder, $process->std_type, $process) === null;
        }

        $type = array_search($process->processName?->identityName(), WorkorderStdListProcessesService::NAME_BY_KEY, true);
        if ($type === false) {
            return true;
        }
        $workorder = $process->tdr?->workorder;
        if (! $workorder) {
            return false;
        }
        $current = $workorder->stdProcesses()->where('std_type', $type)->first();
        $current ??= new WorkorderStdProcess(['std_type' => $type, 'ignore_row' => $process->ignore_row]);

        return $this->exclusionReason($workorder, $type, $current) === null;
    }
}
