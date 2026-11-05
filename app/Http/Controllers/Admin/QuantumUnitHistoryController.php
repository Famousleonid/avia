<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Services\QuantumUnitHistory;
use Illuminate\Http\Request;

class QuantumUnitHistoryController extends Controller
{
    public function index(Request $request, QuantumUnitHistory $history)
    {
        abort_unless($request->user()?->roleIs(['Admin', 'Manager']), 403);
        $data = $request->validate(['group' => 'nullable|string|max:255', 'from' => 'nullable|date_format:d/M/Y|after_or_equal:01/Jan/1900|before_or_equal:today',
            'to' => array_filter(['nullable', 'date_format:d/M/Y', $request->filled('from') ? 'after_or_equal:from' : null, 'before_or_equal:today'])]);
        $report = $history->report($data['group'] ?? '', parse_project_date($data['from'] ?? null), parse_project_date($data['to'] ?? null));
        $numbers = $report['rows']->pluck('wo_number')->map(fn ($n) => preg_replace('/\D/', '', $n))->filter()->unique();
        $report['workorderIds'] = Workorder::whereIn('number', $numbers)->pluck('id', 'number');
        return view('admin.workorders.unit-history', $report);
    }
}
