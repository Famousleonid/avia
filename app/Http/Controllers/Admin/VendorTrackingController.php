<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\VendorTrackingExport;
use App\Models\Customer;
use App\Models\Process;
use App\Models\QuantumRoLine;
use App\Models\QuantumRoSyncRun;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\UserUiSetting;
use App\Models\WoBushingBatch;
use App\Models\WoBushingProcess;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Services\WorkorderStdListProcessesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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
        'part_name',
        'serial',
        'process',
        'sent',
        'returned',
        'ecd',
        'days',
        'changed_at',
    ];

    private const SOURCE_MAP = [
        'tdr_std' => TdrProcess::class,
        'tdr_part' => TdrProcess::class,
        'workorder_std_process' => WorkorderStdProcess::class,
        'wo_bushing_process' => WoBushingProcess::class,
        'wo_bushing_batch' => WoBushingBatch::class,
    ];

    private const QUANTUM_RECENT_PAGE_SIZE = 200;
    private const QUANTUM_SYNC_STALE_AFTER_MINUTES = 15;
    private const QUANTUM_SYNC_WARNING_AFTER_MINUTES = 10;
    private const QUANTUM_STATUS_ECO_FEE = 'ECO FEE';
    private const QUANTUM_STATUS_DISMISSED = 'dismissed';

    private ?array $completedQuantumWorkorderLabels = null;

    public function index(Request $request)
    {
        $this->authorizeVendorTracking();

        $filters = $this->filtersFromRequest($request);
        $completedWorkorderSearch = $this->completedWorkorderFromFilter($filters);
        $totalRowsCount = $this->totalRowsCount();
        $allRows = $this->collectRows($filters);

        $rows = $this->paginateRows(
            $allRows,
            $request
        );

        $vendors = Vendor::query()->orderBy('name')->get(['id', 'name']);
        $customers = Customer::query()->orderBy('name')->get(['id', 'name']);
        $quantumRecentRows = $this->quantumRecentRows();
        $quantumRecentTotal = $this->quantumRecentTotal();
        $quantumUnparsedRows = $this->quantumUnparsedRows();
        $quantumUnparsedTotal = $this->quantumUnparsedTotal();
        $quantumStatusCounts = $this->quantumStatusCounts();
        $quantumSyncHealth = $this->quantumSyncHealth();

        $summary = [
            'filtered_total' => $rows->total(),
            'page_count' => $rows->count(),
            'total_rows' => $totalRowsCount,
        ];

        return view('admin.vendor_tracking.index', compact(
            'rows',
            'vendors',
            'customers',
            'filters',
            'completedWorkorderSearch',
            'summary',
            'quantumRecentRows',
            'quantumRecentTotal',
            'quantumUnparsedRows',
            'quantumUnparsedTotal',
            'quantumStatusCounts',
            'quantumSyncHealth'
        ));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorizeVendorTracking();

        $filters = $this->filtersFromRequest($request);
        $rows = $this->collectRows($filters);
        $columns = $this->exportColumnsFromRequest($request);
        $title = trim((string) $request->input('excel_title', 'Vendor Tracking'));
        $filename = 'vendor-tracking-' . now()->format('Y-m-d_H-i') . '.xlsx';

        return Excel::download(new VendorTrackingExport($rows, $columns, $title), $filename);
    }

    public function updateRow(Request $request): JsonResponse
    {
        $this->authorizeVendorTracking();

        $data = $request->validate([
            'source_key' => ['required', 'string'],
            'id' => ['required', 'integer', 'min:1'],
            'traveler_group' => ['nullable', 'integer', 'min:1'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
        ]);

        $modelClass = self::SOURCE_MAP[$data['source_key']] ?? null;
        if ($data['source_key'] === 'tdr_traveler') {
            $travelerGroup = (int) ($data['traveler_group'] ?? 0);

            $rows = TdrProcess::query()
                ->where('tdrs_id', (int) $data['id'])
                ->where('in_traveler', true)
                ->when($travelerGroup > 0, fn ($query) => $this->whereTravelerGroup($query, $travelerGroup))
                ->get();

            abort_if($rows->isEmpty(), 404);

            foreach ($rows as $row) {
                $row->vendor_id = $data['vendor_id'] ?? null;
                if (auth()->id()) {
                    $row->user_id = auth()->id();
                }
                $row->save();
            }

            $leader = $rows->first();
            $leader->loadMissing('vendor:' . $this->vendorSelectColumns());

            return response()->json([
                'ok' => true,
                'vendor_name' => $leader->vendor?->name,
                'repair_order' => $leader->repair_order,
            ]);
        }

        abort_unless($modelClass !== null, 422, 'Unknown vendor tracking source.');

        /** @var TdrProcess|WorkorderStdProcess|WoBushingProcess|WoBushingBatch $row */
        $row = $modelClass::query()->findOrFail($data['id']);
        $row->vendor_id = $data['vendor_id'] ?? null;

        if (($row instanceof TdrProcess || $row instanceof WorkorderStdProcess) && auth()->id()) {
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

    public function recentQuantumRoLines(Request $request): JsonResponse
    {
        $this->authorizeVendorTracking();

        if (! $this->quantumRoLinesTableReady()) {
            return response()->json([
                'success' => true,
                'html' => '',
                'has_more' => false,
                'next_page' => null,
                'total' => 0,
            ]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $rows = $this->quantumRecentRowsQuery()
            ->forPage($page, self::QUANTUM_RECENT_PAGE_SIZE)
            ->get();
        $total = $this->quantumRecentTotal();
        $hasMore = ($page * self::QUANTUM_RECENT_PAGE_SIZE) < $total;

        return response()->json([
            'success' => true,
            'html' => view('admin.vendor_tracking.partials.quantum_recent_rows', [
                'quantumRecentRows' => $rows,
            ])->render(),
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
            'total' => $total,
        ]);
    }

    public function findQuantumRoLineByWorkorder(Request $request): JsonResponse
    {
        $this->authorizeVendorTracking();

        $data = $request->validate([
            'mode' => ['nullable', 'string', 'in:wo,ro'],
            'repair_order' => ['nullable', 'string', 'max:50'],
            'workorder' => ['nullable', 'string', 'max:50'],
        ]);

        if (! $this->quantumRoLinesTableReady()) {
            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'Quantum list is empty.',
            ]);
        }

        $mode = ($data['mode'] ?? null) === 'ro' ? 'ro' : 'wo';
        $search = trim((string) ($mode === 'ro' ? ($data['repair_order'] ?? '') : ($data['workorder'] ?? '')));
        $filterActive = $search !== '';

        if ($filterActive) {
            $recentQuery = $this->applyQuantumBufferSearch($this->quantumRecentRowsQuery(), $mode, $search);
            $filteredTotal = (clone $recentQuery)->count();
            $matchedLinesWithOverflow = $recentQuery->limit(501)->get();
            $hasMoreMatches = $matchedLinesWithOverflow->count() > 500;
            $recentRows = $matchedLinesWithOverflow->take(500)->values();

            $unparsedMatches = $this->applyQuantumBufferSearch($this->quantumUnparsedRowsQuery(), $mode, $search)
                ->get()
                ->reject(fn (QuantumRoLine $line): bool => $this->isOldQuantumWorkorderNotFound($line, trim((string) $line->apply_status)))
                ->values();
            $unparsedTotal = $unparsedMatches->count();
            $unparsedRows = $unparsedMatches->take(200)->values();
        } else {
            $recentRows = $this->quantumRecentRows();
            $filteredTotal = $this->quantumRecentTotal();
            $hasMoreMatches = false;
            $unparsedRows = $this->quantumUnparsedRows();
            $unparsedTotal = $this->quantumUnparsedTotal();
        }

        $line = $recentRows->first();
        $found = $filterActive && $line !== null;
        $hasMore = ! $filterActive && $recentRows->count() < $filteredTotal;

        return response()->json([
            'success' => true,
            'found' => $found,
            'filter_active' => $filterActive,
            'mode' => $mode,
            'message' => $filterActive && ! $found
                ? ($mode === 'ro' ? 'RO not found in Quantum list.' : 'WO not found in Quantum Reason.')
                : null,
            'html' => view('admin.vendor_tracking.partials.quantum_recent_rows', [
                'quantumRecentRows' => $recentRows,
            ])->render(),
            'unparsed_html' => view('admin.vendor_tracking.partials.quantum_unparsed_rows', [
                'quantumUnparsedRows' => $unparsedRows,
            ])->render(),
            'line_id' => $line ? (int) $line->id : null,
            'line_ids' => $recentRows->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'matched_count' => $filterActive ? $filteredTotal : 0,
            'has_more_matches' => $hasMoreMatches,
            'matched_value' => $search,
            'matched_ro' => $line?->ro_number,
            'matched_wo' => $line?->wo_number,
            'total' => $filteredTotal,
            'unparsed_total' => $unparsedTotal,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? 2 : null,
        ]);
    }

    public function dismissQuantumRoLine(QuantumRoLine $quantumRoLine): JsonResponse
    {
        $this->authorizeVendorTracking();

        $dismissedLine = $this->dismissQuantumLine($quantumRoLine);

        return response()->json([
            'success' => true,
            'dismissed' => $dismissedLine !== null ? 1 : 0,
            'dismissed_ids' => $dismissedLine !== null ? [$dismissedLine['id']] : [],
            'lines' => $dismissedLine !== null ? [$dismissedLine] : [],
            'unparsed_total' => $this->quantumUnparsedTotal(),
            'status_counts' => $this->quantumStatusCounts(),
        ]);
    }

    public function restoreQuantumRoLine(QuantumRoLine $quantumRoLine): JsonResponse
    {
        $this->authorizeVendorTracking();

        $restoredLine = $this->restoreQuantumLine($quantumRoLine);

        return response()->json([
            'success' => true,
            'restored' => $restoredLine !== null ? 1 : 0,
            'restored_id' => $restoredLine['id'] ?? null,
            'line' => $restoredLine,
            'unparsed_total' => $this->quantumUnparsedTotal(),
            'status_counts' => $this->quantumStatusCounts(),
        ]);
    }

    private function sourceFilters(Request $request): array
    {
        $sources = $request->input('sources', ['part', 'std', 'bushing']);
        $sources = is_array($sources) ? $sources : [$sources];
        $sources = array_values(array_intersect($sources, ['part', 'std', 'bushing']));

        return $sources ?: ['part', 'std', 'bushing'];
    }

    private function authorizeVendorTracking(): void
    {
        abort_unless(auth()->user()?->can('feature.vendor_tracking'), 403);
    }

    private function filtersFromRequest(Request $request): array
    {
        if ($request->query() === [] && auth()->id()) {
            $savedFiltersByKey = UserUiSetting::query()
                ->where('user_id', auth()->id())
                ->where('scope', 'vendor-tracking.index')
                ->whereIn('key', ['filters', 'vendorTrackingFilters'])
                ->get(['key', 'value'])
                ->keyBy('key');
            $savedFilters = $savedFiltersByKey->get('filters')?->value
                ?? $savedFiltersByKey->get('vendorTrackingFilters')?->value;

            if (is_array($savedFilters)) {
                $request->merge(array_intersect_key($savedFilters, array_flip([
                    'vendor_id',
                    'customer_id',
                    'status',
                    'sources',
                    'include_vendor_null',
                    'workorder',
                    'part_number',
                    'repair_order',
                ])));
            }
        }

        $filters = [
            'vendor_id' => (int) $request->input('vendor_id', 0),
            'customer_id' => (int) $request->input('customer_id', 0),
            'status' => $request->input('status', 'all'),
            'sources' => $this->sourceFilters($request),
            'include_vendor_null' => $request->boolean('include_vendor_null'),
            'workorder' => $this->normalizeWorkorderFilter((string) $request->input('workorder', '')),
            'part_number' => trim((string) $request->input('part_number', '')),
            'repair_order' => trim((string) $request->input('repair_order', '')),
            'sort' => $this->normalizeSort((string) $request->input('sort', 'sent_date')),
            'sort_explicit' => $request->boolean('sort_user')
                && ! in_array($this->normalizeSort((string) $request->input('sort', 'sent_date')), ['sent_date', 'wo'], true),
            'direction' => $this->normalizeSortDirection((string) $request->input('direction', 'asc')),
        ];

        if (! in_array($filters['status'], ['open', 'returned', 'all'], true)) {
            $filters['status'] = 'all';
        }

        return $filters;
    }

    private function completedWorkorderFromFilter(array $filters): ?Workorder
    {
        $workorderNumber = trim((string) ($filters['workorder'] ?? ''));

        if ($workorderNumber === '' || ! ctype_digit($workorderNumber)) {
            return null;
        }

        return Workorder::query()
            ->where('number', $workorderNumber)
            ->whereNotNull('done_at')
            ->first(['id', 'number', 'done_at']);
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
        return in_array($value, ['vendor', 'type', 'wo', 'ipl', 'process', 'sent_date', 'changed_at'], true)
            ? $value
            : 'sent_date';
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
        $stdProcessNameIds = $this->stdProcessNameIds();
        // TODO(tdr-refactor): Split vendor tracking into source-specific builders and remove the tdr_std compatibility path after legacy STD rows are deleted everywhere.
        $tdrRawRows = TdrProcess::query()
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
            ->whereHas('tdr.workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->when($stdProcessNameIds->isNotEmpty(), fn ($q) => $q->whereNotIn('process_names_id', $stdProcessNameIds))
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
            ->when((int) ($filters['customer_id'] ?? 0) > 0, function ($q) use ($filters): void {
                $q->whereHas('tdr.workorder', fn ($wq) => $wq->where('customer_id', (int) $filters['customer_id']));
            })
            ->when($filters['part_number'] !== '', function ($q) use ($filters): void {
                $q->whereHas('tdr.component', fn ($cq) => $cq->where('part_number', 'like', '%' . $filters['part_number'] . '%'));
            })
            ->when($filters['repair_order'] !== '', fn ($q) => $q->where('repair_order', 'like', '%' . $filters['repair_order'] . '%'))
            ->orderBy('tdrs_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tdrRows = $tdrRawRows
            ->reject(fn (TdrProcess $row) => (bool) $row->in_traveler)
            ->map(fn (TdrProcess $row) => $this->normalizeTdrProcess($row));

        $tdrTravelerRows = $tdrRawRows
            ->filter(fn (TdrProcess $row) => (bool) $row->in_traveler)
            ->groupBy(fn (TdrProcess $row) => ((int) $row->tdrs_id) . ':' . ((int) ($row->traveler_group ?: 1)))
            ->map(fn (Collection $group) => $this->normalizeTdrTravelerGroup($this->loadFullTravelerGroup($group)))
            ->values();

        $stdRows = in_array('std', $filters['sources'], true) ? $this->workorderStdProcessRows($filters) : collect();
        $bushingProcessRows = in_array('bushing', $filters['sources'], true) ? $this->bushingProcessRows($filters) : collect();
        $bushingBatchRows = in_array('bushing', $filters['sources'], true) ? $this->bushingBatchRows($filters) : collect();

        return $this->sortRows(
            $tdrRows
                ->concat($tdrTravelerRows)
                ->concat($stdRows)
                ->concat($bushingProcessRows)
                ->concat($bushingBatchRows)
                ->values(),
            $filters
        );
    }

    private function sortRows(Collection $rows, array $filters): Collection
    {
        if (
            ! (bool) ($filters['sort_explicit'] ?? false)
            && ($filters['workorder'] ?? '') !== ''
            && trim((string) ($filters['part_number'] ?? '')) !== ''
        ) {
            return $rows->sortBy([
                ['workorder_number', 'asc'],
                ['part_number', 'asc'],
                ['tdr_id', 'asc'],
                ['process_sort_order', 'asc'],
                ['process_row_id', 'asc'],
            ])->values();
        }

        $sort = $filters['sort'] ?? 'sent_date';
        $direction = $filters['direction'] ?? 'asc';

        return $rows->sort(function ($a, $b) use ($sort, $direction): int {
            $result = match ($sort) {
                'vendor' => $this->compareText($a->vendor?->name, $b->vendor?->name),
                'type' => $this->compareText($a->source ?? null, $b->source ?? null),
                'ipl' => $this->compareText($a->ipl_num ?? null, $b->ipl_num ?? null),
                'process' => $this->compareText($a->process_name ?? null, $b->process_name ?? null),
                'sent_date' => $this->compareDate($a->date_start ?? null, $b->date_start ?? null),
                'changed_at' => $this->compareDate($a->changed_at ?? null, $b->changed_at ?? null),
                'wo' => $this->compareText($a->workorder?->number ?? null, $b->workorder?->number ?? null, true),
                default => 0,
            };

            if ($result === 0) {
                $result = $this->compareText($a->repair_order ?? null, $b->repair_order ?? null, true);
            }

            if ($result === 0) {
                $result = ((int) ($a->process_sort_order ?? 999999)) <=> ((int) ($b->process_sort_order ?? 999999));
            }

            if ($result === 0) {
                $result = ((int) ($a->process_row_id ?? $a->id ?? 0)) <=> ((int) ($b->process_row_id ?? $b->id ?? 0));
            }

            return $direction === 'asc' ? $result : -$result;
        })->values();
    }

    private function compareDate(mixed $left, mixed $right): int
    {
        $leftTimestamp = $this->dateTimestamp($left);
        $rightTimestamp = $this->dateTimestamp($right);

        if ($leftTimestamp === null && $rightTimestamp === null) {
            return 0;
        }

        if ($leftTimestamp === null) {
            return 1;
        }

        if ($rightTimestamp === null) {
            return -1;
        }

        return $leftTimestamp <=> $rightTimestamp;
    }

    private function dateTimestamp(mixed $value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
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
        // TODO(tdr-refactor): Make the source explicit instead of inferring STD vs Part from a nullable component_id.
        $tdr = $row->tdr;
        $wo = $tdr?->workorder;
        $component = $tdr?->component;

        return (object) [
            'id' => (int) $row->id,
            'tdr_id' => (int) ($tdr?->id ?? 0),
            'workorder_number' => (string) ($wo?->number ?? ''),
            'process_sort_order' => (int) ($row->sort_order ?? 999999),
            'process_row_id' => (int) $row->id,
            'source_key' => $tdr?->component_id === null ? 'tdr_std' : 'tdr_part',
            'source' => $tdr?->component_id === null ? 'STD' : 'Part',
            'date_update_url' => route('tdrprocesses.updateDate', $row),
            'vendor' => $row->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'part_name' => $component?->name,
            'serial' => $tdr?->serial_number ?: $tdr?->assy_serial_number,
            'process_name' => $row->processName?->name ?? null,
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'date_promise' => $row->date_promise,
            'changed_at' => $row->updated_at,
            'is_returned' => ! empty($row->date_finish),
        ];
    }

    private function stdProcessNameIds(): Collection
    {
        return \App\Models\ProcessName::query()
            ->whereIn('name', array_values(WorkorderStdListProcessesService::NAME_BY_KEY))
            ->pluck('id');
    }

    private function workorderStdProcessRows(array $filters): Collection
    {
        return WorkorderStdProcess::query()
            ->with([
                'vendor:' . $this->vendorSelectColumns(),
                'processName:id,name',
                'workorder:id,number,customer_id',
                'workorder.customer:id,name',
            ])
            ->where(function ($q) use ($filters): void {
                $this->applyVendorFilter($q, $filters);
            })
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when((int) ($filters['customer_id'] ?? 0) > 0, function ($q) use ($filters): void {
                $q->whereHas('workorder', fn ($wq) => $wq->where('customer_id', (int) $filters['customer_id']));
            })
            ->when($filters['part_number'] !== '', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($filters['repair_order'] !== '', fn ($q) => $q->where('repair_order', 'like', '%' . $filters['repair_order'] . '%'))
            ->get()
            ->map(fn (WorkorderStdProcess $row) => $this->normalizeWorkorderStdProcess($row));
    }

    private function normalizeWorkorderStdProcess(WorkorderStdProcess $row): object
    {
        $wo = $row->workorder;

        return (object) [
            'id' => (int) $row->id,
            'tdr_id' => 0,
            'workorder_number' => (string) ($wo?->number ?? ''),
            'process_sort_order' => (int) array_search($row->std_type, array_keys(WorkorderStdListProcessesService::NAME_BY_KEY), true),
            'process_row_id' => (int) $row->id,
            'source_key' => 'workorder_std_process',
            'source' => 'STD',
            'date_update_url' => route('workorder_std_processes.updateDate', $row),
            'vendor' => $row->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => null,
            'part_number' => null,
            'part_name' => null,
            'serial' => null,
            'process_name' => $row->processName?->name ?? strtoupper((string) $row->std_type),
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'date_promise' => $row->date_promise,
            'changed_at' => $row->updated_at,
            'is_returned' => ! empty($row->date_finish),
        ];
    }

    private function loadFullTravelerGroup(Collection $group): Collection
    {
        $tdrId = (int) ($group->first()?->tdrs_id ?? 0);
        $travelerGroup = (int) ($group->first()?->traveler_group ?: 1);
        if ($tdrId <= 0) {
            return $group;
        }

        return TdrProcess::query()
            ->with([
                'vendor:' . $this->vendorSelectColumns(),
                'processName:id,name',
                'tdr:id,workorder_id,component_id,serial_number,assy_serial_number',
                'tdr.workorder:id,number,customer_id',
                'tdr.workorder.customer:id,name',
                'tdr.component:id,part_number,ipl_num,name',
            ])
            ->where('tdrs_id', $tdrId)
            ->where('in_traveler', true)
            ->where(fn ($query) => $this->whereTravelerGroup($query, $travelerGroup))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function whereTravelerGroup($query, int $travelerGroup): void
    {
        $query->where(function ($inner) use ($travelerGroup): void {
            $inner->where('traveler_group', $travelerGroup);

            if ($travelerGroup === 1) {
                $inner->orWhereNull('traveler_group');
            }
        });
    }

    private function quantumUnparsedRows(): Collection
    {
        if (! $this->quantumRoLinesTableReady()) {
            return collect();
        }

        return $this->quantumUnparsedRowsQuery()
            ->get()
            ->reject(fn (QuantumRoLine $line): bool => $this->isOldQuantumWorkorderNotFound($line, trim((string) $line->apply_status)))
            ->take(200)
            ->values();
    }

    private function quantumUnparsedRowsQuery(): Builder
    {
        return $this->visibleQuantumRoLinesQuery()
            ->where('apply_status', 'unresolved')
            ->orderByDesc('source_last_modified')
            ->orderByDesc('id');
    }

    private function quantumRecentRows(): Collection
    {
        if (! $this->quantumRoLinesTableReady()) {
            return collect();
        }

        return $this->quantumRecentRowsQuery()
            ->limit(self::QUANTUM_RECENT_PAGE_SIZE)
            ->get();
    }

    private function quantumRecentTotal(): int
    {
        if (! $this->quantumRoLinesTableReady()) {
            return 0;
        }

        return $this->visibleQuantumRoLinesQuery()->count();
    }

    private function quantumUnparsedTotal(): int
    {
        if (! $this->quantumRoLinesTableReady()) {
            return 0;
        }

        return $this->visibleQuantumRoLinesQuery()
            ->where('apply_status', 'unresolved')
            ->get(['apply_status', 'apply_message', 'wo_number'])
            ->reject(fn (QuantumRoLine $line): bool => $this->isOldQuantumWorkorderNotFound($line, trim((string) $line->apply_status)))
            ->count();
    }

    private function quantumRoLinesTableReady(): bool
    {
        return Schema::hasTable('quantum_ro_lines')
            && Schema::hasColumn('quantum_ro_lines', 'apply_status');
    }

    private function quantumSyncHealth(): array
    {
        $base = [
            'ready' => false,
            'status' => 'unavailable',
            'label' => 'Unavailable',
            'age_label' => '--',
            'button_class' => 'btn-outline-secondary',
            'badge_class' => 'text-bg-secondary',
            'icon' => 'bi-database-x',
            'message' => 'Quantum sync tables are not ready.',
            'last_run_id' => null,
            'last_run_status' => null,
            'last_run_at' => null,
            'last_line_seen_at' => null,
            'last_source_modified' => null,
            'rows_received' => null,
            'rows_inserted' => null,
            'rows_updated' => null,
            'rows_unchanged' => null,
            'minutes_since' => null,
            'stale_after_minutes' => self::QUANTUM_SYNC_STALE_AFTER_MINUTES,
        ];

        if (! Schema::hasTable('quantum_ro_sync_runs')) {
            return $base;
        }

        $latestRun = QuantumRoSyncRun::query()
            ->orderByDesc('id')
            ->first();

        $lineTableReady = Schema::hasTable('quantum_ro_lines');
        $lastLineSeenAt = $lineTableReady ? QuantumRoLine::query()->max('last_seen_at') : null;
        $lastSourceModified = $lineTableReady ? QuantumRoLine::query()->max('source_last_modified') : null;

        if (! $latestRun) {
            return array_merge($base, [
                'ready' => true,
                'status' => 'never',
                'label' => 'No runs',
                'button_class' => 'btn-outline-secondary',
                'badge_class' => 'text-bg-secondary',
                'icon' => 'bi-database-x',
                'message' => 'No Quantum sync run has been received yet.',
                'last_line_seen_at' => $lastLineSeenAt,
                'last_source_modified' => $lastSourceModified,
            ]);
        }

        $lastRunAt = $latestRun->finished_at ?: ($latestRun->updated_at ?: $latestRun->created_at);
        $minutesSince = $lastRunAt
            ? max(0, intdiv(now()->getTimestamp() - $lastRunAt->getTimestamp(), 60))
            : null;

        $status = 'ok';
        $label = 'Sync OK';
        $buttonClass = 'btn-outline-success';
        $badgeClass = 'text-bg-success';
        $icon = 'bi-database-check';
        $message = 'Quantum sync is receiving scheduled runs.';

        if ($minutesSince === null) {
            $status = 'unknown';
            $label = 'Unknown';
            $buttonClass = 'btn-outline-secondary';
            $badgeClass = 'text-bg-secondary';
            $icon = 'bi-database-x';
            $message = 'Latest Quantum sync run has no timestamp.';
        } elseif ($minutesSince >= self::QUANTUM_SYNC_STALE_AFTER_MINUTES) {
            $status = 'stale';
            $label = 'Sync stale';
            $buttonClass = 'btn-outline-danger';
            $badgeClass = 'text-bg-danger';
            $icon = 'bi-database-exclamation';
            $message = 'No Quantum sync run has been received within the expected 5 minute cadence.';
        } elseif ($minutesSince >= self::QUANTUM_SYNC_WARNING_AFTER_MINUTES || $latestRun->status !== 'completed') {
            $status = 'warning';
            $label = 'Sync delayed';
            $buttonClass = 'btn-outline-warning';
            $badgeClass = 'text-bg-warning';
            $icon = 'bi-database-exclamation';
            $message = $latestRun->status === 'completed'
                ? 'Latest Quantum sync run is later than expected.'
                : "Latest Quantum sync run status is {$latestRun->status}.";
        }

        return array_merge($base, [
            'ready' => true,
            'status' => $status,
            'label' => $label,
            'age_label' => $this->quantumSyncAgeLabel($minutesSince),
            'button_class' => $buttonClass,
            'badge_class' => $badgeClass,
            'icon' => $icon,
            'message' => $message,
            'last_run_id' => (int) $latestRun->id,
            'last_run_status' => $latestRun->status,
            'last_run_at' => $lastRunAt,
            'last_line_seen_at' => $lastLineSeenAt,
            'last_source_modified' => $lastSourceModified,
            'rows_received' => (int) $latestRun->rows_received,
            'rows_inserted' => (int) $latestRun->rows_inserted,
            'rows_updated' => (int) $latestRun->rows_updated,
            'rows_unchanged' => (int) $latestRun->rows_unchanged,
            'minutes_since' => $minutesSince,
        ]);
    }

    private function quantumSyncAgeLabel(?int $minutes): string
    {
        if ($minutes === null) {
            return '--';
        }

        if ($minutes < 1) {
            return 'just now';
        }

        if ($minutes < 60) {
            return "{$minutes}m ago";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 48) {
            return $remainingMinutes > 0
                ? "{$hours}h {$remainingMinutes}m ago"
                : "{$hours}h ago";
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? "{$days}d {$remainingHours}h ago"
            : "{$days}d ago";
    }

    private function quantumStatusCounts(): array
    {
        $counts = [
            'total' => 0,
            'unresolved' => 0,
            'pending' => 0,
            'wo_not_found' => 0,
            'wo_not_found_old' => 0,
            'applied' => 0,
            'eco_fee' => 0,
            'not_applicable' => 0,
            'dismissed' => 0,
            'error' => 0,
            'other' => 0,
        ];

        if (! $this->quantumRoLinesTableReady()) {
            return $counts;
        }

        $this->visibleQuantumRoLinesQuery()
            ->get(['apply_status', 'apply_message', 'wo_number'])
            ->each(function (QuantumRoLine $line) use (&$counts): void {
                $counts['total']++;
                $counts[$this->quantumStatusCountKey($line)]++;
            });

        return $counts;
    }

    private function quantumStatusCountKey(QuantumRoLine $line): string
    {
        $status = trim((string) $line->apply_status);

        if ($this->isOldQuantumWorkorderNotFound($line, $status)) {
            return 'wo_not_found_old';
        }

        if ($this->isQuantumWorkorderNotFound($line, $status)) {
            return 'wo_not_found';
        }

        return match ($status) {
            '', 'pending' => 'pending',
            'unresolved' => 'unresolved',
            'applied' => 'applied',
            self::QUANTUM_STATUS_ECO_FEE => 'eco_fee',
            'N/A' => 'not_applicable',
            self::QUANTUM_STATUS_DISMISSED => 'dismissed',
            'error' => 'error',
            default => 'other',
        };
    }

    private function dismissQuantumLine(QuantumRoLine $line): ?array
    {
        if (trim((string) $line->apply_status) !== 'unresolved') {
            return null;
        }

        $message = $this->dismissedQuantumMessage($line);

        $line->forceFill([
            'apply_status' => self::QUANTUM_STATUS_DISMISSED,
            'apply_message' => $message,
            'applied_target_table' => null,
            'applied_target_id' => null,
            'applied_source_hash' => $line->source_hash,
            'applied_at' => now(),
        ])->save();

        return [
            'id' => (int) $line->id,
            'status' => self::QUANTUM_STATUS_DISMISSED,
            'message' => $message,
            'restore_url' => route('vendor-tracking.quantum-lines.restore', ['quantumRoLine' => $line->id]),
        ];
    }

    private function dismissedQuantumMessage(QuantumRoLine $line): string
    {
        $currentMessage = trim((string) $line->apply_message);

        if ($currentMessage === '' || str_starts_with($currentMessage, 'Dismissed by user')) {
            return 'Dismissed by user: old Quantum row, no action needed';
        }

        return mb_substr('Dismissed by user: ' . $currentMessage, 0, 5000);
    }

    private function restoreQuantumLine(QuantumRoLine $line): ?array
    {
        if (trim((string) $line->apply_status) !== self::QUANTUM_STATUS_DISMISSED) {
            return null;
        }

        $message = 'Restored by user: waiting for quantum-ro:apply';

        $line->forceFill([
            'apply_status' => 'pending',
            'apply_message' => $message,
            'applied_target_table' => null,
            'applied_target_id' => null,
            'applied_source_hash' => null,
            'applied_at' => null,
        ])->save();

        return [
            'id' => (int) $line->id,
            'status' => 'pending',
            'message' => $message,
            'restore_url' => null,
        ];
    }

    private function isOldQuantumWorkorderNotFound(QuantumRoLine $line, string $status): bool
    {
        if ($status === 'WO not found: old') {
            return true;
        }

        $woNumber = preg_replace('/\D+/', '', (string) $line->wo_number);
        if ($woNumber === '' || (int) $woNumber >= 107000) {
            return false;
        }

        return $this->hasQuantumWorkorderNotFoundMessage($line);
    }

    private function isQuantumWorkorderNotFound(QuantumRoLine $line, string $status): bool
    {
        return in_array($status, ['N/A', 'unresolved'], true)
            && $this->hasQuantumWorkorderNotFoundMessage($line);
    }

    private function hasQuantumWorkorderNotFoundMessage(QuantumRoLine $line): bool
    {
        return str_contains((string) $line->apply_message, 'Workorder not found')
            || str_contains((string) $line->apply_message, 'WO not found');
    }

    private function quantumRecentRowsQuery(): Builder
    {
        return $this->visibleQuantumRoLinesQuery()
            ->orderByRaw('COALESCE(last_seen_at, updated_at, first_seen_at, created_at) DESC')
            ->orderByDesc('updated_at')
            ->orderByDesc('first_seen_at')
            ->orderByDesc('id');
    }

    private function visibleQuantumRoLinesQuery(): Builder
    {
        $query = QuantumRoLine::query();
        $completedLabels = $this->completedQuantumWorkorderLabels();

        if ($completedLabels === []) {
            return $query;
        }

        return $query->where(function (Builder $rows) use ($completedLabels): void {
            $rows->whereNull('quantum_ro_lines.wo_number')
                ->orWhereNotIn(
                    DB::raw('UPPER(TRIM(quantum_ro_lines.wo_number))'),
                    $completedLabels,
                );
        });
    }

    private function completedQuantumWorkorderLabels(): array
    {
        if ($this->completedQuantumWorkorderLabels !== null) {
            return $this->completedQuantumWorkorderLabels;
        }

        $this->completedQuantumWorkorderLabels = Workorder::query()
            ->whereNotNull('done_at')
            ->pluck('number')
            ->flatMap(function ($number): array {
                $digits = trim((string) $number);

                return $digits === '' ? [] : [$digits, 'W' . $digits];
            })
            ->map(fn ($label): string => strtoupper((string) $label))
            ->unique()
            ->values()
            ->all();

        return $this->completedQuantumWorkorderLabels;
    }

    private function applyQuantumBufferSearch(Builder $query, string $mode, string $search): Builder
    {
        $digits = preg_replace('/\D+/', '', $search);
        $term = $digits !== '' ? $digits : trim($search);

        if ($term === '') {
            return $query;
        }

        $like = '%' . $this->escapeLike($term) . '%';

        return $mode === 'ro'
            ? $query->where('ro_number', 'like', $like)
            : $query->where('apply_message', 'like', $like);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function normalizeTdrTravelerGroup(Collection $group): object
    {
        /** @var TdrProcess $leader */
        $leader = $group
            ->first(fn (TdrProcess $row) => ! empty($row->date_start) || ! empty($row->date_finish))
            ?? $group->first(fn (TdrProcess $row) => ! empty($row->vendor_id))
            ?? $group->first();
        $tdr = $leader->tdr;
        $wo = $tdr?->workorder;
        $component = $tdr?->component;
        $travelerGroup = (int) ($leader->traveler_group ?: 1);
        $processIds = $group
            ->flatMap(function (TdrProcess $row): array {
                return TdrProcess::normalizeStoredProcessIds($row->processes);
            })
            ->filter()
            ->unique()
            ->values();
        $processLabels = $processIds->isNotEmpty()
            ? Process::query()->whereIn('id', $processIds)->pluck('process', 'id')
            : collect();
        $children = $group
            ->sortBy('sort_order')
            ->values()
            ->map(function (TdrProcess $row) use ($processLabels, $tdr, $travelerGroup): object {
                $values = TdrProcess::normalizeStoredProcessIds($row->processes);
                $labels = collect($values)
                    ->map(fn ($id) => $processLabels->get((int) $id))
                    ->filter()
                    ->values();
                $processId = collect($values)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->first();
                $formParams = ['tdr_process' => $row->id];
                if ($processId) {
                    $formParams['process_id'] = $processId;
                }

                return (object) [
                    'id' => (int) $row->id,
                    'source_key' => $row->tdr?->component_id === null ? 'tdr_std' : 'tdr_part',
                    'process_name' => $row->processName?->name ?? '--',
                    'process_label' => $labels->isNotEmpty() ? $labels->implode(', ') : ($row->processName?->name ?? '--'),
                    'part_number' => $row->tdr?->component?->part_number,
                    'part_name' => $row->tdr?->component?->name,
                    'repair_order' => $row->repair_order,
                    'vendor' => $row->vendor,
                    'form_url' => route('tdr-processes.show', $formParams),
                    'traveler_form_url' => route('tdr-processes.travelForm', ['id' => $tdr->id, 'traveler_group' => $travelerGroup]),
                    'date_start' => $row->date_start,
                    'date_finish' => $row->date_finish,
                    'date_promise' => $row->date_promise,
                ];
            });

        return (object) [
            'id' => (int) $tdr->id,
            'tdr_id' => (int) $tdr->id,
            'workorder_number' => (string) ($wo?->number ?? ''),
            'process_sort_order' => (int) ($group->min('sort_order') ?? 999999),
            'process_row_id' => (int) ($group->min('id') ?? 0),
            'row_key' => 'tdr-' . (int) $tdr->id . '-traveler-' . $travelerGroup,
            'traveler_group' => $travelerGroup,
            'source_key' => 'tdr_traveler',
            'source' => 'Traveler',
            'date_update_url' => route('tdrprocesses.updateTravelerGroupDates', ['tdr' => $tdr, 'traveler_group' => $travelerGroup]),
            'vendor' => $leader->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'part_name' => $component?->name,
            'serial' => $tdr?->serial_number ?: $tdr?->assy_serial_number,
            'process_name' => ($travelerGroup === 1 ? 'Traveler' : 'Traveler ' . $travelerGroup) . ' (' . $children->count() . ')',
            'repair_order' => $leader->repair_order,
            'date_start' => $leader->date_start,
            'date_finish' => $leader->date_finish,
            'date_promise' => $leader->date_promise,
            'changed_at' => $group->max('updated_at'),
            'is_returned' => ! empty($leader->date_finish),
            'is_traveler_group' => true,
            'traveler_children' => $children,
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
            ->whereHas('line.workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('line.workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when((int) ($filters['customer_id'] ?? 0) > 0, function ($q) use ($filters): void {
                $q->whereHas('line.workorder', fn ($wq) => $wq->where('customer_id', (int) $filters['customer_id']));
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
            ->whereHas('workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->when($filters['vendor_id'] > 0, fn ($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->when($filters['status'] === 'open', fn ($q) => $q->whereNotNull('date_start')->whereNull('date_finish'))
            ->when($filters['status'] === 'returned', fn ($q) => $q->whereNotNull('date_finish'))
            ->when($filters['workorder'] !== '', function ($q) use ($filters): void {
                $q->whereHas('workorder', fn ($wq) => $wq->where('number', 'like', '%' . $filters['workorder'] . '%'));
            })
            ->when((int) ($filters['customer_id'] ?? 0) > 0, function ($q) use ($filters): void {
                $q->whereHas('workorder', fn ($wq) => $wq->where('customer_id', (int) $filters['customer_id']));
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
            'tdr_id' => 0,
            'workorder_number' => (string) ($wo?->number ?? ''),
            'process_sort_order' => 999999,
            'process_row_id' => (int) $row->id,
            'source_key' => 'wo_bushing_process',
            'source' => 'Bush',
            'date_update_url' => route('wo_bushing_processes.updateDate', $row),
            'vendor' => $row->vendor,
            'workorder' => $wo,
            'customer' => $wo?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'part_name' => $component?->name,
            'serial' => null,
            'process_name' => $row->process?->process_name?->name ?? $row->process?->process,
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'date_promise' => $row->date_promise,
            'changed_at' => $row->updated_at,
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
            'tdr_id' => 0,
            'workorder_number' => (string) ($row->workorder?->number ?? ''),
            'process_sort_order' => 999999,
            'process_row_id' => (int) $row->id,
            'source_key' => 'wo_bushing_batch',
            'source' => 'Bush',
            'date_update_url' => route('wo_bushing_batches.updateDate', $row),
            'vendor' => $row->vendor,
            'workorder' => $row->workorder,
            'customer' => $row->workorder?->customer,
            'ipl_num' => $component?->ipl_num,
            'part_number' => $component?->part_number,
            'part_name' => $component?->name,
            'serial' => null,
            'process_name' => ($row->process?->process_name?->name ?? $row->process?->process ?? 'Bushing batch') . ' / Batch',
            'repair_order' => $row->repair_order,
            'date_start' => $row->date_start,
            'date_finish' => $row->date_finish,
            'date_promise' => $row->date_promise,
            'changed_at' => $row->updated_at,
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
        $stdProcessNameIds = $this->stdProcessNameIds();
        $tdrBaseQuery = TdrProcess::query()
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('tdr.workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->when($stdProcessNameIds->isNotEmpty(), fn ($q) => $q->whereNotIn('process_names_id', $stdProcessNameIds));

        $tdrCount = (clone $tdrBaseQuery)
            ->where(function ($q): void {
                $q->whereNull('in_traveler')->orWhere('in_traveler', false);
            })
            ->count();

        $tdrTravelerGroupCount = (clone $tdrBaseQuery)
            ->where('in_traveler', true)
            ->get(['tdrs_id', 'traveler_group'])
            ->groupBy(fn (TdrProcess $row): string => ((int) $row->tdrs_id) . ':' . ((int) ($row->traveler_group ?: 1)))
            ->count();

        $stdCount = WorkorderStdProcess::query()
            ->where(function ($q): void {
                $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
            })
            ->whereHas('workorder', fn (Builder $workorder) => $workorder->notCompleted())
            ->count();

        $bushingProcessCount = 0;
        if (Schema::hasColumn('wo_bushing_processes', 'vendor_id')) {
            $bushingProcessCount = WoBushingProcess::query()
                ->where(function ($q): void {
                    $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
                })
                ->whereHas('line.workorder', fn (Builder $workorder) => $workorder->notCompleted())
                ->count();
        }

        $bushingBatchCount = 0;
        if (Schema::hasColumn('wo_bushing_batches', 'vendor_id')) {
            $bushingBatchCount = WoBushingBatch::query()
                ->where(function ($q): void {
                    $q->whereNotNull('date_start')->orWhereNotNull('date_finish');
                })
                ->whereHas('workorder', fn (Builder $workorder) => $workorder->notCompleted())
                ->count();
        }

        return $tdrCount + $tdrTravelerGroupCount + $stdCount + $bushingProcessCount + $bushingBatchCount;
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
