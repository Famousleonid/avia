<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\MachiningWorkStep;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\Workorder;
use App\Services\MachiningWorkorderQueueRelease;
use App\Services\PaintIndexRowsBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JetBrains\PhpStorm\NoReturn;

class TdrProcessController extends Controller
{
    /**
     * Кэшированные ID для ProcessName
     */
    private $ecProcessNameId = null;
    private $machiningProcessNameId = null;

    /**
     * Получает ID для ProcessName 'EC' (кэширование)
     */
    private function getEcProcessNameId()
    {
        if ($this->ecProcessNameId === null) {
            $this->ecProcessNameId = ProcessName::where('name', 'EC')->value('id');
        }
        return $this->ecProcessNameId;
    }

    /**
     * Получает ID для ProcessName 'Machining (EC)' / 'Machining' / 'Machining (Blend)' (кэширование)
     */
    private function getMachiningProcessNameId()
    {
        if ($this->machiningProcessNameId === null) {
            $machiningEC = ProcessName::where('name', 'Machining (EC)')->first();
            $machining = ProcessName::where('name', 'Machining')->first();
            $machiningBlend = ProcessName::where('name', 'Machining (Blend)')->first();

            if ($machiningEC) {
                $this->machiningProcessNameId = $machiningEC->id;
            } elseif ($machining) {
                $this->machiningProcessNameId = $machining->id;
            } elseif ($machiningBlend) {
                $this->machiningProcessNameId = $machiningBlend->id;
            }
        }
        return $this->machiningProcessNameId;
    }

    /**
     * Получает массив ID процессов, для которых показывается чекбокс EC и создаётся запись EC.
     * Включает: Machining (EC) / Machining / Machining (Blend), RIL
     */
    private function getEcEligibleProcessNameIds(): array
    {
        $ids = [];
        $machiningId = $this->getMachiningProcessNameId();
        if ($machiningId) {
            $ids[] = $machiningId;
        }
        $ril = ProcessName::where('name', 'RIL')->first();
        if ($ril) {
            $ids[] = $ril->id;
        }
        return $ids;
    }

    /**
     * Process name «Machining (EC)» подразумевает EC по определению; отдельный чекбокс EC не используется.
     */
    private function isProcessNameMachiningEc(?int $processNamesId): bool
    {
        if ($processNamesId === null) {
            return false;
        }

        return ProcessName::where('id', $processNamesId)->where('name', 'Machining (EC)')->exists();
    }

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
        return Manual::manualIdsForWorkorder((int) $workorderId);
    }

    /**
     * @return list<string>
     */
    private function machiningHeaderManualLibsForWorkorder(?ProcessName $processName, int $workorderId): array
    {
        if (! ProcessName::isMachiningPrintedForm($processName)) {
            return [];
        }

        return Manual::orderedLibValuesForManualIds(Manual::manualIdsForWorkorder($workorderId));
    }
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        return 1;

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        return 1;
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

        // Получаем имена процессов, отсортированные по алфавиту
        $processNames = ProcessName::forPicker()->orderBy('name')->get();

        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем процессы, уже связанные с текущим Tdr
        $tdrProcesses = TdrProcess::where('tdrs_id', $tdrId)->orderBy('sort_order')->get();

        // Передаем данные в представление
        // Получаем все NDT процессы для дополнительного выбора
        $ndtProcessNames = ProcessName::where(function($query) {
            $query->where('name', 'like', 'NDT-%')
                  ->orWhereIn('name', ['NDT-1', 'NDT-2', 'NDT-3', 'NDT-4', 'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8']);
        })->get();

        $ecEligibleProcessNameIds = $this->getEcEligibleProcessNameIds();
        $ecProcessNameId = ProcessName::where('name', 'EC')->value('id');

        return view('admin.tdr-processes.createProcesses', compact(
            'current_tdr',
            'current_wo',
            'processNames',
            'processes',
            'tdrProcesses',
            'manual_id',
            'ndtProcessNames',
            'ecEligibleProcessNameIds',
            'ecProcessNameId'
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
            'processes' => 'required' // Может быть массивом или JSON строкой
        ]);

        $tdrId = $request->input('tdrs_id');
        $processesInput = $request->input('processes');

        // Обрабатываем processes: может быть массивом или JSON строкой (для обратной совместимости)
        if (is_string($processesInput)) {
            $processesData = json_decode($processesInput, true);
        } else {
            $processesData = $processesInput;
        }

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

        // ID процессов, для которых показывается EC checkbox: Machining (EC)/Machining/Machining (Blend), RIL
        $ecEligibleIds = $this->getEcEligibleProcessNameIds();
        $ecProcessNameIdForRow = $this->getEcProcessNameId();

        // Сохраняем каждый процесс
        foreach ($processesData as $index => $data) {
            $isEcEligible = in_array((int)$data['process_names_id'], $ecEligibleIds);
            $rawStandalone = $data['standalone_ec_only'] ?? false;
            $standaloneEcOnly = $rawStandalone === true
                || $rawStandalone === 1
                || $rawStandalone === '1'
                || (is_string($rawStandalone) && strcasecmp($rawStandalone, 'on') === 0)
                || (is_string($rawStandalone) && strcasecmp($rawStandalone, 'true') === 0);

            $isEcProcessName = $ecProcessNameIdForRow && (int) $data['process_names_id'] === (int) $ecProcessNameIdForRow;
            if ($standaloneEcOnly && ! $isEcProcessName) {
                return response()->json(['error' => __('“EC only” applies only to process name EC.')], 422);
            }
            if ($standaloneEcOnly) {
                $procIds = is_array($data['processes'] ?? null) ? $data['processes'] : [];
                if (count(array_filter($procIds)) === 0) {
                    return response()->json(['error' => __('“EC only” requires at least one specification process selected.')], 422);
                }
            }

            // Проверяем значение ec (может быть true, 1, "1", или отсутствовать)
            $ecValue = isset($data['ec']) ? (
                $data['ec'] === true ||
                $data['ec'] === 1 ||
                $data['ec'] === '1' ||
                $data['ec'] === 'true'
            ) : false;

            if ($isEcProcessName && $standaloneEcOnly) {
                $ecValue = true;
            } elseif (! $isEcProcessName) {
                // no-op: ecValue from checkbox
            } else {
                // Правило: если в запросе только один процесс и это EC (без «только EC») — ставим ec = 1
                if (count($processesData) === 1) {
                    $ecValue = true;
                }
            }

            if ($this->isProcessNameMachiningEc((int) $data['process_names_id'])) {
                $ecValue = true;
            }

            $isStandaloneEcRow = $isEcProcessName && $standaloneEcOnly;

            // Создаем запись процесса
            TdrProcess::create([
                'tdrs_id' => $tdrId,
                'process_names_id' => $data['process_names_id'],
                'plus_process' => $data['plus_process'] ?? null, // Дополнительные NDT process_names_id через запятую
                'processes' => array_values(TdrProcess::normalizeStoredProcessIds($data['processes'] ?? [])), // массив ID для cast JSON
                'sort_order' => $maxSortOrder + $sortOrderCounter + 1, // Устанавливаем sort_order в конец списка
                'date_start' => null,
                'date_finish' => null,
                'ec' => $ecValue, // Добавляем поле EC
                'standalone_ec_only' => $isStandaloneEcRow,
                'description' => $data['description'] ?? null, // Добавляем поле description (необязательное)
                'notes' => $data['notes'] ?? null, // Добавляем поле notes (необязательное)
            ]);

            $sortOrderCounter++;

            // Если это EC-eligible процесс (Machining/RIL) с отмеченным чекбоксом 'EC'
            if ($isEcEligible && $ecValue) {
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
                    } catch (\Exception $e) {
                        Log::error('Error creating ProcessName "EC"', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Если не удалось создать ProcessName, пропускаем создание TdrProcess для EC
                        // но продолжаем обработку других процессов
                        continue;
                    }
                }

                // Проверяем, что $ecProcessName был успешно получен или создан
                if ($ecProcessName && $ecProcessName->id) {
                    // 2. Проверяем, существует ли уже запись companion EC (не «только EC») для этого компонента
                    $existingEcProcess = TdrProcess::where('tdrs_id', $tdrId)
                        ->where('process_names_id', $ecProcessName->id)
                        ->where('standalone_ec_only', false)
                        ->first();

                    try {
                        if ($existingEcProcess) {
                            // Обновляем существующую запись EC: добавляем новые процессы в JSON-массив
                            $existingProcesses = TdrProcess::normalizeStoredProcessIds($existingEcProcess->processes);
                            $newProcesses = TdrProcess::normalizeStoredProcessIds($data['processes'] ?? []);

                            // Объединяем массивы и убираем дубликаты
                            $mergedProcesses = array_unique(array_merge($existingProcesses, $newProcesses));
                            $mergedProcesses = array_values($mergedProcesses); // Переиндексируем массив

                            $existingEcProcess->update([
                                'processes' => $mergedProcesses,
                                // Обновляем description и notes, если они переданы (опционально)
                                // Если нужно сохранять последние значения:
                                'description' => $data['description'] ?? $existingEcProcess->description,
                                'notes' => $data['notes'] ?? $existingEcProcess->notes,
                            ]);

                        } else {
                            // Создаем новую запись EC, если её еще нет
                        TdrProcess::create([
                            'tdrs_id' => $tdrId,
                            'process_names_id' => $ecProcessName->id,
                            'processes' => array_values(TdrProcess::normalizeStoredProcessIds($data['processes'] ?? [])), // те же ID что у Machining
                            'sort_order' => $maxSortOrder + $sortOrderCounter + 1, // Следующий порядок после Machining
                            'date_start' => null,
                            'date_finish' => null,
                            'ec' => 0, // Поле ec = 0
                            'standalone_ec_only' => false,
                            'description' => $data['description'] ?? null, // Добавляем поле description (необязательное)
                            'notes' => $data['notes'] ?? null, // Добавляем поле notes (необязательное)
                        ]);

                        $sortOrderCounter++; // Увеличиваем счетчик, так как создали дополнительную запись

                        }
                    } catch (\Exception $e) {
                        Log::error('Error processing TdrProcess for EC', [
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
        $current_tdr = Tdr::with(['component', 'workorder'])->findOrFail($id);

        $workorder_id = $current_tdr->workorder_id;
        $current_wo = Workorder::find($workorder_id);

        if (!$current_wo) {
            abort(404);
        }

        $manual = $current_wo->unit->manuals;

        $vendorId = $request->input('vendor_id', $request->input('vendor'));
        if (!$vendorId) {
            return redirect()
                ->route('tdr-processes.traveler', ['tdrId' => $current_tdr->id])
                ->with('error', __('Please select a vendor.'));
        }

        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return redirect()
                ->route('tdr-processes.traveler', ['tdrId' => $current_tdr->id])
                ->with('error', __('Invalid vendor.'));
        }

        $vendorName = $vendor->name;

        $excludeInput = $request->input('exclude_process_ids', []);
        if (is_string($excludeInput)) {
            $excludeInput = array_filter(array_map('intval', explode(',', $excludeInput)));
        } elseif (! is_array($excludeInput)) {
            $excludeInput = [];
        }
        $excludeIds = array_values(array_unique(array_map('intval', $excludeInput)));
        $excludeIds = array_values(array_filter($excludeIds, fn ($id) => $id > 0));
        $allowedIds = TdrProcess::where('tdrs_id', $current_tdr->id)->pluck('id')->all();
        $excludeIds = array_values(array_intersect($excludeIds, $allowedIds));

        $tdrProcesses = TdrProcess::with('processName')
            ->where('tdrs_id', $current_tdr->id)
            ->where('ignore_row', false)
            ->when(count($excludeIds) > 0, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $proces = Process::all();

        $repairNum = $request->input('repair_num');
        $repairNum = ($repairNum !== null && $repairNum !== '') ? $repairNum : 'N/A';

        $formConfig = config('process_forms.travel-form', config('process_forms.tdr-processes'));

        return view('admin.tdr-processes.travelForm', compact(
            'current_tdr',
            'current_wo',
            'tdrProcesses',
            'proces',
            'manual',
            'repairNum',
            'vendorName',
            'formConfig'
        ));
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

        if (ProcessName::hasNoProcessForm($process_name)) {
            return redirect()->back()->with('error', __('There is no process form for EC.'));
        }

        if (empty($process_name->process_sheet_name)) {
            return redirect()->back()->with('error', __('There is no form for this process.'));
        }

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
            // Получаем ID process names одним запросом (все NDT типы для отображения в форме)
            $processNames = ProcessName::whereIn('name', [
                'NDT-1', 'NDT-2', 'NDT-3', 'NDT-4', 'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8',
                'Eddy Current Test',
                'BNI'
            ])->pluck('id', 'name');

            // Извлекаем ID по именам (Eddy Current = #6, BNI = #5)
            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                'ndt2_name_id' => $processNames['NDT-2'] ?? null,
                'ndt3_name_id' => $processNames['NDT-3'] ?? null,
                'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                'ndt5_name_id' => $processNames['BNI'] ?? $processNames['NDT-5'] ?? null,
                'ndt6_name_id' => $processNames['Eddy Current Test'] ?? $processNames['NDT-6'] ?? null,
                'ndt7_name_id' => $processNames['NDT-7'] ?? null,
                'ndt8_name_id' => $processNames['NDT-8'] ?? null,
            ];
            $ndt_ids_filtered = array_filter($ndt_ids);

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids, $ndt_ids_filtered) {
                    if (!empty($ndt_ids_filtered)) {
                        $query->whereIn('process_names_id', $ndt_ids_filtered);
                    }
                    if (!empty($ndt_ids['ndt1_name_id'])) {
                        $query->orWhere('process_names_id', $ndt_ids['ndt1_name_id']);
                    }
                    if (!empty($ndt_ids['ndt4_name_id'])) {
                        $query->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                    }
                })
                ->get();

            // Получаем NDT components
            $ndt_components = TdrProcess::whereIn('tdrs_id', $tdr_ids)
                ->whereIn('process_names_id', $ndt_ids_filtered)
                ->with(['tdr', 'processName'])
                ->orderBy('sort_order')
                ->get();

            return view('admin.tdr-processes.processesForm', array_merge([
                'module' => 'tdr-processes',
                'current_wo' => $current_wo,
                'components' => $components,
                'tdrs' => $tdr_ids,
                'manuals' => Manual::where('id', $manual_id)->get(),
                'process_name' => $process_name,
                'ndt_processes' => $ndt_processes,
                'ndt_components' => $ndt_components,
                'selectedVendor' => $selectedVendor,
                'machining_header_manual_libs' => $this->machiningHeaderManualLibsForWorkorder($process_name, (int) $current_wo->id),
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
            'module' => 'tdr-processes',
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => $tdr_ids,
            'manuals' => Manual::where('id', $manual_id)->get(),
            'process_name' => $process_name,
            'process_components' => $process_components,
            'process_tdr_components' => $process_tdr_components,
            'manual_id' => $manual_id,
            'selectedVendor' => $selectedVendor,
            'machining_header_manual_libs' => $this->machiningHeaderManualLibsForWorkorder($process_name, (int) $current_wo->id),
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
        if (ProcessName::hasNoProcessForm($process_name)) {
            abort(404, __('There is no process form for EC.'));
        }
        $current_tdr = $current_tdrs_process->tdr;
        $current_wo = $current_tdr->workorder;
        $manual_id = $current_wo->unit->manual_id;

        // Получаем компоненты и процессы из руководства
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'module' => 'tdr-processes',
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => [$current_tdr->id],
            'manuals' => Manual::where('id', $manual_id)->get(),
            'process_name' => $process_name,
            'manual_id' => $manual_id,
            'selectedVendor' => $selectedVendor,
            'machining_header_manual_libs' => $this->machiningHeaderManualLibsForWorkorder($process_name, (int) $current_wo->id),
        ];

        // Обработка случая для NDT-форм
        if ($process_name->process_sheet_name == 'NDT') {
            $processNames = ProcessName::whereIn('name', [
                'NDT-1', 'NDT-2', 'NDT-3', 'NDT-4', 'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8',
                'Eddy Current Test', 'BNI'
            ])->pluck('id', 'name');

            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                'ndt2_name_id' => $processNames['NDT-2'] ?? null,
                'ndt3_name_id' => $processNames['NDT-3'] ?? null,
                'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                'ndt5_name_id' => $processNames['BNI'] ?? $processNames['NDT-5'] ?? null,
                'ndt6_name_id' => $processNames['Eddy Current Test'] ?? $processNames['NDT-6'] ?? null,
                'ndt7_name_id' => $processNames['NDT-7'] ?? null,
                'ndt8_name_id' => $processNames['NDT-8'] ?? null,
            ];
            $ndt_ids_filtered = array_filter($ndt_ids);

            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids, $ndt_ids_filtered) {
                    if (!empty($ndt_ids_filtered)) {
                        $query->whereIn('process_names_id', $ndt_ids_filtered);
                    }
                    if (!empty($ndt_ids['ndt1_name_id'])) {
                        $query->orWhere('process_names_id', $ndt_ids['ndt1_name_id']);
                    }
                    if (!empty($ndt_ids['ndt4_name_id'])) {
                        $query->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                    }
                })
                ->get();

            $viewData += [
                'ndt_processes' => $ndt_processes,
                'ndt_components' => collect([$current_tdrs_process->load(['tdr', 'processName'])]),
                'current_ndt_id' => $process_name->id,
            ] + $ndt_ids;

        } else {
            // Базовый набор доступных процессов для данного имени
            $processComponents = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $process_name->id)
                ->get();

            // Если передан конкретный process_id (элемент из JSON-массива), фильтруем JSON «processes» у текущей записи
            if ($specificProcessId !== null) {
                $currentProcesses = json_decode($current_tdrs_process->processes, true) ?: [];
                $currentProcesses = array_values(array_filter($currentProcesses, function ($pid) use ($specificProcessId) {
                    return (int) $pid === (int) $specificProcessId;
                }));
                $current_tdrs_process->processes = json_encode($currentProcesses);
            }

            $viewData += [
                'process_components' => $processComponents,
                // Строго одна выбранная запись (возможно с отфильтрованным одним process_id)
                'process_tdr_components' => collect([$current_tdrs_process->load(['tdr', 'processName'])]),
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
        $current_wo = $workorder_id ? Workorder::find($workorder_id) : null;

        // Загружаем только процессы для текущего TDR, отсортированные по sort_order
        // Загружаем связь processName для избежания ошибок в представлении
        $tdrProcesses = TdrProcess::where('tdrs_id', $current_tdr->id)
            ->with('processName')
            ->orderBy('sort_order')
            ->get();
        $proces = Process::all();

        // Получаем всех поставщиков
        $vendors = Vendor::all();

        $built = $this->buildProcessGroupsForSingleTdr($current_tdr, $tdrProcesses, $proces);
        $processGroups = $built['processGroups'];
        $totalQty = $built['totalQty'];

        $ecEligibleProcessNameIds = $this->getEcEligibleProcessNameIds();

        return view('admin.tdr-processes.processes',compact('current_tdr',
            'current_wo','tdrProcesses','proces','vendors','processGroups','totalQty','ecEligibleProcessNameIds'
        ));
    }

    /**
     * Return processes body partial for modal (AJAX).
     */
    public function processesBodyPartial($tdrId)
    {
        $current_tdr = Tdr::with(['workorder', 'component'])->findOrFail($tdrId);
        $current_wo = $current_tdr->workorder_id ? Workorder::find($current_tdr->workorder_id) : null;
        $tdrProcesses = TdrProcess::where('tdrs_id', $current_tdr->id)->with('processName')->orderBy('sort_order')->get();
        $proces = Process::all();
        $vendors = Vendor::all();
        $ecEligibleProcessNameIds = $this->getEcEligibleProcessNameIds();
        $built = $this->buildProcessGroupsForSingleTdr($current_tdr, $tdrProcesses, $proces);
        $processGroups = $built['processGroups'];
        $totalQty = $built['totalQty'];

        return view('admin.tdr-processes.partials.processes-body', compact(
            'current_tdr', 'current_wo', 'tdrProcesses', 'proces', 'vendors', 'ecEligibleProcessNameIds',
            'processGroups', 'totalQty'
        ));
    }

    /**
     * @return array{processGroups: array, totalQty: int}
     */
    private function buildProcessGroupsForSingleTdr(Tdr $current_tdr, $tdrProcesses, $proces): array
    {
        $processGroups = [];
        $totalQty = 0;

        if ($current_tdr->component) {
            foreach ($tdrProcesses as $tdrProcess) {
                if (!$tdrProcess->processName) {
                    continue;
                }

                $processName = $tdrProcess->processName;
                if (ProcessName::hasNoProcessForm($processName)) {
                    continue;
                }

                $groupKey = ProcessName::groupFormsGroupKey($processName, false);

                if (!isset($processGroups[$groupKey])) {
                    if ($groupKey === ProcessName::GROUP_KEY_MERGE_MACHINING_MEC) {
                        $rep = ProcessName::machiningMachiningEcRepresentative() ?? $processName;
                        $processGroups[$groupKey] = [
                            'process_name' => $rep,
                            'representative_process_name_id' => $rep->id,
                            'processes_qty' => [],
                            'processes' => [],
                            'logical_unit_keys' => [],
                        ];
                    } else {
                        $processGroups[$groupKey] = [
                            'process_name' => $processName,
                            'representative_process_name_id' => $processName->id,
                            'processes_qty' => [],
                            'processes' => [],
                            'logical_unit_keys' => [],
                        ];
                    }
                }

                $rawProcesses = $tdrProcess->processes;
                $processData = is_array($rawProcesses) ? $rawProcesses : json_decode((string) $rawProcesses, true);
                if (!is_array($processData)) {
                    continue;
                }

                $isCombinedNdtRow = $tdrProcess->isCombinedNdtPrimaryRow();

                foreach ($processData as $processId) {
                    $process = $proces->firstWhere('id', $processId);
                    if (!$process) {
                        continue;
                    }

                    if ($isCombinedNdtRow) {
                        $processGroups[$groupKey]['logical_unit_keys']['ndt_combined_'.$tdrProcess->id] = true;
                    } else {
                        $processGroups[$groupKey]['logical_unit_keys']['pid_'.(int) $processId] = true;
                    }

                    $qty = 1;
                    $processGroups[$groupKey]['processes_qty'][$processId] =
                        ($processGroups[$groupKey]['processes_qty'][$processId] ?? 0) + $qty;

                    if (!isset($processGroups[$groupKey]['processes'][$processId])) {
                        $processGroups[$groupKey]['processes'][$processId] = [
                            'id' => $processId,
                            'name' => $process->process,
                            'tdr_process_id' => $tdrProcess->id,
                            'ec' => $tdrProcess->ec ?? false,
                            'qty' => $qty,
                        ];
                    } else {
                        $processGroups[$groupKey]['processes'][$processId]['qty'] += $qty;
                    }

                    $totalQty += $qty;
                }
            }
        }

        foreach ($processGroups as &$group) {
            $processesQty = $group['processes_qty'];
            $group['count'] = count($group['logical_unit_keys'] ?? []);
            unset($group['logical_unit_keys']);
            $group['qty'] = array_sum($processesQty);
            $group['processes'] = array_values($group['processes']);
            unset($group['processes_qty']);
        }
        unset($group);

        return ['processGroups' => $processGroups, 'totalQty' => $totalQty];
    }

    public function travelerGroup(Request $request, $tdrId)
    {
        $validated = $request->validate([
            'process_ids' => 'required|array|min:1',
            'process_ids.*' => 'integer|exists:tdr_processes,id',
        ]);

        $current_tdr = Tdr::findOrFail($tdrId);
        $ids = array_values(array_unique(array_map('intval', $validated['process_ids'])));

        $hasBlock = TdrProcess::where('tdrs_id', $current_tdr->id)->where('in_traveler', true)->exists();
        if ($hasBlock) {
            return response()->json([
                'success' => false,
                'message' => __('A Traveler group already exists. UnGroup it first.'),
            ], 422);
        }

        $processes = TdrProcess::whereIn('id', $ids)->where('tdrs_id', $current_tdr->id)->get();
        if ($processes->count() !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid process selection for this part.'),
            ], 422);
        }

        if ($processes->contains(fn ($p) => (bool) $p->in_traveler)) {
            return response()->json([
                'success' => false,
                'message' => __('Selected processes must not already be in a Traveler group.'),
            ], 422);
        }

        TdrProcess::whereIn('id', $ids)->update(['in_traveler' => true]);

        return response()->json(['success' => true]);
    }

    public function travelerUngroup(Request $request, $tdrId)
    {
        $current_tdr = Tdr::findOrFail($tdrId);
        TdrProcess::where('tdrs_id', $current_tdr->id)->where('in_traveler', true)->update(['in_traveler' => false]);

        return response()->json(['success' => true]);
    }

    public function traveler(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        $tdrProcesses = TdrProcess::with('processName')
            ->where('tdrs_id', $current_tdr->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $proces = Process::all();

        $vendors = Vendor::all();

        return view('admin.tdr-processes.traveler', compact(
            'current_tdr',
            'current_wo',
            'tdrProcesses',
            'proces',
            'vendors'
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

        // Получаем имена процессов, отсортированные по алфавиту
        $processNames = ProcessName::forPicker()->orderBy('name')->get();

        // Получаем процессы, связанные с manual_id
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manualProcesses', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем все NDT process names для дополнительного селекта
        $ndtProcessNames = ProcessName::where('name', 'like', 'NDT-%')->get();

        $ecEligibleProcessNameIds = $this->getEcEligibleProcessNameIds();

        return view('admin.tdr-processes.edit', compact(
            'current_tdr',
            'current_wo',
            'current_tdr_processes',
            'processNames',
            'processes',
            'ndtProcessNames',
            'ecEligibleProcessNameIds'
        ));
    }

    /**
     * Return edit form partial for modal (AJAX) or full page for iframe (?modal=1).
     */
    public function editFormPartial(Request $request, $id)
    {
        $current_tdr_processes = TdrProcess::findOrFail($id);
        $current_tdr = Tdr::with(['workorder.unit', 'component'])->find($current_tdr_processes->tdrs_id);
        $current_wo = $current_tdr->workorder;
        $processNames = ProcessName::forPicker()->orderBy('name')->get();
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manuals', fn($q) => $q->where('manual_id', $manual_id))->get();
        $ndtProcessNames = ProcessName::where('name', 'like', 'NDT-%')->get();
        $ecEligibleProcessNameIds = $this->getEcEligibleProcessNameIds();

        $vars = compact('current_tdr', 'current_wo', 'current_tdr_processes', 'processNames', 'processes', 'ndtProcessNames', 'ecEligibleProcessNameIds');

        if ($request->query('modal')) {
            return view('admin.tdr-processes.edit-form-modal', $vars);
        }

        return view('admin.tdr-processes.partials.edit-form', $vars);
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

        // Нормализация: radio отправляет process как скаляр, валидация ожидает массив
        $processes = $request->input('processes', []);
        foreach ($processes as $i => $p) {
            if (isset($p['process']) && !is_array($p['process'])) {
                $processes[$i]['process'] = [(int) $p['process']];
            }
        }
        $request->merge(['processes' => $processes]);

        $pd0 = $request->input('processes.0', []);
        $rawStandaloneIn = $pd0['standalone_ec_only'] ?? false;
        $requestWantsStandaloneEc = $rawStandaloneIn === true
            || $rawStandaloneIn === 1
            || $rawStandaloneIn === '1'
            || (is_string($rawStandaloneIn) && strcasecmp($rawStandaloneIn, 'on') === 0)
            || (is_string($rawStandaloneIn) && strcasecmp($rawStandaloneIn, 'true') === 0);

        if ($current_tdr_processes->standalone_ec_only && ! $requestWantsStandaloneEc) {
            $tdrIdForRedirect = $current_tdr_processes->tdrs_id;
            $current_tdr_processes->delete();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'tdrId' => $tdrIdForRedirect]);
            }

            return redirect()->route('tdr-processes.processes', ['tdrId' => $tdrIdForRedirect])
                ->with('success', 'EC-only row removed.');
        }

        // Валидация данных
        $validated = $request->validate([
            'tdrs_id' => 'required|integer|exists:tdrs,id',
            'processes' => 'required|array',
            'processes.*.process_names_id' => 'required|integer|exists:process_names,id',
            'processes.*.process' => 'required|array',
            'processes.*.process.*' => 'integer|exists:processes,id',
            'processes.*.ec' => 'nullable|boolean',
            'processes.*.plus_process' => 'nullable|string', // Дополнительные NDT process_names_id через запятую
            'description' => 'nullable|string|max:255', // Валидация для description
            'notes' => 'nullable|string|max:255', // Валидация для notes
        ]);

        // Извлекаем данные из запроса
        $processData = $validated['processes'][0]; // Берём первый элемент массива

        // process может быть массивом (checkbox) или скаляром (radio)
        $processInput = $processData['process'] ?? [];
        $processesArray = is_array($processInput)
            ? array_map('intval', $processInput)
            : [ (int) $processInput ];

        // Сохраняем старые данные ДО обновления
        $oldProcessNamesId = $current_tdr_processes->process_names_id;
        $oldProcesses = json_decode($current_tdr_processes->processes, true) ?: [];
        $newProcessNamesId = $processData['process_names_id'];
        $ecEligibleIds = $this->getEcEligibleProcessNameIds();

        $ecProcessNameId = $this->getEcProcessNameId();
        $isEcName = $ecProcessNameId && (int) $newProcessNamesId === (int) $ecProcessNameId;
        $standaloneEcRow = $isEcName && $requestWantsStandaloneEc;

        if ($standaloneEcRow && count(array_filter($processesArray)) === 0) {
            $msg = __('“EC only” requires at least one specification process selected.');
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $msg, 'errors' => ['processes' => [$msg]]], 422);
            }

            return redirect()->back()->withErrors(['processes' => $msg]);
        }

        // Определяем значение EC: только для EC-eligible процессов (Machining, RIL)
        $isNewEcEligible = in_array((int)$newProcessNamesId, $ecEligibleIds);
        $ecValue = false;
        if ($isNewEcEligible) {
            $ecValue = $processData['ec'] ?? false;
        }

        if ($isEcName) {
            if ($standaloneEcRow) {
                $ecValue = true;
            } else {
                $processCount = TdrProcess::where('tdrs_id', $validated['tdrs_id'])->count();
                if ($processCount === 1) {
                    $ecValue = true;
                }
            }
        }

        if ($this->isProcessNameMachiningEc((int) $newProcessNamesId)) {
            $ecValue = true;
        }

        // Обрабатываем plus_process: если это NDT процесс, сохраняем дополнительные NDT process_names_id
        $plusProcess = null;
        $processName = ProcessName::find($processData['process_names_id']);
        if ($processName && strpos($processName->name, 'NDT-') === 0) {
            $plusProcess = $processData['plus_process'] ?? null;
        }

        // Формируем данные для обновления
        $dataToUpdate = [
            'tdrs_id' => $validated['tdrs_id'],
            'process_names_id' => $processData['process_names_id'],
            'plus_process' => $plusProcess, // Дополнительные NDT process_names_id через запятую
            'processes' => array_values(TdrProcess::normalizeStoredProcessIds($processesArray)), // cast модели сам кодирует JSON
            'ec' => $ecValue, // Используем вычисленное значение EC
            'standalone_ec_only' => $standaloneEcRow,
            'description' => $request->input('description') ?? null, // Добавляем поле description (необязательное)
            'notes' => $request->input('notes') ?? null, // Добавляем поле notes (необязательное)
        ];

        // Обновляем запись
        $current_tdr_processes->update($dataToUpdate);

        // Обработка EC записи при обновлении EC-eligible процесса (Machining, RIL)
        $isOldEcEligible = in_array((int)$oldProcessNamesId, $ecEligibleIds);

        if ($isOldEcEligible || $isNewEcEligible) {
            $this->handleEcProcessOnEcEligibleUpdate(
                $validated['tdrs_id'],
                $oldProcessNamesId,
                $newProcessNamesId,
                $oldProcesses,
                $processesArray,
                $ecEligibleIds,
                $ecValue
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'tdrId' => $validated['tdrs_id']]);
        }
        return redirect()->route('tdr-processes.processes', ['tdrId' => $validated['tdrs_id']])
            ->with('success', 'TDR for Component updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $tdr_process)
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

        if ($tdrProcess->in_traveler) {
            $msg = __('Cannot delete a process that is part of a Traveler group. UnGroup first.');
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $ecEligibleIds = $this->getEcEligibleProcessNameIds();

        // Проверяем, является ли удаляемая запись EC-eligible процессом (Machining, RIL)
        $isEcEligible = in_array((int)$tdrProcess->process_names_id, $ecEligibleIds);

        // Сохраняем процессы удаляемой записи для последующего удаления из EC
        $processesToRemoveFromEC = null;
        if ($isEcEligible) {
            $processesToRemoveFromEC = json_decode($tdrProcess->processes, true) ?: [];
        }

        // Декодируем JSON-поле processes
        $processData = json_decode($tdrProcess->processes, true);

        $jsonResponse = fn() => $request->wantsJson() || $request->ajax()
            ? response()->json(['success' => true])
            : redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])->with('success', 'Process deleted successfully.');

        // Если processes пустой или не является массивом, удаляем всю запись
        if (!is_array($processData)) {
            $tdrProcess->delete();
            $isMachining = in_array((int)$tdrProcess->process_names_id, $this->getEcEligibleProcessNameIds());
            if ($isMachining && $processesToRemoveFromEC) {
                $this->handleEcProcessOnMachiningDelete($tdrId, $processesToRemoveFromEC);
            }
            return $jsonResponse();
        }

        // Если processes содержит только одно значение, удаляем всю запись
        if (count($processData) === 1) {
            $tdrProcess->delete();
            if ($isEcEligible && $processesToRemoveFromEC) {
                $this->handleEcProcessOnEcEligibleDelete($tdrId, $processesToRemoveFromEC);
            }
            return $jsonResponse();
        }

        // Удаляем значение из массива (приводим типы к int для сравнения)
        $processData = array_filter($processData, function ($process) use ($processToRemove) {
            return (int)$process !== (int)$processToRemove;
        });

        $tdrProcess->processes = json_encode(array_values($processData));
        $tdrProcess->save();

        if ($isEcEligible && $processesToRemoveFromEC) {
            $this->handleEcProcessOnEcEligibleDelete($tdrId, $processesToRemoveFromEC);
        }

        return $jsonResponse();
    }

    /**
     * Обрабатывает запись EC при обновлении EC-eligible процесса (Machining, RIL)
     *
     * @param int $tdrId ID компонента
     * @param int $oldProcessNamesId Старый process_names_id
     * @param int $newProcessNamesId Новый process_names_id
     * @param array $oldProcesses Старые процессы
     * @param array $newProcesses Новые процессы
     * @param array $ecEligibleIds ID процессов с EC checkbox (Machining, RIL)
     * @param bool $ecChecked Отмечен ли чекбокс EC
     */


    private function handleEcProcessOnEcEligibleUpdate(
        $tdrId,
        $oldProcessNamesId,
        $newProcessNamesId,
        $oldProcesses,
        $newProcesses,
        array $ecEligibleIds,
        $ecChecked = false
    ) {
        $ecProcessNameId = $this->getEcProcessNameId();
        if (!$ecProcessNameId) {
            Log::warning('EC ProcessName not found when updating EC-eligible process', ['tdrs_id' => $tdrId]);
            return;
        }

        $isOldEcEligible = in_array((int)$oldProcessNamesId, $ecEligibleIds);
        $isNewEcEligible = in_array((int)$newProcessNamesId, $ecEligibleIds);

        // Вариант C: Тип процесса изменился (с другого на EC-eligible) и чекбокс EC отмечен
        // Обрабатываем ПЕРЕД поиском существующей EC записи, так как её может не быть
        if (!$isOldEcEligible && $isNewEcEligible && $ecChecked) {
            // Проверяем, существует ли уже запись companion EC для этого компонента
            $ecProcess = TdrProcess::where('tdrs_id', $tdrId)
                ->where('process_names_id', $ecProcessNameId)
                ->where('standalone_ec_only', false)
                ->first();

            if ($ecProcess) {
                // Обновляем существующую запись EC: добавляем новые процессы
                $ecProcesses = TdrProcess::normalizeStoredProcessIds($ecProcess->processes);
                $mergedProcesses = array_unique(array_merge($ecProcesses, TdrProcess::normalizeStoredProcessIds($newProcesses)));
                $mergedProcesses = array_values($mergedProcesses);

                $ecProcess->update(['processes' => $mergedProcesses]);

            } else {
                // Создаем новую запись EC
                $maxSortOrder = TdrProcess::where('tdrs_id', $tdrId)->max('sort_order') ?? 0;

                TdrProcess::create([
                    'tdrs_id' => $tdrId,
                    'process_names_id' => $ecProcessNameId,
                    'processes' => array_values(TdrProcess::normalizeStoredProcessIds($newProcesses)),
                    'sort_order' => $maxSortOrder + 1,
                    'date_start' => null,
                    'date_finish' => null,
                    'ec' => 0,
                    'standalone_ec_only' => false,
                    'description' => null,
                    'notes' => null,
                ]);

            }
            return; // Выходим, так как уже обработали случай создания/обновления EC
        }

        // Находим запись companion EC для этого компонента (для вариантов A и B)
        $ecProcess = TdrProcess::where('tdrs_id', $tdrId)
            ->where('process_names_id', $ecProcessNameId)
            ->where('standalone_ec_only', false)
            ->first();

        if (!$ecProcess) {
            return; // Нет EC записи - ничего не делаем (для вариантов A и B)
        }

        $ecProcesses = TdrProcess::normalizeStoredProcessIds($ecProcess->processes);

        // Вариант A: Тип процесса не изменился (остался EC-eligible)
        if ($isOldEcEligible && $isNewEcEligible) {
            // Заменяем старые процессы на новые в EC
            foreach ($oldProcesses as $oldProcessId) {
                $index = array_search((int)$oldProcessId, array_map('intval', $ecProcesses));
                if ($index !== false) {
                    // Если старый процесс есть в EC, заменяем на новый (если есть)
                    if (!empty($newProcesses)) {
                        $ecProcesses[$index] = $newProcesses[0]; // Заменяем первый старый на первый новый
                    } else {
                        // Если новых процессов нет, удаляем старый
                        unset($ecProcesses[$index]);
                    }
                }
            }

            // Добавляем новые процессы, которых еще нет в EC
            foreach ($newProcesses as $newProcessId) {
                if (!in_array((int)$newProcessId, array_map('intval', $ecProcesses))) {
                    $ecProcesses[] = $newProcessId;
                }
            }

            $ecProcesses = array_values(array_unique($ecProcesses));

            if (empty($ecProcesses)) {
                // Если EC пуст, удаляем запись
                $ecProcess->delete();
            } else {
                $ecProcess->update(['processes' => $ecProcesses]);
            }
        }
        // Вариант B: Тип процесса изменился (с EC-eligible на другой)
        elseif ($isOldEcEligible && !$isNewEcEligible) {
            // Удаляем старые процессы из EC
            foreach ($oldProcesses as $oldProcessId) {
                $ecProcesses = array_filter($ecProcesses, function($id) use ($oldProcessId) {
                    return (int)$id !== (int)$oldProcessId;
                });
            }
            $ecProcesses = array_values($ecProcesses);

            // Проверяем, остались ли ещё EC-eligible процессы (Machining / RIL и т.д.) на этом TDR
            $remainingEcEligible = TdrProcess::where('tdrs_id', $tdrId)
                ->whereIn('process_names_id', $ecEligibleIds)
                ->get();

            if ($remainingEcEligible->isEmpty() || empty($ecProcesses)) {
                // Если нет больше Machining (EC) ИЛИ EC пуст - удаляем запись EC
                $ecProcess->delete();
            } else {
                // Обновляем запись EC
                $ecProcess->update(['processes' => $ecProcesses]);
            }
        }
    }

    /**
     * Обрабатывает запись EC при удалении EC-eligible процесса (Machining, RIL)
     *
     * @param int $tdrId ID компонента
     * @param array $processesToRemove Процессы, которые нужно удалить из EC записи
     */
    private function handleEcProcessOnEcEligibleDelete($tdrId, $processesToRemove)
    {
        $ecProcessNameId = $this->getEcProcessNameId();
        if (!$ecProcessNameId) {
            Log::warning('EC ProcessName not found when deleting EC-eligible process', ['tdrs_id' => $tdrId]);
            return;
        }

        // Только companion EC (строка «только EC» с standalone не трогаем)
        $ecProcesses = TdrProcess::where('tdrs_id', $tdrId)
            ->where('process_names_id', $ecProcessNameId)
            ->where('standalone_ec_only', false)
            ->get();

        if ($ecProcesses->isEmpty()) {
            return;
        }

        // Если найдено несколько записей companion EC, объединяем их в первую
        if ($ecProcesses->count() > 1) {
            Log::warning('Multiple companion EC process records found during delete, merging them', [
                'tdrs_id' => $tdrId,
                'count' => $ecProcesses->count(),
            ]);

            $allEcProcesses = [];
            foreach ($ecProcesses as $ecProc) {
                $proc = TdrProcess::normalizeStoredProcessIds($ecProc->processes);
                $allEcProcesses = array_merge($allEcProcesses, $proc);
            }
            $allEcProcesses = array_values(array_unique($allEcProcesses));

            $ecProcess = $ecProcesses->first();
            $ecProcess->update(['processes' => $allEcProcesses]);

            foreach ($ecProcesses->skip(1) as $duplicateEc) {
                $duplicateEc->delete();
            }
        } else {
            $ecProcess = $ecProcesses->first();
        }

        // Получаем текущие процессы из EC записи
        $ecProcessesArray = TdrProcess::normalizeStoredProcessIds($ecProcess->processes);

        // Удаляем процессы, которые были в удаленном EC-eligible процессе
        $remainingProcesses = array_filter($ecProcessesArray, function ($processId) use ($processesToRemove) {
            return !in_array((int)$processId, array_map('intval', $processesToRemove));
        });
        $remainingProcesses = array_values($remainingProcesses);

        $ecEligibleIds = $this->getEcEligibleProcessNameIds();
        $remainingEcEligibleProcesses = TdrProcess::where('tdrs_id', $tdrId)
            ->whereIn('process_names_id', $ecEligibleIds)
            ->get();

        // Если это был последний EC-eligible процесс, удаляем EC запись полностью
        if ($remainingEcEligibleProcesses->isEmpty()) {
            $ecProcess->delete();
        } else {
            // Обновляем EC запись: удаляем процессы из удаленного Machining
            if (empty($remainingProcesses)) {
                // Если не осталось процессов, удаляем EC запись
                $ecProcess->delete();
            } else {
                // Обновляем процессы в EC записи
                $ecProcess->update([
                    'processes' => array_values($remainingProcesses),
                ]);
            }
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
            $processIds = $request->input('process_ids');

            if (!is_array($processIds) || empty($processIds)) {
                return response()->json(['success' => false, 'message' => 'Invalid process IDs'], 400);
            }

            // Проверяем, что все процессы принадлежат одному TDR
            $tdrProcesses = TdrProcess::whereIn('id', $processIds)->get();
            if ($tdrProcesses->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No processes found'], 404);
            }

            // Получаем уникальные TDR ID из процессов
            $tdrIds = $tdrProcesses->pluck('tdrs_id')->unique();
            if ($tdrIds->count() > 1) {
                return response()->json(['success' => false, 'message' => 'Processes must belong to the same TDR'], 400);
            }

            $tdrId = $tdrIds->first();

            // Обновляем порядок процессов в транзакции
            DB::transaction(function() use ($processIds, $tdrId) {
                foreach ($processIds as $index => $processId) {
                    TdrProcess::where('id', $processId)
                             ->where('tdrs_id', $tdrId) // Дополнительная проверка безопасности
                             ->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Order updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()], 500);
        }
    }


    public function updateDate(\Illuminate\Http\Request $request, \App\Models\TdrProcess $tdrProcess)
    {
        $isAjax = $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $v = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date_start'  => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);

        // 1) Обычная валидация (формат дат)
        if ($v->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors'  => $v->errors(),
                ], 422);
            }
            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        // текущий start из БД
        $currentStart = $tdrProcess->date_start ? $tdrProcess->date_start->format('Y-m-d') : null;

        // effectiveStart: если date_start пришёл — он главный (даже пустой), иначе текущий из БД
        $effectiveStart = array_key_exists('date_start', $data)
            ? ($data['date_start'] ?: null)
            : $currentStart;

        // 2) Бизнес-правило: finish нельзя без start
        if (!empty($data['date_finish']) && !$effectiveStart) {
            $errors = [
                'date_finish' => ['The start date must be filled in before setting the end date.']
            ];

            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }
            return back()->withErrors($errors)->withInput();
        }

        // 3) Бизнес-правило: finish не может быть раньше start
        if (!empty($data['date_finish']) && $effectiveStart) {
            if (\Carbon\Carbon::parse($data['date_finish'])->lt(\Carbon\Carbon::parse($effectiveStart))) {
                $errors = [
                    'date_finish' => ['The end date cannot be earlier than the start date.']
                ];

                if ($isAjax) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }
                return back()->withErrors($errors)->withInput();
            }
        }

        $fromPaintIndex = (int) $request->input('from_paint_index', 0) === 1;
        $fromMachiningIndex = (int) $request->input('from_machining_index', 0) === 1;
        $oldStart = $tdrProcess->date_start ? $tdrProcess->date_start->format('Y-m-d') : null;
        $oldFinish = $tdrProcess->date_finish ? $tdrProcess->date_finish->format('Y-m-d') : null;
        $authId = auth()->id();

        // 4) Обновляем только те поля, которые реально пришли в запросе
        if (array_key_exists('date_start', $data)) {
            $nextStart = $data['date_start'] ?: null;
            $tdrProcess->date_start = $nextStart;
            if ($oldStart !== $nextStart) {
                $tdrProcess->date_start_user_id = $authId;
            }

            // если старт очистили — логично очистить и finish,
            // чтобы не осталось "конец без начала"
            if (empty($data['date_start'])) {
                $tdrProcess->date_finish = null;
                if ($oldFinish !== null) {
                    $tdrProcess->date_finish_user_id = $authId;
                }
                if ($fromMachiningIndex) {
                    $tdrProcess->working_steps_count = null;
                    MachiningWorkStep::query()->where('tdr_process_id', $tdrProcess->id)->delete();
                }
            }
        }

        if ($fromMachiningIndex && (int) ($tdrProcess->working_steps_count ?? 0) >= 1 && array_key_exists('date_finish', $data)) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['date_finish' => ['Use work step rows to set finish when steps are enabled.']],
                ], 422);
            }

            return back()->withErrors(['date_finish' => 'Use work step rows.'])->withInput();
        }

        if (array_key_exists('date_finish', $data)) {
            $nextFinish = $data['date_finish'] ?: null;
            $tdrProcess->date_finish = $nextFinish;
            if ($oldFinish !== $nextFinish) {
                $tdrProcess->date_finish_user_id = $authId;
            }
        }

        // фиксируем пользователя
        $tdrProcess->user_id = $authId;

        $tdrProcess->save();

        if ($fromPaintIndex) {
            $newStart = $tdrProcess->date_start ? $tdrProcess->date_start->format('Y-m-d') : null;
            $newFinish = $tdrProcess->date_finish ? $tdrProcess->date_finish->format('Y-m-d') : null;

            $startChanged = $oldStart !== $newStart;
            $finishChanged = $oldFinish !== $newFinish;

            if ($startChanged || $finishChanged) {
                $tdrProcess->loadMissing(['tdr.workorder', 'tdr.component', 'processName']);

                activity('paint_date_change')
                    ->causedBy(auth()->user())
                    ->performedOn($tdrProcess)
                    ->event('updated')
                    ->withProperties([
                        'workorder_id' => (int) ($tdrProcess->tdr?->workorder_id ?? 0),
                        'workorder_number' => (int) ($tdrProcess->tdr?->workorder?->number ?? 0),
                        'tdr_id' => (int) ($tdrProcess->tdrs_id ?? 0),
                        'tdr_process_id' => (int) $tdrProcess->id,
                        'process_name' => (string) ($tdrProcess->processName?->name ?? ''),
                        'detail_part_number' => (string) ($tdrProcess->tdr?->component?->part_number ?? ''),
                        'old' => [
                            'date_start' => $oldStart,
                            'date_finish' => $oldFinish,
                        ],
                        'new' => [
                            'date_start' => $newStart,
                            'date_finish' => $newFinish,
                        ],
                    ])
                    ->log('Paint date updated');
            }
        }

        if ($fromMachiningIndex) {
            $newStart = $tdrProcess->date_start ? $tdrProcess->date_start->format('Y-m-d') : null;
            $newFinish = $tdrProcess->date_finish ? $tdrProcess->date_finish->format('Y-m-d') : null;

            $startChanged = $oldStart !== $newStart;
            $finishChanged = $oldFinish !== $newFinish;

            if ($startChanged || $finishChanged) {
                $tdrProcess->loadMissing(['tdr.workorder', 'tdr.component', 'processName']);

                activity('machining_date_change')
                    ->causedBy(auth()->user())
                    ->performedOn($tdrProcess)
                    ->event('updated')
                    ->withProperties([
                        'workorder_id' => (int) ($tdrProcess->tdr?->workorder_id ?? 0),
                        'workorder_number' => (int) ($tdrProcess->tdr?->workorder?->number ?? 0),
                        'tdr_id' => (int) ($tdrProcess->tdrs_id ?? 0),
                        'tdr_process_id' => (int) $tdrProcess->id,
                        'process_name' => (string) ($tdrProcess->processName?->name ?? ''),
                        'detail_part_number' => (string) ($tdrProcess->tdr?->component?->part_number ?? ''),
                        'old' => [
                            'date_start' => $oldStart,
                            'date_finish' => $oldFinish,
                        ],
                        'new' => [
                            'date_start' => $newStart,
                            'date_finish' => $newFinish,
                        ],
                    ])
                    ->log('Machining date updated');
            }
        }

        // Снятие с очереди Paint: после сохранения любой даты — если по WO закрыты все строки
        // (List + детали, обе даты), как на экране. Раньше требовался непустой date_finish в ЭТОМ
        // запросе — при сохранении только Start поле finish не приходило, очередь не сбрасывалась.
        $paintQueueDequeued = false;
        if ($fromPaintIndex) {
            $tdrProcess->loadMissing('tdr');
            $workorderId = (int) ($tdrProcess->tdr?->workorder_id ?? 0);

            if ($workorderId > 0) {
                $freshWo = Workorder::query()->find($workorderId);
                $allPaintClosed = $freshWo !== null
                    && app(PaintIndexRowsBuilder::class)->isWorkorderPaintFullyClosed($freshWo);

                if ($allPaintClosed) {
                    $queuedWo = Workorder::query()
                        ->select(['id', 'paint_queue_order'])
                        ->whereKey($workorderId)
                        ->first();

                    if ($queuedWo !== null && $queuedWo->paint_queue_order !== null) {
                        $oldPosition = (int) $queuedWo->paint_queue_order;

                        Workorder::query()
                            ->whereKey($workorderId)
                            ->update(['paint_queue_order' => null]);

                        Workorder::query()
                            ->whereNotNull('approve_at')
                            ->whereNull('done_at')
                            ->where('is_draft', 0)
                            ->whereNotNull('paint_queue_order')
                            ->where('paint_queue_order', '>', $oldPosition)
                            ->decrement('paint_queue_order');

                        $paintQueueDequeued = true;
                    }
                }
            }
        }

        if ($fromMachiningIndex) {
            $tdrProcess->loadMissing('tdr');
            $workorderId = (int) ($tdrProcess->tdr?->workorder_id ?? 0);

            if ($workorderId > 0) {
                $freshWo = Workorder::query()->find($workorderId);
                if ($freshWo !== null) {
                    app(MachiningWorkorderQueueRelease::class)->releaseIfFullyClosed($freshWo);
                }
            }
        }

        if ($isAjax) {
            $tdrProcess->loadMissing(['dateStartUpdatedBy:id,name', 'dateFinishUpdatedBy:id,name']);

            return response()->json([
                'success'               => true,
                'user'                  => auth()->user()->name ?? 'system',
                'date_start'            => $tdrProcess->date_start ? $tdrProcess->date_start->format('Y-m-d') : null,
                'date_finish'           => $tdrProcess->date_finish ? $tdrProcess->date_finish->format('Y-m-d') : null,
                'date_start_user'       => $tdrProcess->dateStartUpdatedBy?->name,
                'date_finish_user'      => $tdrProcess->dateFinishUpdatedBy?->name,
                'paint_queue_changed'   => $paintQueueDequeued,
            ], 200);
        }

        return back()->with('success', 'Process dates updated');
    }

    /**
     * Даты для группы Traveler (все tdr_processes с in_traveler у одного TDR) — main, правая панель.
     */
    public function updateTravelerGroupDates(Request $request, Tdr $tdr)
    {
        $isAjax = $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $processes = TdrProcess::query()
            ->where('tdrs_id', (int) $tdr->id)
            ->where('in_traveler', true)
            ->orderBy('id')
            ->get();

        if ($processes->isEmpty()) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'No traveler processes'], 422);
            }

            abort(404);
        }

        $v = Validator::make($request->all(), [
            'date_start'  => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors'  => $v->errors(),
                ], 422);
            }

            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        if (! array_key_exists('date_start', $data) && ! array_key_exists('date_finish', $data)) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'No date fields'], 422);
            }

            return back()->withErrors(['date' => 'No date fields'])->withInput();
        }

        return DB::transaction(function () use ($data, $tdr, $isAjax, $processes) {
            $uid = auth()->id();

            if (array_key_exists('date_start', $data)) {
                $newStart = $data['date_start'] !== null && $data['date_start'] !== ''
                    ? $data['date_start']
                    : null;

                $fresh = TdrProcess::query()
                    ->where('tdrs_id', (int) $tdr->id)
                    ->where('in_traveler', true)
                    ->orderBy('id')
                    ->get();

                foreach ($fresh as $p) {
                    $oldStart = $p->date_start ? $p->date_start->format('Y-m-d') : null;
                    $oldFinish = $p->date_finish ? $p->date_finish->format('Y-m-d') : null;
                    $p->date_start = $newStart;
                    if ($oldStart !== $newStart) {
                        $p->date_start_user_id = $uid;
                    }
                    if ($newStart === null) {
                        $p->date_finish = null;
                        if ($oldFinish !== null) {
                            $p->date_finish_user_id = $uid;
                        }
                    }
                    $p->user_id = $uid;
                    $p->save();
                }
            }

            if (array_key_exists('date_finish', $data)) {
                $fresh = TdrProcess::query()
                    ->where('tdrs_id', (int) $tdr->id)
                    ->where('in_traveler', true)
                    ->orderBy('id')
                    ->get();

                $finishVal = $data['date_finish'] !== null && $data['date_finish'] !== ''
                    ? $data['date_finish']
                    : null;

                $starts = $fresh->map(static function (TdrProcess $p) {
                    return $p->date_start ? $p->date_start->format('Y-m-d') : null;
                })->filter()->values();

                $effectiveStart = $starts->isNotEmpty() ? $starts->sort()->first() : null;

                if ($finishVal !== null && $finishVal !== '' && ($effectiveStart === null || $effectiveStart === '')) {
                    $errors = [
                        'date_finish' => ['The start date must be filled in before setting the end date.'],
                    ];
                    if ($isAjax) {
                        return response()->json(['success' => false, 'errors' => $errors], 422);
                    }

                    return back()->withErrors($errors)->withInput();
                }

                if ($finishVal !== null && $finishVal !== '' && $effectiveStart) {
                    if (Carbon::parse($finishVal)->lt(Carbon::parse($effectiveStart))) {
                        $errors = [
                            'date_finish' => ['The end date cannot be earlier than the start date.'],
                        ];
                        if ($isAjax) {
                            return response()->json(['success' => false, 'errors' => $errors], 422);
                        }

                        return back()->withErrors($errors)->withInput();
                    }
                }

                foreach ($fresh as $p) {
                    $oldFinish = $p->date_finish ? $p->date_finish->format('Y-m-d') : null;
                    $nextFinish = ($finishVal === null || $finishVal === '') ? null : $finishVal;
                    $p->date_finish = $nextFinish;
                    if ($oldFinish !== $nextFinish) {
                        $p->date_finish_user_id = $uid;
                    }
                    $p->user_id = $uid;
                    $p->save();
                }
            }

            if ($isAjax) {
                $leader = TdrProcess::query()
                    ->where('tdrs_id', (int) $tdr->id)
                    ->where('in_traveler', true)
                    ->with(['dateStartUpdatedBy:id,name', 'dateFinishUpdatedBy:id,name'])
                    ->orderBy('id')
                    ->first();

                return response()->json([
                    'success'          => true,
                    'user'             => auth()->user()->name ?? 'system',
                    'date_start'       => $leader?->date_start ? $leader->date_start->format('Y-m-d') : null,
                    'date_finish'      => $leader?->date_finish ? $leader->date_finish->format('Y-m-d') : null,
                    'date_start_user'  => $leader?->dateStartUpdatedBy?->name,
                    'date_finish_user' => $leader?->dateFinishUpdatedBy?->name,
                ], 200);
            }

            return back()->with('success', 'Traveler dates updated');
        });
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
            if (ProcessName::hasNoProcessForm($process_name)) {
                continue;
            }
            $selectedVendor = $vendorId ? Vendor::find($vendorId) : null;

            // Базовые данные для формы
            $formData = [
                'module' => 'tdr-processes',
                'current_wo' => $current_wo,
                'components' => $components,
                'tdrs' => [$current_tdr->id],
                'manuals' => Manual::where('id', $manual_id)->get(),
                'process_name' => $process_name,
                'manual_id' => $manual_id,
                'selectedVendor' => $selectedVendor,
                'current_tdr' => $current_tdr,
                'machining_header_manual_libs' => $this->machiningHeaderManualLibsForWorkorder($process_name, (int) $current_wo->id),
            ];

            // Обработка NDT форм
            if ($process_name && $process_name->process_sheet_name == 'NDT') {
                $processNames = ProcessName::whereIn('name', [
                    'NDT-1', 'NDT-2', 'NDT-3', 'NDT-4', 'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8',
                    'Eddy Current Test', 'BNI'
                ])->pluck('id', 'name');

                $ndt_ids = [
                    'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                    'ndt2_name_id' => $processNames['NDT-2'] ?? null,
                    'ndt3_name_id' => $processNames['NDT-3'] ?? null,
                    'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                    'ndt5_name_id' => $processNames['BNI'] ?? $processNames['NDT-5'] ?? null,
                    'ndt6_name_id' => $processNames['Eddy Current Test'] ?? $processNames['NDT-6'] ?? null,
                    'ndt7_name_id' => $processNames['NDT-7'] ?? null,
                    'ndt8_name_id' => $processNames['NDT-8'] ?? null,
                ];
                $ndt_ids_filtered = array_filter($ndt_ids);

                $ndt_processes = Process::whereIn('id', $manualProcesses)
                    ->where(function ($query) use ($ndt_ids, $ndt_ids_filtered) {
                        if (!empty($ndt_ids_filtered)) {
                            $query->whereIn('process_names_id', $ndt_ids_filtered);
                        }
                        if (!empty($ndt_ids['ndt1_name_id'])) {
                            $query->orWhere('process_names_id', $ndt_ids['ndt1_name_id']);
                        }
                        if (!empty($ndt_ids['ndt4_name_id'])) {
                            $query->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                        }
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
                ] + $ndt_ids;
            } else {
                // Обработка обычных процессов
                $processComponents = Process::whereIn('id', $manualProcesses)
                    ->where('process_names_id', $process_name->id)
                    ->get();

                // Фильтруем processes по конкретному process_id
                $currentProcesses = json_decode($tdrProcess->processes, true) ?: [];
                $currentProcesses = array_values(array_filter($currentProcesses, function ($pid) use ($processId) {
                    return (int) $pid === (int) $processId;
                }));
                $tdrProcess->processes = json_encode($currentProcesses);

                $formData += [
                    'process_components' => $processComponents,
                    'process_tdr_components' => collect([$tdrProcess->load(['tdr', 'processName'])]),
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


    protected function validationError(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        return back()->withErrors([$field => $message]);
    }
}
