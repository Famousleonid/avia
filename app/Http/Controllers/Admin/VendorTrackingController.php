<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class VendorTrackingController extends Controller
{
    private const SOURCE_MAP = [
        'tdr_std' => TdrProcess::class,
        'tdr_part' => TdrProcess::class,
        'wo_bushing_process' => WoBushingProcess::class,
        'wo_bushing_batch' => WoBushingBatch::class,
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $filters = [
            'vendor_id' => (int) $request->input('vendor_id', 0),
            'status' => $request->input('status', 'all'),
            'sources' => $this->sourceFilters($request),
            'include_vendor_null' => $request->boolean('include_vendor_null'),
            'workorder' => trim((string) $request->input('workorder', '')),
            'part_number' => trim((string) $request->input('part_number', '')),
            'repair_order' => trim((string) $request->input('repair_order', '')),
        ];

        $totalRowsCount = $this->totalRowsCount();

        if (! in_array($filters['status'], ['open', 'returned', 'all'], true)) {
            $filters['status'] = 'all';
        }

        $tdrRows = TdrProcess::query()
            ->with([
                'vendor:id,name',
                'processName:id,name',
                'tdr:id,workorder_id,component_id,serial_number,assy_serial_number',
                'tdr.workorder:id,number,customer_id',
                'tdr.workorder.customer:id,name',
                'tdr.component:id,part_number,ipl_num,name',
            ])
            ->where(function ($q) use ($filters): void {
                $this->applyVendorFilter($q, $filters);
            })
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('tdr.workorder')
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when(! $this->hasAllTdrSources($filters), function ($q) use ($filters): void {
                $q->whereHas('tdr', function ($tdrQuery) use ($filters): void {
                    if (in_array('part', $filters['sources'], true) && in_array('std', $filters['sources'], true)) {
                        return;
                    }

                    if (in_array('part', $filters['sources'], true)) {
                        $tdrQuery->whereNotNull('component_id');
                        return;
                    }

                    if (in_array('std', $filters['sources'], true)) {
                        $tdrQuery->whereNull('component_id');
                        return;
                    }

                    $tdrQuery->whereRaw('1 = 0');
                });
            })
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('tdr.workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when($filters['part_number'] !== '', function ($q) use ($filters): void {
                $q->whereHas('tdr.component', fn ($cq) => $cq->where('part_number', 'like', '%' . $filters['part_number'] . '%'));
            })
            ->when($filters['repair_order'] !== '', fn ($q) => $q->where('repair_order', 'like', '%' . $filters['repair_order'] . '%'))
            ->get()
            ->map(fn (TdrProcess $row) => $this->normalizeTdrProcess($row));

        $bushingProcessRows = in_array('bushing', $filters['sources'], true) ? $this->bushingProcessRows($filters) : collect();
        $bushingBatchRows = in_array('bushing', $filters['sources'], true) ? $this->bushingBatchRows($filters) : collect();

        $rows = $this->paginateRows(
            $tdrRows
                ->concat($bushingProcessRows)
                ->concat($bushingBatchRows)
                ->sort(function ($a, $b): int {
                    $returned = (int) $a->is_returned <=> (int) $b->is_returned;
                    if ($returned !== 0) {
                        return $returned;
                    }

                    $start = strcmp(
                        (string) ($a->date_start?->format('Y-m-d') ?? '9999-12-31'),
                        (string) ($b->date_start?->format('Y-m-d') ?? '9999-12-31')
                    );
                    if ($start !== 0) {
                        return $start;
                    }

                    return $b->id <=> $a->id;
                })
                ->values(),
            $request
        );

        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name']);

        $summary = [
            'filtered_total' => $rows->total(),
            'page_count' => $rows->count(),
            'total_rows' => $totalRowsCount,
        ];

        return view('admin.vendor_tracking.index', compact('rows', 'vendors', 'filters', 'summary'));
    }

    public function updateRow(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $data = $request->validate([
            'source_key' => ['required', 'string'],
            'id' => ['required', 'integer', 'min:1'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'repair_order' => ['nullable', 'string', 'max:255'],
        ]);

        $modelClass = self::SOURCE_MAP[$data['source_key']] ?? null;
        abort_unless($modelClass !== null, 422, 'Unknown vendor tracking source.');

        /** @var TdrProcess|WoBushingProcess|WoBushingBatch $row */
        $row = $modelClass::query()->findOrFail($data['id']);
        $row->vendor_id = $data['vendor_id'] ?? null;
        $row->repair_order = $this->normalizeRepairOrder($data['repair_order'] ?? null);

        if ($row instanceof TdrProcess && auth()->id()) {
            $row->user_id = auth()->id();
        }

        $row->save();
        $row->loadMissing('vendor:id,name');

        return response()->json([
            'ok' => true,
            'vendor_name' => $row->vendor?->name,
            'repair_order' => $row->repair_order,
        ]);
    }

    private function sourceFilters(Request $request): array
    {
        $sources = $request->input('sources', ['part', 'std', 'bushing']);
        $sources = is_array($sources) ? $sources : [$sources];
        $sources = array_values(array_intersect($sources, ['part', 'std', 'bushing']));

        return $sources ?: ['part', 'std', 'bushing'];
    }

    private function hasAllTdrSources(array $filters): bool
    {
        return in_array('part', $filters['sources'], true)
            && in_array('std', $filters['sources'], true);
    }

    private function applyVendorFilter($query, array $filters): void
    {
        $vendorId = (int) ($filters['vendor_id'] ?? 0);
        $includeVendorNull = (bool) ($filters['include_vendor_null'] ?? false);

        if ($vendorId > 0) {
            $query->where(function ($inner) use ($vendorId, $includeVendorNull): void {
                $inner->where('vendor_id', $vendorId);
                if ($includeVendorNull) {
                    $inner->orWhereNull('vendor_id');
                }
            });

            return;
        }

        if (! $includeVendorNull) {
            $query->whereNotNull('vendor_id');
        }
    }

    private function normalizeTdrProcess(TdrProcess $row): object
    {
        $tdr = $row->tdr;
        $wo = $tdr?->workorder;
        $component = $tdr?->component;

        return (object) [
            'id' => (int) $row->id,
            'source_key' => $tdr?->component_id === null ? 'tdr_std' : 'tdr_part',
            'source' => $tdr?->component_id === null ? 'STD' : 'Part',
            'vendor' => $row->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'serial' => $tdr?->serial_number ?: $tdr?->assy_serial_number,
            'process_name' => $row->processName?->name ?? null,
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'is_returned' => ! empty($row->date_finish),
        ];
    }

    private function bushingProcessRows(array $filters): Collection
    {
        if (! Schema::hasColumn('wo_bushing_processes', 'vendor_id')) {
            return collect();
        }

        return WoBushingProcess::query()
            ->with([
                'vendor:id,name',
                'process.process_name:id,name',
                'line:id,workorder_id,component_id,qty',
                'line.workorder:id,number,customer_id',
                'line.workorder.customer:id,name',
                'line.component:id,part_number,ipl_num,name',
            ])
            ->where(function ($q) use ($filters): void {
                $this->applyVendorFilter($q, $filters);
            })
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('line.workorder')
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('line.workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when($filters['part_number'] !== '', function ($q) use ($filters): void {
                $q->whereHas('line.component', fn ($cq) => $cq->where('part_number', 'like', '%' . $filters['part_number'] . '%'));
            })
            ->when($filters['repair_order'] !== '', fn ($q) => $q->where('repair_order', 'like', '%' . $filters['repair_order'] . '%'))
            ->get()
            ->map(fn (WoBushingProcess $row) => $this->normalizeBushingProcess($row));
    }

    private function bushingBatchRows(array $filters): Collection
    {
        if (! Schema::hasColumn('wo_bushing_batches', 'vendor_id')) {
            return collect();
        }

        return WoBushingBatch::query()
            ->with([
                'vendor:id,name',
                'process.process_name:id,name',
                'workorder:id,number,customer_id',
                'workorder.customer:id,name',
                'woBushingProcesses.line.component:id,part_number,ipl_num,name',
            ])
            ->where(function ($q) use ($filters): void {
                $this->applyVendorFilter($q, $filters);
            })
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('workorder')
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when($filters['part_number'] !== '', function ($q) use ($filters): void {
                $q->whereHas('woBushingProcesses.line.component', fn ($cq) => $cq->where('part_number', 'like', '%' . $filters['part_number'] . '%'));
            })
            ->when($filters['repair_order'] !== '', fn ($q) => $q->where('repair_order', 'like', '%' . $filters['repair_order'] . '%'))
            ->get()
            ->map(fn (WoBushingBatch $row) => $this->normalizeBushingBatch($row));
    }

    private function normalizeBushingProcess(WoBushingProcess $row): object
    {
        $line = $row->line;
        $wo = $line?->workorder;
        $component = $line?->component;

        return (object) [
            'id' => (int) $row->id,
            'source_key' => 'wo_bushing_process',
            'source' => 'Bushing',
            'vendor' => $row->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'serial' => null,
            'process_name' => $row->process?->process_name?->name ?? $row->process?->process,
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'is_returned' => ! empty($row->date_finish),
        ];
    }

    private function normalizeBushingBatch(WoBushingBatch $row): object
    {
        $component = $row->woBushingProcesses
            ->map(fn (WoBushingProcess $process) => $process->line?->component)
            ->filter()
            ->first();

        return (object) [
            'id' => (int) $row->id,
            'source_key' => 'wo_bushing_batch',
            'source' => 'Bushing',
            'vendor' => $row->vendor,
            'workorder' => $row->workorder,
            'customer' => $row->workorder?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'serial' => null,
            'process_name' => ($row->process?->process_name?->name ?? $row->process?->process ?? 'Bushing batch') . ' / Batch',
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'is_returned' => ! empty($row->date_finish),
        ];
    }

    private function paginateRows(Collection $rows, Request $request): LengthAwarePaginator
    {
        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));
    }

    private function totalRowsCount(): int
    {
        $tdrCount = TdrProcess::query()
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('tdr.workorder')
            ->count();

        $bushingProcessCount = 0;
        if (Schema::hasColumn('wo_bushing_processes', 'vendor_id')) {
            $bushingProcessCount = WoBushingProcess::query()
                ->where(function ($q): void {
                    $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
                })
                ->whereHas('line.workorder')
                ->count();
        }

        $bushingBatchCount = 0;
        if (Schema::hasColumn('wo_bushing_batches', 'vendor_id')) {
            $bushingBatchCount = WoBushingBatch::query()
                ->where(function ($q): void {
                    $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
                })
                ->whereHas('workorder')
                ->count();
        }

        return $tdrCount + $bushingProcessCount + $bushingBatchCount;
    }

    private function normalizeRepairOrder(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
