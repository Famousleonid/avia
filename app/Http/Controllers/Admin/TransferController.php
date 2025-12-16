<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Tdr;
use App\Models\Workorder;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Display all transfers related to given workorder.
     *
     * Workorder can be either target (workorder_id) or source (workorder_source).
     *
     * @param  \App\Models\Workorder  $workorder
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Workorder $workorder)
    {
        $incomingTransfers = Transfer::with(['workorderSource', 'component', 'reasonCode'])
            ->where('workorder_id', $workorder->id)
            ->get();

        $outgoingTransfers = Transfer::with(['workorder', 'component', 'reasonCode'])
            ->where('workorder_source', $workorder->id)
            ->get();

        return view('admin.transfers.show', [
            'workorder' => $workorder,
            'incomingTransfers' => $incomingTransfers,
            'outgoingTransfers' => $outgoingTransfers,
        ]);
    }

    /**
     * Show printable Transfer Form for specific transfer.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function transferForm($id)
    {
        $transfer = Transfer::with([
            'workorder.unit.manuals.builder',
            'workorder.unit.manuals.plane',
            'workorder.instruction',
            'workorderSource.unit.manuals.builder',
            'workorderSource.unit.manuals.plane',
            'workorderSource.instruction',
            'component',
            'reasonCode'
        ])->findOrFail($id);

        // HTML view: resources/views/admin/transfers/transferForm.blade.php
        return view('admin.transfers.transferForm', compact('transfer'));
    }

    /**
     * Update component serial number for transfer (AJAX).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  Transfer ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSn(Request $request, $id)
    {
        $request->validate([
            'component_sn' => 'nullable|string|max:255',
        ]);

        $transfer = Transfer::findOrFail($id);
        $transfer->component_sn = $request->input('component_sn');
        $transfer->save();

        return response()->json([
            'success' => true,
            'component_sn' => $transfer->component_sn,
        ]);
    }

    /**
     * Create a transfer record from TDR
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  TDR ID
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $id)
    {
        $request->validate([
            'workorder_number' => 'required|string',
            'target_workorder_number' => 'required|string'
        ]);

        try {
            // Получаем TDR запись
            $tdr = Tdr::findOrFail($id);

            // Получаем текущий workorder (куда отправляется компонент)
            $currentWorkorder = Workorder::where('number', $request->workorder_number)->firstOrFail();

            // Получаем целевой workorder (откуда отправляется компонент)
            $sourceWorkorder = Workorder::where('number', $request->target_workorder_number)->firstOrFail();

            // Определяем component_id (используем order_component_id если есть, иначе component_id)
            $componentId = $tdr->order_component_id ?? $tdr->component_id;

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID not found in TDR'
                ], 400);
            }

            // Создаем запись в transfers
            $transfer = Transfer::create([
                'workorder_id'      => $currentWorkorder->id,   // Текущий workorder (куда отправляется компонент)
                'workorder_source'  => $sourceWorkorder->id,    // Workorder, откуда переводится компонент
                'component_id'      => $componentId,
                'component_sn'      => $tdr->serial_number ?? null,
                // Причину берём из codes_id TDR (если есть)
                'reason'            => $tdr->codes_id ?? null,
                'unit_on_po'        => null, // Заполнится позже при необходимости
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully',
                'transfer' => $transfer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete transfer record by TDR ID
     *
     * @param  int  $id  TDR ID
     * @return \Illuminate\Http\Response
     */
    public function deleteByTdr($id)
    {
        try {
            // Получаем TDR запись
            $tdr = Tdr::findOrFail($id);

            // Определяем component_id (используем order_component_id если есть, иначе component_id)
            $componentId = $tdr->order_component_id ?? $tdr->component_id;

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID not found in TDR'
                ], 400);
            }

            // Удаляем все записи transfers для этого TDR (workorder_id и component_id)
            $deleted = Transfer::where('workorder_id', $tdr->workorder_id)
                ->where('component_id', $componentId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transfer deleted successfully',
                'deleted_count' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}
