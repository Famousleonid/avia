<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Services\ManualLogCardOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualLogCardOrderController extends Controller
{
    private function authorizeOrder(): void
    {
        abort_unless(auth()->user()?->roleIs(['Admin','Manager']), 403);
    }
    public function index(Manual $manual, ManualLogCardOrder $order)
    {
        $this->authorizeOrder();
        return view('admin.manuals.log-card-order', ['manual'=>$manual, 'groups'=>$order->groups($manual), 'revision'=>$order->revision($manual)]);
    }
    public function preview(Request $request, Manual $manual, ManualLogCardOrder $order)
    {
        $this->authorizeOrder();
        $request->validate(['file'=>['required','file','mimes:xls,xlsx','max:20480']]);
        try { return response()->json($order->preview($manual, $request->file('file')->getPathname())); }
        catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) { report($e); throw ValidationException::withMessages(['file'=>'Could not read this workbook.']); }
    }
    public function update(Request $request, Manual $manual, ManualLogCardOrder $order)
    {
        $this->authorizeOrder();
        $data = $request->validate(['ids'=>['present','array'], 'ids.*'=>['integer','distinct'], 'revision'=>['required','string']]);
        return DB::transaction(function () use ($data, $manual, $order) {
            $locked = Manual::whereKey($manual->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($order->revision($locked), $data['revision']), 409, 'Order changed by another user. Reload this page.');
            $groups = $order->groups($locked);
            $ids = array_map('intval', $data['ids']);
            $expected = $groups->pluck('ids')->flatten()->all();
            $sorted = $ids; sort($sorted); sort($expected);
            if ($sorted !== $expected) throw ValidationException::withMessages(['ids'=>'Parts changed. Reload the page before saving.']);
            $positions = array_flip($ids);
            foreach ($groups as $group) {
                $indexes = array_map(fn ($id) => $positions[$id], $group['ids']);
                if (max($indexes) - min($indexes) + 1 !== count($indexes)) throw ValidationException::withMessages(['ids'=>'Keep alternative parts together.']);
            }
            $locked->log_card_order = $ids;
            $locked->save();
            return response()->json(['revision'=>$order->revision($locked)]);
        });
    }
}
