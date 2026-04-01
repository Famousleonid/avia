<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WoBushing;
use App\Models\Workorder;
use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
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

    /**
     * Process name rows that share the Bushing "Stress Relief" column (bake vs plain stress relief).
     *
     * @return list<string>
     */
    private static function stressReliefProcessNames(): array
    {
        return ['Bake (Stress relief)', 'Stress Relief'];
    }

    private function resolveProcessKey(?string $processName, ?string $processCode = null): string
    {
        return WoBushingProcessColumnKey::resolve(
            (string) $processName,
            (string) ($processCode ?? '')
        );
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
            $componentId = (int) $line->component_id;
            foreach ($line->processes as $wp) {
                $name = $wp->process?->process_name?->name;
                $code = $wp->process?->process;
                $key = $this->resolveProcessKey($name, $code);
                if ($key === 'other') {
                    continue;
                }
                $batch = $wp->batch;
                $rows[$componentId][$key] = [
                    'wo_process_id' => (int) $wp->id,
                    'batch_id' => $wp->batch_id ? (int) $wp->batch_id : null,
                    'locked' => ! empty($batch?->date_start),
                ];
            }
        }

        return $rows;
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

        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->get();

        // Sort by numeric part of ipl_num
        $bushingsQuery = $bushingsQuery->sortBy(function ($item) {
            $parts = explode('-', $item->ipl_num);
            $numericPart = preg_replace('/[^0-9]/', '', end($parts));
            return (int)$numericPart;
        });

        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');

        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Stress Relief: процессы для Bake (Stress relief) и Stress Relief
        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
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

        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Anodizing processes - все процессы для 'Anodizing'
        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Xylan processes - все процессы для 'Xylan coating'
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

        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->get();

        // Sort by numeric part of ipl_num
        $bushingsQuery = $bushingsQuery->sortBy(function ($item) {
            $parts = explode('-', $item->ipl_num);
            $numericPart = preg_replace('/[^0-9]/', '', end($parts));
            return (int)$numericPart;
        });

        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');

        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Stress Relief: Bake (Stress relief) и Stress Relief
        $stressReliefProcesses = Process::whereHas('process_name', function ($query) {
                $query->whereIn('name', self::stressReliefProcessNames());
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
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

        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Anodizing processes - все процессы для 'Anodizing'
        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Xylan processes - все процессы для 'Xylan coating'
        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Get existing WoBushing data if available (из нормализованных таблиц или legacy JSON)
        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->with('lines')->first();
        $linesExist = $woBushing && $woBushing->lines->isNotEmpty();
        $bushData = $linesExist ? $this->woBushingSync->resolveBushDataForViews($woBushing) : [];
        $processAssignments = $this->buildProcessAssignments($woBushing);

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

        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->get();

        $bushingsQuery = $bushingsQuery->sortBy(function ($item) {
            $parts = explode('-', $item->ipl_num);
            $numericPart = preg_replace('/[^0-9]/', '', end($parts));
            return (int)$numericPart;
        });

        $bushings = $bushingsQuery->groupBy('bush_ipl_num');

        $machiningProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'Machining'))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $stressReliefProcesses = Process::whereHas('process_name', fn($q) => $q->whereIn('name', self::stressReliefProcessNames()))
            ->whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))
            ->with('process_name')->get();

        $ndtProcesses = Process::whereHas('process_name', fn($q) => $q->where('name', 'LIKE', 'NDT%'))
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

        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->get();

        // Sort by numeric part of ipl_num
        $bushingsQuery = $bushingsQuery->sortBy(function ($item) {
            $parts = explode('-', $item->ipl_num);
            $numericPart = preg_replace('/[^0-9]/', '', end($parts));
            return (int)$numericPart;
        });

        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');

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

        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
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

        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Anodizing processes - все процессы для 'Anodizing'
        $anodizingProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Anodizing');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();

        // Xylan processes - все процессы для 'Xylan coating'
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

        // Получаем vendor_id из запроса
        $vendorId = $request->input('vendor_id');
        $selectedVendor = null;
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Получаем связанные данные
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = \App\Models\ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'wo_bushing' => $woBushing,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName,
            'selectedVendor' => $selectedVendor
        ];

        $bushData = $this->woBushingSync->resolveBushDataForViews($woBushing);

        $filterBushingComponentIds = null;
        if ($request->has('bushing_component_ids')) {
            $raw = $request->input('bushing_component_ids', []);
            $filterBushingComponentIds = array_values(array_unique(array_filter(array_map('intval', is_array($raw) ? $raw : [$raw]))));
        }

        $applyBushingComponentFilter = function (array $rows) use ($filterBushingComponentIds) {
            if ($filterBushingComponentIds === null) {
                return $rows;
            }
            $allowed = array_flip($filterBushingComponentIds);
            return array_values(array_filter($rows, function ($row) use ($allowed) {
                $c = $row['component'] ?? null;
                return $c && isset($allowed[$c->id]);
            }));
        };

        // Обработка NDT формы
        if ($processName->process_sheet_name == 'NDT') {
            // Получаем ID process names одним запросом
            $processNames = ProcessName::whereIn('name', [
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->pluck('id', 'name');

            // Извлекаем ID по именам
            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                'ndt6_name_id' => $processNames['Eddy Current Test'] ?? null,
                'ndt5_name_id' => $processNames['BNI'] ?? null
            ];

            // Получаем NDT processes
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $ndt_ids)
                ->get();

            // Функция для извлечения номера NDT из имени процесса
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

            // Создаем массив данных для отображения в таблице
            // Группируем NDT процессы по компоненту для объединения номеров
            $tableData = [];
            $componentNdtMap = []; // Карта для группировки NDT по компонентам
            
            if ($bushData && is_array($bushData)) {
                foreach ($bushData as $bushItem) {
                    if (isset($bushItem['bushing']) && isset($bushItem['processes']['ndt']) && !empty($bushItem['processes']['ndt'])) {
                        $component = Component::find($bushItem['bushing']);
                        if ($component) {
                            $componentId = $component->id;
                            
                            // Инициализируем запись для компонента, если её еще нет
                            if (!isset($componentNdtMap[$componentId])) {
                                $componentNdtMap[$componentId] = [
                                    'component' => $component,
                                    'qty' => $bushItem['qty'] ?? 1,
                                    'ndt_numbers' => [],
                                    'processes' => []
                                ];
                            }
                            
                            // NDT может быть массивом или одним значением
                            $ndtProcessIds = is_array($bushItem['processes']['ndt']) 
                                ? $bushItem['processes']['ndt'] 
                                : [$bushItem['processes']['ndt']];
                            
                            foreach ($ndtProcessIds as $ndtProcessId) {
                                $process = Process::find($ndtProcessId);
                                if ($process) {
                                    // Получаем конкретный process_name для правильного отображения номера
                                    $specificProcessName = ProcessName::find($process->process_names_id);
                                    if ($specificProcessName) {
                                        $ndtNumber = $getNdtNumber($specificProcessName);
                                        // Добавляем номер, если его еще нет
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
            
            // Преобразуем карту в массив tableData с объединенными номерами
            foreach ($componentNdtMap as $componentData) {
                if (empty($componentData['ndt_numbers'])) {
                    continue; // Пропускаем компоненты без NDT номеров
                }
                
                // Сортируем номера для правильного отображения (преобразуем в числа для корректной сортировки)
                $ndtNumbers = $componentData['ndt_numbers'];
                usort($ndtNumbers, function($a, $b) {
                    return (int)$a <=> (int)$b;
                });
                
                // Объединяем номера через " / "
                $combinedNdtNumber = implode(' / ', $ndtNumbers);
                
                // Используем первый процесс для совместимости с шаблоном
                $firstProcess = !empty($componentData['processes']) ? $componentData['processes'][0] : null;
                
                $tableData[] = [
                    'component' => $componentData['component'],
                    'wo_bushing' => $woBushing,
                    'qty' => $componentData['qty'],
                    'combined_ndt_number' => $combinedNdtNumber, // Всегда устанавливаем combined_ndt_number
                    'process' => $firstProcess,
                    'process_name' => $firstProcess ? ProcessName::find($firstProcess->process_names_id) : null
                ];
            }

            $tableData = $applyBushingComponentFilter($tableData);

            return view('admin.wo_bushings.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'table_data' => $tableData
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processNameId)
            ->get();

        // Создаем массив данных для отображения в таблице
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
                                // Получаем конкретный process_name для правильного отображения номера
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

        $tableData = $applyBushingComponentFilter($tableData);

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
        
        // Получаем связанные данные
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manuals = \App\Models\Manual::where('id', $manual_id)->get();

        $bushData = $this->woBushingSync->resolveBushDataForViews($woBushing);

        // Группируем втулки по процессам
        $processGroups = [];
        
        if ($bushData) {
            foreach ($bushData as $bushItem) {
                if (isset($bushItem['bushing']) && isset($bushItem['processes'])) {
                    $component = Component::find($bushItem['bushing']);
                    if ($component) {
                        $processes = $bushItem['processes'];
                        // Собираем активные процессы в правильном порядке
                        $activeProcesses = [];
                        $processOrder = [
                            'Machining' => 'machining',
                            'Bake (Stress relief)' => 'stress_relief',
                            'NDT' => 'ndt',
                            'Passivation' => 'passivation',
                            'CAD' => 'cad',
                            'Anodizing' => 'anodizing',
                            'Xylan' => 'xylan'
                        ];
                        
                        foreach ($processOrder as $processType => $processKey) {
                            if ($processKey === 'ndt') {
                                $ndtVal = $processes['ndt'] ?? null;
                                if (is_array($ndtVal) && !empty($ndtVal)) {
                                    $activeProcesses[] = $processType;
                                }
                            } elseif (isset($processes[$processKey]) && $processes[$processKey] !== null && $processes[$processKey] !== '') {
                                $activeProcesses[] = $processType;
                            }
                        }
                        
                        // Создаем уникальный ключ группы БЕЗ сортировки
                        $groupKey = implode('|', $activeProcesses);
                        
                        if (!isset($processGroups[$groupKey])) {
                            $processGroups[$groupKey] = [
                                'processes' => $activeProcesses,
                                'components' => [],
                                'total_qty' => 0,
                                'process_numbers' => []
                            ];
                            
                            // Рассчитываем номера процессов для этой группы
                            $processNumber = 1;
                            foreach ($activeProcesses as $process) {
                                $processGroups[$groupKey]['process_numbers'][$process] = $processNumber;
                                $processNumber++;
                            }
                        }
                        
                        $processGroups[$groupKey]['components'][] = [
                            'component' => $component,
                            'qty' => $bushItem['qty'] ?? 1
                        ];
                        $processGroups[$groupKey]['total_qty'] += $bushItem['qty'] ?? 1;
                    }
                }
            }
        }

        // Сортируем группы по количеству процессов (от большего к меньшему)
        uasort($processGroups, function($a, $b) {
            return count($b['processes']) - count($a['processes']);
        });

        // Получаем названия процессов для отображения
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
            'processNames'
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

        // Get all bushings (components where is_bush = 1) for the selected manual, grouped by bush_ipl_num
        // Sort by is_bush (desc - 1 first), then by bush_ipl_num, then by numeric part of ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('is_bush', 'desc')
            ->orderBy('bush_ipl_num', 'asc')
            ->get();

        // Sort by numeric part of ipl_num within each group
        $bushingsQuery = $bushingsQuery->sortBy(function ($item) {
            // First sort by bush_ipl_num
            $bushIplNum = $item->bush_ipl_num ?? '';
            // Then by numeric part of ipl_num
            $parts = explode('-', $item->ipl_num);
            $numericPart = preg_replace('/[^0-9]/', '', end($parts));
            return [$bushIplNum, (int)$numericPart];
        });

        // Group bushings by bush_ipl_num (sorted groups)
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');

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
                $query->where('name', 'LIKE', 'NDT%');
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

            // Одна колонка шапки (NDT, Machining, …), не NDT-1 vs NDT-4.
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

            // Одна открытая партия на (WO + колонка шапки: ndt, machining, …).
            $batch = WoBushingBatch::query()
                ->where('workorder_id', $woBushing->workorder_id)
                ->whereNull('date_start')
                ->where('process_column_key', $processColumnKey)
                ->orderBy('id')
                ->first();

            if (! $batch) {
                $batch = WoBushingBatch::create([
                    'workorder_id' => $woBushing->workorder_id,
                    'process_id' => $canonicalProcessId,
                    'process_column_key' => $processColumnKey,
                ]);
            }

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
