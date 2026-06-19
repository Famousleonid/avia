<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Models\Tdr;
use App\Models\Workorder;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    /**
     * Build the data payload for the Transfers view/partial.
     *
     * @return array<string, mixed>
     */
    protected function transferShowData(Workorder $workorder): array
    {
        $incomingTransfers = Transfer::with(['workorderSource', 'component', 'reasonCode'])
            ->where('workorder_id', $workorder->id)
            ->get();

        $outgoingTransfers = Transfer::with(['workorder', 'component', 'reasonCode'])
            ->where('workorder_source', $workorder->id)
            ->get();

        $incomingGrouped = $incomingTransfers->groupBy('workorder_source');
        $incomingGroupsWithMultiple = $incomingGrouped->filter(function ($group) {
            return $group->count() > 1;
        });

        $hasOutgoingGroup = $outgoingTransfers->count() > 1;

        return [
            'workorder' => $workorder,
            'incomingTransfers' => $incomingTransfers,
            'outgoingTransfers' => $outgoingTransfers,
            'incomingGroupsWithMultiple' => $incomingGroupsWithMultiple,
            'hasOutgoingGroup' => $hasOutgoingGroup,
        ];
    }

    public function show(Workorder $workorder)
    {
        return view('admin.transfers.show', $this->transferShowData($workorder));
    }

    /**
     * HTML fragment for TDR show "Transfers" tab (AJAX).
     */
    public function partial(Workorder $workorder)
    {
        return view('admin.transfers.partial', $this->transferShowData($workorder));
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

        // Все трансферы ОТ этого WO (workorder_source). Бланк Form #011 вмещает
        // 5 позиций на лист — разбиваем на страницы по 5, печатаем несколько листов.
        $transfers = Transfer::with(['workorder', 'component', 'reasonCode'])
            ->where('workorder_source', $sourceWoId)
            ->orderBy('id')
            ->get();

        $pages = $transfers->chunk(5)->values();
        if ($pages->isEmpty()) {
            $pages = collect([collect()]); // хотя бы один пустой бланк
        }

        // HTML view: resources/views/admin/transfers/transfersForm.blade.php
        return view('admin.transfers.transfersForm', [
            'sourceWo' => $sourceWo,
            'transfers' => $transfers,
            'pages' => $pages,
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
     * Update "Unit Purchased on PO No." for a transfer (AJAX, inline edit).
     *
     * @param  int  $id  Transfer ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUnitOnPo(Request $request, $id)
    {
        $request->validate([
            'unit_on_po' => 'nullable|string|max:255',
        ]);

        $transfer = Transfer::findOrFail($id);
        $transfer->unit_on_po = $request->input('unit_on_po');
        $transfer->save();

        return response()->json([
            'success' => true,
            'unit_on_po' => $transfer->unit_on_po,
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
        // source_workorder_number — WO-источник (откуда берётся деталь).
        // Старое имя target_workorder_number оставлено для обратной совместимости.
        $request->validate([
            'workorder_number'          => 'required|string',
            'source_workorder_number'   => 'required_without:target_workorder_number|string',
            'target_workorder_number'   => 'sometimes|string',
        ]);

        $sourceNumber = $request->input('source_workorder_number', $request->input('target_workorder_number'));

        try {
            // TDR-строка в текущем WO (куда деталь приходит)
            $tdr = Tdr::findOrFail($id);

            // WO-приёмник (куда деталь приходит)
            $currentWorkorder = Workorder::where('number', $request->workorder_number)->first();
            if (!$currentWorkorder) {
                return response()->json([
                    'success' => false,
                    'message' => "Work Order W{$request->workorder_number} not found",
                ], 422);
            }

            // WO-источник (откуда деталь берётся)
            $sourceWorkorder = Workorder::where('number', $sourceNumber)->first();
            if (!$sourceWorkorder) {
                return response()->json([
                    'success' => false,
                    'message' => "Source Work Order W{$sourceNumber} not found",
                ], 422);
            }

            if ($sourceWorkorder->id === $currentWorkorder->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Source and receiving Work Orders must differ',
                ], 422);
            }

            // component_id (order_component_id приоритетнее)
            $componentId = $tdr->order_component_id ?? $tdr->component_id;

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID not found in TDR'
                ], 400);
            }

            // Идемпотентность: один TDR = максимум один перевод. Повторный create
            // (двойной клик, ретрай, две вкладки) возвращает существующий, не плодя дубли.
            $existing = Transfer::where('tdr_id', $tdr->id)->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transfer already exists',
                    'transfer' => $existing,
                    'new_tdr_id' => $existing->cloned_tdr_id,
                ]);
            }

            $result = DB::transaction(function () use ($tdr, $currentWorkorder, $sourceWorkorder, $componentId) {
                // Клон TDR в WO-источнике. replicate() копирует ВСЕ поля оригинала,
                // поэтому помечаем его как transfer_clone и сбрасываем поля, которые
                // не должны переезжать в свежую запись чужого WO.
                $newTdr = $tdr->replicate();
                $newTdr->workorder_id = $sourceWorkorder->id;
                $newTdr->tdr_type = Tdr::TYPE_TRANSFER_CLONE;
                $newTdr->po_num = null;
                $newTdr->received = null;
                $newTdr->last_synced_measurement_id = null;
                $newTdr->result_status = null;
                $newTdr->scrap_reason = null;
                $newTdr->replaced_by_tdr_id = null;
                $newTdr->save();

                // Запись перевода с ЯВНЫМИ ссылками на оба конца (origin + клон)
                $transfer = Transfer::create([
                    'tdr_id'            => $tdr->id,                 // origin-TDR (WO-приёмник)
                    'workorder_id'      => $currentWorkorder->id,   // WO-приёмник
                    'workorder_source'  => $sourceWorkorder->id,    // WO-источник
                    'component_id'      => $componentId,
                    'component_sn'      => $tdr->serial_number ?? null,
                    'cloned_tdr_id'     => $newTdr->id,
                    'reason'            => $tdr->codes_id ?? null,
                    'unit_on_po'        => null,
                ]);

                // Если исходный TDR — Missing, синхронизируем флаг part_missing и строку Unit Inspection
                $missingCode = Code::missing();
                if ($missingCode && $tdr->codes_id === $missingCode->id) {
                    if (!$sourceWorkorder->part_missing) {
                        $sourceWorkorder->part_missing = true;
                        $sourceWorkorder->save();
                    }

                    // Строка-плейсхолдер для блока Unit Inspection в WO-источнике
                    $missingCondition = \App\Models\Condition::partsMissing();
                    if ($missingCondition) {
                        $existingUnitInspection = Tdr::where('workorder_id', $sourceWorkorder->id)
                            ->where('conditions_id', $missingCondition->id)
                            ->whereNull('component_id')
                            ->whereNull('codes_id')
                            ->first();

                        if (!$existingUnitInspection) {
                            Tdr::create([
                                'workorder_id'      => $sourceWorkorder->id,
                                'tdr_type'          => Tdr::TYPE_UNIT_INSPECTION,
                                'component_id'      => null,
                                'conditions_id'     => $missingCondition->id,
                                'codes_id'          => null,
                                'use_tdr'           => true,
                                'use_process_forms' => false,
                            ]);
                        }
                    }
                }

                return ['transfer' => $transfer, 'new_tdr_id' => $newTdr->id];
            });

            return response()->json([
                'success' => true,
                'message' => 'Transfer created successfully',
                'transfer' => $result['transfer'],
                'new_tdr_id' => $result['new_tdr_id'],
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

            // Находим переводы по явной связи origin-TDR; для legacy-записей
            // (tdr_id ещё не проставлен) — fallback на (workorder_id, component_id).
            $transfers = Transfer::where('tdr_id', $tdr->id)->get();
            if ($transfers->isEmpty()) {
                $transfers = Transfer::whereNull('tdr_id')
                    ->where('workorder_id', $tdr->workorder_id)
                    ->where('component_id', $componentId)
                    ->get();
            }

            // Код Missing (для управления флагом part_missing в workorders)
            $missingCode = Code::missing();

            $result = DB::transaction(function () use ($transfers, $tdr, $missingCode) {
                $deletedTransfers = 0;
                $deletedClonedTdrs = 0;

                foreach ($transfers as $transfer) {
                    if ($transfer->workorder_source) {
                        // Явная связь, если есть; иначе — fallback на старую эвристику для legacy-записей
                        $cloned = $transfer->cloned_tdr_id
                            ? Tdr::find($transfer->cloned_tdr_id)
                            : $this->findLegacyClone($tdr, $transfer->workorder_source);

                        if ($cloned) {
                            $cloned->delete();
                            $deletedClonedTdrs++;

                            // Если это была запись с кодом Missing — возможно нужно снять part_missing у WO-источника
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

                                    // Убираем строку-плейсхолдер Unit Inspection, если Missing больше нет
                                    $missingCondition = \App\Models\Condition::partsMissing();
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

                return compact('deletedTransfers', 'deletedClonedTdrs');
            });

            return response()->json([
                'success' => true,
                'message' => 'Transfer deleted successfully',
                'deleted_transfers' => $result['deletedTransfers'],
                'deleted_cloned_tdrs' => $result['deletedClonedTdrs'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Legacy fallback: для переводов, созданных до колонки cloned_tdr_id,
     * клон ищется по совпадению полей оригинала (эвристика, может ошибаться).
     */
    private function findLegacyClone(Tdr $tdr, int $sourceWorkorderId): ?Tdr
    {
        return Tdr::where('workorder_id', $sourceWorkorderId)
            ->where(function ($q) use ($tdr) {
                $q->where('component_id', $tdr->component_id)
                    ->where('order_component_id', $tdr->order_component_id)
                    ->where('codes_id', $tdr->codes_id)
                    ->where('conditions_id', $tdr->conditions_id)
                    ->where('necessaries_id', $tdr->necessaries_id)
                    ->where('qty', $tdr->qty)
                    ->where('serial_number', $tdr->serial_number);
            })
            ->where('id', '!=', $tdr->id)
            ->orderByDesc('id')
            ->first();
    }
}
