<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\VendorTrackingExport;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorTrackingController extends Controller
{
    private const EXPORTABLE_COLUMNS = [
        'repair_order',
        'type',
        'vendor',
        'wo',
        'customer',
        'ipl',
        'part_number',
        'serial',
        'process',
        'sent',
        'returned',
        'days',
    ];

    private const SOURCE_MAP = [
        'tdr_std' => TdrProcess::class,
        'tdr_part' => TdrProcess::class,
        'wo_bushing_process' => WoBushingProcess::class,
        'wo_bushing_batch' => WoBushingBatch::class,
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $filters = $this->filtersFromRequest($request);
        $totalRowsCount = $this->totalRowsCount();
        $allRows = $this->collectRows($filters);

        $rows = $this->paginateRows(
            $allRows,
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

    public function export(Request $request): BinaryFileResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $filters = $this->filtersFromRequest($request);
        $rows = $this->collectRows($filters);
        $columns = $this->exportColumnsFromRequest($request);
        $title = trim((string) $request->input('excel_title', 'Vendor Tracking'));
        $filename = 'vendor-tracking-' . now()->format('Y-m-d_H-i') . '.xlsx';

        return Excel::download(new VendorTrackingExport($rows, $columns, $title), $filename);
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
        $row->loadMissing('vendor:' . $this->vendorSelectColumns());

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

    private function filtersFromRequest(Request $request): array
    {
        $filters = [
            'vendor_id' => (int) $request->input('vendor_id', 0),
            'status' => $request->input('status', 'all'),
            'sources' => $this->sourceFilters($request),
            'include_vendor_null' => $request->boolean('include_vendor_null'),
            'workorder' => $this->normalizeWorkorderFilter((string) $request->input('workorder', '')),
            'part_number' => trim((string) $request->input('part_number', '')),
            'repair_order' => trim((string) $request->input('repair_order', '')),
            'sort' => $this->normalizeSort((string) $request->input('sort', 'wo')),
            'direction' => $this->normalizeSortDirection((string) $request->input('direction', 'desc')),
        ];

        if (! in_array($filters['status'], ['open', 'returned', 'all'], true)) {
            $filters['status'] = 'all';
        }

        return $filters;
    }

    private function exportColumnsFromRequest(Request $request): array
    {
        $columns = $request->input('columns', self::EXPORTABLE_COLUMNS);
        $columns = is_array($columns) ? $columns : [$columns];
        $columns = array_values(array_intersect($columns, self::EXPORTABLE_COLUMNS));

        return $columns ?: self::EXPORTABLE_COLUMNS;
    }

    private function normalizeSort(string $value): string
    {
        return in_array($value, ['vendor', 'type', 'wo', 'ipl', 'process'], true)
            ? $value
            : 'wo';
    }

    private function normalizeSortDirection(string $value): string
    {
        return strtolower($value) === 'asc' ? 'asc' : 'desc';
    }

    private function vendorSelectColumns(): string
    {
        $columns = ['id', 'name'];

        if (Schema::hasColumn('vendors', 'is_trusted')) {
            $columns[] = 'is_trusted';
        }

        return implode(',', $columns);
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

    private function collectRows(array $filters): Collection
    {
        $tdrRows = TdrProcess::query()
            ->with([
                'vendor:' . $this->vendorSelectColumns(),
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

        return $this->sortRows(
            $tdrRows
                ->concat($bushingProcessRows)
                ->concat($bushingBatchRows)
                ->values(),
            $filters
        );
    }

    private function sortRows(Collection $rows, array $filters): Collection
    {
        $sort = $filters['sort'] ?? 'wo';
        $direction = $filters['direction'] ?? 'desc';

        return $rows->sort(function ($a, $b) use ($sort, $direction): int {
            $result = match ($sort) {
                'vendor' => $this->compareText($a->vendor?->name, $b->vendor?->name),
                'type' => $this->compareText($a->source ?? null, $b->source ?? null),
                'ipl' => $this->compareText($a->ipl_num ?? null, $b->ipl_num ?? null),
                'process' => $this->compareText($a->process_name ?? null, $b->process_name ?? null),
                'wo' => $this->compareText($a->workorder?->number ?? null, $b->workorder?->number ?? null, true),
                default => 0,
            };

            if ($result === 0) {
                $result = $this->compareText($a->repair_order ?? null, $b->repair_order ?? null, true);
            }

            if ($result === 0) {
                $result = $b->id <=> $a->id;
            }

            return $direction === 'asc' ? $result : -$result;
        })->values();
    }

    private function compareText(?string $left, ?string $right, bool $natural = false): int
    {
        $left = trim((string) $left);
        $right = trim((string) $right);

        if ($left === '' && $right === '') {
            return 0;
        }

        if ($left === '') {
            return 1;
        }

        if ($right === '') {
            return -1;
        }

        return $natural
            ? strnatcasecmp($left, $right)
            : strcasecmp($left, $right);
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
            'date_update_url' => route('tdrprocesses.updateDate', $row),
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
                'vendor:' . $this->vendorSelectColumns(),
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
                'vendor:' . $this->vendorSelectColumns(),
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
            'source' => 'Bush',
            'date_update_url' => route('wo_bushing_processes.updateDate', $row),
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
            'source' => 'Bush',
            'date_update_url' => route('wo_bushing_batches.updateDate', $row),
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

    private function normalizeWorkorderFilter(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return preg_replace('/^\s*w\s*/i', '', $value) ?? $value;
    }
}
