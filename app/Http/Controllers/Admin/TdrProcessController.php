<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;

class TdrProcessController extends Controller
{
    /**
     * Получает manual_id для TDR записи
     * Сначала пытается получить из компонента, затем из workorder
     */
    private function getManualIdForTdr($tdrId)
    {
        $tdr = Tdr::with('component')->findOrFail($tdrId);

        // Если есть компонент, используем его manual_id
        if ($tdr->component && $tdr->component->manual_id) {
            return $tdr->component->manual_id;
        }

        // Иначе используем manual_id из workorder (fallback)
        return $tdr->workorder->unit->manual_id ?? null;
    }

    /**
     * Получает все manual_id для workorder (включая manual'ы компонентов)
     */
    private function getManualIdsForWorkorder($workorderId)
    {
        $workorder = Workorder::findOrFail($workorderId);
        $manualIds = collect();

        // Добавляем основной manual_id из workorder
        if ($workorder->unit && $workorder->unit->manual_id) {
            $manualIds->push($workorder->unit->manual_id);
        }

        // Добавляем manual_id из всех компонентов TDR записей
        $tdrs = Tdr::with('component')->where('workorder_id', $workorderId)->get();
        foreach ($tdrs as $tdr) {
            if ($tdr->component && $tdr->component->manual_id) {
                $manualIds->push($tdr->component->manual_id);
            }
        }

        return $manualIds->unique()->values()->toArray();
    }
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
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
    public function create()
    {
       //
    }



    public function createProcesses(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID с загруженным компонентом
        $current_tdr = Tdr::with('component')->findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        // Если Workorder не найден, выбрасываем исключение
        if (!$current_wo) {
            abort(404, 'Workorder not found');
        }

        // Получаем manual_id для данного TDR
        $manual_id = $this->getManualIdForTdr($tdrId);

        // Если manual_id не найден, выбрасываем ошибку
        if (!$manual_id) {
            abort(404, 'Manual not found for component or workorder');
        }

        // Получаем имена процессов
        $processNames = ProcessName::all();

        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем процессы, уже связанные с текущим Tdr
        $tdrProcesses = TdrProcess::where('tdrs_id', $tdrId)->orderBy('sort_order')->get();

        // Передаем данные в представление
        return view('admin.tdr-processes.createProcesses', compact(
            'current_tdr',
            'current_wo',
            'processNames',
            'processes',
            'tdrProcesses',
            'manual_id'
        ));
    }

    public function getProcess($processNameId, Request $request)
    {
        try {
            // Получаем manual_id из запроса
            $manualId = $request->query('manual_id');

            // Валидация и преобразование processNameId в число
            $processNameId = (int)$processNameId;
            if (!$processNameId || $processNameId <= 0) {
                return response()->json(['error' => 'Invalid process name ID'], 400);
            }

            // Проверяем существование ProcessName
            $processName = ProcessName::find($processNameId);
            if (!$processName) {
                return response()->json(['error' => 'Process name not found'], 404);
            }

            // Фильтруем процессы по processNameId
            $query = Process::where('process_names_id', $processNameId);

            // Если manual_id передан, фильтруем по нему
            if ($manualId) {
                $manualId = (int)$manualId;
                if ($manualId > 0) {
                    $query->whereHas('manuals', function ($query) use ($manualId) {
                        $query->where('manual_id', $manualId);
                    });
                }
            }

            $processes = $query->get();

            // Log or inspect the response data for debugging
            Log::info('getProcess called', [
                'processNameId' => $processNameId,
                'manualId' => $manualId,
                'processes_count' => $processes->count()
            ]);

            return response()->json($processes);
        } catch (\Exception $e) {
            Log::error('Error in getProcess', [
                'processNameId' => $processNameId ?? null,
                'manualId' => $request->query('manual_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error retrieving processes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProcesses(Request $request)
    {
        $processNameId = $request->query('processNameId');
        $manualId = $request->query('manualId');

        // Получаем процессы, которые уже связаны с данным manual_id
        $existingProcessIds = ManualProcess::where('manual_id', $manualId)
            ->pluck('processes_id')
            ->toArray();

        // Фильтруем процессы для выбора (исключаем существующие)
        $availableProcesses = Process::where('process_names_id', $processNameId)
            ->whereNotIn('id', $existingProcessIds)
            ->get();

        // Получаем существующие процессы
        $existingProcesses = Process::whereIn('id', $existingProcessIds)
            ->where('process_names_id', $processNameId)
            ->get();

        return response()->json([
            'existingProcesses' => $existingProcesses,
            'availableProcesses' => $availableProcesses,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'tdrs_id' => 'required|integer|exists:tdrs,id',
            'processes' => 'required|json'
        ]);

        $tdrId = $request->input('tdrs_id');
        $processesJson = $request->input('processes');
        $processesData = json_decode($processesJson, true);

        // Логируем входящие данные для отладки
        Log::info('Store method called', [
            'tdrs_id' => $tdrId,
            'processes_json' => $processesJson,
            'processes_data' => $processesData
        ]);

        // Если processesData пустой, возвращаем ошибку
        if (empty($processesData)) {
            Log::warning('No processes selected', ['tdrs_id' => $tdrId]);
            return response()->json(['error' => 'No processes selected.'], 400);
        }

        // Находим запись Tdr
        $tdr = Tdr::find($tdrId);
        if (!$tdr) {
            return response()->json(['error' => 'TDR not found.'], 404);
        }

        // Получаем максимальный sort_order для данного tdr_id
        $maxSortOrder = TdrProcess::where('tdrs_id', $tdrId)->max('sort_order') ?? 0;

        // Счетчик для правильного расчета sort_order (учитывает дополнительные записи EC)
        $sortOrderCounter = 0;

        // Сохраняем каждый процесс
        foreach ($processesData as $index => $data) {
            $machiningProcessNameId = 10; // ID для процесса 'Machining'
            $isMachining = (int)$data['process_names_id'] === $machiningProcessNameId;

            // Проверяем значение ec (может быть true, 1, "1", или отсутствовать)
            $ecValue = isset($data['ec']) ? (
                $data['ec'] === true ||
                $data['ec'] === 1 ||
                $data['ec'] === '1' ||
                $data['ec'] === 'true'
            ) : false;

            // Логируем для отладки
            Log::info('Processing process data', [
                'process_names_id' => $data['process_names_id'],
                'isMachining' => $isMachining,
                'ec_value' => $data['ec'] ?? null,
                'ec_checked' => $ecValue,
                'tdrs_id' => $tdrId
            ]);

            // Создаем запись процесса
            TdrProcess::create([
                'tdrs_id' => $tdrId,
                'process_names_id' => $data['process_names_id'],
                'processes' => json_encode($data['processes']), // Сохраняем массив ID процессов
                'sort_order' => $maxSortOrder + $sortOrderCounter + 1, // Устанавливаем sort_order в конец списка
                'date_start' => null,
                'date_finish' => null,
                'ec' => $ecValue, // Добавляем поле EC
                'description' => $data['description'] ?? null, // Добавляем поле description (необязательное)
                'notes' => $data['notes'] ?? null, // Добавляем поле notes (необязательное)
            ]);

            $sortOrderCounter++;

            // Если это процесс 'Machining' с отмеченным чекбоксом 'EC'
            if ($isMachining && $ecValue) {
                Log::info('Machining with EC checked - processing', [
                    'tdrs_id' => $tdrId,
                    'process_names_id' => $data['process_names_id']
                ]);

                // 1. Проверяем наличие ProcessName с name = 'EC'
                $ecProcessName = ProcessName::where('name', 'EC')->first();

                // Если нет - создаем запись
                if (!$ecProcessName) {
                    try {
                        $ecProcessName = ProcessName::create([
                            'name' => 'EC',
                            'process_sheet_name' => 'EC',
                            'form_number' => 'EC',
                        ]);
                        Log::info('Created new ProcessName with name "EC"', [
                            'id' => $ecProcessName->id,
                            'name' => $ecProcessName->name,
                            'process_sheet_name' => $ecProcessName->process_sheet_name,
                            'form_number' => $ecProcessName->form_number
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error creating ProcessName "EC"', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Если не удалось создать ProcessName, пропускаем создание TdrProcess для EC
                        // но продолжаем обработку других процессов
                        continue;
                    }
                } else {
                    Log::info('ProcessName "EC" already exists', [
                        'id' => $ecProcessName->id
                    ]);
                }

                // Проверяем, что $ecProcessName был успешно получен или создан
                if ($ecProcessName && $ecProcessName->id) {
                    // 2. Создаем дополнительную запись для 'EC' процесса
                    try {
                        TdrProcess::create([
                            'tdrs_id' => $tdrId,
                            'process_names_id' => $ecProcessName->id,
                            'processes' => json_encode($data['processes']), // Те же processes что и у Machining
                            'sort_order' => $maxSortOrder + $sortOrderCounter + 1, // Следующий порядок после Machining
                            'date_start' => null,
                            'date_finish' => null,
                            'ec' => 0, // Поле ec = 0
                            'description' => $data['description'] ?? null, // Добавляем поле description (необязательное)
                            'notes' => $data['notes'] ?? null, // Добавляем поле notes (необязательное)
                        ]);

                        $sortOrderCounter++; // Увеличиваем счетчик, так как создали дополнительную запись

                        Log::info('Created EC process record for Machining', [
                            'tdrs_id' => $tdrId,
                            'ec_process_name_id' => $ecProcessName->id,
                            'machining_processes' => $data['processes']
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error creating TdrProcess for EC', [
                            'tdrs_id' => $tdrId,
                            'ec_process_name_id' => $ecProcessName->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::error('EC ProcessName not available for creating TdrProcess', [
                        'tdrs_id' => $tdrId
                    ]);
                }
            }
        }

        // Возвращаем JSON-ответ с URL для перенаправления
        return response()->json([
            'message' => 'Processes saved successfully!',
            'redirect' => route('tdr-processes.processes', ['tdrId' => $tdrId])
        ], 200);
    }

    public function travelForm(Request $request, $id)
    {
        $current_tdr = Tdr::findOrFail($id);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        $manual = $current_wo->unit->manuals;

        $tdrProcesses = TdrProcess::orderBy('sort_order')->get();

        $proces = Process::all();

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        // Получаем параметры из запроса
        $repairNum = $request->input('repair_num', 'N/A');
        $vendorId = $request->input('vendor');
        
        // Получаем данные о vendor для каждой строки
        $vendorsData = [];
        $vendorsDataJson = $request->input('vendors_data');
        if ($vendorsDataJson) {
            try {
                $vendorsData = json_decode($vendorsDataJson, true);
            } catch (\Exception $e) {
                \Log::error('Error parsing vendors_data: ' . $e->getMessage());
            }
        }
        
        // Получаем данные о отмеченных чекбоксах AT
        $atData = [];
        $atDataJson = $request->input('at_data');
        if ($atDataJson) {
            try {
                $atData = json_decode($atDataJson, true);
            } catch (\Exception $e) {
                \Log::error('Error parsing at_data: ' . $e->getMessage());
            }
        }
        
        // Получаем имя вендора если передан ID (для обратной совместимости)
        $vendorName = null;
        if ($vendorId) {
            $vendor = Vendor::find($vendorId);
            $vendorName = $vendor ? $vendor->name : null;
        }

        return view('admin.tdr-processes.travelForm',compact('current_tdr',
            'current_wo','tdrProcesses','proces','vendors', 'manual', 'repairNum', 'vendorName', 'vendorsData', 'atData' ));
    }

    public function processesForm(Request $request, $id)
    {
        // Загрузка Workorder с необходимыми отношениями
        $current_wo = Workorder::findOrFail($id);

        // Получаем все manual_id для данного workorder
        $manualIds = $this->getManualIdsForWorkorder($id);

        // Для обратной совместимости оставляем первый manual_id
        $manual_id = $manualIds[0] ?? null;

        // Получаем ID процесса из запроса
        $processes_name_id = $request->input('process_name_id');
        $process_name = ProcessName::findOrFail($processes_name_id);

        // Получаем выбранного Vendor (если передан)
        $vendorId = $request->input('vendor_id');
        $selectedVendor = $vendorId ? Vendor::find($vendorId) : null;

        // Получаем компоненты из всех manual'ов
        $components = Component::whereIn('manual_id', $manualIds)->get();
        $tdr_ids = Tdr::where('workorder_id', $current_wo->id)->pluck('id');

        // Получаем manual processes из всех manual'ов
        $manualProcesses = ManualProcess::whereIn('manual_id', $manualIds)
            ->pluck('processes_id');

        // Обработка NDT формы
        if ($process_name->process_sheet_name == 'NDT') {
            // Получаем ID process names одним запросом
            $processNames = ProcessName::whereIn('name', [
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->pluck('id', 'name');

            // Извлекаем ID по именам
            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'],
                'ndt4_name_id' => $processNames['NDT-4'],
                'ndt6_name_id' => $processNames['Eddy Current Test'],
                'ndt5_name_id' => $processNames['BNI']
            ];

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids) {
                    $query->whereIn('process_names_id', $ndt_ids)
                        // Всегда включаем процессы для NDT-1 и NDT-4, даже если они не связаны с текущим процессом
                        ->orWhere('process_names_id', $ndt_ids['ndt1_name_id'])
                        ->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                })
                ->get();

            // Получаем NDT components
            $ndt_components = TdrProcess::whereIn('tdrs_id', $tdr_ids)
                ->whereIn('process_names_id', $ndt_ids)
                ->with(['tdr', 'processName'])
                ->orderBy('sort_order')
                ->get();

            return view('admin.tdr-processes.processesForm', array_merge([
                'current_wo' => $current_wo,
                'components' => $components,
                'tdrs' => $tdr_ids,
                'manuals' => Manual::where('id', $manual_id)->get(),
                'process_name' => $process_name,
                'ndt_processes' => $ndt_processes,
                'ndt_components' => $ndt_components,
                'selectedVendor' => $selectedVendor
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processes_name_id)
            ->get();

        $process_tdr_components = TdrProcess::whereIn('tdrs_id', $tdr_ids)
            ->where('process_names_id', $processes_name_id)
            ->with(['tdr', 'processName'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.tdr-processes.processesForm', [
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => $tdr_ids,
            'manuals' => Manual::where('id', $manual_id)->get(),
            'process_name' => $process_name,
            'process_components' => $process_components,
            'process_tdr_components' => $process_tdr_components,
            'manual_id' => $manual_id,
            'selectedVendor' => $selectedVendor
        ]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id, Request $request)
    {
        // Загружаем процесс TDR со связанными данными (жадная загрузка)
        $current_tdrs_process = TdrProcess::with([
            'processName',                   // Название процесса
            'tdr.workorder.unit.manuals',    // Рабочий заказ -> агрегат ->
            // руководство
            'tdr.workorder'                 // Рабочий заказ
        ])->findOrFail($id);

        // Получаем vendor_id и (опционально) конкретный process_id из запроса
        $vendorId = $request->input('vendor_id');
        $specificProcessId = $request->input('process_id');
        $selectedVendor = null;
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Получаем связанные данные через отношения
        $process_name = $current_tdrs_process->processName;
        $current_tdr = $current_tdrs_process->tdr;
        $current_wo = $current_tdr->workorder;
        $manual_id = $current_wo->unit->manual_id;

        // Получаем компоненты и процессы из руководства
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,             // Текущий рабочий заказ
            'components' => $components,             // Компоненты
            'tdrs' => [$current_tdr->id],           // ID связанных TDR (массив для совместимости)
            'manuals' => Manual::where('id', $manual_id)->get(), // Руководства
            'process_name' => $process_name,         // Название процесса
            'manual_id' => $manual_id,              // ID руководства
            'selectedVendor' => $selectedVendor     // Выбранный поставщик
        ];

        // Обработка случая для NDT-форм
        if ($process_name->process_sheet_name == 'NDT') {
            // Получаем ID process names для NDT
            $ndt_ids = [
                'ndt1_name_id' => ProcessName::where('name', 'NDT-1')->value('id'),
                'ndt4_name_id' => ProcessName::where('name', 'NDT-4')->value('id'),
                'ndt6_name_id' => ProcessName::where('name', 'Eddy Current Test')->value('id'),
                'ndt5_name_id' => ProcessName::where('name', 'BNI')->value('id')
            ];

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids) {
                    $query->whereIn('process_names_id', $ndt_ids)
                        // Всегда включаем процессы для NDT-1 и NDT-4, даже если они не связаны с текущим процессом
                        ->orWhere('process_names_id', $ndt_ids['ndt1_name_id'])
                        ->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                })
                ->get();

            $viewData += [
                'ndt_processes' => $ndt_processes,

                // Показываем строго одну выбранную запись
                'ndt_components' => collect([$current_tdrs_process->load(['tdr', 'processName'])]),

                // Добавляем ID текущего процесса (а не всех NDT процессов)
                'current_ndt_id' => $process_name->id,

                // Оставляем остальные ID для возможного использования в шаблоне
                'ndt1_name_id' => $ndt_ids['ndt1_name_id'],
                'ndt4_name_id' => $ndt_ids['ndt4_name_id'],
                'ndt6_name_id' => $ndt_ids['ndt6_name_id'],
                'ndt5_name_id' => $ndt_ids['ndt5_name_id']
            ];

        } else {
            // Обработка обычных процессов
            // Базовый набор доступных процессов для данного имени
            $processComponents = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $process_name->id)
                ->get();

            // Если передан конкретный process_id (элемент из JSON-массива), фильтруем JSON «processes» у текущей записи
            if ($specificProcessId !== null) {
                $currentProcesses = json_decode($current_tdrs_process->processes, true) ?: [];
                $currentProcesses = array_values(array_filter($currentProcesses, function($pid) use ($specificProcessId) {
                    return (int)$pid === (int)$specificProcessId;
                }));
                $current_tdrs_process->processes = json_encode($currentProcesses);
            }

            $viewData += [
                'process_components' => $processComponents,
                // Строго одна выбранная запись (возможно с отфильтрованным одним process_id)
                'process_tdr_components' => collect([$current_tdrs_process->load(['tdr', 'processName'])])
            ];
        }

        return view('admin.tdr-processes.processesForm', $viewData);
    }

    public function processes(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        $tdrProcesses = TdrProcess::orderBy('sort_order')->get();
        $proces = Process::all();

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        // Группируем процессы для создания кнопок групповых форм
        $processGroups = [];
        $totalQty = 0;

        if ($current_tdr->component) {
            // Получаем все процессы для этого TDR
            $tdrProcessesForTdr = $tdrProcesses->where('tdrs_id', $current_tdr->id);

            foreach ($tdrProcessesForTdr as $tdrProcess) {
                if (!$tdrProcess->processName) {
                    continue;
                }

                $processName = $tdrProcess->processName;
                // Определяем ключ группы: для NDT процессов используем 'NDT_GROUP', иначе processNameId
                $groupKey = ($processName->process_sheet_name == 'NDT') ? 'NDT_GROUP' : $processName->id;

                if (!isset($processGroups[$groupKey])) {
                    // Для NDT группы создаем виртуальный ProcessName или используем первый найденный NDT процесс
                    if ($groupKey == 'NDT_GROUP') {
                        // Находим первый ProcessName с process_sheet_name == 'NDT' для использования в группе
                        $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->first();
                        if ($ndtProcessName) {
                            $processGroups[$groupKey] = [
                                'process_name' => $ndtProcessName,
                                'processes_qty' => [],
                                'processes' => []
                            ];
                        } else {
                            // Если не найден, используем текущий процесс
                            $processGroups[$groupKey] = [
                                'process_name' => $processName,
                                'processes_qty' => [],
                                'processes' => []
                            ];
                        }
                    } else {
                        $processGroups[$groupKey] = [
                            'process_name' => $processName,
                            'processes_qty' => [],
                            'processes' => []
                        ];
                    }
                }

                // Декодируем JSON-поле processes
                $processData = json_decode($tdrProcess->processes, true);
                if (is_array($processData)) {
                    foreach ($processData as $processId) {
                        // Находим процесс по ID
                        $process = $proces->firstWhere('id', $processId);
                        if ($process) {
                            // Добавляем/обновляем количество по процессу (для TDR обычно qty = 1)
                            $qty = 1;
                            $processGroups[$groupKey]['processes_qty'][$processId] =
                                ($processGroups[$groupKey]['processes_qty'][$processId] ?? 0) + $qty;

                            // Сохраняем информацию о процессе
                            if (!isset($processGroups[$groupKey]['processes'][$processId])) {
                                $processGroups[$groupKey]['processes'][$processId] = [
                                    'id' => $processId,
                                    'name' => $process->process,
                                    'tdr_process_id' => $tdrProcess->id,
                                    'ec' => $tdrProcess->ec ?? false,
                                    'qty' => $qty
                                ];
                            } else {
                                // Обновляем qty если процесс уже есть
                                $processGroups[$groupKey]['processes'][$processId]['qty'] += $qty;
                            }

                            $totalQty += $qty;
                        }
                    }
                }
            }
        }

        // Подсчитываем уникальные процессы и суммы qty для каждого процесса
        foreach ($processGroups as $processNameId => &$group) {
            $processesQty = $group['processes_qty'];
            $uniqueProcesses = array_keys($processesQty);
            $group['count'] = count($uniqueProcesses);
            $group['qty'] = array_sum($processesQty);
            // Преобразуем массив процессов в обычный массив для удобства в представлении
            $group['processes'] = array_values($group['processes']);
            unset($group['processes_qty']); // Удаляем техническое поле
        }

        return view('admin.tdr-processes.processes',compact('current_tdr',
            'current_wo','tdrProcesses','proces','vendors','processGroups','totalQty'
        ));
    }

    public function traveler(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        $tdrProcesses = TdrProcess::orderBy('sort_order')->get();
        $proces = Process::all();

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        return view('admin.tdr-processes.traveler',compact('current_tdr',
            'current_wo','tdrProcesses','proces','vendors'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        // Находим запись TdrProcess по ID
        $current_tdr_processes = TdrProcess::findOrFail($id);

        $tdr_id = $current_tdr_processes->tdrs_id;

        $current_tdr = Tdr::find($tdr_id);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        // Получаем имена процессов
        $processNames = ProcessName::all();

        // Получаем процессы, связанные с manual_id
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manualProcesses', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        return view('admin.tdr-processes.edit', compact(
            'current_tdr',
            'current_wo',
            'current_tdr_processes',
            'processNames',
            'processes'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Находим запись TdrProcess по ID
        $current_tdr_processes = TdrProcess::findOrFail($id);

        // Валидация данных
        $validated = $request->validate([
            'tdrs_id' => 'required|integer|exists:tdrs,id',
            'processes' => 'required|array',
            'processes.*.process_names_id' => 'required|integer|exists:process_names,id',
            'processes.*.process' => 'required|array',
            'processes.*.process.*' => 'integer|exists:processes,id',
            'processes.*.ec' => 'nullable|boolean',
            'description' => 'nullable|string|max:255', // Валидация для description
            'notes' => 'nullable|string|max:255', // Валидация для notes
        ]);

        // Извлекаем данные из запроса
        $processData = $validated['processes'][0]; // Берём первый элемент массива

        // Преобразуем все элементы массива process в целые числа
        $processesArray = array_map('intval', $processData['process']);

        // Формируем данные для обновления
        $dataToUpdate = [
            'tdrs_id' => $validated['tdrs_id'],
            'process_names_id' => $processData['process_names_id'],
            'processes' => json_encode($processesArray), // Преобразуем массив в JSON
            'ec' => $processData['ec'] ?? false, // Добавляем поле EC
            'description' => $request->input('description') ?? null, // Добавляем поле description (необязательное)
            'notes' => $request->input('notes') ?? null, // Добавляем поле notes (необязательное)
        ];

        // Обновляем запись
        \Log::info('Before update:', $current_tdr_processes->toArray());
        $current_tdr_processes->update($dataToUpdate);
        \Log::info('After update:', $current_tdr_processes->fresh()->toArray());

        // Редирект назад с сообщением об успехе
        return redirect()->back()
            ->with('success', 'TDR for Component updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($tdr_process)
    {
        // Получаем tdrId из запроса
        $tdrId = request('tdrId');
        $processToRemove = request('process'); // Значение, которое нужно удалить

        // Находим запись Tdr
        $tdr = Tdr::find($tdrId);
        if (!$tdr) {
            return redirect()->back()->with('error', 'TDR not found.');
        }

        // Находим запись по ID
        $tdrProcess = TdrProcess::findOrFail($tdr_process);

        // Декодируем JSON-поле processes
        $processData = json_decode($tdrProcess->processes, true);

        // Если processes пустой или не является массивом, удаляем всю запись
        if (!is_array($processData)) {
            $tdrProcess->delete();
            return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
                ->with('success', 'Process deleted successfully.');
        }

        // Если processes содержит только одно значение, удаляем всю запись
        if (count($processData) === 1) {
            $tdrProcess->delete();
            return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
                ->with('success', 'Process deleted successfully.');
        }

        // Удаляем значение из массива (приводим типы к int для сравнения)
        $processData = array_filter($processData, function ($process) use ($processToRemove) {
            return (int)$process !== (int)$processToRemove;
        });

        // Обновляем поле processes
        $tdrProcess->processes = json_encode(array_values($processData)); // Переиндексируем массив
        $tdrProcess->save();

        // Перенаправляем с сообщением об успехе
        return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
            ->with('success', 'Process removed successfully.');
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
            $processIds = $request->input('process_ids');

            if (!is_array($processIds)) {
                return response()->json(['success' => false, 'message' => 'Invalid process IDs'], 400);
            }

            DB::transaction(function() use ($processIds) {
                foreach ($processIds as $index => $processId) {
                    TdrProcess::where('id', $processId)
                             ->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()], 500);
        }
    }


    public function updateDate(Request $request, TdrProcess $tdrProcess)
    {

   // Log::channel('avia')->info($request->date_start  . $request->date_finish );


        $data = $request->validate([
            'date_start'  => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);


        $effectiveStart = $data['date_start'] ?? $tdrProcess->date_start;


        if (array_key_exists('date_finish', $data) && $data['date_finish']) {
            if (!$effectiveStart) {
                return back()->withErrors([
                    'date_finish' => 'The start date must be filled in before setting the end date.'
                ]);
            }

            if (\Carbon\Carbon::parse($data['date_finish'])->lt(\Carbon\Carbon::parse($effectiveStart))) {
                return back()->withErrors([
                    'date_finish' => 'The end date cannot be earlier than the start date.'
                ]);
            }
        }

        $tdrProcess->update($data);

        return back()->with('success', 'Process dates updated');
    }

    /**
     * Генерирует многостраничную страницу из выбранных форм процессов
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $tdrId
     * @return Application|Factory|View
     */
    public function packageForms(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::with('component')->findOrFail($tdrId);
        $current_wo = $current_tdr->workorder;

        // Получаем выбранные процессы из запроса
        $processesJson = $request->input('processes');
        $selectedProcesses = json_decode($processesJson, true);

        if (empty($selectedProcesses) || !is_array($selectedProcesses)) {
            abort(400, 'No processes selected');
        }

        $manual_id = $this->getManualIdForTdr($tdrId);
        if (!$manual_id) {
            abort(404, 'Manual not found for component or workorder');
        }

        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)->pluck('processes_id');

        // Массив для хранения данных всех форм
        $formsData = [];

        foreach ($selectedProcesses as $selectedProcess) {
            $tdrProcessId = $selectedProcess['tdr_process_id'] ?? null;
            $processId = $selectedProcess['process_id'] ?? null;
            $vendorId = $selectedProcess['vendor_id'] ?? null;

            if (!$tdrProcessId || !$processId) {
                continue;
            }

            // Загружаем TdrProcess
            $tdrProcess = TdrProcess::with([
                'processName',
                'tdr.workorder.unit.manuals',
                'tdr.workorder'
            ])->find($tdrProcessId);

            if (!$tdrProcess || $tdrProcess->tdrs_id != $tdrId) {
                continue;
            }

            $process_name = $tdrProcess->processName;
            $selectedVendor = $vendorId ? Vendor::find($vendorId) : null;

            // Базовые данные для формы
            $formData = [
                'current_wo' => $current_wo,
                'components' => $components,
                'tdrs' => [$current_tdr->id],
                'manuals' => Manual::where('id', $manual_id)->get(),
                'process_name' => $process_name,
                'manual_id' => $manual_id,
                'selectedVendor' => $selectedVendor,
                'current_tdr' => $current_tdr
            ];

            // Обработка NDT форм
            if ($process_name && $process_name->process_sheet_name == 'NDT') {
                $ndt_ids = [
                    'ndt1_name_id' => ProcessName::where('name', 'NDT-1')->value('id'),
                    'ndt4_name_id' => ProcessName::where('name', 'NDT-4')->value('id'),
                    'ndt6_name_id' => ProcessName::where('name', 'Eddy Current Test')->value('id'),
                    'ndt5_name_id' => ProcessName::where('name', 'BNI')->value('id')
                ];

                $ndt_processes = Process::whereIn('id', $manualProcesses)
                    ->where(function ($query) use ($ndt_ids) {
                        $query->whereIn('process_names_id', $ndt_ids)
                            ->orWhere('process_names_id', $ndt_ids['ndt1_name_id'])
                            ->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                    })
                    ->get();

                // Фильтруем processes по конкретному process_id
                $currentProcesses = json_decode($tdrProcess->processes, true) ?: [];
                $currentProcesses = array_values(array_filter($currentProcesses, function($pid) use ($processId) {
                    return (int)$pid === (int)$processId;
                }));
                $tdrProcess->processes = json_encode($currentProcesses);

                $formData += [
                    'ndt_processes' => $ndt_processes,
                    'ndt_components' => collect([$tdrProcess->load(['tdr', 'processName'])]),
                    'current_ndt_id' => $process_name->id,
                    'ndt1_name_id' => $ndt_ids['ndt1_name_id'],
                    'ndt4_name_id' => $ndt_ids['ndt4_name_id'],
                    'ndt6_name_id' => $ndt_ids['ndt6_name_id'],
                    'ndt5_name_id' => $ndt_ids['ndt5_name_id']
                ];
            } else {
                // Обработка обычных процессов
                $processComponents = Process::whereIn('id', $manualProcesses)
                    ->where('process_names_id', $process_name->id)
                    ->get();

                // Фильтруем processes по конкретному process_id
                $currentProcesses = json_decode($tdrProcess->processes, true) ?: [];
                $currentProcesses = array_values(array_filter($currentProcesses, function($pid) use ($processId) {
                    return (int)$pid === (int)$processId;
                }));
                $tdrProcess->processes = json_encode($currentProcesses);

            $formData += [
                'process_components' => $processComponents,
                'process_tdr_components' => collect([$tdrProcess->load(['tdr', 'processName'])])
            ];
        }

        // Добавляем current_tdr для использования в view
        $formData['current_tdr'] = $current_tdr;
        $formsData[] = $formData;
    }

        if (empty($formsData)) {
            abort(400, 'No valid processes found');
        }

        return view('admin.tdr-processes.packageForms', [
            'formsData' => $formsData,
            'current_tdr' => $current_tdr
        ]);
    }

}
