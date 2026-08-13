<?php

namespace App\Http\Controllers;

use App\Models\Manual;
use App\Models\PrintMark;
use App\Services\PrintMarkService;
use Illuminate\Contracts\View\View;

class PrintMarkController extends Controller
{
    public function __construct(private readonly PrintMarkService $printMarks)
    {
    }

    public function show(string $token): View
    {
        $printMark = PrintMark::query()
            ->where('token', strtoupper($token))
            ->firstOrFail();

        $manualLibs = $printMark->workorder_id
            ? Manual::orderedLibValuesForManualIds(
                Manual::manualIdsForWorkorder((int) $printMark->workorder_id)
            )
            : [];

        return view('print-mark.show', [
            'workorder' => $printMark->workorder_number,
            'formName' => $printMark->form_name,
            'requirementWarnings' => $this->printMarks->requirementWarnings($printMark),
            'printedBy' => $printMark->printed_by_name,
            'printedDate' => $printMark->printed_at?->format('d/M/Y') ?? '',
            'manualLibs' => $manualLibs,
        ]);
    }
}
