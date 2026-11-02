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
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $editingRecord = request()->filled('edit_id')
            ? ExtraProcess::where('workorder_id', $current_wo->id)->findOrFail(request('edit_id')) : null;
        $manual_id = $editingRecord ? $editingRecord->component->manual_id : $current_wo->unit->manual_id;
        $savedItems = $editingRecord ? $this->editableItems($editingRecord) : [];
        $editIndex = (int) request('process_index', 0);
        if (request()->filled('process_name_id')) {
            foreach ($savedItems as $key => $item) {
                if ((int) $item['process_name_id'] === (int) request('process_name_id')) $editIndex = $key;
            }
        }
        if ($editingRecord) abort_unless(isset($savedItems[$editIndex]), 422);
        $editingItem = $savedItems[$editIndex] ?? null;
        $components = Component::where('manual_id', $manual_id)->get();
        $processNames = ProcessName::forPicker()->orderBy('name')->get();
        if ($editingItem && !$processNames->contains('id', $editingItem['process_name_id'])) {
            $savedName = ProcessName::find($editingItem['process_name_id']);
            if ($savedName) $processNames->push($savedName);
        }
        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();
        $manuals = Manual::all();

        return view('admin.extra_processes.create', compact('current_wo', 'components', 'processNames', 'processes', 'manual_id', 'manuals', 'editingRecord', 'editingItem', 'editIndex', 'savedItems'));
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
            // Проверяем, приходят ли данные как JSON
            if ($request->isJson()) {
                $data = $request->json()->all();
                $workorderId = (int)($data['workorder_id'] ?? 0);
                $componentId = (int)($data['component_id'] ?? 0);
                $serial_num = $data['serial_num'] ?? null;
                $qty = (int)($data['qty'] ?? 1);
                $processesInput = $data['processes'] ?? null;
                $processesData = is_string($processesInput)
                    ? json_decode($processesInput, true)
                    : $processesInput;

            } else {
                $workorderId = (int)($request->input('workorder_id'));
                $componentId = (int)($request->input('component_id'));
                $serial_num = $request->input('serial_num');
                $qty = (int)($request->input('qty', 1));
                $processesData = $request->input('processes');

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
                            'notes' => $processData['notes'] ?? null,
                            'vendor_id' => $request->input('vendor_id')
                        ];

                        // Если это NDT процесс с дополнительными NDT, добавляем поля
                        $processName = ProcessName::find($processNameId);
                        if ($processName && strpos($processName->identityName(), 'NDT-') === 0) {
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

            $request->validate(['qty' => 'required|integer|min:1', 'serial_num' => 'nullable|string|max:255', 'vendor_id' => 'nullable|exists:vendors,id']);
            foreach ($processesJson as $item) {
                $selectedProcess = Process::find($item['process_id']);
                if (!ProcessName::canPrintProcessForm(ProcessName::find($item['process_name_id']))
                    || (int) $selectedProcess->process_names_id !== (int) $item['process_name_id']
                    || !$selectedProcess->manuals()->where('manuals.id', $component->manual_id)->exists()) {
                    return response()->json(['success' => false, 'message' => 'Select a printable specification from the selected part manual.'], 422);
                }
            }

            // Получаем максимальный sort_order для данного workorder_id и component_id
            $maxSortOrder = ExtraProcess::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->max('sort_order') ?? 0;

            // Создаем новую запись
            $createdExtraProcess = ExtraProcess::create([
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
                'form_url' => route('extra_processes.show_form', ['id' => $createdExtraProcess->id, 'processNameId' => $processesJson[0]['process_name_id']]),
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
     * Return partial HTML for Extra Part Processes tab (AJAX).
     *
     * @param  int  $id  Workorder ID
     * @return Application|Factory|View
     */
    public function extraProcessesPartial($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $extra_components = ExtraProcess::where('workorder_id', $current_wo->id)
            ->with(['component', 'workorder'])
            ->get();

        $processGroups = [];
        foreach ($extra_components as $extra_component) {
            if (!$extra_component->processes || !$extra_component->component) {
                continue;
            }
            if (is_array($extra_component->processes) && array_keys($extra_component->processes) !== range(0, count($extra_component->processes) - 1)) {
                foreach ($extra_component->processes as $processNameId => $processId) {
                    $processName = ProcessName::find($processNameId);
                    if (ProcessName::canPrintProcessForm($processName)) {
                        $groupKey = ($processName->process_sheet_name == 'NDT') ? 'NDT_GROUP' : $processNameId;
                        if (!isset($processGroups[$groupKey])) {
                            if ($groupKey == 'NDT_GROUP') {
                                $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->where('print_form', true)->first();
                                $processGroups[$groupKey] = ['process_name' => $ndtProcessName ?? $processName, 'components_qty' => [], 'components' => []];
                            } else {
                                $processGroups[$groupKey] = ['process_name' => $processName, 'components_qty' => [], 'components' => []];
                            }
                        }
                        $processGroups[$groupKey]['components_qty'][$extra_component->component->id] = (int)($extra_component->qty ?? 0);
                        if (!isset($processGroups[$groupKey]['components'][$extra_component->component->id])) {
                            $processGroups[$groupKey]['components'][$extra_component->component->id] = [
                                'id' => $extra_component->component->id,
                                'name' => $extra_component->component->name,
                                'ipl_num' => $extra_component->component->ipl_num,
                                'qty' => (int)($extra_component->qty ?? 0)
                            ];
                        }
                    }
                }
            } else {
                foreach ($extra_component->processes as $processItem) {
                    $processName = ProcessName::find($processItem['process_name_id']);
                    if (ProcessName::canPrintProcessForm($processName)) {
                        $groupKey = ($processName->process_sheet_name == 'NDT') ? 'NDT_GROUP' : $processItem['process_name_id'];
                        if (!isset($processGroups[$groupKey])) {
                            if ($groupKey == 'NDT_GROUP') {
                                $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->where('print_form', true)->first();
                                $processGroups[$groupKey] = ['process_name' => $ndtProcessName ?? $processName, 'components_qty' => [], 'components' => []];
                            } else {
                                $processGroups[$groupKey] = ['process_name' => $processName, 'components_qty' => [], 'components' => []];
                            }
                        }
                        $processGroups[$groupKey]['components_qty'][$extra_component->component->id] = (int)($extra_component->qty ?? 0);
                        if (!isset($processGroups[$groupKey]['components'][$extra_component->component->id])) {
                            $processGroups[$groupKey]['components'][$extra_component->component->id] = [
                                'id' => $extra_component->component->id,
                                'name' => $extra_component->component->name,
                                'ipl_num' => $extra_component->component->ipl_num,
                                'qty' => (int)($extra_component->qty ?? 0)
                            ];
                        }
                    }
                }
            }
        }
        foreach ($processGroups as $processNameId => &$group) {
            $group['count'] = count(array_keys($group['components_qty']));
            $group['qty'] = array_sum($group['components_qty']);
            $group['components'] = array_values($group['components']);
            unset($group['components_qty']);
        }

        $vendors = Vendor::all();

        return view('admin.tdrs.partials.extra-part-processes', compact('current_wo', 'extra_components', 'processGroups', 'vendors'));
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

        if (!ProcessName::canPrintProcessForm($processName)) {
            return redirect()->back()->with('error', __('There is no form for this process.'));
        }

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
                ->where('print_form', true)
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
                            if ($process && ProcessName::canPrintProcessForm($ndtProcessName)) {
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
                            if ($process && ProcessName::canPrintProcessForm($ndtProcessName)) {
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
            'selectedVendor' => $selectedVendor,
            'machining_header_manual_libs' => ProcessName::isMachiningPrintedForm($processName)
                ? Manual::orderedLibValuesForManualIds(Manual::manualIdsForWorkorder((int) $current_wo->id))
                : [],
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
            $processNames = ProcessName::whereIdentityNames([
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->where('print_form', true)->pluck('id', 'identity_name');

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
                if (strpos($processName->identityName(), 'NDT-') === 0) {
                    return substr($processName->identityName(), 4);
                } elseif ($processName->identityName() === 'Eddy Current Test') {
                    return '6';
                } elseif ($processName->identityName() === 'BNI') {
                    return '5';
                }
                return substr($processName->identityName(), -1);
            };

            // Группируем NDT процессы по компонентам для объединения номеров
            $componentNdtMap = [];

            foreach ($groupedComponents as $index => $item) {
                if (!isset($item['component']) || !$item['component']) {
                    \Log::warning('ExtraProcess NDT: item missing component', ['index' => $index]);
                    continue;
                }

                $componentId = $item['component']->id;

                // Инициализируем запись для компонента, если её еще нет
                if (!isset($componentNdtMap[$componentId])) {
                    $componentNdtMap[$componentId] = [
                        'component' => $item['component'],
                        'extra_process' => $item['extra_process'] ?? null,
                        'manual' => $item['manual'] ?? null,
                        'ndt_numbers' => [],
                        'processes' => [],
                        'process_names' => []
                    ];
                }

                // Добавляем номер NDT процесса
                if (isset($item['process_name']) && $item['process_name'] && isset($item['process']) && $item['process']) {
                    $ndtNumber = $getNdtNumber($item['process_name']);

                    // Добавляем номер, если его еще нет (используем строгое сравнение)
                    if (!in_array($ndtNumber, $componentNdtMap[$componentId]['ndt_numbers'], true)) {
                        $componentNdtMap[$componentId]['ndt_numbers'][] = $ndtNumber;
                        $componentNdtMap[$componentId]['processes'][] = $item['process'];
                        $componentNdtMap[$componentId]['process_names'][] = $item['process_name'];
                    } else {
                    }
                } else {
                    \Log::warning('ExtraProcess NDT: item missing process_name or process', [
                        'component_id' => $componentId,
                        'has_process_name' => isset($item['process_name']),
                        'has_process' => isset($item['process'])
                    ]);
                }
            }

            // Преобразуем карту в массив tableData с объединенными номерами
            $tableData = [];
            foreach ($componentNdtMap as $componentId => $componentData) {
                if (empty($componentData['ndt_numbers'])) {
                    \Log::warning('ExtraProcess NDT: component has no ndt_numbers', [
                        'component_id' => $componentId
                    ]);
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
                $firstProcessName = !empty($componentData['process_names']) ? $componentData['process_names'][0] : null;

                $tableData[] = [
                    'component' => $componentData['component'],
                    'extra_process' => $componentData['extra_process'],
                    'manual' => $componentData['manual'],
                    'combined_ndt_number' => $combinedNdtNumber, // Всегда устанавливаем combined_ndt_number
                    'process' => $firstProcess,
                    'process_name' => $firstProcessName
                ];
            }

            // Собираем все уникальные номера manual из компонентов
            $manualNumbers = [];
            foreach ($tableData as $item) {
                if (isset($item['manual']) && $item['manual']) {
                    $manualNumber = $item['manual']->number;
                    if (!in_array($manualNumber, $manualNumbers)) {
                        $manualNumbers[] = $manualNumber;
                    }
                }
            }
            $manualNumbersString = implode(', ', $manualNumbers);

            // Обновляем table_data в viewData - ВАЖНО: перезаписываем groupedComponents на tableData с объединенными номерами
            $viewData['table_data'] = $tableData;

            // Явно устанавливаем table_data с объединенными номерами
            $finalViewData = array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'selectedVendor' => $selectedVendor,
                'manual_numbers' => $manualNumbersString
            ], $ndt_ids);

            // ВАЖНО: Перезаписываем table_data на tableData с объединенными номерами
            $finalViewData['table_data'] = $tableData;

            return view('admin.extra_processes.processesForm', $finalViewData);
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

    private function editableItems(ExtraProcess $record): array
    {
        $items = $record->processes ?? [];
        if (!$items || is_array(reset($items))) return array_values($items);
        $result = [];
        foreach ($items as $nameId => $processId) {
            $result[] = ['process_name_id' => (int) $nameId, 'process_id' => (int) $processId];
        }
        return $result;
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'workorder_id' => 'required|integer', 'component_id' => 'required|exists:components,id',
            'serial_num' => 'nullable|string|max:255', 'qty' => 'required|integer|min:1',
            'process_index' => 'required|integer|min:0', 'process_name_id' => 'required|exists:process_names,id',
            'process_id' => 'required|exists:processes,id', 'description' => 'nullable|string',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);
        return DB::transaction(function () use ($data, $id) {
            $record = ExtraProcess::where('workorder_id', $data['workorder_id'])->lockForUpdate()->findOrFail($id);
            $component = Component::findOrFail($data['component_id']);
            $items = $this->editableItems($record);
            $index = (int) $data['process_index'];
            abort_unless(isset($items[$index]), 422, 'Saved process no longer exists. Reload the editor.');
            $old = $items[$index];
            $item = array_replace($old, [
                'process_name_id' => (int) $data['process_name_id'], 'process_id' => (int) $data['process_id'],
                'description' => $data['description'] ?? null, 'vendor_id' => $data['vendor_id'] ?? null,
            ]);
            // Supplemental NDT specifications belong to their original primary process.
            if ((int) $old['process_name_id'] !== $item['process_name_id'] || (int) $old['process_id'] !== $item['process_id']) {
                unset($item['plus_process_names'], $item['plus_process_ids']);
            }
            $items[$index] = $item;
            foreach ($items as $key => $candidate) {
                // Existing historical links may be absent from today's manual. Validate changed links only.
                if ($key !== $index && (int) $record->component_id === (int) $component->id) continue;
                $unchanged = $key === $index && (int) $record->component_id === (int) $component->id
                    && (int) $old['process_name_id'] === (int) $candidate['process_name_id']
                    && (int) $old['process_id'] === (int) $candidate['process_id'];
                if ($unchanged) continue;
                $spec = Process::find($candidate['process_id']);
                abort_unless($spec && (int) $spec->process_names_id === (int) $candidate['process_name_id']
                    && $spec->manuals()->where('manuals.id', $component->manual_id)->exists(), 422,
                    'Select a specification from the selected part manual.');
            }
            $record->update(['component_id' => $component->id, 'serial_num' => $data['serial_num'] ?? null,
                'qty' => $data['qty'], 'processes' => $items]);
            return response()->json(['success' => true, 'message' => 'Saved.', 'id' => $record->id]);
        });
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

        if (!ProcessName::canPrintProcessForm($processName)) {
            return redirect()->back()->with('error', __('There is no form for this process.'));
        }

        // Получаем vendor_id из запроса
        $vendorId = $request->input('vendor_id');
        $selectedVendor = null;
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Получаем связанные данные
        $manual_id = $component->manual_id;
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
            'selectedVendor' => $selectedVendor,
            'machining_header_manual_libs' => ProcessName::isMachiningPrintedForm($processName)
                ? Manual::orderedLibValuesForManualIds(Manual::manualIdsForWorkorder((int) $current_wo->id))
                : [],
        ];

        // Обработка NDT формы (если нужно)
        if ($processName->process_sheet_name == 'NDT') {
            // Получаем ID process names одним запросом
            $processNames = ProcessName::whereIdentityNames([
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->where('print_form', true)->pluck('id', 'identity_name');

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
                if (strpos($processName->identityName(), 'NDT-') === 0) {
                    return substr($processName->identityName(), 4);
                } elseif ($processName->identityName() === 'Eddy Current Test') {
                    return '6';
                } elseif ($processName->identityName() === 'BNI') {
                    return '5';
                }
                return substr($processName->identityName(), -1);
            };

            // Получаем все NDT process_name_ids для группировки
            $ndtProcessNameIds = ProcessName::where('process_sheet_name', 'NDT')
                ->where('print_form', true)
                ->pluck('id')
                ->toArray();

            // Собираем все NDT процессы для этого компонента (включая plus_process_names)
            $ndtProcessesData = [];
            if ($extra_process->processes && !empty($extra_process->processes)) {
                if (is_array($extra_process->processes) && array_keys($extra_process->processes) !== range(0, count($extra_process->processes) - 1)) {
                    // Старая структура: ассоциативный массив
                    foreach ($ndtProcessNameIds as $ndtProcessNameId) {
                        if (isset($extra_process->processes[$ndtProcessNameId])) {
                            $processId = $extra_process->processes[$ndtProcessNameId];
                            $process = Process::find($processId);
                            $ndtProcessName = ProcessName::find($ndtProcessNameId);
                            if ($process && ProcessName::canPrintProcessForm($ndtProcessName)) {
                                $ndtProcessesData[] = [
                                    'process_name' => $ndtProcessName,
                                    'process' => $process,
                                    'process_name_id' => $ndtProcessNameId,
                                    'process_id' => $processId
                                ];
                            }
                        }
                    }
                } else {
                    // Новая структура: массив объектов
                    foreach ($extra_process->processes as $processItem) {
                        $processNameId = is_array($processItem) ? $processItem['process_name_id'] : null;

                        // Проверяем основной процесс
                        if ($processNameId && in_array($processNameId, $ndtProcessNameIds)) {
                            $process = Process::find($processItem['process_id']);
                            $ndtProcessName = ProcessName::find($processNameId);
                            if ($process && ProcessName::canPrintProcessForm($ndtProcessName)) {
                                $ndtProcessesData[] = [
                                    'process_name' => $ndtProcessName,
                                    'process' => $process,
                                    'process_name_id' => $processNameId,
                                    'process_id' => $processItem['process_id']
                                ];
                            }
                        }

                        // Проверяем plus_process_names (объединенные процессы)
                        if (isset($processItem['plus_process_names']) && is_array($processItem['plus_process_names'])) {
                            foreach ($processItem['plus_process_names'] as $plusProcessNameId) {
                                if (in_array($plusProcessNameId, $ndtProcessNameIds)) {
                                    // Ищем процесс по plus_process_ids
                                    $plusProcessId = null;
                                    if (isset($processItem['plus_process_ids']) && is_array($processItem['plus_process_ids'])) {
                                        $index = array_search($plusProcessNameId, $processItem['plus_process_names']);
                                        if ($index !== false && isset($processItem['plus_process_ids'][$index])) {
                                            $plusProcessId = $processItem['plus_process_ids'][$index];
                                        }
                                    }

                                    if ($plusProcessId) {
                                        $process = Process::find($plusProcessId);
                                        $ndtProcessName = ProcessName::find($plusProcessNameId);
                                        if ($process && ProcessName::canPrintProcessForm($ndtProcessName)) {
                                            // Проверяем, не добавлен ли уже этот процесс
                                            $alreadyAdded = false;
                                            foreach ($ndtProcessesData as $existing) {
                                                if ($existing['process_name_id'] == $plusProcessNameId && $existing['process_id'] == $plusProcessId) {
                                                    $alreadyAdded = true;
                                                    break;
                                                }
                                            }
                                            if (!$alreadyAdded) {
                                                $ndtProcessesData[] = [
                                                    'process_name' => $ndtProcessName,
                                                    'process' => $process,
                                                    'process_name_id' => $plusProcessNameId,
                                                    'process_id' => $plusProcessId
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Группируем NDT процессы и создаем combined_ndt_number
            $ndtNumbers = [];
            $firstProcess = null;
            $firstProcessName = null;

            foreach ($ndtProcessesData as $ndtProcessData) {
                $ndtNumber = $getNdtNumber($ndtProcessData['process_name']);
                if (!in_array($ndtNumber, $ndtNumbers, true)) {
                    $ndtNumbers[] = $ndtNumber;
                }
                if ($firstProcess === null) {
                    $firstProcess = $ndtProcessData['process'];
                    $firstProcessName = $ndtProcessData['process_name'];
                }
            }

            // Сортируем номера для правильного отображения
            usort($ndtNumbers, function($a, $b) {
                return (int)$a <=> (int)$b;
            });

            // Объединяем номера через " / "
            $combinedNdtNumber = !empty($ndtNumbers) ? implode(' / ', $ndtNumbers) : null;

            // Создаем массив данных для отображения в таблице
            $tableData = [];
            if (!empty($ndtProcessesData)) {
                // Используем первый процесс для совместимости с шаблоном
                $tableData[] = [
                    'process_name' => $firstProcessName ?: $processName,
                    'process' => $firstProcess,
                    'component' => $component,
                    'extra_process' => $extra_process,
                    'combined_ndt_number' => $combinedNdtNumber // Добавляем объединенный номер
                ];
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


}
