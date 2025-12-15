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
use Illuminate\Http\Request;

class WoBushingController extends Controller
{
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

        if ($existingWoBushing) {
            return redirect()->route('wo_bushings.show', $workorderId)
                ->with('warning', 'Bushings data already exists for this Work Order. Please use Edit to modify.');
        }

        // Process group data and convert to individual component records
        $bushDataArray = [];
        foreach ($groupBushingsData as $groupKey => $groupData) {
            if (isset($groupData['components']) && is_array($groupData['components'])) {
                foreach ($groupData['components'] as $componentId) {
                    $bushDataArray[] = [
                        'bushing' => (int)$componentId,
                        'qty' => (int)($groupData['qty'] ?? 1),
                        'processes' => [
                            'machining' => $groupData['machining'] ? (int)$groupData['machining'] : null,
                            'ndt' => $groupData['ndt'] ? (int)$groupData['ndt'] : null,
                            'passivation' => $groupData['passivation'] ? (int)$groupData['passivation'] : null,
                            'cad' => $groupData['cad'] ? (int)$groupData['cad'] : null,
                            'anodizing' => $groupData['anodizing'] ? (int)$groupData['anodizing'] : null,
                            'xylan' => $groupData['xylan'] ? (int)$groupData['xylan'] : null,
                        ]
                    ];
                }
            }
        }

        if (empty($bushDataArray)) {
            return redirect()->back()
                ->with('error', 'Please select at least one component before submitting.')
                ->withInput();
        }

        WoBushing::create([
            'workorder_id' => $workorderId,
            'bush_data' => $bushDataArray,
        ]);

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

        // Get existing WoBushing data if available
        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->first();
        $bushData = [];
        if ($woBushing && $woBushing->bush_data) {
            $bushData = is_array($woBushing->bush_data)
                ? $woBushing->bush_data
                : json_decode($woBushing->bush_data, true);
        }

        // Get all vendors
        $vendors = Vendor::all();

        return view('admin.wo_bushings.show', compact(
            'current_wo',
            'bushings',
            'machiningProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'woBushing',
            'bushData',
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

        // Get existing bush data
        $bushData = is_array($woBushing->bush_data)
            ? $woBushing->bush_data
            : json_decode($woBushing->bush_data, true);

        return view('admin.wo_bushings.edit', compact(
            'current_wo',
            'woBushing',
            'bushings',
            'machiningProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'anodizingProcesses',
            'xylanProcesses',
            'bushData'
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
            return redirect()->back()
                ->with('error', 'Please select at least one group before submitting.')
                ->withInput();
        }

        // Process group data and convert to individual component records
        $bushDataArray = [];
        foreach ($groupBushingsData as $groupKey => $groupData) {
            if (isset($groupData['components']) && is_array($groupData['components'])) {
                foreach ($groupData['components'] as $componentId) {
                    $bushDataArray[] = [
                        'bushing' => (int)$componentId,
                        'qty' => (int)($groupData['qty'] ?? 1),
                        'processes' => [
                            'machining' => $groupData['machining'] ? (int)$groupData['machining'] : null,
                            'ndt' => $groupData['ndt'] ? (int)$groupData['ndt'] : null,
                            'passivation' => $groupData['passivation'] ? (int)$groupData['passivation'] : null,
                            'cad' => $groupData['cad'] ? (int)$groupData['cad'] : null,
                            'anodizing' => $groupData['anodizing'] ? (int)$groupData['anodizing'] : null,
                            'xylan' => $groupData['xylan'] ? (int)$groupData['xylan'] : null,
                        ]
                    ];
                }
            }
        }

        if (empty($bushDataArray)) {
            return redirect()->back()
                ->with('error', 'Please select at least one component in the selected groups.')
                ->withInput();
        }

        // Update record with processed component data
        $woBushing->update([
            'bush_data' => $bushDataArray
        ]);

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

        // Получаем данные о втулках
        $bushData = is_array($woBushing->bush_data)
            ? $woBushing->bush_data
            : json_decode($woBushing->bush_data, true);

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

            // Создаем массив данных для отображения в таблице
            $tableData = [];
            if ($bushData) {
                foreach ($bushData as $bushItem) {
                    if (isset($bushItem['bushing']) && isset($bushItem['processes']['ndt'])) {
                        $component = Component::find($bushItem['bushing']);
                        if ($component && $bushItem['processes']['ndt']) {
                            $process = Process::find($bushItem['processes']['ndt']);
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
                if (isset($bushItem['bushing'])) {
                    $component = Component::find($bushItem['bushing']);
                    if ($component) {
                        // Определяем тип процесса
                        $processType = null;
                        $processId = null;

                        switch ($processName->name) {
                            case 'Machining':
                                $processId = $bushItem['processes']['machining'] ?? null;
                                break;
                            case 'Passivation':
                                $processId = $bushItem['processes']['passivation'] ?? null;
                                break;
                            case 'Cad plate':
                                $processId = $bushItem['processes']['cad'] ?? null;
                                break;
                            case 'Anodizing':
                                $processId = $bushItem['processes']['anodizing'] ?? null;
                                break;
                            case 'Xylan coating':
                                $processId = $bushItem['processes']['xylan'] ?? null;
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

        // Получаем данные о втулках
        $bushData = is_array($woBushing->bush_data) 
            ? $woBushing->bush_data 
            : json_decode($woBushing->bush_data, true);

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
                        $processOrder = ['Machining', 'NDT', 'Passivation', 'CAD', 'Anodizing', 'Xylan'];
                        
                        foreach ($processOrder as $processType) {
                            $processKey = strtolower($processType);
                            // Проверяем, что процесс существует и не равен null
                            if (isset($processes[$processKey]) && $processes[$processKey] !== null) {
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
}
