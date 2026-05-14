<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TdrProcess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EcController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $showAll = $request->boolean('show_all');

        $ecRows = TdrProcess::query()
            ->with([
                'processName:id,name',
                'tdr:id,workorder_id,component_id,order_component_id,description',
                'tdr.workorder:id,number,unit_id',
                'tdr.workorder.unit:id,manual_id',
                'tdr.workorder.unit.manual:id,unit_name_training,planes_id',
                'tdr.workorder.unit.manual.plane:id,type',
                'tdr.component:id,name,part_number,assy_part_number',
                'tdr.orderComponent:id,name,part_number,assy_part_number',
            ])
            ->whereHas('processName', function ($processNameQuery): void {
                $processNameQuery->where('name', 'EC');
            })
            ->whereHas('tdr.workorder')
            ->when(! $showAll, function ($query): void {
                $query->where(function ($inWorkQuery): void {
                    $inWorkQuery
                        ->whereNull('date_start')
                        ->orWhereNull('date_finish');
                });
            })
            ->orderByRaw('date_finish IS NULL')
            ->orderByDesc('date_finish')
            ->orderByRaw('date_start IS NULL')
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->paginate(100)
            ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.ec.partials.rows', compact('ecRows'))->render(),
                'next_page_url' => $ecRows->nextPageUrl(),
            ]);
        }

        return view('admin.ec.index', compact('ecRows', 'showAll'));
    }
}
