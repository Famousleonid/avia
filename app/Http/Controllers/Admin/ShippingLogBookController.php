<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Services\WorkorderNotifyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ShippingLogBookController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAccess($request);

        $payload = $this->buildIndexPayload($request);

        if ($request->boolean('fragment') || $request->expectsJson()) {
            return response()->json([
                'html' => view('admin.shipping-log-book-rows', [
                    'workorders' => $payload['workorders'],
                ])->render(),
                'next_page' => $payload['nextPage'],
                'has_more' => $payload['hasMore'],
                'loaded_count' => count($payload['workorders']),
                'total_count' => $payload['totalCount'],
                'sort' => $payload['sort'],
                'direction' => $payload['direction'],
            ]);
        }

        return view('admin.shipping-log-book', [
            'workorders' => $payload['workorders'],
            'q' => $payload['q'],
            'nextPage' => $payload['nextPage'],
            'hasMore' => $payload['hasMore'],
            'totalCount' => $payload['totalCount'],
            'sort' => $payload['sort'],
            'direction' => $payload['direction'],
        ]);
    }

    public function update(Request $request, Workorder $workorder): JsonResponse
    {
        $this->authorizeAccess($request);
        abort_if($workorder->is_draft, 404);

        $data = $request->validate([
            'shipping_shipment_at' => ['nullable', 'string', 'max:32'],
            'shipping_freight_forwarder' => ['nullable', 'string', 'max:255'],
            'shipping_awb_no' => ['nullable', 'string', 'max:255'],
            'shipping_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $data['shipping_shipment_at'] = parse_project_date($request->input('shipping_shipment_at'));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'shipping_shipment_at' => $e->getMessage(),
            ]);
        }

        $workorder->update($data);

        $changedShippingFields = array_values(array_intersect(array_keys($workorder->getChanges()), [
            'shipping_shipment_at',
            'shipping_freight_forwarder',
            'shipping_awb_no',
            'shipping_notes',
        ]));

        if ($changedShippingFields !== []) {
            app(WorkorderNotifyService::class)->shippingLogUpdated(
                $workorder,
                (int) $request->user()->id,
                (string) $request->user()->selection_name,
                $changedShippingFields
            );
        }

        return response()->json([
            'success' => true,
            'workorder' => [
                'id' => $workorder->id,
                'shipping_shipment_at' => $workorder->shipping_shipment_at?->format('Y-m-d'),
                'shipping_shipment_at_display' => $workorder->shipping_shipment_at?->format('d/M/Y'),
                'shipping_freight_forwarder' => $workorder->shipping_freight_forwarder,
                'shipping_awb_no' => $workorder->shipping_awb_no,
                'shipping_notes' => $workorder->shipping_notes,
            ],
        ]);
    }

    private function buildIndexPayload(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = max(1, min(150, (int) $request->query('per_page', 100)));
        $page = max(1, (int) $request->query('page', 1));
        [$sort, $direction] = $this->normalizeSort($request);

        $query = Workorder::query()
            ->where('is_draft', false)
            ->with([
                'customer:id,name',
                'main.task:id,name',
                'unit:id,part_number',
            ]);

        $this->applySearch($query, $q);

        $totalCount = (clone $query)->count();

        $this->applySorting($query, $sort, $direction);

        $workorders = $query
            ->forPage($page, $perPage)
            ->get(['workorders.*']);

        $hasMore = ($page * $perPage) < $totalCount;

        return [
            'q' => $q,
            'workorders' => $workorders,
            'nextPage' => $hasMore ? $page + 1 : null,
            'hasMore' => $hasMore,
            'totalCount' => $totalCount,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function normalizeSort(Request $request): array
    {
        $sort = (string) $request->query('sort', 'wo');
        $direction = strtolower((string) $request->query('direction', 'desc'));

        if (! in_array($sort, ['wo', 'completed', 'shipment'], true)) {
            $sort = 'wo';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return [$sort, $direction];
    }

    private function applySorting(Builder $query, string $sort, string $direction): void
    {
        if ($sort === 'completed') {
            $query
                ->orderByRaw('workorders.done_at IS NULL')
                ->orderBy('workorders.done_at', $direction)
                ->orderByDesc('workorders.number')
                ->orderByDesc('workorders.id');

            return;
        }

        if ($sort === 'shipment') {
            $query
                ->orderByRaw('workorders.shipping_shipment_at IS NULL')
                ->orderBy('workorders.shipping_shipment_at', $direction)
                ->orderByDesc('workorders.number')
                ->orderByDesc('workorders.id');

            return;
        }

        $query
            ->orderBy('workorders.number', $direction)
            ->orderBy('workorders.id', $direction);
    }

    private function applySearch(Builder $query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

        $query->where(function (Builder $where) use ($like): void {
            $where->where('workorders.number', 'like', $like)
                ->orWhere('workorders.customer_po', 'like', $like)
                ->orWhere('workorders.shipping_freight_forwarder', 'like', $like)
                ->orWhere('workorders.shipping_awb_no', 'like', $like)
                ->orWhere('workorders.shipping_notes', 'like', $like)
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like))
                ->orWhereHas('unit', fn (Builder $unit) => $unit->where('part_number', 'like', $like));
        });
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole('Admin|Manager'), 403);
    }
}
