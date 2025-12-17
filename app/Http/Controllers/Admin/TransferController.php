<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Tdr;
use App\Models\Workorder;
use App\Models\Code;
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

        // Группируем входящие трансферы по workorder_source для определения, есть ли несколько от одного WO
        $incomingGrouped = $incomingTransfers->groupBy('workorder_source');
        $incomingGroupsWithMultiple = $incomingGrouped->filter(function ($group) {
            return $group->count() > 1;
        });

        // Для исходящих: если их несколько от текущего WO, то это одна группа
        $hasOutgoingGroup = $outgoingTransfers->count() > 1;

        return view('admin.transfers.show', [
            'workorder' => $workorder,
            'incomingTransfers' => $incomingTransfers,
            'outgoingTransfers' => $outgoingTransfers,
            'incomingGroupsWithMultiple' => $incomingGroupsWithMultiple,
            'hasOutgoingGroup' => $hasOutgoingGroup,
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
     * Show printable Transfers Form for multiple transfers from one source workorder.
     * Groups up to 5 transfers from the same workorder_source on one form.
     *
     * @param int $sourceWoId Source Workorder ID (workorder_source)
     * @return \Illuminate\Contracts\View\View
     */
    public function transfersForm($sourceWoId)
    {
        // Исходный WO, откуда переводили (End Assembly Details)
        $sourceWo = Workorder::with([
            'unit.manuals.builder',
            'unit.manuals.plane',
            'instruction',
        ])->findOrFail($sourceWoId);

        // Все трансферы ОТ этого WO (workorder_source), максимум 5
        $transfers = Transfer::with(['workorder', 'component', 'reasonCode'])
            ->where('workorder_source', $sourceWoId)
            ->orderBy('id')
            ->take(5)
            ->get();

        // HTML view: resources/views/admin/transfers/transfersForm.blade.php
        return view('admin.transfers.transfersForm', [
            'sourceWo' => $sourceWo,
            'transfers' => $transfers,
        ]);
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
            // Получаем TDR запись (строка в текущем WO, куда приходит компонент)
            $tdr = Tdr::findOrFail($id);

            // Получаем текущий workorder (куда отправляется компонент)
            $currentWorkorder = Workorder::where('number', $request->workorder_number)->firstOrFail();

            // Получаем workorder-источник (откуда отправляется компонент)
            $sourceWorkorder = Workorder::where('number', $request->target_workorder_number)->firstOrFail();

            // Определяем component_id (используем order_component_id если есть, иначе component_id)
            $componentId = $tdr->order_component_id ?? $tdr->component_id;

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID not found in TDR'
                ], 400);
            }

            // Создаем запись в transfers (связь между WO-приёмником и WO-источником)
            $transfer = Transfer::create([
                'workorder_id'      => $currentWorkorder->id,   // Текущий workorder (куда отправляется компонент)
                'workorder_source'  => $sourceWorkorder->id,    // Workorder, откуда переводится компонент
                'component_id'      => $componentId,
                'component_sn'      => $tdr->serial_number ?? null,
                // Причину берём из codes_id TDR (если есть)
                'reason'            => $tdr->codes_id ?? null,
                'unit_on_po'        => null, // Заполнится позже при необходимости
            ]);

            // Дополнительно: создаём запись в tdrs для WO-источника (target WO из ввода пользователя)
            // Клонируем все поля из текущего TDR, кроме workorder_id (он будет указывать на WO-источник)
            $newTdr = $tdr->replicate();
            $newTdr->workorder_id = $sourceWorkorder->id;
            $newTdr->save();

            // Если исходный TDR имеет код Missing, убедимся, что для WO-источника корректно выставлен флаг part_missing
            $missingCode = Code::where('name', 'Missing')->first();
            if ($missingCode && $tdr->codes_id === $missingCode->id) {
                $remainingMissingForSource = Tdr::where('workorder_id', $sourceWorkorder->id)
                    ->where('codes_id', $missingCode->id)
                    ->count();

                if ($remainingMissingForSource > 0) {
                    // Есть хотя бы одна запись Missing в WO-источнике — флаг part_missing должен быть включён
                    if (!$sourceWorkorder->part_missing) {
                        $sourceWorkorder->part_missing = true;
                        $sourceWorkorder->save();
                    }
                }

                // Проверяем, есть ли в WO-источнике запись с condition_id = 1 и component_id = null
                // Если нет - создаём такую запись для отображения в блоке Unit Inspection
                $missingCondition = \App\Models\Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
                if ($missingCondition) {
                    $existingUnitInspection = Tdr::where('workorder_id', $sourceWorkorder->id)
                        ->where('conditions_id', $missingCondition->id)
                        ->whereNull('component_id')
                        ->whereNull('codes_id')
                        ->first();

                    if (!$existingUnitInspection) {
                        // Создаём запись для блока Unit Inspection
                        Tdr::create([
                            'workorder_id' => $sourceWorkorder->id,
                            'component_id' => null,
                            'conditions_id' => $missingCondition->id,
                            'codes_id' => null,
                            'use_tdr' => true,
                            'use_process_forms' => false,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully',
                'transfer' => $transfer,
                'new_tdr_id' => $newTdr->id,
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
            // Получаем TDR запись (в текущем WO)
            $tdr = Tdr::findOrFail($id);

            // Определяем component_id (используем order_component_id если есть, иначе component_id)
            $componentId = $tdr->order_component_id ?? $tdr->component_id;

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID not found in TDR'
                ], 400);
            }

            // Находим все transfers, связанные с этим TDR (по текущему workorder_id и component_id)
            $transfers = Transfer::where('workorder_id', $tdr->workorder_id)
                ->where('component_id', $componentId)
                ->get();

            $deletedTransfers = 0;
            $deletedClonedTdrs = 0;

            // Код Missing (для управления флагом part_missing в workorders)
            $missingCode = Code::where('name', 'Missing')->first();

            foreach ($transfers as $transfer) {
                // Для каждого transfer пытаемся удалить "клонированный" TDR в workorder_source
                if ($transfer->workorder_source) {
                    $cloned = Tdr::where('workorder_id', $transfer->workorder_source)
                        ->where(function ($q) use ($tdr) {
                            // Пытаемся найти запись, максимально похожую на исходный TDR
                            $q->where('component_id', $tdr->component_id)
                                ->where('order_component_id', $tdr->order_component_id)
                                ->where('codes_id', $tdr->codes_id)
                                ->where('conditions_id', $tdr->conditions_id)
                                ->where('necessaries_id', $tdr->necessaries_id)
                                ->where('qty', $tdr->qty)
                                ->where('serial_number', $tdr->serial_number);
                        })
                        ->where('id', '!=', $tdr->id)
                        ->orderByDesc('id') // берём самую "свежую" как вероятный клон
                        ->first();

                    if ($cloned) {
                        $cloned->delete();
                        $deletedClonedTdrs++;

                        // Если это была запись с кодом Missing, возможно нужно обновить part_missing для WO-источника
                        if ($missingCode && $tdr->codes_id === $missingCode->id) {
                            $remainingMissingForSource = Tdr::where('workorder_id', $transfer->workorder_source)
                                ->where('codes_id', $missingCode->id)
                                ->count();

                            if ($remainingMissingForSource === 0) {
                                $sourceWo = Workorder::find($transfer->workorder_source);
                                if ($sourceWo && $sourceWo->part_missing) {
                                    $sourceWo->part_missing = false;
                                    $sourceWo->save();
                                }

                                // Если не осталось записей с codes_id = 7, удаляем запись с condition_id = 1, component_id = null, codes_id = null
                                $missingCondition = \App\Models\Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
                                if ($missingCondition) {
                                    $unitInspectionRecord = Tdr::where('workorder_id', $transfer->workorder_source)
                                        ->where('conditions_id', $missingCondition->id)
                                        ->whereNull('component_id')
                                        ->whereNull('codes_id')
                                        ->first();

                                    if ($unitInspectionRecord) {
                                        $unitInspectionRecord->delete();
                                    }
                                }
                            }
                        }
                    }
                }

                $transfer->delete();
                $deletedTransfers++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer deleted successfully',
                'deleted_transfers' => $deletedTransfers,
                'deleted_cloned_tdrs' => $deletedClonedTdrs,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}
