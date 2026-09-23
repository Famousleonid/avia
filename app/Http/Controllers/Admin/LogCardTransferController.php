<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{LogCard, Tdr, Transfer, Workorder};
use App\Services\LogCardTransferSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogCardTransferController extends Controller
{
    public function sources(Request $request, Workorder $workorder, LogCardTransferSource $sources)
    {
        $data = $request->validate(['row_key' => 'required|string|max:100', 'source_number' => 'required|string|max:20']);
        $source = $this->source($data['source_number']);
        $part = $sources->target($workorder, $data['row_key']);
        return response()->json(['success' => true, 'source_id' => $source->id, 'source_number' => $source->number,
            'parts' => $sources->choices($workorder, $part, $source)]);
    }

    public function store(Request $request, Workorder $workorder, LogCardTransferSource $sources)
    {
        \App\Services\PartReceiptAudit::authorize();
        $data = $request->validate(['row_key' => 'required|string|max:100', 'source_number' => 'required|string|max:20', 'source_token' => 'required|string|size:64']);
        $source = $this->source($data['source_number']);
        $transfer = DB::transaction(function () use ($workorder, $source, $sources, $data) {
            Workorder::whereIn('id', [$workorder->id, $source->id])->orderBy('id')->lockForUpdate()->get();
            LogCard::where('workorder_id', $source->id)->lockForUpdate()->get();
            $part = $sources->target($workorder, $data['row_key']);
            $existing = Transfer::where('workorder_id', $workorder->id)->where(function ($q) use ($part) {
                $q->where('receipt_row_key', $part->id);
                if ($part->tdr_id) $q->orWhere('tdr_id', $part->tdr_id);
            })->first();
            if ($existing) {
                if ((int) $existing->workorder_source === (int) $source->id && $existing->source_log_token === $data['source_token']) return $existing;
                throw ValidationException::withMessages(['source' => 'Cancel the existing transfer before choosing another source.']);
            }
            $choice = $sources->choices($workorder, $part, $source)->firstWhere('token', $data['source_token']);
            if (!$choice || !$choice['can_transfer']) throw ValidationException::withMessages(['source' => 'The Log Card part changed or is no longer available. Load the source again.']);
            $transfer = Transfer::create([
                'workorder_id' => $workorder->id, 'workorder_source' => $source->id,
                'tdr_id' => $part->tdr_id, 'component_id' => $choice['component_id'],
                'component_sn' => $choice['serial_number'], 'part_number' => $choice['part_number'], 'ipl_num' => $choice['ipl'],
                'receipt_row_key' => $part->id, 'source_log_card_id' => $choice['log_card_id'],
                'source_log_row_key' => $choice['source_key'], 'source_log_token' => $choice['token'],
                'qty' => $part->qty, 'reason' => $choice['reason_id'] ?: null,
            ]);
            $po = 'Transfer from WO '.$source->number;
            \App\Services\PartReceiptAudit::record($workorder, $part->id, ['po_num' => $part->po_num], ['po_num' => $po]);
            if ($part->tdr_id) Tdr::where('workorder_id', $workorder->id)->whereKey($part->tdr_id)->update(['po_num' => $po]);
            DB::table('workorder_part_receipts')->updateOrInsert(['workorder_id' => $workorder->id, 'row_key' => $part->id],
                ['po_num' => $po, 'received' => $part->received, 'updated_at' => now(), 'created_at' => now()]);
            return $transfer;
        });
        return response()->json(['success' => true, 'transfer_id' => $transfer->id,
            'po_num' => 'Transfer from WO '.$source->number, 'serial_number' => $transfer->component_sn,
            'form_url' => route('transfers.transferForm', $transfer)]);
    }

    public function destroy(Request $request, Workorder $workorder)
    {
        \App\Services\PartReceiptAudit::authorize();
        $data = $request->validate(['row_key' => 'required|string|max:100']);
        $transfer = Transfer::where('workorder_id', $workorder->id)->where('receipt_row_key', $data['row_key'])->first();
        if (!$transfer) {
            $part = app(LogCardTransferSource::class)->target($workorder, $data['row_key']);
            if ($part->tdr_id) $transfer = Transfer::where('workorder_id', $workorder->id)->where('tdr_id', $part->tdr_id)->first();
        }
        if (!$transfer) throw ValidationException::withMessages(['transfer' => 'No Log Card transfer found for this row.']);
        DB::transaction(function () use ($workorder, $transfer, $data) {
            Workorder::whereIn('id', [$workorder->id, $transfer->workorder_source])->orderBy('id')->lockForUpdate()->get();
            $current = Transfer::whereKey($transfer->id)->lockForUpdate()->first();
            if (!$current) return;
            $part = app(LogCardTransferSource::class)->target($workorder, $data['row_key']);
            \App\Services\PartReceiptAudit::record($workorder, $data['row_key'],
                ['po_num' => $part->po_num, 'received' => $part->received], ['po_num' => null, 'received' => null]);
            if ($current->tdr_id) Tdr::where('workorder_id', $workorder->id)->whereKey($current->tdr_id)->update(['po_num' => null, 'received' => null]);
            DB::table('workorder_part_receipts')->where('workorder_id', $workorder->id)->where('row_key', $data['row_key'])->update(['po_num' => null, 'received' => null, 'updated_at' => now()]);
            if (!$current->source_log_row_key && $current->tdr_id) {
                $response = app(TransferController::class)->deleteByTdr($current->tdr_id);
                if (!$response->getData()->success) throw ValidationException::withMessages(['transfer' => 'Legacy transfer could not be cancelled.']);
            } else {
                $current->delete();
            }
        });
        return response()->json(['success' => true]);
    }

    private function source(string $number): Workorder
    {
        $number = preg_replace('/^w(?:o)?\s*/i', '', trim($number));
        $source = Workorder::where('number', $number)->first();
        if (!$source) throw ValidationException::withMessages(['source' => 'Source workorder not found.']);
        return $source;
    }
}
