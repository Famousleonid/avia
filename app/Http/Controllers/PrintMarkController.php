<?php

namespace App\Http\Controllers;

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

        return view('print-mark.show', [
            'workorder' => $printMark->workorder_number,
            'formName' => $printMark->form_name,
            'requirementWarnings' => $this->printMarks->requirementWarnings($printMark),
            'printedBy' => $printMark->printed_by_name,
            'printedDate' => $printMark->printed_at?->format('d/M/Y') ?? '',
        ]);
    }
}
