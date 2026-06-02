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
use App\Models\WoBushingProcess;
use App\Services\WoBushingRelationalSync;
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WoBushingController extends Controller
{
    public function __construct(
        private WoBushingRelationalSync $woBushingSync
    ) {
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

        return WoBushingBatch::query()
            ->where('workorder_id', $woBushing->workorder_id)
            ->whereHas('woBushingProcesses')
            ->exists();
    }

    /**
     * The bushing SP form is printed from real batches only; loose single bushing
     * processes stay visible in the tab but do not create a print form.
     *
     * @return list<array<string, mixed>>
     */
    private function buildBushingSpecProcessGroups(Workorder $workorder): array
    {
        $labelsByProcess = $this->batchLabelsByProcessForWorkorder((int) $workorder->id);
        $labelMap = self::bushingProcessPrintLabels();
        $sortOrder = array_flip(array_keys($labelMap));

        $groups = [];
        $batches = WoBushingBatch::query()
            ->where('workorder_id', $workorder->id)
            ->whereHas('woBushingProcesses')
            ->with([
                'process.process_name',
                'woBushingProcesses.line.component',
            ])
            ->orderBy('id')
            ->get();

        foreach ($batches as $batch) {
            $key = $this->resolveBatchProcessKey($batch);
            $processLabel = $labelMap[$key] ?? null;
            if (! $processLabel) {
                continue;
            }

            $components = [];
            $totalQty = 0;
            foreach ($batch->woBushingProcesses as $woProcess) {
                $component = $woProcess->line?->component;
                if (! $component) {
                    continue;
                }

                $qty = max(1, (int) ($woProcess->qty ?: ($woProcess->line?->qty ?? 1)));
                $components[] = [
                    'component' => $component,
                    'qty' => $qty,
                ];
                $totalQty += $qty;
            }

            if ($components === []) {
                continue;
            }

            $groups[] = [
                'batch_id' => (int) $batch->id,
                'batch_label' => $labelsByProcess[$key][(int) $batch->id] ?? 'B?',
                'process_key' => $key,
                'processes' => [$processLabel],
                'components' => $components,
                'total_qty' => $totalQty,
                'process_numbers' => [$processLabel => 1],
            ];
        }

        usort($groups, function (array $left, array $right) use ($sortOrder): int {
            $leftOrder = $sortOrder[$left['process_key'] ?? ''] ?? 999;
            $rightOrder = $sortOrder[$right['process_key'] ?? ''] ?? 999;

            return ($leftOrder <=> $rightOrder)
                ?: ((int) ($left['batch_id'] ?? 0) <=> (int) ($right['batch_id'] ?? 0));
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

        // Check if WoBushing already exists for this workorder
        $existingWoBushing = WoBushing::where('workorder_id', $workorderId)->first();
        $isAjax = $request->ajax();

        if ($existingWoBushing) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => __('Bushings data already exists for this Work Order. Please use Edit to modify.')]);
            }
            return redirect()->route('wo_bushings.show', $workorderId)
                ->with('warning', 'Bushings data already exists for this Work Order. Please use Edit to modify.');
        }

        $bushDataArray = $this->woBushingSync->buildBushDataArrayFromGroups($groupBushingsData);

        if (empty($bushDataArray)) {
            if ($isAjax ?? $request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one component before submitting.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one component before submitting.')
                ->withInput();
        }

        $woBushing = WoBushing::create([
            'workorder_id' => $workorderId,
        ]);
        $this->woBushingSync->syncFromGroupBushings($woBushing, $groupBushingsData);

        if ($isAjax ?? $request->ajax()) {
            return response()->json(['success' => true]);
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
        $groupBushingsData = $request->group_bushings ?? [];

        if (empty($groupBushingsData)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one group before submitting.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one group before submitting.')
                ->withInput();
        }

        $bushDataArray = $this->woBushingSync->buildBushDataArrayFromGroups($groupBushingsData);

        if (empty($bushDataArray)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => __('Please select at least one component in the selected groups.')]);
            }
            return redirect()->back()
                ->with('error', 'Please select at least one component in the selected groups.')
                ->withInput();
        }

        $this->woBushingSync->syncFromGroupBushings($woBushing, $groupBushingsData);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
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

        if (empty($processName->process_sheet_name)) {
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
            ])->pluck('id', 'name');

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

        return response()->json(['success' => true]);
    }

    public function ungroupBatch(Request $request, WoBushing $woBushing)
    {
        $data = $request->validate([
            'wo_bushing_process_ids' => ['required', 'array', 'min:1'],
            'wo_bushing_process_ids.*' => ['integer', 'exists:wo_bushing_processes,id'],
        ]);

        $ids = collect($data['wo_bushing_process_ids'])->map(fn ($v) => (int) $v)->unique()->values();

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

        return response()->json(['success' => true]);
    }
}
