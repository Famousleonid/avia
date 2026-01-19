<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ExtraProcess;
use App\Models\Manual;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Vendor;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        $manuals = Manual::all();

        return view('admin.extra_processes.create', compact('current_wo', 'components', 'processNames', 'processes', 'manual_id', 'manuals'));
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

        // Получаем все NDT process names для дополнительного селекта
        $ndtProcessNames = ProcessName::where('name', 'like', 'NDT-%')->get();

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
            'existingExtraProcess',
            'ndtProcessNames'
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
                $serial_num = $data['serial_num'] ?? null;
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);

                \Log::info('JSON data parsed', [
                    'workorderId' => $workorderId,
                    'componentId' => $componentId,
                    'qty' => $qty,
                    'serial_num' => $serial_num,
                    'processesData' => $processesData
                ]);
            } else {
                $workorderId = (int)($request->input('workorder_id'));
                $componentId = (int)($request->input('component_id'));
                $serial_num = $request->input('serial_num');
                $qty = (int)($request->input('qty', 1));
                $processesData = $request->input('processes');

                \Log::info('Form data parsed', [
                    'workorderId' => $workorderId,
                    'componentId' => $componentId,
                    'qty' => $qty,
                    'serial_num'=>$serial_num,
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
                        // Формируем объект процесса
                        $processObject = [
                            'process_name_id' => $processNameId,
                            'process_id' => $processId,
                            'description' => $processData['description'] ?? null,
                            'notes' => $processData['notes'] ?? null
                        ];

                        // Если это NDT процесс с дополнительными NDT, добавляем поля
                        $processName = ProcessName::find($processNameId);
                        if ($processName && strpos($processName->name, 'NDT-') === 0) {
                            if (isset($processData['plus_process_names']) && !empty($processData['plus_process_names'])) {
                                $processObject['plus_process_names'] = $processData['plus_process_names'];
                                $processObject['plus_process_ids'] = $processData['plus_process_ids'] ?? [];
                            }
                        }

                        // Сохраняем в порядке добавления как массив объектов
                        $processesJson[] = $processObject;
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

            // Получаем максимальный sort_order для данного workorder_id и component_id
            $maxSortOrder = ExtraProcess::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->max('sort_order') ?? 0;

            // Создаем новую запись
            ExtraProcess::create([
                'workorder_id' => $workorderId,
                'component_id' => $componentId,
                'qty' => $qty,
                'serial_num' => $serial_num,
                'processes' => $processesJson,
                'sort_order' => $maxSortOrder + 1, // Устанавливаем sort_order в конец списка
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
                $serial_num = $data['serial_num'] ?? null;
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);
            } else {
                $workorderId = (int)($request->input('workorder_id'));
                $componentId = (int)($request->input('component_id'));
                $serial_num = $request->input('serial_num');
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
                                    'process_id' => (int)$processId,
                                    'description' => null, // Старая структура не содержит description
                                    'notes' => null // Старая структура не содержит notes
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
                            // Формируем объект процесса
                            $processObject = [
                                'process_name_id' => $processNameId,
                                'process_id' => $processId,
                                'description' => $processData['description'] ?? null,
                                'notes' => $processData['notes'] ?? null
                            ];

                            // Если это NDT процесс с дополнительными NDT, добавляем поля
                            $processName = ProcessName::find($processNameId);
                            if ($processName && strpos($processName->name, 'NDT-') === 0) {
                                if (isset($processData['plus_process_names']) && !empty($processData['plus_process_names'])) {
                                    $processObject['plus_process_names'] = $processData['plus_process_names'];
                                    $processObject['plus_process_ids'] = $processData['plus_process_ids'] ?? [];
                                }
                            }

                            $finalProcesses[] = $processObject;
                            \Log::info("Added new process to finalProcesses", [
                                'processNameId' => $processNameId,
                                'processId' => $processId,
                                'hasPlusProcess' => isset($processObject['plus_process_names'])
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
            $updateData = [
                'processes' => $finalProcesses,
                'qty' => $qty,
            ];

            if (isset($serial_num)) {
                $updateData['serial_num'] = $serial_num;
            }

            $existingExtraProcess->update($updateData);

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

        // Группируем процессы для создания кнопок групповых форм
        $processGroups = [];
        $totalQty = 0;

        \Log::info('Starting process grouping', [
            'extra_components_count' => $extra_components->count()
        ]);

        foreach ($extra_components as $extra_component) {
            if (!$extra_component->processes || !$extra_component->component) {
                \Log::info('Skipping component', [
                    'component_id' => $extra_component->component_id,
                    'has_processes' => !is_null($extra_component->processes),
                    'has_component' => !is_null($extra_component->component)
                ]);
                continue;
            }

            // Суммируем общее количество по всем компонентам
            $totalQty += (int)($extra_component->qty ?? 0);

            \Log::info('Processing component', [
                'component_id' => $extra_component->component->id,
                'component_name' => $extra_component->component->name,
                'processes' => $extra_component->processes
            ]);

            // Проверяем старую и новую структуру данных
            if (is_array($extra_component->processes) && array_keys($extra_component->processes) !== range(0, count($extra_component->processes) - 1)) {
                // Старая структура: ассоциативный массив
                \Log::info('Using old structure (associative array)');
                foreach ($extra_component->processes as $processNameId => $processId) {
                    $processName = ProcessName::find($processNameId);
                    if ($processName) {
                        // Определяем ключ группы: для NDT процессов используем 'NDT_GROUP', иначе processNameId
                        $groupKey = ($processName->process_sheet_name == 'NDT') ? 'NDT_GROUP' : $processNameId;

                        if (!isset($processGroups[$groupKey])) {
                            // Для NDT группы создаем виртуальный ProcessName или используем первый найденный NDT процесс
                            if ($groupKey == 'NDT_GROUP') {
                                // Находим первый ProcessName с process_sheet_name == 'NDT' для использования в группе
                                $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->first();
                                if ($ndtProcessName) {
                                    $processGroups[$groupKey] = [
                                        'process_name' => $ndtProcessName,
                                        'components_qty' => [],
                                        'components' => []
                                    ];
                                } else {
                                    // Если не найден, используем текущий процесс
                                    $processGroups[$groupKey] = [
                                        'process_name' => $processName,
                                        'components_qty' => [],
                                        'components' => []
                                    ];
                                }
                            } else {
                                $processGroups[$groupKey] = [
                                    'process_name' => $processName,
                                    'components_qty' => [],
                                    'components' => []
                                ];
                            }
                        }
                        // Добавляем/обновляем количество по компоненту
                        $processGroups[$groupKey]['components_qty'][$extra_component->component->id] = (int)($extra_component->qty ?? 0);
                        // Сохраняем информацию о компоненте
                        if (!isset($processGroups[$groupKey]['components'][$extra_component->component->id])) {
                            $processGroups[$groupKey]['components'][$extra_component->component->id] = [
                                'id' => $extra_component->component->id,
                                'name' => $extra_component->component->name,
                                'ipl_num' => $extra_component->component->ipl_num,
                                'qty' => (int)($extra_component->qty ?? 0)
                            ];
                        }
                        \Log::info('Added component to process group', [
                            'group_key' => $groupKey,
                            'process_name_id' => $processNameId,
                            'process_name' => $processName->name,
                            'component_id' => $extra_component->component->id
                        ]);
                    }
                }
            } else {
                // Новая структура: массив объектов
                \Log::info('Using new structure (array of objects)');
                foreach ($extra_component->processes as $processItem) {
                    $processName = ProcessName::find($processItem['process_name_id']);
                    if ($processName) {
                        $processNameId = $processItem['process_name_id'];
                        // Определяем ключ группы: для NDT процессов используем 'NDT_GROUP', иначе processNameId
                        $groupKey = ($processName->process_sheet_name == 'NDT') ? 'NDT_GROUP' : $processNameId;

                        if (!isset($processGroups[$groupKey])) {
                            // Для NDT группы создаем виртуальный ProcessName или используем первый найденный NDT процесс
                            if ($groupKey == 'NDT_GROUP') {
                                // Находим первый ProcessName с process_sheet_name == 'NDT' для использования в группе
                                $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->first();
                                if ($ndtProcessName) {
                                    $processGroups[$groupKey] = [
                                        'process_name' => $ndtProcessName,
                                        'components_qty' => [],
                                        'components' => []
                                    ];
                                } else {
                                    // Если не найден, используем текущий процесс
                                    $processGroups[$groupKey] = [
                                        'process_name' => $processName,
                                        'components_qty' => [],
                                        'components' => []
                                    ];
                                }
                            } else {
                                $processGroups[$groupKey] = [
                                    'process_name' => $processName,
                                    'components_qty' => [],
                                    'components' => []
                                ];
                            }
                        }
                        // Добавляем/обновляем количество по компоненту
                        $processGroups[$groupKey]['components_qty'][$extra_component->component->id] = (int)($extra_component->qty ?? 0);
                        // Сохраняем информацию о компоненте
                        if (!isset($processGroups[$groupKey]['components'][$extra_component->component->id])) {
                            $processGroups[$groupKey]['components'][$extra_component->component->id] = [
                                'id' => $extra_component->component->id,
                                'name' => $extra_component->component->name,
                                'ipl_num' => $extra_component->component->ipl_num,
                                'qty' => (int)($extra_component->qty ?? 0)
                            ];
                        }
                        \Log::info('Added component to process group', [
                            'group_key' => $groupKey,
                            'process_name_id' => $processNameId,
                            'process_name' => $processName->name,
                            'component_id' => $extra_component->component->id
                        ]);
                    }
                }
            }
        }

        // Подсчитываем уникальные компоненты и суммы qty для каждого процесса
        foreach ($processGroups as $processNameId => &$group) {
            $componentsQty = $group['components_qty'];
            $uniqueComponents = array_keys($componentsQty);
            $group['count'] = count($uniqueComponents);
            $group['qty'] = array_sum($componentsQty);
            // Преобразуем массив компонентов в обычный массив для удобства в представлении
            $group['components'] = array_values($group['components']);
            \Log::info('Process group count', [
                'process_name_id' => $processNameId,
                'process_name' => $group['process_name']->name,
                'unique_components' => $uniqueComponents,
                'count' => $group['count'],
                'qty' => $group['qty']
            ]);
            unset($group['components_qty']); // Удаляем техническое поле
        }

        // Отладочная информация
        \Log::info('ExtraProcess showAll method - Process Groups Count', [
            'workorder_id' => $id,
            'extra_components_count' => $extra_components->count(),
            'process_groups_count' => count($processGroups),
            'process_groups' => $processGroups,
            'extra_components_data' => $extra_components->map(function($item) {
                return [
                    'id' => $item->id,
                    'component_id' => $item->component_id,
                    'component_relation' => $item->component ? [
                        'id' => $item->component->id,
                        'name' => $item->component->name,
                        'ipl_num' => $item->component->ipl_num
                    ] : null,
                    'processes' => $item->processes
                ];
            })
        ]);

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        return view('admin.extra_processes.show', compact('current_wo', 'extra_components', 'processGroups', 'vendors', 'totalQty'));
    }

    /**
     * Display grouped forms for all extra processes by process name.
     *
     * @param  int  $id
     * @param  int  $processNameId
     * @param  Request  $request
     * @return Application|Factory|View
     */
    public function showGroupForms($id, $processNameId, Request $request)
    {
        $current_wo = Workorder::findOrFail($id);
        $processName = ProcessName::findOrFail($processNameId);

        // Получаем все extra processes для этого work order
        $extra_processes = ExtraProcess::where('workorder_id', $current_wo->id)
            ->with(['component.manual'])
            ->orderBy('sort_order')
            ->get();

        // Определяем, является ли это NDT группой
        $isNdtGroup = ($processName->process_sheet_name == 'NDT');

        // Если это NDT группа, получаем все NDT process_name_ids
        $ndtProcessNameIds = [];
        if ($isNdtGroup) {
            $ndtProcessNameIds = ProcessName::where('process_sheet_name', 'NDT')
                ->pluck('id')
                ->toArray();
        }

        // Группируем компоненты по process_name_id
        $groupedComponents = [];

        foreach ($extra_processes as $extra_process) {
            if (!$extra_process->component || !$extra_process->processes) {
                continue;
            }

            // Проверяем старую и новую структуру данных
            if (is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1)) {
                // Старая структура: ассоциативный массив
                if ($isNdtGroup) {
                    // Для NDT группы обрабатываем все NDT процессы
                    foreach ($ndtProcessNameIds as $ndtProcessNameId) {
                        if (isset($extra_process->processes[$ndtProcessNameId])) {
                            $processId = $extra_process->processes[$ndtProcessNameId];
                            $process = Process::find($processId);
                            $ndtProcessName = ProcessName::find($ndtProcessNameId);
                            if ($process && $ndtProcessName) {
                                // Получаем manual для компонента
                                $componentManual = $extra_process->component->manual ?? null;
                                $groupedComponents[] = [
                                    'process_name' => $ndtProcessName,
                                    'process' => $process,
                                    'component' => $extra_process->component,
                                    'extra_process' => $extra_process,
                                    'manual' => $componentManual
                                ];
                            }
                        }
                    }
                } else {
                    // Для обычных процессов обрабатываем только указанный processNameId
                    if (isset($extra_process->processes[$processNameId])) {
                        $processId = $extra_process->processes[$processNameId];
                        $process = Process::find($processId);
                        if ($process) {
                            // Получаем manual для компонента
                            $componentManual = $extra_process->component->manual ?? null;
                            $groupedComponents[] = [
                                'process_name' => $processName,
                                'process' => $process,
                                'component' => $extra_process->component,
                                'extra_process' => $extra_process,
                                'manual' => $componentManual
                            ];
                        }
                    }
                }
            } else {
                // Новая структура: массив объектов
                if ($isNdtGroup) {
                    // Для NDT группы обрабатываем все NDT процессы
                    foreach ($extra_process->processes as $processItem) {
                        if (in_array($processItem['process_name_id'], $ndtProcessNameIds)) {
                            $process = Process::find($processItem['process_id']);
                            $ndtProcessName = ProcessName::find($processItem['process_name_id']);
                            if ($process && $ndtProcessName) {
                                // Получаем manual для компонента
                                $componentManual = $extra_process->component->manual ?? null;
                                $groupedComponents[] = [
                                    'process_name' => $ndtProcessName,
                                    'process' => $process,
                                    'component' => $extra_process->component,
                                    'extra_process' => $extra_process,
                                    'manual' => $componentManual
                                ];
                            }
                        }
                    }
                } else {
                    // Для обычных процессов обрабатываем только указанный processNameId
                    foreach ($extra_process->processes as $processItem) {
                        if ($processItem['process_name_id'] == $processNameId) {
                            $process = Process::find($processItem['process_id']);
                            if ($process) {
                                // Получаем manual для компонента
                                $componentManual = $extra_process->component->manual ?? null;
                                $groupedComponents[] = [
                                    'process_name' => $processName,
                                    'process' => $process,
                                    'component' => $extra_process->component,
                                    'extra_process' => $extra_process,
                                    'manual' => $componentManual
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Получаем связанные данные
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = \App\Models\ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Получаем выбранного vendor (если передан)
        $selectedVendor = null;
        $vendorId = $request->input('vendor_id');
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Фильтруем компоненты по выбранным component_ids (если переданы)
        $componentIds = $request->input('component_ids');
        if ($componentIds) {
            // Разбиваем строку с ID на массив
            $filteredComponentIds = is_array($componentIds)
                ? array_map('intval', $componentIds)
                : array_map('intval', explode(',', $componentIds));

            // Фильтруем groupedComponents по выбранным component_id
            $groupedComponents = array_filter($groupedComponents, function($item) use ($filteredComponentIds) {
                return in_array($item['component']->id, $filteredComponentIds);
            });
            // Переиндексируем массив после фильтрации
            $groupedComponents = array_values($groupedComponents);
        }

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName,
            'table_data' => $groupedComponents,
            'selectedVendor' => $selectedVendor
        ];

        // Добавляем первый компонент для заголовка формы (если есть компоненты)
        if (!empty($groupedComponents)) {
            $viewData['component'] = $groupedComponents[0]['component'];
        } else {
            // Если нет компонентов, создаем пустой объект
            $viewData['component'] = (object)[
                'name' => 'Multiple Components',
                'part_number' => 'Various',
                'ipl_num' => 'Various'
            ];
        }

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

            // Собираем все уникальные номера manual из компонентов
            $manualNumbers = [];
            foreach ($groupedComponents as $item) {
                if (isset($item['manual']) && $item['manual']) {
                    $manualNumber = $item['manual']->number;
                    if (!in_array($manualNumber, $manualNumbers)) {
                        $manualNumbers[] = $manualNumber;
                    }
                }
            }
            $manualNumbersString = implode(', ', $manualNumbers);

            return view('admin.extra_processes.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'selectedVendor' => $selectedVendor, // Явно сохраняем selectedVendor
                'manual_numbers' => $manualNumbersString // Номера manual через запятую для NDT
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processNameId)
            ->get();

        return view('admin.extra_processes.processesForm', array_merge($viewData, [
            'process_components' => $process_components,
            'selectedVendor' => $selectedVendor // Явно сохраняем selectedVendor
        ]));
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

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        return view('admin.extra_processes.processes', compact('current_wo', 'component', 'extra_process', 'allProcesses', 'vendors'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return Application|Factory|View
     */
    public function edit($id, Request $request)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $component = Component::findOrFail($extra_process->component_id);

        // Получаем параметры для фильтрации конкретного процесса
        $processIndex = $request->input('process_index');
        $processNameId = $request->input('process_name_id');

        // Фильтруем процессы для редактирования только выбранного
        $processesToEdit = [];
        if ($processIndex !== null || $processNameId !== null) {
            $allProcesses = $extra_process->processes ?? [];

            if (is_array($allProcesses) && array_keys($allProcesses) !== range(0, count($allProcesses) - 1)) {
                // Старая структура: ассоциативный массив
                if ($processNameId && isset($allProcesses[$processNameId])) {
                    $processesToEdit[] = [
                        'process_name_id' => $processNameId,
                        'process_id' => $allProcesses[$processNameId],
                        'description' => null, // Старая структура не содержит description
                        'notes' => null // Старая структура не содержит notes
                    ];
                }
            } else {
                // Новая структура: массив объектов
                if ($processIndex !== null && isset($allProcesses[$processIndex])) {
                    $processesToEdit[] = $allProcesses[$processIndex];
                } elseif ($processNameId !== null) {
                    // Ищем по process_name_id
                    foreach ($allProcesses as $processItem) {
                        if (isset($processItem['process_name_id']) && $processItem['process_name_id'] == $processNameId) {
                            $processesToEdit[] = $processItem;
                            break;
                        }
                    }
                }
            }
        }

        // Получаем имена процессов
        $processNames = ProcessName::all();

        // Получаем процессы, связанные с manual_id
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем все NDT process names для дополнительного селекта
        $ndtProcessNames = ProcessName::where('name', 'like', 'NDT-%')->get();

        return view('admin.extra_processes.edit', compact(
            'current_wo',
            'component',
            'extra_process',
            'processNames',
            'processes',
            'manual_id',
            'processesToEdit',
            'processIndex',
            'processNameId',
            'ndtProcessNames'
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

            // Получаем параметры для обновления конкретного процесса
            $processIndex = $request->input('process_index');
            $processNameId = $request->input('process_name_id');

            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $serial_num = $data['serial_num'] ?? null;
                $qty = (int)($data['qty'] ?? 1);
                $processesData = json_decode($data['processes'], true);
            } else {
                $serial_num = $request->input('serial_num');
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

            // Получаем существующие процессы
            $existingProcesses = $extra_process->processes ?? [];

            // Если редактируется конкретный процесс, обновляем только его
            if ($processIndex !== null || $processNameId !== null) {
                // Формируем обновленный процесс из данных формы
                $updatedProcess = null;
                foreach ($processesData as $processData) {
                    if (!isset($processData['process_names_id']) || !isset($processData['processes'])) {
                        continue;
                    }

                    $newProcessNameId = $processData['process_names_id'];
                    $processIds = $processData['processes'];

                    // Проверяем, что process_name_id существует
                    $processName = ProcessName::find($newProcessNameId);
                    if (!$processName) {
                        continue;
                    }

                    // Берем только первый process_id
                    if (!empty($processIds) && is_array($processIds)) {
                        $newProcessId = (int)$processIds[0];

                        // Проверяем, что process_id существует
                        $process = Process::find($newProcessId);
                        if ($process) {
                            // Формируем объект процесса
                            $updatedProcess = [
                                'process_name_id' => $newProcessNameId,
                                'process_id' => $newProcessId,
                                'description' => $processData['description'] ?? null,
                                'notes' => $processData['notes'] ?? null
                            ];

                            // Если это NDT процесс с дополнительными NDT, добавляем поля
                            if ($processName && strpos($processName->name, 'NDT-') === 0) {
                                if (isset($processData['plus_process_names']) && !empty($processData['plus_process_names'])) {
                                    $updatedProcess['plus_process_names'] = $processData['plus_process_names'];
                                    $updatedProcess['plus_process_ids'] = $processData['plus_process_ids'] ?? [];
                                }
                            }

                            break;
                        }
                    }
                }

                if ($updatedProcess) {
                    // Обновляем только конкретный процесс в массиве
                    if (is_array($existingProcesses) && array_keys($existingProcesses) !== range(0, count($existingProcesses) - 1)) {
                        // Старая структура: ассоциативный массив - конвертируем в новую структуру
                        $convertedProcesses = [];
                        foreach ($existingProcesses as $oldProcessNameId => $oldProcessId) {
                            if ($oldProcessNameId == $processNameId) {
                                // Заменяем обновленным процессом
                                $convertedProcesses[] = $updatedProcess;
                            } else {
                                // Сохраняем остальные процессы
                                $convertedProcesses[] = [
                                    'process_name_id' => (string)$oldProcessNameId,
                                    'process_id' => (int)$oldProcessId,
                                    'description' => null,
                                    'notes' => null
                                ];
                            }
                        }
                        $processesJson = $convertedProcesses;
                    } else {
                        // Новая структура: массив объектов
                        if ($processIndex !== null && isset($existingProcesses[$processIndex])) {
                            $existingProcesses[$processIndex] = $updatedProcess;
                        } elseif ($processNameId !== null) {
                            // Ищем по process_name_id и обновляем
                            foreach ($existingProcesses as $index => $processItem) {
                                if (isset($processItem['process_name_id']) && $processItem['process_name_id'] == $processNameId) {
                                    $existingProcesses[$index] = $updatedProcess;
                                    break;
                                }
                            }
                        }
                        $processesJson = array_values($existingProcesses);
                    }
                } else {
                    // Если не удалось сформировать updatedProcess, конвертируем существующие в новую структуру
                    if (is_array($existingProcesses) && array_keys($existingProcesses) !== range(0, count($existingProcesses) - 1)) {
                        // Старая структура: конвертируем в новую
                        $convertedProcesses = [];
                        foreach ($existingProcesses as $processNameId => $processId) {
                            $convertedProcesses[] = [
                                'process_name_id' => (string)$processNameId,
                                'process_id' => (int)$processId,
                                'description' => null,
                                'notes' => null
                            ];
                        }
                        $processesJson = $convertedProcesses;
                    } else {
                        $processesJson = $existingProcesses;
                    }
                }
            } else {
                // Если не передан конкретный процесс, обновляем все (старое поведение)
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
                            // Формируем объект процесса
                            $processObject = [
                                'process_name_id' => $processNameId,
                                'process_id' => $processId,
                                'description' => $processData['description'] ?? null,
                                'notes' => $processData['notes'] ?? null
                            ];

                            // Если это NDT процесс с дополнительными NDT, добавляем поля
                            if ($processName && strpos($processName->name, 'NDT-') === 0) {
                                if (isset($processData['plus_process_names']) && !empty($processData['plus_process_names'])) {
                                    $processObject['plus_process_names'] = $processData['plus_process_names'];
                                    $processObject['plus_process_ids'] = $processData['plus_process_ids'] ?? [];
                                }
                            }

                            $processesJson[] = $processObject;
                        }
                    }
                }
            }

            // Обновляем запись
            $updateData = [
                'processes' => $processesJson,
                'qty' => $qty,
            ];

            if (isset($serial_num)) {
                $updateData['serial_num'] = $serial_num;
            }

            $extra_process->update($updateData);

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
    public function showForm($id, $processNameId, Request $request)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $component = Component::findOrFail($extra_process->component_id);
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
            'component' => $component,
            'extra_process' => $extra_process,
            'manuals' => \App\Models\Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName,
            'selectedVendor' => $selectedVendor
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
                                'process_id' => $processId,
                                'description' => null, // Старая структура не содержит description
                                'notes' => null // Старая структура не содержит notes
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
                            'process_id' => $processId,
                            'description' => null, // Старая структура не содержит description
                            'notes' => null // Старая структура не содержит notes
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

    /**
     * Update the order of processes
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        try {
            $processesOrder = $request->input('processes_order');

            if (!is_array($processesOrder)) {
                return response()->json(['success' => false, 'message' => 'Invalid processes order data'], 400);
            }

            DB::transaction(function() use ($processesOrder) {
                foreach ($processesOrder as $extraProcessId => $orderData) {
                    $extraProcess = ExtraProcess::find($extraProcessId);

                    if (!$extraProcess) {
                        \Log::warning('ExtraProcess not found', ['id' => $extraProcessId]);
                        continue;
                    }

                    // Получаем текущие процессы
                    $currentProcesses = $extraProcess->processes ?? [];

                    if (empty($currentProcesses) || !is_array($currentProcesses)) {
                        \Log::warning('ExtraProcess has no processes or invalid format', [
                            'id' => $extraProcessId,
                            'processes' => $currentProcesses
                        ]);
                        continue;
                    }

                    // Проверяем структуру данных
                    $isOldFormat = array_keys($currentProcesses) !== range(0, count($currentProcesses) - 1);

                    if ($isOldFormat) {
                        // Старая структура: ассоциативный массив
                        // Конвертируем в новую структуру для обработки
                        $convertedProcesses = [];
                        foreach ($currentProcesses as $processNameId => $processId) {
                            $convertedProcesses[] = [
                                'process_name_id' => (string)$processNameId,
                                'process_id' => (int)$processId,
                                'description' => null, // Старая структура не содержит description
                                'notes' => null // Старая структура не содержит notes
                            ];
                        }
                        $currentProcesses = $convertedProcesses;
                    }

                    // Создаем новый массив процессов в правильном порядке
                    $reorderedProcesses = [];

                    // Сортируем orderData по new_index
                    usort($orderData, function($a, $b) {
                        return $a['new_index'] - $b['new_index'];
                    });

                    // Переупорядочиваем процессы согласно новому порядку
                    foreach ($orderData as $orderItem) {
                        $oldIndex = $orderItem['old_index'];
                        if (isset($currentProcesses[$oldIndex])) {
                            $reorderedProcesses[] = $currentProcesses[$oldIndex];
                        }
                    }

                    // Обновляем запись с переупорядоченными процессами
                    $extraProcess->update([
                        'processes' => $reorderedProcesses
                    ]);

                    \Log::info('ExtraProcess processes order updated', [
                        'id' => $extraProcessId,
                        'old_count' => count($currentProcesses),
                        'new_count' => count($reorderedProcesses)
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            \Log::error('Error updating extra processes order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the component of extra process.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function editComponent($id)
    {
        $extra_process = ExtraProcess::findOrFail($id);
        $current_wo = Workorder::findOrFail($extra_process->workorder_id);
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();

        return view('admin.extra_processes.edit_component', compact(
            'current_wo',
            'extra_process',
            'components',
            'manual_id'
        ));
    }

    /**
     * Update the component of extra process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function updateComponent(Request $request, $id)
    {
        try {
            $extra_process = ExtraProcess::findOrFail($id);

            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $componentId = (int)($data['component_id'] ?? 0);
                $serial_num = $data['serial_num'] ?? null;
                $qty = (int)($data['qty'] ?? 1);
            } else {
                $componentId = (int)$request->input('component_id');
                $serial_num = $request->input('serial_num');
                $qty = (int)$request->input('qty', 1);
            }

            // Валидация
            $validator = \Validator::make([
                'component_id' => $componentId,
                'serial_num' => $serial_num,
                'qty' => $qty,
            ], [
                'component_id' => 'required|exists:components,id',
                'serial_num' => 'nullable|string',
                'qty' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Проверяем существование component
            $component = Component::find($componentId);
            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component not found.'
                ], 422);
            }

            // Обновляем запись
            $extra_process->update([
                'component_id' => $componentId,
                'serial_num' => $serial_num,
                'qty' => $qty,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Component updated successfully.',
                'redirect' => route('extra_processes.show_all', ['id' => $extra_process->workorder_id])
            ]);

        } catch (\Exception $e) {
            \Log::error('ExtraProcess updateComponent error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating component: ' . $e->getMessage()
            ], 500);
        }
    }
}
