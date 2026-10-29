<?php

namespace App\Services;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\Workorder;

/** Disposable context for one read of a WO's form counters. Never a singleton. */
class WorkorderFormCalculation
{
    private Workorder $workorder;
    private TdrPrintFormController $print;
    private WorkorderStdProcessItemsService $std;
    public PartVariantGrouping $grouping;
    private ?array $kitQuantities = null;

    public function __construct(Workorder $workorder)
    {
        $this->workorder = $workorder;
        $coverage = app(PartGroupCoverageResolver::class)->forSnapshot($workorder);
        $this->grouping = app(PartVariantGrouping::class)->forSnapshot();
        $this->print = app(TdrPrintFormController::class)->forSnapshot($workorder, $coverage, $this->grouping);
        $this->std = app(WorkorderStdProcessItemsService::class)->forSnapshot(
            $workorder, $coverage, $this->grouping, fn () => $this->kitQuantities()
        );
    }

    private function kitQuantities(): array
    {
        return $this->kitQuantities ??= app(WorkorderPartsList::class)->kitReplacementQuantities(
            $this->workorder, $this->print->kitRows($this->workorder)
        );
    }

    public function stdRows(string $scope): array
    {
        return $this->std->snapshotRowsForWorkorder($this->workorder, $scope);
    }

    public function printedPrlRows()
    {
        return $this->print->prlPrintRows($this->workorder);
    }
}
