<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Manual;
use App\Models\Plane;
use App\Services\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesReportService $reports)
    {
        abort_unless($request->user()?->roleIs(['Admin', 'Manager']), 403);

        $defaults = [
            'report_type' => 'customer',
            'customer_id' => null,
            'plane_id' => null,
            'manual_id' => null,
            'date_from' => CarbonImmutable::now()->startOfYear()->format('Y-m-d'),
            'date_to' => CarbonImmutable::now()->endOfYear()->format('Y-m-d'),
        ];

        $filters = array_merge($defaults, $request->validate([
            'report_type' => ['nullable', Rule::in(['customer', 'aircraft', 'component'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'plane_id' => ['nullable', 'integer', 'exists:planes,id'],
            'manual_id' => ['nullable', 'integer', 'exists:manuals,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'run' => ['nullable'],
        ]));

        $hasRun = $request->has('run');
        $report = $hasRun
            ? $reports->build($filters)
            : $reports->emptyReport((string) $filters['report_type']);
        if ($hasRun) {
            $filters['report_type'] = $report['report_type'];
        }

        return view('admin.sales_reports.index', [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'planes' => Plane::query()->orderBy('type')->get(['id', 'type']),
            'componentManuals' => Manual::query()
                ->whereHas('units')
                ->orderBy('title')
                ->orderBy('number')
                ->get(['id', 'number', 'title']),
            'filters' => $filters,
            'report' => $report,
            'hasRun' => $hasRun,
        ]);
    }
}
