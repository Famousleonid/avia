<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ExtraProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExtraProcessController extends Controller
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
     * @return Application|Factory|View
     */
    public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $processNames = ProcessName::all();
        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        return view('admin.extra_processes.create', compact('current_wo', 'components', 'processNames', 'processes', 'manual_id'));
    }

    /**
     * Show the form for creating processes for existing component.
     *
     * @param int $workorderId
     * @param int $componentId
     * @return Application|Factory|View
     */
    public function createProcesses($workorderId, $componentId)
    {
        \Log::info('ExtraProcess createProcesses method called', [
            'workorderId' => $workorderId,
            'componentId' => $componentId
        ]);

        $current_wo = Workorder::findOrFail($workorderId);
        $component = Component::findOrFail($componentId);
        $manual_id = $current_wo->unit->manual_id;
        $processNames = ProcessName::all();
        
        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем существующие extra processes для этого компонента
        $existingExtraProcess = ExtraProcess::where('workorder_id', $workorderId)
            ->where('component_id', $componentId)
            ->first();

        \Log::info('ExtraProcess createProcesses data', [
            'current_wo_id' => $current_wo->id,
            'component_id' => $component->id,
            'component_name' => $component->name,
            'manual_id' => $manual_id,
            'processNames_count' => $processNames->count(),
            'processes_count' => $processes->count(),
            'existingExtraProcess' => $existingExtraProcess ? $existingExtraProcess->id : null
        ]);

        return view('admin.extra_processes.create_processes', compact(
            'current_wo', 
            'component', 
            'processNames', 
            'processes', 
            'manual_id',
            'existingExtraProcess'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Отладочная информация
            \Log::info('ExtraProcess store request received', [
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
                'is_json' => $request->isJson(),
                'all_data' => $request->all(),
                'raw_body' => $request->getContent()
            ]);

            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $workorderId = (int)($data['workorder_id'] ?? 0);
                $componentId = (int)($data['component_id'] ?? 0);
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);
                
                \Log::info('JSON data parsed', [
                    'workorderId' => $workorderId,
                    'componentId' => $componentId,
                    'qty' => $qty,
                    'processesData' => $processesData
                ]);
            } else {
                $workorderId = (int)($request->input('workorder_id'));
                $componentId = (int)($request->input('component_id'));
                $qty = (int)($request->input('qty', 1));
                $processesData = $request->input('processes');
                
                \Log::info('Form data parsed', [
                    'workorderId' => $workorderId,
                    'componentId' => $componentId,
                    'qty' => $qty,
                    'processesData' => $processesData
                ]);
            }

            // Валидация
            if (!$workorderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workorder ID is required.'
                ], 422);
            }

            if (!$componentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component ID is required.'
                ], 422);
            }

            if (!$processesData || !is_array($processesData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Processes data is required and must be an array.'
                ], 422);
            }

            // Проверяем существование workorder и component
            $workorder = Workorder::find($workorderId);
            if (!$workorder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workorder not found.'
                ], 422);
            }

            $component = Component::find($componentId);
            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component not found.'
                ], 422);
            }

            // Проверяем, существует ли уже запись для этого компонента в этом workorder
            $existingExtraProcess = ExtraProcess::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->first();

            if ($existingExtraProcess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Extra processes for this component already exist in this work order.'
                ], 400);
            }

            // Формируем данные для сохранения в JSON формате
            // Каждый process_name_id содержит только один process_id
            $processesJson = [];
            foreach ($processesData as $processData) {
                if (!isset($processData['process_names_id']) || !isset($processData['processes'])) {
                    continue;
                }

                $processNameId = $processData['process_names_id'];
                $processIds = $processData['processes'];
                
                // Проверяем, что process_name_id существует
                $processName = ProcessName::find($processNameId);
                if (!$processName) {
                    continue;
                }

                // Берем только первый process_id для каждого process_name_id
                if (!empty($processIds) && is_array($processIds)) {
                    $processId = (int)$processIds[0];
                    
                    // Проверяем, что process_id существует
                    $process = Process::find($processId);
                    if ($process) {
                        // Сохраняем в порядке добавления как массив объектов
                        $processesJson[] = [
                            'process_name_id' => $processNameId,
                            'process_id' => $processId
                        ];
                    }
                }
            }

            if (empty($processesJson)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid processes selected.'
                ], 422);
            }

            \Log::info('Final processes JSON to save', [
                'workorderId' => $workorderId,
                'componentId' => $componentId,
                'processesJson' => $processesJson
            ]);

            // Создаем новую запись
            ExtraProcess::create([
                'workorder_id' => $workorderId,
                'component_id' => $componentId,
                'qty' => $qty,
                'processes' => $processesJson,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Extra processes created successfully.',
                'redirect' => route('extra_processes.show_all', ['id' => $workorderId])
            ]);

        } catch (\Exception $e) {
            \Log::error('ExtraProcess store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating extra processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store additional processes for existing component.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function storeProcesses(Request $request)
    {
        try {
            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $workorderId = (int)($data['workorder_id'] ?? 0);
                $componentId = (int)($data['component_id'] ?? 0);
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);
            } else {
                $workorderId = (int)($request->input('workorder_id'));
                $componentId = (int)($request->input('component_id'));
                $qty = (int)($request->input('qty', 1));
                $processesData = $request->input('processes');
            }

            // Валидация
            if (!$workorderId || !$componentId || !$processesData || !is_array($processesData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data provided.'
                ], 422);
            }

            // Находим существующий extra process
            $existingExtraProcess = ExtraProcess::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->first();

            if (!$existingExtraProcess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Extra process record not found.'
                ], 404);
            }

            // Получаем существующие процессы
            $existingProcesses = $existingExtraProcess->processes ?? [];

            // Проверяем и исправляем структуру существующих данных
            if (!empty($existingProcesses)) {
                \Log::info('Existing processes structure check', [
                    'existingProcesses' => $existingProcesses,
                    'isArray' => is_array($existingProcesses),
                    'keys' => is_array($existingProcesses) ? array_keys($existingProcesses) : 'not array'
                ]);

                // Если данные имеют неправильную структуру, исправляем их
                if (is_array($existingProcesses) && !empty($existingProcesses)) {
                    $firstItem = reset($existingProcesses);
                    if (is_array($firstItem) && isset($firstItem['process_name_id']) && isset($firstItem['process_id'])) {
                        // Данные уже в правильном формате
                        \Log::info('Existing data is in correct format');
                    } else {
                        // Данные в старом формате, конвертируем в новый
                        \Log::info('Converting existing data from old format to new format');
                        $convertedProcesses = [];
                        foreach ($existingProcesses as $processNameId => $processId) {
                            if (is_numeric($processNameId) && is_numeric($processId)) {
                                $convertedProcesses[] = [
                                    'process_name_id' => (string)$processNameId,
                                    'process_id' => (int)$processId
                                ];
                            }
                        }
                        $existingProcesses = $convertedProcesses;
                        
                        // Обновляем запись с исправленными данными
                        $existingExtraProcess->update(['processes' => $existingProcesses]);
                        \Log::info('Updated existing data with correct format', ['convertedProcesses' => $convertedProcesses]);
                    }
                }
            }

            // Создаем массив для хранения существующих процессов в правильном формате
            $existingProcessesArray = [];
            if (is_array($existingProcesses)) {
                foreach ($existingProcesses as $process) {
                    if (is_array($process) && isset($process['process_name_id']) && isset($process['process_id'])) {
                        $existingProcessesArray[] = $process;
                    }
                }
            }

            \Log::info('Debug storeProcesses data', [
                'workorderId' => $workorderId,
                'componentId' => $componentId,
                'processesData' => $processesData,
                'existingProcesses' => $existingProcesses,
                'processesDataType' => gettype($processesData),
                'processesDataCount' => is_array($processesData) ? count($processesData) : 'not array'
            ]);

            // Создаем финальный массив процессов
            $finalProcesses = $existingProcessesArray;

            // Добавляем новые процессы в порядке их выбора
            foreach ($processesData as $index => $processData) {
                \Log::info("Processing item {$index}", [
                    'processData' => $processData,
                    'processDataType' => gettype($processData)
                ]);

                if (!isset($processData['process_names_id']) || !isset($processData['processes'])) {
                    \Log::warning("Missing required fields in processData", ['processData' => $processData]);
                    continue;
                }

                $processNameId = $processData['process_names_id'];
                $processIds = $processData['processes'];
                
                \Log::info("Extracted values", [
                    'processNameId' => $processNameId,
                    'processIds' => $processIds,
                    'processIdsType' => gettype($processIds)
                ]);
                
                // Проверяем, что process_name_id существует
                $processName = ProcessName::find($processNameId);
                if (!$processName) {
                    \Log::warning("ProcessName not found", ['processNameId' => $processNameId]);
                    continue;
                }

                // Берем только первый process_id для каждого process_name_id
                if (!empty($processIds) && is_array($processIds)) {
                    $processId = (int)$processIds[0];
                    
                    \Log::info("Process ID extracted", [
                        'processId' => $processId,
                        'processIdType' => gettype($processId)
                    ]);
                    
                    // Проверяем, что process_id существует
                    $process = Process::find($processId);
                    if ($process) {
                        // Проверяем, что такого process_name_id еще нет
                        $exists = false;
                        foreach ($finalProcesses as $existingProcess) {
                            if ($existingProcess['process_name_id'] == $processNameId) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $finalProcesses[] = [
                                'process_name_id' => $processNameId,
                                'process_id' => $processId
                            ];
                            \Log::info("Added new process to finalProcesses", [
                                'processNameId' => $processNameId,
                                'processId' => $processId
                            ]);
                        } else {
                            \Log::info("Process name already exists, skipping", ['processNameId' => $processNameId]);
                        }
                    } else {
                        \Log::warning("Process not found", ['processId' => $processId]);
                    }
                }
            }

            \Log::info('Final processes JSON to save', [
                'workorderId' => $workorderId,
                'componentId' => $componentId,
                'finalProcesses' => $finalProcesses
            ]);

            \Log::info('Processes order tracking', [
                'originalOrder' => array_map(function($item, $index) {
                    return [
                        'order' => $index + 1,
                        'process_name_id' => $item['process_names_id'],
                        'process_id' => $item['processes'][0]
                    ];
                }, $processesData, array_keys($processesData)),
                'finalOrder' => array_map(function($process, $index) {
                    return [
                        'order' => $index + 1,
                        'process_name_id' => $process['process_name_id'],
                        'process_id' => $process['process_id']
                    ];
                }, $finalProcesses, array_keys($finalProcesses))
            ]);

            // Обновляем запись с финальными процессами
            $existingExtraProcess->update([
                'processes' => $finalProcesses,
                'qty' => $qty,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Additional processes added successfully.',
                'redirect' => route('extra_processes.show_all', ['id' => $workorderId])
            ]);

        } catch (\Exception $e) {
            \Log::error('ExtraProcess storeProcesses error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display all extra processes for a work order.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function showAll($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $extra_components = ExtraProcess::where('workorder_id', $current_wo->id)
            ->with(['component', 'workorder'])
            ->get();

        // Отладочная информация
        \Log::info('ExtraProcess showAll method', [
            'workorder_id' => $id,
            'extra_components_count' => $extra_components->count(),
            'extra_components_data' => $extra_components->map(function($item) {
                return [
                    'id' => $item->id,
                    'component_id' => $item->component_id,
                    'component_relation' => $item->component ? [
                        'id' => $item->component->id,
                        'name' => $item->component->name,
                        'ipl_num' => $item->component->ipl_num
                    ] : null
                ];
            })
        ]);

        return view('admin.extra_processes.show', compact('current_wo', 'extra_components'));
    }

    /**
     * Display processes for a specific component.
     *
     * @param  int  $workorderId
     * @param  int  $componentId
     * @return Application|Factory|View
     */
    public function processes($workorderId, $componentId)
    {
        $current_wo = Workorder::findOrFail($workorderId);
        $component = Component::findOrFail($componentId);
        $extra_process = ExtraProcess::where('workorder_id', $workorderId)
            ->where('component_id', $componentId)
            ->first();

        // Получаем все процессы для отображения
        $allProcesses = Process::all();

        return view('admin.extra_processes.processes', compact('current_wo', 'component', 'extra_process', 'allProcesses'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $component = Component::findOrFail($extra_process->component_id);
        
        // Получаем имена процессов
        $processNames = ProcessName::all();
        
        // Получаем процессы, связанные с manual_id
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        return view('admin.extra_processes.edit', compact(
            'current_wo',
            'component',
            'extra_process',
            'processNames',
            'processes',
            'manual_id'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $extra_process = ExtraProcess::findOrFail($id);
            
            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);
            } else {
                $qty = (int)($request->input('qty', 1));
                $processesData = $request->input('processes');
            }

            // Валидация
            if (!$processesData || !is_array($processesData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Processes data is required and must be an array.'
                ], 422);
            }

            // Формируем данные для сохранения в JSON формате
            $processesJson = [];
            foreach ($processesData as $processData) {
                if (!isset($processData['process_names_id']) || !isset($processData['processes'])) {
                    continue;
                }

                $processNameId = $processData['process_names_id'];
                $processIds = $processData['processes'];
                
                // Проверяем, что process_name_id существует
                $processName = ProcessName::find($processNameId);
                if (!$processName) {
                    continue;
                }

                // Берем только первый process_id для каждого process_name_id
                if (!empty($processIds) && is_array($processIds)) {
                    $processId = (int)$processIds[0];
                    
                    // Проверяем, что process_id существует
                    $process = Process::find($processId);
                    if ($process) {
                        $processesJson[] = [
                            'process_name_id' => $processNameId,
                            'process_id' => $processId
                        ];
                    }
                }
            }

            // Обновляем запись
            $extra_process->update([
                'processes' => $processesJson,
                'qty' => $qty,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Extra processes updated successfully.',
                'redirect' => route('extra_processes.processes', [
                    'workorderId' => $extra_process->workorder_id,
                    'componentId' => $extra_process->component_id
                ])
            ]);

        } catch (\Exception $e) {
            \Log::error('ExtraProcess update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating extra processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display form for specific process name.
     *
     * @param  int  $id
     * @param  int  $processNameId
     * @return Application|Factory|View
     */
    public function showForm($id, $processNameId)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $component = Component::findOrFail($extra_process->component_id);
        $processName = ProcessName::findOrFail($processNameId);
        
        // Получаем связанные данные
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = \App\Models\ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'component' => $component,
            'extra_process' => $extra_process,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName
        ];

        // Обработка NDT формы (если нужно)
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

            // Фильтруем процессы только для указанного process_name_id
            $filteredProcesses = [];
            if ($extra_process->processes && !empty($extra_process->processes)) {
                if (is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1)) {
                    // Старая структура: ассоциативный массив
                    if (isset($extra_process->processes[$processNameId])) {
                        $processId = $extra_process->processes[$processNameId];
                        $process = Process::find($processId);
                        if ($process) {
                            $filteredProcesses[] = [
                                'process_name_id' => $processNameId,
                                'process_id' => $processId
                            ];
                        }
                    }
                } else {
                    // Новая структура: массив объектов
                    foreach ($extra_process->processes as $processItem) {
                        if ($processItem['process_name_id'] == $processNameId) {
                            $filteredProcesses[] = $processItem;
                        }
                    }
                }
            }

            // Создаем массив данных для отображения в таблице
            $tableData = [];
            foreach ($filteredProcesses as $processItem) {
                $processNameId = $processItem['process_name_id'];
                $processId = $processItem['process_id'];
                
                $process = Process::find($processId);
                
                if ($process) {
                    $tableData[] = [
                        'process_name' => $processName,
                        'process' => $process,
                        'component' => $component,
                        'extra_process' => $extra_process
                    ];
                }
            }

            return view('admin.extra_processes.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'table_data' => $tableData
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processNameId)
            ->get();

        // Фильтруем процессы только для указанного process_name_id
        $filteredProcesses = [];
        if ($extra_process->processes && !empty($extra_process->processes)) {
            if (is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1)) {
                // Старая структура: ассоциативный массив
                if (isset($extra_process->processes[$processNameId])) {
                    $processId = $extra_process->processes[$processNameId];
                    $process = Process::find($processId);
                    if ($process) {
                        $filteredProcesses[] = [
                            'process_name_id' => $processNameId,
                            'process_id' => $processId
                        ];
                    }
                }
            } else {
                // Новая структура: массив объектов
                foreach ($extra_process->processes as $processItem) {
                    if ($processItem['process_name_id'] == $processNameId) {
                        $filteredProcesses[] = $processItem;
                    }
                }
            }
        }

        // Создаем массив данных для отображения в таблице
        $tableData = [];
        foreach ($filteredProcesses as $processItem) {
            $processNameId = $processItem['process_name_id'];
            $processId = $processItem['process_id'];
            
            $process = Process::find($processId);
            
            if ($process) {
                $tableData[] = [
                    'process_name' => $processName,
                    'process' => $process,
                    'component' => $component,
                    'extra_process' => $extra_process
                ];
            }
        }

        return view('admin.extra_processes.processesForm', array_merge($viewData, [
            'process_components' => $process_components,
            'table_data' => $tableData
        ]));
    }

    /**
     * Display the specified resource (form view).
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $component = Component::findOrFail($extra_process->component_id);
        
        // Получаем связанные данные
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = \App\Models\ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'component' => $component,
            'extra_process' => $extra_process,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id
        ];

        // Если есть процессы, получаем данные для отображения формы
        if ($extra_process->processes && !empty($extra_process->processes)) {
            // Получаем все уникальные process_name_id из процессов
            $processNameIds = [];
            if (is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1)) {
                // Старая структура: ассоциативный массив
                $processNameIds = array_keys($extra_process->processes);
            } else {
                // Новая структура: массив объектов
                $processNameIds = array_unique(array_column($extra_process->processes, 'process_name_id'));
            }
            
            if (!empty($processNameIds)) {
                $processName = ProcessName::find($processNameIds[0]);
                if ($processName) {
                    $viewData['process_name'] = $processName;
                    
                    // Получаем все процессы для отображения
                    $viewData['process_components'] = Process::whereIn('id', $manualProcesses)
                        ->whereIn('process_names_id', $processNameIds)
                        ->get();
                        
                    // Создаем массив данных для отображения в таблице
                    $tableData = [];
                    foreach ($extra_process->processes as $processItem) {
                        $processNameId = is_array($processItem) ? $processItem['process_name_id'] : $processItem;
                        $processId = is_array($processItem) ? $processItem['process_id'] : $extra_process->processes[$processItem];
                        
                        $processName = ProcessName::find($processNameId);
                        $process = Process::find($processId);
                        
                        if ($processName && $process) {
                            $tableData[] = [
                                'process_name' => $processName,
                                'process' => $process,
                                'component' => $component,
                                'extra_process' => $extra_process
                            ];
                        }
                    }
                    $viewData['table_data'] = $tableData;
                }
            }
        }

        return view('admin.extra_processes.processesForm', $viewData);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $extraProcess = ExtraProcess::findOrFail($id);
            
            // Проверяем, нужно ли удалить конкретный процесс или всю запись
            $processNameId = request('process_name_id');
            $processIndex = request('process_index');
            
            if ($processNameId || $processIndex !== null) {
                // Удаляем конкретный процесс
                $processes = $extraProcess->processes ?? [];
                
                if (is_array($processes) && !empty($processes)) {
                    if ($processIndex !== null) {
                        // Новая структура: массив объектов
                        if (isset($processes[$processIndex])) {
                            unset($processes[$processIndex]);
                            $processes = array_values($processes); // Переиндексируем массив
                        }
                    } else {
                        // Старая структура: ассоциативный массив
                        if (isset($processes[$processNameId])) {
                            unset($processes[$processNameId]);
                        }
                    }
                    
                    // Если процессов не осталось, удаляем всю запись
                    if (empty($processes)) {
                        $extraProcess->delete();
                        return response()->json([
                            'success' => true,
                            'message' => 'Process deleted successfully. No processes remaining.',
                            'redirect' => route('extra_processes.show_all', ['id' => $extraProcess->workorder_id])
                        ]);
                    } else {
                        // Обновляем запись с оставшимися процессами
                        $extraProcess->update(['processes' => $processes]);
                        return response()->json([
                            'success' => true,
                            'message' => 'Process deleted successfully.',
                            'redirect' => route('extra_processes.processes', [
                                'workorderId' => $extraProcess->workorder_id,
                                'componentId' => $extraProcess->component_id
                            ])
                        ]);
                    }
                }
            }
            
            // Если не указан конкретный процесс, удаляем всю запись
            $extraProcess->delete();

            return response()->json([
                'success' => true,
                'message' => 'Extra process deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting extra process: ' . $e->getMessage()
            ], 500);
        }
    }
}
