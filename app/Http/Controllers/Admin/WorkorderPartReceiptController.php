<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Workorder, Tdr};
use App\Services\WorkorderPartsList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkorderPartReceiptController extends Controller
{
    public function update(Request $request, Workorder $workorder, WorkorderPartsList $parts)
    {
        \App\Services\PartReceiptAudit::authorize();
        $data = $request->validate([
            'row_key' => ['required', 'string', 'max:100'],
            'field' => ['required', 'in:po_num,received,received_qty'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['field'] === 'received' && !empty($data['value'])) {
            $request->validate(['value' => ['date_format:Y-m-d']]);
        }
        $row = $parts->rows($workorder)->firstWhere('id', $data['row_key']);
        abort_unless($row && !$row->crossed_out, 422, 'This part is no longer available in KIT/PRL. Reload the list.');
        if ($data['field'] === 'po_num' && ($row->transfer_id || str_starts_with((string) ($data['value'] ?? ''), 'Transfer from WO'))) {
            abort(422, 'Use the Transfer dialog to create or cancel a transfer.');
        }
        $value = trim((string) ($data['value'] ?? '')) ?: null;
        if ($data['field'] === 'received_qty') {
            $request->validate(['value' => ['nullable', 'integer', 'min:0', 'max:'.(int) $row->qty]]);
            $value = ($data['value'] ?? '') === '' || $data['value'] === null ? null : (int) $data['value'];
        }
        DB::transaction(function () use ($row, $workorder, $data, $value) {
            // Serialize simultaneous PO/date writes without replacing the other field.
            Workorder::whereKey($workorder->id)->lockForUpdate()->firstOrFail();
            $fresh = app(WorkorderPartsList::class)->rows($workorder)->firstWhere('id', $row->id);
            abort_unless($fresh && !$fresh->crossed_out, 422);
            if ($data['field'] === 'received_qty') abort_if($value !== null && $value > (int) $fresh->qty, 422);
            \App\Services\PartReceiptAudit::record($workorder, $row->id,
                [$data['field'] => $fresh->{$data['field']}], [$data['field'] => $value]);
            if ($row->tdr_id && $data['field'] !== 'received_qty') {
                $tdr = Tdr::where('workorder_id', $workorder->id)->findOrFail($row->tdr_id);
                $tdr->{$data['field']} = $value;
                $tdr->save();
            }
            if (str_starts_with($row->id, 'tdr:') && $data['field'] !== 'received_qty') return;
            // Persist by printed-row identity as well: a KIT row can outlive its old TDR.
            $query = DB::table('workorder_part_receipts')->where('workorder_id', $workorder->id)->where('row_key', $row->id);
            if (!$query->exists()) {
                DB::table('workorder_part_receipts')->insert([
                    'workorder_id' => $workorder->id, 'row_key' => $row->id,
                    'po_num' => $row->po_num, 'received' => $row->received,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $query->update([$data['field'] => $value, 'updated_at' => now()]);
        });
        return response()->json(['success' => true]);
    }
}
