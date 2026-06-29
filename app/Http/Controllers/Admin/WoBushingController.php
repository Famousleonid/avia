<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WoBushing;
use App\Models\Workorder;
use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\Vendor;
use App\Models\Manual;
use App\Models\WoBushingBatch;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use App\Services\WoBushingRelationalSync;
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WoBushingController extends Controller
{
    public function __construct(
        private WoBushingRelationalSync $woBushingSync
    ) {
    }

    private function bushingSnapshot(?WoBushing $woBushing): array
    {
        if (! $woBushing || ! $woBushing->exists) {
            return [
                'line_count' => 0,
                'total_qty' => 0,
                'process_count' => 0,
                'rows' => [],
            ];
        }

        $woBushing->load([
            'lines.component:id,ipl_num,part_number',
            'lines.processes.process.process_name:id,name',
        ]);

        $rows = [];
        $totalQty = 0;
        $processCount = 0;

        foreach ($woBushing->lines as $line) {
            $processes = $line->processes
                ->map(function (WoBushingProcess $row): string {
                    $processName = trim((string) ($row->process?->process_name?->name ?? ''));
                    $processText = trim((string) ($row->process?->process ?? ''));

                    return trim(($processName !== '' ? $processName . ': ' : '') . ($processText !== '' ? $processText : 'Process #' . $row->process_id));
                })
                ->filter()
                ->values()
                ->all();

            $totalQty += (int) $line->qty;
            $processCount += count($processes);

            $rows[] = [
                'line_id' => (int) $line->id,
                'component_id' => (int) $line->component_id,
                'ipl' => (string) ($line->component?->ipl_num ?? ''),
                'part_number' => (string) ($line->component?->part_number ?? ''),
                'qty' => (int) $line->qty,
                'processes' => $processes,
            ];
        }

        return [
            'wo_bushing_id' => (int) $woBushing->id,
            'line_count' => count($rows),
            'total_qty' => $totalQty,
            'process_count' => $processCount,
            'rows' => $rows,
        ];
    }

    private function payloadBushingSummary(array $bushDataArray): array
    {
        $rows = [];
        $totalQty = 0;
        $processCount = 0;

        foreach ($bushDataArray as $row) {
            $processes = $row['processes'] ?? [];
            $rowProcessCount = 0;

            foreach (['machining', 'stress_relief', 'passivation', 'cad', 'anodizing', 'xylan'] as $field) {
                if (! empty($processes[$field])) {
                    $rowProcessCount++;
                }
            }

            $ndtCount = is_array($processes['ndt'] ?? null)
                ? count(array_filter($processes['ndt']))
                : (! empty($processes['ndt'] ?? null) ? 1 : 0);
            $rowProcessCount += $ndtCount;

            $qty = (int) ($row['qty'] ?? 0);
            $totalQty += $qty;
            $processCount += $rowProcessCount;

            $rows[] = [
                'component_id' => (int) ($row['bushing'] ?? 0),
                'qty' => $qty,
                'need_processes' => (bool) ($row['need_processes'] ?? false),
                'process_count' => $rowProcessCount,
            ];
        }

        return [
            'line_count' => count($rows),
            'total_qty' => $totalQty,
            'process_count' => $processCount,
            'rows' => $rows,
        ];
    }

    private function bushingSummaryText(array $snapshot): string
    {
        return sprintf(
            '%d row(s), qty %d, %d process row(s)',
            (int) ($snapshot['line_count'] ?? 0),
            (int) ($snapshot['total_qty'] ?? 0),
            (int) ($snapshot['process_count'] ?? 0)
        );
    }

    private function logBushingSaveResult(
        Workorder $workorder,
        string $action,
        string $description,
        ?array $before,
        array $after,
        array $payloadSummary,
        string $status = 'success',
        ?string $message = null
    ): void {
        $beforeSummary = $before ? $this->bushingSummaryText($before) : null;
        $afterSummary = $this->bushingSummaryText($after);
        $actionLabel = ucfirst($action);

        activity('workorder')
            ->causedBy(auth()->user())
            ->performedOn($workorder)
            ->event($status === 'success' ? $action : 'bushing_' . $status)
            ->withProperties(array_filter([
                'source' => 'wo_bushings',
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'payload_summary' => $payloadSummary,
                'snapshot_before' => $before,
                'snapshot_after' => $after,
                'attributes' => [
                    'bushing_save' => $message ?: ($actionLabel . ': ' . $afterSummary),
                ],
                'old' => [
                    'bushing_save' => $beforeSummary ? 'Before: ' . $beforeSummary : null,
                ],
            ], fn ($value) => $value !== null))
            ->log($description);
    }

    private function countTdrSpecProcessPages(Workorder $workorder): int
    {
        $quarantineProcessNameId = ProcessName::where('name', 'Quarantine')->value('id');
        $columns = Tdr::where('workorder_id', $workorder->id)
            ->where('use_process_forms', true)
            ->with('tdrProcesses:id,tdrs_id,process_names_id')
            ->get()
            ->sum(function (Tdr $tdr) use ($quarantineProcessNameId): int {
                if ($quarantineProcessNameId && $tdr->tdrProcesses->contains('process_names_id', (int) $quarantineProcessNameId)) {
                    return 2;
                }

                return 1;
            });

        return max(1, (int) ceil($columns / 6));
    }

    /**
     * Process name rows that share the Bushing "Stress Relief" column (bake vs plain stress relief).
     *
     * @return list<string>
     */
    private static function stressReliefProcessNames(): array
    {
        return ['Bake (Stress relief)', 'Stress Relief'];
    }

    /**
     * Bushing setup is limited to these two NDT variants.
     *
     * @return list<string>
     */
    private static function bushingNdtProcessNames(): array
    {
        return ['NDT-1', 'NDT-4'];
    }

    private function resolveProcessKey(?string $processName, ?string $processCode = null): string
    {
        return WoBushingProcessColumnKey::resolve(
            (string) $processName,
            (string) ($processCode ?? '')
        );
    }

    /**
     * Labels used by the bushing special process print form.
     *
     * @return array<string, string>
     */
    private static function bushingProcessPrintLabels(): array
    {
        return [
            'machining' => 'Machining',
            'stress_relief' => 'Bake (Stress relief)',
            'ndt' => 'NDT',
            'passivation' => 'Passivation',
            'cad' => 'CAD',
            'anodizing' => 'Anodizing',
            'xylan' => 'Xylan',
        ];
    }

    private function resolveBatchProcessKey(WoBushingBatch $batch): string
    {
        $stored = trim((string) ($batch->process_column_key ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        return WoBushingProcessColumnKey::fromProcess($batch->process);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function batchLabelsByProcessForWorkorder(int $workorderId): array
    {
        $batches = WoBushingBatch::query()
            ->where('workorder_id', $workorderId)
            ->whereHas('woBushingProcesses')
            ->with('process.process_name')
            ->orderBy('id')
            ->get(['id', 'workorder_id', 'process_id', 'process_column_key']);

        $idsByProcess = [];
        foreach ($batches as $batch) {
            $key = $this->resolveBatchProcessKey($batch);
            if (! array_key_exists($key, self::bushingProcessPrintLabels())) {
                continue;
            }
            $idsByProcess[$key][] = (int) $batch->id;
        }

        $labels = [];
        foreach (array_keys(self::bushingProcessPrintLabels()) as $key) {
            $ids = $idsByProcess[$key] ?? [];
            sort($ids, SORT_NUMERIC);
            foreach ($ids as $idx => $id) {
                $labels[$key][$id] = 'B' . ($idx + 1);
            }
        }

        return $labels;
    }

    private function hasBushingSpecProcessBatches(?WoBushing $woBushing): bool
    {
        if (! $woBushing) {
            return false;
        }

        return WoBushingProcess::query()
            ->whereHas('line', fn ($query) => $query->where('wo_bushing_id', $woBushing->id))
            ->with('process.process_name')
            ->get()
            ->contains(fn (WoBushingProcess $process): bool => array_key_exists(
                WoBushingProcessColumnKey::fromProcess($process->process),
                self::bushingProcessPrintLabels()
            ));
    }

    /**
     * The bushing SP form is grouped by process route.
     * Batches are only operational grouping in the tab and must not split print columns.
     *
     * @return list<array<string, mixed>>
     */
    private function buildBushingSpecProcessGroups(Workorder $workorder): array
    {
        $labelMap = self::bushingProcessPrintLabels();
        $sortOrder = array_flip(array_keys($labelMap));
        $partNumbersPerCell = 6;
        $maxPartNumberCellsPerColumn = 7;
        $maxPartNumbersPerColumn = $partNumbersPerCell * $maxPartNumberCellsPerColumn;

        $groupBuckets = [];
        $lines = WoBushingLine::query()
            ->where('workorder_id', $workorder->id)
            ->with([
                'component',
                'processes.process.process_name',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($lines as $line) {
            $component = $line->component;
            if (! $component) {
                continue;
            }

            $processRows = [];
            foreach ($line->processes as $woProcess) {
                $key = WoBushingProcessColumnKey::fromProcess($woProcess->process);
                if (! array_key_exists($key, $labelMap)) {
                    continue;
                }

                $processRows[] = [
                    'key' => $key,
                    'process_id' => (int) $woProcess->process_id,
                    'order' => $sortOrder[$key] ?? 999,
                ];
            }

            if ($processRows === []) {
                continue;
            }

            usort($processRows, function (array $left, array $right): int {
                return ((int) $left['order'] <=> (int) $right['order'])
                    ?: ((int) $left['process_id'] <=> (int) $right['process_id']);
            });

            $processSignature = implode('|', array_values(array_unique(array_map(
                fn (array $row): string => $row['key'] . ':' . $row['process_id'],
                $processRows
            ))));
            $partNumber = trim((string) $component->part_number);
            $signature = $processSignature;

            if (! isset($groupBuckets[$signature])) {
                $groupBuckets[$signature] = [
                    'process_keys' => [],
                    'components_by_line' => [],
                    'part_numbers' => [],
                    'min_process_order' => 999,
                    'min_line_order' => (int) ($line->sort_order ?? 0),
                    'min_line_id' => (int) $line->id,
                ];
            }

            $bucket = &$groupBuckets[$signature];
            foreach ($processRows as $row) {
                $bucket['process_keys'][$row['key']] = true;
                $bucket['min_process_order'] = min((int) $bucket['min_process_order'], (int) $row['order']);
            }
            $bucket['min_line_order'] = min((int) $bucket['min_line_order'], (int) ($line->sort_order ?? 0));
            $bucket['min_line_id'] = min((int) $bucket['min_line_id'], (int) $line->id);
            $normalizedPartNumber = mb_strtoupper($partNumber);
            if ($normalizedPartNumber !== '' && ! isset($bucket['part_numbers'][$normalizedPartNumber])) {
                $bucket['part_numbers'][$normalizedPartNumber] = [
                    'part_number' => $partNumber,
                    'sort_order' => (int) ($line->sort_order ?? 0),
                    'line_id' => (int) $line->id,
                ];
            }
            $bucket['components_by_line'][(int) $line->id] = [
                'line_id' => (int) $line->id,
                'component_id' => (int) $component->id,
                'component' => $component,
                'qty' => max(1, (int) ($line->qty ?? 1)),
                'sort_order' => (int) ($line->sort_order ?? 0),
            ];
            unset($bucket);
        }

        $groups = [];
        foreach ($groupBuckets as $bucket) {
            $processKeys = array_keys($bucket['process_keys']);
            usort($processKeys, fn (string $left, string $right): int => ($sortOrder[$left] ?? 999) <=> ($sortOrder[$right] ?? 999));

            $processes = [];
            $processNumbers = [];
            foreach ($processKeys as $idx => $processKey) {
                $processLabel = $labelMap[$processKey];
                $processes[] = $processLabel;
                $processNumbers[$processLabel] = $idx + 1;
            }

            $components = array_values($bucket['components_by_line']);
            usort($components, function (array $left, array $right): int {
                return ((int) $left['sort_order'] <=> (int) $right['sort_order'])
                    ?: ((int) $left['line_id'] <=> (int) $right['line_id']);
            });

            $partNumbers = array_values($bucket['part_numbers']);
            usort($partNumbers, function (array $left, array $right): int {
                return ((int) $left['sort_order'] <=> (int) $right['sort_order'])
                    ?: ((int) $left['line_id'] <=> (int) $right['line_id'])
                    ?: strnatcasecmp((string) $left['part_number'], (string) $right['part_number']);
            });

            $partNumberColumns = array_chunk($partNumbers, $maxPartNumbersPerColumn);
            if ($partNumberColumns === []) {
                $partNumberColumns = [[]];
            }

            foreach ($partNumberColumns as $columnIndex => $partNumberColumn) {
                $columnPartNumbers = array_column($partNumberColumn, 'part_number');
                $allowedPartNumbers = array_flip(array_map(
                    fn (string $partNumber): string => mb_strtoupper(trim($partNumber)),
                    $columnPartNumbers
                ));
                $columnComponents = $columnPartNumbers === []
                    ? $components
                    : array_values(array_filter(
                        $components,
                        fn (array $entry): bool => isset($allowedPartNumbers[mb_strtoupper(trim((string) ($entry['component']->part_number ?? '')))])
                    ));

                $groups[] = [
                    'batch_id' => 0,
                    'batch_label' => '',
                    'process_key' => $processKeys[0] ?? '',
                    'process_order' => (int) $bucket['min_process_order'],
                    'line_order' => (int) $bucket['min_line_order'],
                    'line_id' => (int) $bucket['min_line_id'],
                    'part_number' => (string) ($columnPartNumbers[0] ?? ''),
                    'part_numbers' => $columnPartNumbers,
                    'part_number_cells' => array_chunk($columnPartNumbers, $partNumbersPerCell),
                    'processes' => $processes,
                    'components' => $columnComponents,
                    'total_qty' => array_sum(array_map(fn (array $entry): int => (int) $entry['qty'], $columnComponents)),
                    'process_numbers' => $processNumbers,
                    'split_index' => $columnIndex,
                ];
            }
        }

        usort($groups, function (array $left, array $right) use ($sortOrder): int {
            $leftOrder = $left['line_order'] ?? 999;
            $rightOrder = $right['line_order'] ?? 999;
            $leftProcessOrder = $left['process_order'] ?? ($sortOrder[$left['process_key'] ?? ''] ?? 999);
            $rightProcessOrder = $right['process_order'] ?? ($sortOrder[$right['process_key'] ?? ''] ?? 999);

            return ($leftOrder <=> $rightOrder)
                ?: ($leftProcessOrder <=> $rightProcessOrder)
                ?: strnatcasecmp((string) ($left['part_number'] ?? ''), (string) ($right['part_number'] ?? ''))
                ?: ((int) ($left['split_index'] ?? 0) <=> (int) ($right['split_index'] ?? 0))
                ?: ((int) ($left['line_id'] ?? 0) <=> (int) ($right['line_id'] ?? 0));
        });

        return array_values($groups);
    }

    private function buildProcessAssignments(?WoBushing $woBushing): array
    {
        if (! $woBushing) {
            return [];
        }

        $lines = $woBushing->lines()->with([
            'processes.process.process_name',
            'processes.batch',
        ])->get();

        $rows = [];
        foreach ($lines as $line) {
            $lineId = (int) $line->id;
            foreach ($line->processes as $wp) {
                $name = $wp->process?->process_name?->name;
                $code = $wp->process?->process;
                $key = $this->resolveProcessKey($name, $code);
                if ($key === 'other') {
                    continue;
                }
                $batch = $wp->batch;
                $dateStartSet = $batch
                    ? ! empty($batch->date_start)
                    : ! empty($wp->date_start);
                $dateFinishSet = $batch
                    ? ! empty($batch->date_finish)
                    : ! empty($wp->date_finish);

                $rows[$lineId][$key] = [
                    'wo_process_id' => (int) $wp->id,
                    'batch_id' => $wp->batch_id ? (int) $wp->batch_id : null,
                    'locked' => $dateStartSet,
                    'finished' => $dateStartSet && $dateFinishSet,
                ];
            }
        }

        return $rows;
    }

    private function bushingGroupsForWorkorder(Workorder $workorder)
    {
        return $this->bushingGroupsForManual((int) $workorder->unit->manual_id);
    }

    private function bushingGroupsForManual(int $manualId)
    {
        $bushings = Component::where('manual_id', $manualId)
            ->where('is_bush', 1)
            ->get()
            ->sort(function (Component $left, Component $right): int {
                $iplCompare = StdProcess::compareIplValues(
                    (string) ($left->ipl_num ?? ''),
                    (string) ($right->ipl_num ?? '')
                );

                if ($iplCompare !== 0) {
                    return $iplCompare;
                }

                $groupCompare = StdProcess::compareIplValues(
                    (string) ($left->bush_ipl_num ?? ''),
                    (string) ($right->bush_ipl_num ?? '')
                );

                if ($groupCompare !== 0) {
                    return $groupCompare;
                }

                $partCompare = strnatcasecmp((string) $left->part_number, (string) $right->part_number);

                return $partCompare !== 0
                    ? $partCompare
                    : ((int) $left->id) <=> ((int) $right->id);
            })
            ->values();

        return $bushings
            ->groupBy(fn (Component $component) => (string) ($component->bush_ipl_num ?? ''))
            ->sort(function ($leftGroup, $rightGroup): int {
                $left = $leftGroup->first();
                $right = $rightGroup->first();

                return StdProcess::compareIplValues(
                    (string) ($left?->ipl_num ?? ''),
                    (string) ($right?->ipl_num ?? '')
                );
            });
    }

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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;

        $bushings = $this->bushingGroupsForWorkorder($current_wo);

        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->whereIn('name', self::bushingNdtProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Get all manuals for dropdown
        $manuals = Manual::all();

        return view('admin.wo_bushings.create', compact(
            'current_wo',
            'bushings',
            'machiningProcesses',
            'stressReliefProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'manuals'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'group_bushings' => 'array',
        ]);

        $workorderId = $request->workorder_id;
        $groupBushingsData = $request->group_bushings ?? [];
        $workorder = Workorder::findOrFail($workorderId);

        // Check if WoBushing already exists for this workorder
        $existingWoBushing = WoBushing::where('workorder_id', $workorderId)->first();
        $isAjax = $request->ajax();

        if ($existingWoBushing) {
            $snapshot = $this->bushingSnapshot($existingWoBushing);
            $this->logBushingSaveResult(
                $workorder,
                'create',
                'Bushing data create rejected',
                $snapshot,
                $snapshot,
                [],
                'rejected',
                'Create rejected: bushing data already exists for this Work Order.'
            );

            if ($isAjax) {
                return response()->json(['success' => false, 'message' => __('Bushings data already exists for this Work Order. Please use Edit to modify.')]);
            }
            return redirect()->route('wo_bushings.show', $workorderId)
                ->with('warning', 'Bushings data already exists for this Work Order. Please use Edit to modify.');
        }

        $bushDataArray = $this->woBushingSync->buildBushDataArrayFromGroups($groupBushingsData);
        $payloadSummary = $this->payloadBushingSummary($bushDataArray);

        if (empty($bushDataArray)) {
            $this->logBushingSaveResult(
                $workorder,
                'create',
                'Bushing data create rejected',
                null,
                $this->bushingSnapshot(null),
                $payloadSummary,
                'rejected',
                'Create rejected: no selected bushing components were received.'
            );

            if ($isAjax ?? $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one component before submitting.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one component before submitting.')
                ->withInput();
        }

        try {
            $woBushing = DB::transaction(function () use ($workorderId, $groupBushingsData) {
                $woBushing = WoBushing::create([
                    'workorder_id' => $workorderId,
                ]);

                $this->woBushingSync->syncFromGroupBushings($woBushing, $groupBushingsData);

                return $woBushing;
            });
            $woBushing->refresh();
            $after = $this->bushingSnapshot($woBushing);
            $this->logBushingSaveResult(
                $workorder,
                'created',
                'Bushing data created',
                null,
                $after,
                $payloadSummary
            );
        } catch (Throwable $e) {
            $this->logBushingSaveResult(
                $workorder,
                'create',
                'Bushing data create failed',
                null,
                $this->bushingSnapshot(null),
                $payloadSummary,
                'failed',
                'Create failed: ' . $e->getMessage()
            );

            throw $e;
        }

        if ($isAjax ?? $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Bushing data saved (:rows rows, :processes process rows).', [
                    'rows' => $after['line_count'] ?? 0,
                    'processes' => $after['process_count'] ?? 0,
                ]),
                'line_count' => $after['line_count'] ?? 0,
                'process_count' => $after['process_count'] ?? 0,
            ]);
        }
        return redirect()->route('wo_bushings.show', $workorderId)
            ->with('success', 'Bushings data created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;

        $bushings = $this->bushingGroupsForWorkorder($current_wo);

        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->whereIn('name', self::bushingNdtProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->with('lines')->first();
        $linesExist = $woBushing && $woBushing->lines->isNotEmpty();
        $bushData = $linesExist ? $this->woBushingSync->resolveBushDataForViews($woBushing) : [];
        $processAssignments = $this->buildProcessAssignments($woBushing);
        $hasBushingSpecProcessBatches = $this->hasBushingSpecProcessBatches($woBushing);

        // Get all vendors
        $vendors = Vendor::all();

        return view('admin.wo_bushings.show', compact(
            'current_wo',
            'bushings',
            'machiningProcesses',
            'stressReliefProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'woBushing',
            'bushData',
            'linesExist',
            'processAssignments',
            'hasBushingSpecProcessBatches',
            'vendors'
        ));
    }

    /**
     * Return partial HTML for Bushing Processes tab (embedded in TDR show).
     *
     * @param int $workorder_id
     * @return \Illuminate\Contracts\View\View
     */
    public function partial($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        $bushings = $this->bushingGroupsForWorkorder($current_wo);

        $machiningProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Machining'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $stressReliefProcesses = Process::whereHas('process_name', fn($q) => $q->whereIn('name', self::stressReliefProcessNames()))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $ndtProcesses = Process::whereHas('process_name', fn($q) => $q->whereIn('name', self::bushingNdtProcessNames()))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $passivationProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Passivation'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $cadProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Cad plate'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $anodizingProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Anodizing'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $xylanProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Xylan coating'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->with('lines')->first();
        $linesExist = $woBushing && $woBushing->lines->isNotEmpty();
        $bushData = $linesExist ? $this->woBushingSync->resolveBushDataForViews($woBushing) : [];
        $processAssignments = $this->buildProcessAssignments($woBushing);
        $hasBushingSpecProcessBatches = $this->hasBushingSpecProcessBatches($woBushing);

        $vendors = Vendor::all();
        $manuals = Manual::all();

        $returnTo = route('tdrs.show', ['id' => $current_wo->id]);

        return view('admin.wo_bushings.partial', compact(
            'current_wo',
            'bushings',
            'returnTo',
            'manuals',
            'machiningProcesses',
            'stressReliefProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'woBushing',
            'bushData',
            'linesExist',
            'processAssignments',
            'hasBushingSpecProcessBatches',
            'vendors'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $woBushing = WoBushing::findOrFail($id);
        $current_wo = $woBushing->workorder;
        $manual_id = $current_wo->unit->manual_id;

        $bushings = $this->bushingGroupsForWorkorder($current_wo);

        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->whereIn('name', self::bushingNdtProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        $woBushing->load('lines');
        $linesExist = $woBushing->lines->isNotEmpty();
        $bushData = $this->woBushingSync->resolveBushDataForViews($woBushing);

        return view('admin.wo_bushings.edit', compact(
            'current_wo',
            'woBushing',
            'bushings',
            'machiningProcesses',
            'stressReliefProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'bushData',
            'linesExist'
        ));
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
        $request->validate([
            'group_bushings' => 'array',
        ]);

        $woBushing = WoBushing::findOrFail($id);
        $workorder = $woBushing->workorder ?: Workorder::findOrFail($woBushing->workorder_id);
        $groupBushingsData = $request->group_bushings ?? [];
        $before = $this->bushingSnapshot($woBushing);

        if (empty($groupBushingsData)) {
            $this->logBushingSaveResult(
                $workorder,
                'update',
                'Bushing data update rejected',
                $before,
                $before,
                [],
                'rejected',
                'Update rejected: no bushing groups were received.'
            );

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one group before submitting.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one group before submitting.')
                ->withInput();
        }

        $bushDataArray = $this->woBushingSync->buildBushDataArrayFromGroups($groupBushingsData);
        $payloadSummary = $this->payloadBushingSummary($bushDataArray);

        if (empty($bushDataArray)) {
            $this->logBushingSaveResult(
                $workorder,
                'update',
                'Bushing data update rejected',
                $before,
                $before,
                $payloadSummary,
                'rejected',
                'Update rejected: no selected bushing components were received.'
            );

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one component in the selected groups.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one component in the selected groups.')
                ->withInput();
        }

        try {
            $this->woBushingSync->syncFromGroupBushings($woBushing, $groupBushingsData);
            $woBushing->refresh();
            $after = $this->bushingSnapshot($woBushing);
            $this->logBushingSaveResult(
                $workorder,
                'updated',
                'Bushing data updated',
                $before,
                $after,
                $payloadSummary
            );
        } catch (Throwable $e) {
            $this->logBushingSaveResult(
                $workorder,
                'update',
                'Bushing data update failed',
                $before,
                $before,
                $payloadSummary,
                'failed',
                'Update failed: ' . $e->getMessage()
            );

            throw $e;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Bushing data saved (:rows rows, :processes process rows).', [
                    'rows' => $after['line_count'] ?? 0,
                    'processes' => $after['process_count'] ?? 0,
                ]),
                'line_count' => $after['line_count'] ?? 0,
                'process_count' => $after['process_count'] ?? 0,
            ]);
        }
        return redirect()->route('wo_bushings.show', $woBushing->workorder_id)
            ->with('success', 'Bushings data updated successfully!');
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
     * Display form for specific process name.
     *
     * @param  int  $id
     * @param  int  $processNameId
     * @return \Illuminate\Contracts\View\View
     */
    public function processesForm($id, $processNameId, Request $request)
    {
        $woBushing = WoBushing::findOrFail($id);
        $current_wo = Workorder::findOrFail($woBushing->workorder_id);
        $processName = ProcessName::findOrFail($processNameId);

        if (!ProcessName::canPrintProcessForm($processName)) {
            return redirect()->back()->with('error', __('There is no form for this process.'));
        }

        $vendorId = $request->input('vendor_id');
        $selectedVendor = null;
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = \App\Models\ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'wo_bushing' => $woBushing,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName,
            'selectedVendor' => $selectedVendor,
            'machining_header_manual_libs' => ProcessName::isMachiningPrintedForm($processName)
                ? Manual::orderedLibValuesForManualIds(Manual::manualIdsForWorkorder((int) $current_wo->id))
                : [],
        ];

        $bushData = $this->woBushingSync->resolveBushDataForViews($woBushing);
        $processKey = $this->resolveProcessKey($processName->name, $processName->process_sheet_name);
        $batchLabelsByProcess = $this->batchLabelsByProcessForWorkorder((int) $current_wo->id);
        $batchMetaByComponentAndProcess = [];
        $lineComponentIds = $woBushing->lines()
            ->pluck('component_id', 'id')
            ->mapWithKeys(fn ($componentId, $lineId) => [(int) $lineId => (int) $componentId])
            ->all();

        foreach ($this->buildProcessAssignments($woBushing) as $lineId => $assignments) {
            $componentId = $lineComponentIds[(int) $lineId] ?? 0;
            if ($componentId <= 0 || ! is_array($assignments)) {
                continue;
            }

            foreach ($assignments as $assignmentKey => $assignment) {
                $batchId = (int) ($assignment['batch_id'] ?? 0);
                if ($batchId <= 0) {
                    continue;
                }

                $batchMetaByComponentAndProcess[$componentId][$assignmentKey] = [
                    'batch_id' => $batchId,
                    'batch_label' => $batchLabelsByProcess[$assignmentKey][$batchId] ?? 'B?',
                ];
            }
        }

        $rawBatchIds = $request->input('bushing_batch_ids', []);
        $filterBushingBatchIds = array_values(array_unique(array_filter(array_map(
            'intval',
            is_array($rawBatchIds) ? $rawBatchIds : [$rawBatchIds]
        ))));
        $filterBushingBatchIds = array_slice($filterBushingBatchIds, 0, 1);
        $allowedBatchIds = array_flip($filterBushingBatchIds);

        $filterBushingComponentIds = null;
        if ($request->has('bushing_component_ids')) {
            $raw = $request->input('bushing_component_ids', []);
            $filterBushingComponentIds = array_values(array_unique(array_filter(array_map('intval', is_array($raw) ? $raw : [$raw]))));
        }

        $selectedBatchLabels = [];
        foreach ($filterBushingBatchIds as $batchId) {
            $label = $batchLabelsByProcess[$processKey][$batchId] ?? null;
            if ($label) {
                $selectedBatchLabels[] = $label;
            }
        }
        $viewData['formHeaderRepairOrder'] = implode(', ', array_values(array_unique($selectedBatchLabels)));

        $applyBushingBatchFilter = function (array $rows) use (
            $processKey,
            $batchMetaByComponentAndProcess,
            $allowedBatchIds,
            $filterBushingComponentIds
        ) {
            $allowedComponentIds = $filterBushingComponentIds === null
                ? null
                : array_flip($filterBushingComponentIds);

            $filtered = [];
            foreach ($rows as $row) {
                $c = $row['component'] ?? null;
                if (! $c) {
                    continue;
                }

                if ($allowedComponentIds !== null && ! isset($allowedComponentIds[(int) $c->id])) {
                    continue;
                }

                $batchMeta = $batchMetaByComponentAndProcess[(int) $c->id][$processKey] ?? null;
                if (! $batchMeta) {
                    continue;
                }

                if (! isset($allowedBatchIds[(int) $batchMeta['batch_id']])) {
                    continue;
                }

                $row['batch_id'] = (int) $batchMeta['batch_id'];
                $row['batch_label'] = $batchMeta['batch_label'];
                $filtered[] = $row;
            }

            return array_values($filtered);
        };

        if ($processName->process_sheet_name == 'NDT') {
            $processNames = ProcessName::whereIn('name', [
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->where('print_form', true)->pluck('id', 'name');

            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                'ndt6_name_id' => $processNames['Eddy Current Test'] ?? null,
                'ndt5_name_id' => $processNames['BNI'] ?? null
            ];

            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $ndt_ids)
                ->get();

            $getNdtNumber = function($processName) {
                if (strpos($processName->name, 'NDT-') === 0) {
                    return substr($processName->name, 4);
                } elseif ($processName->name === 'Eddy Current Test') {
                    return '6';
                } elseif ($processName->name === 'BNI') {
                    return '5';
                }
                return substr($processName->name, -1);
            };

            $tableData = [];
            $componentNdtMap = [];

            if ($bushData && is_array($bushData)) {
                foreach ($bushData as $bushItem) {
                    if (isset($bushItem['bushing']) && isset($bushItem['processes']['ndt']) && !empty($bushItem['processes']['ndt'])) {
                        $component = Component::find($bushItem['bushing']);
                        if ($component) {
                            $componentId = $component->id;

                            if (!isset($componentNdtMap[$componentId])) {
                                $componentNdtMap[$componentId] = [
                                    'component' => $component,
                                    'qty' => $bushItem['qty'] ?? 1,
                                    'ndt_numbers' => [],
                                    'processes' => []
                                ];
                            }

                            $ndtProcessIds = is_array($bushItem['processes']['ndt'])
                                ? $bushItem['processes']['ndt']
                                : [$bushItem['processes']['ndt']];

                            foreach ($ndtProcessIds as $ndtProcessId) {
                                $process = Process::find($ndtProcessId);
                                if ($process) {
                                    $specificProcessName = ProcessName::find($process->process_names_id);
                                    if ($specificProcessName) {
                                        $ndtNumber = $getNdtNumber($specificProcessName);
                                        if (!in_array($ndtNumber, $componentNdtMap[$componentId]['ndt_numbers'])) {
                                            $componentNdtMap[$componentId]['ndt_numbers'][] = $ndtNumber;
                                            $componentNdtMap[$componentId]['processes'][] = $process;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach ($componentNdtMap as $componentData) {
                if (empty($componentData['ndt_numbers'])) {
                    continue;
                }

                $ndtNumbers = $componentData['ndt_numbers'];
                usort($ndtNumbers, function($a, $b) {
                    return (int)$a <=> (int)$b;
                });

                $combinedNdtNumber = implode(' / ', $ndtNumbers);

                $firstProcess = !empty($componentData['processes']) ? $componentData['processes'][0] : null;

                $tableData[] = [
                    'component' => $componentData['component'],
                    'wo_bushing' => $woBushing,
                    'qty' => $componentData['qty'],
                    'combined_ndt_number' => $combinedNdtNumber,
                    'process' => $firstProcess,
                    'process_name' => $firstProcess ? ProcessName::find($firstProcess->process_names_id) : null
                ];
            }

            $tableData = $applyBushingBatchFilter($tableData);

            return view('admin.wo_bushings.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'table_data' => $tableData
            ], $ndt_ids));
        }

        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processNameId)
            ->get();

        $tableData = [];
        if ($bushData) {
            foreach ($bushData as $bushItem) {
                if (isset($bushItem['bushing']) && isset($bushItem['processes'])) {
                    $component = Component::find($bushItem['bushing']);
                    if ($component) {
                        $processes = $bushItem['processes'];
                        $processId = null;

                        switch ($processName->name) {
                            case 'Machining':
                                $processId = data_get($processes, 'machining');
                                break;
                            case 'Bake (Stress relief)':
                            case 'Stress Relief':
                                $processId = data_get($processes, 'stress_relief');
                                break;
                            case 'Passivation':
                                $processId = data_get($processes, 'passivation');
                                break;
                            case 'Cad plate':
                                $processId = data_get($processes, 'cad');
                                break;
                            case 'Anodizing':
                                $processId = data_get($processes, 'anodizing');
                                break;
                            case 'Xylan coating':
                                $processId = data_get($processes, 'xylan');
                                break;
                        }

                        if ($processId) {
                            $process = Process::find($processId);
                            if ($process) {
                                $specificProcessName = ProcessName::find($process->process_names_id);
                                $tableData[] = [
                                    'process_name' => $specificProcessName,
                                    'process' => $process,
                                    'component' => $component,
                                    'wo_bushing' => $woBushing,
                                    'qty' => $bushItem['qty'] ?? 1
                                ];
                            }
                        }
                    }
                }
            }
        }

        $tableData = $applyBushingBatchFilter($tableData);

        return view('admin.wo_bushings.processesForm', array_merge($viewData, [
            'process_components' => $process_components,
            'table_data' => $tableData
        ]));
    }

    /**
     * Display special process form for bushings grouped by processes.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function specProcessForm($id)
    {
        $woBushing = WoBushing::findOrFail($id);
        $current_wo = Workorder::findOrFail($woBushing->workorder_id);

        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manuals = \App\Models\Manual::where('id', $manual_id)->get();

        $processGroups = $this->buildBushingSpecProcessGroups($current_wo);

        $spPageOffset = $this->countTdrSpecProcessPages($current_wo);
        $bushingPageCount = max(1, (int) ceil(max(1, count($processGroups)) / 6));
        $combinedSpecPageTotal = $spPageOffset + $bushingPageCount;

        $processNames = ProcessName::whereIn('name', [
            'Machining',
            'Bake (Stress relief)',
            'NDT-1',
            'NDT-4',
            'Eddy Current Test',
            'BNI',
            'Passivation',
            'Cad plate',
            'Anodizing',
            'Xylan coating'
        ])->get();

        return view('admin.wo_bushings.specProcessForm', compact(
            'current_wo',
            'woBushing',
            'components',
            'manuals',
            'manual_id',
            'processGroups',
            'processNames',
            'spPageOffset',
            'combinedSpecPageTotal'
        ));
    }

    /**
     * Get bushings from another manual via AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBushingsFromManual(Request $request)
    {
        $request->validate([
            'manual_id' => 'required|exists:manuals,id',
            'current_manual_id' => 'required|exists:manuals,id',
        ]);

        $manual_id = $request->manual_id;
        $current_manual_id = $request->current_manual_id;

        $bushings = $this->bushingGroupsForManual((int) $manual_id);

        // Get processes for each process type for the current manual (not the selected one)
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->whereIn('name', self::bushingNdtProcessNames());
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($current_manual_id) {
                $query->where('manual_id', $current_manual_id);
            })
            ->with('process_name')
            ->get();

        // Format data for response
        $formattedBushings = [];
        foreach ($bushings as $bushIplNum => $bushingGroup) {
            $groupData = [
                'bush_ipl_num' => $bushIplNum ?: 'no_ipl',
                'components' => []
            ];
            foreach ($bushingGroup as $bushing) {
                $groupData['components'][] = [
                    'id' => $bushing->id,
                    'ipl_num' => $bushing->ipl_num,
                    'part_number' => $bushing->part_number,
                ];
            }
            $formattedBushings[] = $groupData;
        }

        return response()->json([
            'success' => true,
            'bushings' => $formattedBushings,
            'processes' => [
                'machining' => $machiningProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
                'stress_relief' => $stressReliefProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
                'ndt' => $ndtProcesses->map(function($p) {
                    return ['id' => $p->id, 'name' => $p->process_name->name];
                }),
                'passivation' => $passivationProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
                'cad' => $cadProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
                'anodizing' => $anodizingProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
                'xylan' => $xylanProcesses->map(function($p) {
                    return ['id' => $p->id, 'process' => $p->process];
                }),
            ]
        ]);
    }

    public function createBatch(Request $request, WoBushing $woBushing)
    {
        $data = $request->validate([
            'wo_bushing_process_ids' => ['required', 'array', 'min:1'],
            'wo_bushing_process_ids.*' => ['integer', 'exists:wo_bushing_processes,id'],
        ]);

        $ids = collect($data['wo_bushing_process_ids'])->map(fn ($v) => (int) $v)->unique()->values();
        $workorder = $woBushing->workorder ?: Workorder::findOrFail($woBushing->workorder_id);
        $before = $this->bushingSnapshot($woBushing);
        $payloadSummary = [
            'selected_process_ids' => $ids->all(),
            'selected_count' => $ids->count(),
        ];

        try {
            DB::transaction(function () use ($ids, $woBushing) {
                $rows = WoBushingProcess::query()
                    ->whereIn('id', $ids)
                    ->whereHas('line', fn ($q) => $q->where('wo_bushing_id', $woBushing->id))
                    ->with('process')
                    ->lockForUpdate()
                    ->get();

                if ($rows->count() !== $ids->count()) {
                    abort(422, 'Some selected rows are invalid.');
                }

                $columnKeys = $rows->map(fn (WoBushingProcess $wp) => WoBushingProcessColumnKey::fromProcess($wp->process));
                if ($columnKeys->unique()->count() !== 1) {
                    abort(422, 'Batch must contain only one process column (header).');
                }
                $processColumnKey = $columnKeys->first();
                if ($processColumnKey === 'other') {
                    abort(422, 'Invalid process for selected rows.');
                }

                $unavailable = $rows->filter(function (WoBushingProcess $wp) {
                    if (empty($wp->batch_id)) {
                        return false;
                    }
                    $batch = $wp->batch()->first();
                    return ! empty($batch?->date_start);
                });
                if ($unavailable->isNotEmpty()) {
                    abort(422, 'Some selected rows are already sent and locked.');
                }

                $canonicalProcessId = (int) $rows->first()->process_id;

                $batch = WoBushingBatch::create([
                    'workorder_id' => $woBushing->workorder_id,
                    'process_id' => $canonicalProcessId,
                    'process_column_key' => $processColumnKey,
                ]);

                WoBushingProcess::query()
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['batch_id' => $batch->id]);

                WoBushingBatch::query()
                    ->where('workorder_id', $woBushing->workorder_id)
                    ->whereNull('date_start')
                    ->whereDoesntHave('woBushingProcesses')
                    ->delete();
            });

            $woBushing->refresh();
            $after = $this->bushingSnapshot($woBushing);
            $this->logBushingSaveResult(
                $workorder,
                'batch_created',
                'Bushing batch created',
                $before,
                $after,
                $payloadSummary
            );
        } catch (Throwable $e) {
            $this->logBushingSaveResult(
                $workorder,
                'batch_create',
                'Bushing batch create failed',
                $before,
                $before,
                $payloadSummary,
                'failed',
                'Batch create failed: ' . $e->getMessage()
            );

            throw $e;
        }

        return response()->json(['success' => true]);
    }

    public function ungroupBatch(Request $request, WoBushing $woBushing)
    {
        $data = $request->validate([
            'wo_bushing_process_ids' => ['required', 'array', 'min:1'],
            'wo_bushing_process_ids.*' => ['integer', 'exists:wo_bushing_processes,id'],
        ]);

        $ids = collect($data['wo_bushing_process_ids'])->map(fn ($v) => (int) $v)->unique()->values();
        $workorder = $woBushing->workorder ?: Workorder::findOrFail($woBushing->workorder_id);
        $before = $this->bushingSnapshot($woBushing);
        $payloadSummary = [
            'selected_process_ids' => $ids->all(),
            'selected_count' => $ids->count(),
        ];

        try {
            DB::transaction(function () use ($ids, $woBushing) {
                $rows = WoBushingProcess::query()
                    ->whereIn('id', $ids)
                    ->whereHas('line', fn ($q) => $q->where('wo_bushing_id', $woBushing->id))
                    ->with('batch')
                    ->lockForUpdate()
                    ->get();

                if ($rows->count() !== $ids->count()) {
                    abort(422, 'Some selected rows are invalid.');
                }

                $batchIds = $rows->pluck('batch_id')->filter()->unique()->values();

                foreach ($rows as $wp) {
                    if (! $wp->batch_id) {
                        continue;
                    }
                    if (! empty($wp->batch?->date_start)) {
                        abort(422, 'Cannot ungroup sent rows.');
                    }
                    $wp->batch_id = null;
                    $wp->save();
                }

                if ($batchIds->isNotEmpty()) {
                    $stillUsed = WoBushingProcess::query()
                        ->whereIn('batch_id', $batchIds)
                        ->pluck('batch_id')
                        ->filter()
                        ->unique()
                        ->values();
                    $toDelete = $batchIds->diff($stillUsed);
                    if ($toDelete->isNotEmpty()) {
                        WoBushingBatch::query()->whereIn('id', $toDelete)->delete();
                    }
                }
            });

            $woBushing->refresh();
            $after = $this->bushingSnapshot($woBushing);
            $this->logBushingSaveResult(
                $workorder,
                'batch_ungrouped',
                'Bushing batch ungrouped',
                $before,
                $after,
                $payloadSummary
            );
        } catch (Throwable $e) {
            $this->logBushingSaveResult(
                $workorder,
                'batch_ungroup',
                'Bushing batch ungroup failed',
                $before,
                $before,
                $payloadSummary,
                'failed',
                'Batch ungroup failed: ' . $e->getMessage()
            );

            throw $e;
        }

        return response()->json(['success' => true]);
    }
}
