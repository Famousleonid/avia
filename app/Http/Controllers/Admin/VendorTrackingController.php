<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TdrProcess;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorTrackingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $filters = [
            'vendor_id' => (int) $request->input('vendor_id', 0),
            'status' => $request->input('status', 'open'),
            'workorder' => trim((string) $request->input('workorder', '')),
            'part_number' => trim((string) $request->input('part_number', '')),
            'repair_order' => trim((string) $request->input('repair_order', '')),
        ];

        if (! in_array($filters['status'], ['open', 'returned', 'all'], true)) {
            $filters['status'] = 'open';
        }

        $query = TdrProcess::query()
            ->with([
                'vendor:id,name',
                'processName:id,name',
                'tdr:id,workorder_id,component_id,serial_number,assy_serial_number',
                'tdr.workorder:id,number,customer_id',
                'tdr.workorder.customer:id,name',
                'tdr.component:id,part_number,ipl_num,name',
            ])
            ->whereNotNull('vendor_id')
            ->whereHas('tdr.workorder');

        if ($filters['vendor_id'] > 0) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if ($filters['status'] === 'open') {
            $query->whereNotNull('date_start')->whereNull('date_finish');
        } elseif ($filters['status'] === 'returned') {
            $query->whereNotNull('date_finish');
        }

        if ($filters['workorder'] !== '') {
            $query->whereHas('tdr.workorder', function ($q) use ($filters): void {
                $q->where('number', 'like', '%' . $filters['workorder'] . '%');
            });
        }

        if ($filters['part_number'] !== '') {
            $query->whereHas('tdr.component', function ($q) use ($filters): void {
                $q->where('part_number', 'like', '%' . $filters['part_number'] . '%');
            });
        }

        if ($filters['repair_order'] !== '') {
            $query->where('repair_order', 'like', '%' . $filters['repair_order'] . '%');
        }

        $rows = $query
            ->orderByRaw('date_finish IS NOT NULL')
            ->orderBy('date_start')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.vendor_tracking.index', compact('rows', 'vendors', 'filters'));
    }
}
