<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function tablePdf(Request $request)
    {

        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            abort(422, 'No ids provided');
        }

        $rows = Workorder::with('unit','customer','instruction')
            ->whereIn('id', $ids)
            ->orderBy('number')
            ->get();

        $date = Carbon::now()->format('d-M-Y');
        $filename = "avia_{$date}.pdf";

        $pdf = Pdf::loadView('pdf.table', compact('rows'))
            ->setPaper('a4', 'landscape');


        return $pdf->stream($filename);
    }
}
