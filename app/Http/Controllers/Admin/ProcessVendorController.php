<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ProcessVendorCheck, ProcessVendorRepairOrder, Vendor, Workorder};
use App\Services\ProcessVendorQueue;
use App\Services\ProcessVendorCache;
use App\Services\ProcessVendorPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProcessVendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:feature.process_vendor');
    }

    public function index(ProcessVendorCache $queue)
    {
        ['rows' => $rows, 'cache' => $cache] = $queue->read();
        $preferences = app(ProcessVendorPreferences::class)->forUser(auth()->id());
        $rows = app(ProcessVendorPreferences::class)->apply($rows, $preferences, true);
        $vendors = Vendor::orderBy('name')->get(array_merge(['id', 'name'], Vendor::CONTACT_FIELDS));
        $filters = \App\Models\UserUiSetting::where('user_id', auth()->id())->where('scope', 'process-vendor.index')
            ->where('key', 'filters')->first()?->value;
        $filters = is_array($filters) ? $filters : [];
        return view('admin.process_vendor.index', compact('rows', 'vendors', 'cache', 'preferences', 'filters'));
    }

    public function orders(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['prepared', 'cancelled'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = ProcessVendorRepairOrder::query();
        $search = trim($data['q'] ?? '');
        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';
            $query->where(function ($query) use ($like) {
                $query->where('number', 'like', $like)->orWhere('document->vendor_name', 'like', $like)
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(document, '$.items[*].wo')) LIKE ?", [$like]);
            });
        }
        if (($data['status'] ?? '') === 'cancelled') $query->whereNotNull('cancelled_at');
        if (($data['status'] ?? '') === 'prepared') $query->whereNull('cancelled_at');
        $orders = $query->orderByDesc('id')->paginate(20);
        return response()->json(['orders' => $orders->getCollection()->map(fn ($order) => [
            'number' => $order->number, 'vendor' => $order->document['vendor_name'] ?? '',
            'workorders' => collect($order->document['items'] ?? [])->pluck('wo')->unique()->values(),
            'date' => format_project_date($order->created_at), 'status' => $order->cancelled_at ? 'Cancelled' : 'Prepared',
            'url' => route('process-vendor.orders.show', $order),
        ]), 'page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()])
            ->header('Cache-Control', 'no-store');
    }

    public function queue(ProcessVendorCache $queue)
    {
        ['rows' => $rows, 'cache' => $cache] = $queue->read();
        $preferences = app(ProcessVendorPreferences::class)->forUser(auth()->id());
        $searchRows = app(ProcessVendorPreferences::class)->apply($rows, $preferences, true);
        $rows = app(ProcessVendorPreferences::class)->apply($rows, $preferences);
        return response()->json(['rows' => $rows, 'search_rows' => $searchRows, 'html' => view('admin.process_vendor._queue', ['rows' => $searchRows])->render(),
            'cache' => $cache, 'preferences' => $preferences])
            ->header('Cache-Control', 'no-store');
    }

    public function refresh(ProcessVendorCache $queue)
    {
        // Warm-up can take longer; never publish a partially rebuilt queue.
        if (function_exists('set_time_limit')) set_time_limit(300);
        try {
            $result = $queue->refresh();
        } catch (\Throwable $error) {
            report($error);
            return response()->json(['message' => 'Unable to refresh the queue. The previous saved queue is retained.'], 503);
        }
        $response = $this->queue($queue);
        $response->setData($response->getData(true) + ['refresh' => $result]);
        return $response->setStatusCode($result['busy'] ? 202 : 200);
    }

    public function check(Request $request)
    {
        $data = $request->validate($this->selectionRules() + ['checked' => ['required', 'boolean']]);
        DB::transaction(function () use ($data) {
            $rows = $this->freshSelection($data['items'], $data['selection_process'] ?? null, (bool) ($data['search_all'] ?? false));
            foreach ($rows as $row) {
                $check = ProcessVendorCheck::firstOrNew(['item_key' => $row['key']]);
                if ($check->repair_order_id || $row['repair_order_id']) throw ValidationException::withMessages(['items' => 'A selected item is already in a Repair Order. Cancel that draft first.']);
                $check->fill(['workorder_id' => $row['workorder_id'], 'fingerprint' => $row['fingerprint'],
                    'checked_at' => $data['checked'] ? now() : null, 'checked_by' => $data['checked'] ? auth()->id() : null])->save();
            }
            activity()->causedBy(auth()->user())->withProperties(['items' => $rows->pluck('key'), 'checked' => $data['checked']])->log('process_vendor.shipping_check');
        });
        return response()->json(['ok' => true]);
    }

    public function preferences(Request $request, ProcessVendorCache $queue)
    {
        $preferences = app(ProcessVendorPreferences::class)->forUser(auth()->id());
        $options = $preferences['options']->pluck('name')->all();
        $data = $request->validate(['processes' => ['present', 'array', 'max:200'],
            'processes.*' => ['string', 'distinct', Rule::in($options)],
            'groups' => ['sometimes', 'array', 'max:100'], 'groups.*' => ['array:name,members,enabled'],
            'groups.*.enabled' => ['sometimes', 'boolean'],
            'groups.*.name' => ['required', 'string', 'max:60'],
            'groups.*.members' => ['required', 'array', 'min:1', 'max:200'],
            'groups.*.members.*' => ['required', 'string', Rule::in($options)]]);
        if (isset($data['groups'])) $data['groups'] = app(ProcessVendorPreferences::class)->validateGroups($data['groups'], $options);
        else $data['groups'] = $preferences['groups']->all();
        DB::transaction(function () use ($data) {
            foreach (['visible_processes' => $data['processes'], 'process_groups' => $data['groups'], 'process_catalog_version' => 2] as $key => $value) {
                if ($value === null) continue;
                \App\Models\UserUiSetting::updateOrCreate(
                    ['user_id' => auth()->id(), 'scope' => 'process-vendor.index', 'key' => $key], ['value' => is_array($value) ? array_values($value) : $value]
                );
            }
        });
        return $this->queue($queue);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->selectionRules() + [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'vendor_address' => ['nullable', 'string', 'max:1500'],
            'vendor_contact_name' => Vendor::contactRules()['contact_name'],
            'vendor_phone' => Vendor::contactRules()['phone'],
            'vendor_email' => Vendor::contactRules()['email'],
            'vendor_number' => Vendor::contactRules()['vendor_number'],
            'ship_via' => ['nullable', 'string', 'max:100'],
            'terms' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:4000'],
        ]);
        $order = DB::transaction(function () use ($data) {
            $rows = $this->freshSelection($data['items'], $data['selection_process'] ?? null, (bool) ($data['search_all'] ?? false));
            $checks = ProcessVendorCheck::whereIn('item_key', $rows->pluck('key'))->lockForUpdate()->get()->keyBy('item_key');
            foreach ($rows as $row) {
                $check = $checks->get($row['key']);
                if (!$check?->checked_at || !hash_equals($check->fingerprint, $row['fingerprint']) || $check->repair_order_id || $row['repair_order_id']) {
                    throw ValidationException::withMessages(['items' => 'Check every selected item at Shipping first. An item may have changed or already belongs to a Repair Order. Refresh the queue.']);
                }
            }
            $vendor = Vendor::findOrFail($data['vendor_id']);
            // Store the reviewed values (including explicit blanks), never live vendor references.
            $details = [];
            foreach (Vendor::CONTACT_FIELDS as $field) {
                $key = in_array($field, ['ship_via', 'terms', 'vendor_number'], true) ? $field : 'vendor_'.$field;
                $details[$key] = (string) (array_key_exists($key, $data) ? $data[$key] : $vendor->$field);
            }
            $order = ProcessVendorRepairOrder::create([
                'vendor_id' => $vendor->id, 'created_by' => auth()->id(),
                'document' => ['vendor_name' => $vendor->name, ...$details,
                    'prepared_by' => auth()->user()->name, 'date' => today()->toDateString(),
                    'instructions' => $data['instructions'] ?? '',
                    'items' => $rows->map(fn ($row) => collect($row)->except(['checked', 'checked_by', 'repair_order_id', 'changed'])->all())->all()],
            ]);
            $order->update(['number' => 'AV-RO-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
            ProcessVendorCheck::whereIn('id', $checks->pluck('id'))->update(['repair_order_id' => $order->id]);
            activity()->performedOn($order)->causedBy(auth()->user())->withProperties(['items' => $rows->pluck('key')])->log('process_vendor.repair_order_created');
            return $order;
        });
        return response()->json(['ok' => true, 'url' => route('process-vendor.orders.show', $order)]);
    }

    public function show(ProcessVendorRepairOrder $order)
    {
        return view('admin.process_vendor.repair_order', compact('order'));
    }

    public function cancel(ProcessVendorRepairOrder $order)
    {
        DB::transaction(function () use ($order) {
            $order = ProcessVendorRepairOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->cancelled_at) return;
            $order->update(['cancelled_at' => now()]);
            ProcessVendorCheck::where('repair_order_id', $order->id)->update(['repair_order_id' => null]);
            activity()->performedOn($order)->causedBy(auth()->user())->log('process_vendor.repair_order_cancelled');
        });
        return response()->json(['ok' => true]);
    }

    private function selectionRules(): array
    {
        return ['search_all' => ['sometimes', 'boolean'], 'selection_process' => ['nullable', 'string', 'max:255'], 'items' => ['required', 'array', 'min:1', 'max:500'], 'items.*.key' => ['required', 'string', 'max:160', 'distinct'],
            'items.*.workorder_id' => ['required', 'integer'], 'items.*.fingerprint' => ['required', 'string', 'size:64']];
    }

    private function freshSelection(array $items, ?string $selectionProcess = null, bool $searchAll = false)
    {
        $ids = collect($items)->pluck('workorder_id')->unique()->sort()->values()->all();
        // Serialize Shipping confirmations and RO creation for the same workorders.
        Workorder::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        $preferences = app(ProcessVendorPreferences::class)->forUser(auth()->id());
        $current = app(ProcessVendorPreferences::class)->apply(app(ProcessVendorQueue::class)->rows($ids), $preferences, $searchAll)->keyBy('key');
        $selected = collect($items)->map(function ($item) use ($current, $selectionProcess) {
            $row = $current->get($item['key']);
            if (!$row || ($selectionProcess !== null && $row['process_name'] !== $selectionProcess) || $row['workorder_id'] != $item['workorder_id'] || !hash_equals($row['fingerprint'], $item['fingerprint'])) {
                throw ValidationException::withMessages(['items' => 'The queue changed. Refresh and check the affected parts again. No changes were saved.']);
            }
            return $row;
        });
        foreach ($selected->groupBy(fn ($row) => $row['workorder_id'].'|'.$row['process_group']) as $group) {
            $first = $group->first();
            $expected = $current->where('workorder_id', $first['workorder_id'])->where('process_group', $first['process_group'])
                ->when($selectionProcess !== null, fn ($rows) => $rows->where('process_name', $selectionProcess))
                ->reject(fn ($row) => $row['repair_order_id'])->pluck('key')->sort()->values()->all();
            if ($expected !== $group->pluck('key')->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => 'The workorder parts changed. Refresh and select the complete workorder again.']);
            }
        }
        return $selected;
    }
}
