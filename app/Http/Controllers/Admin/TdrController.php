<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\ModCsv;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Training;
use App\Models\Transfer;
use App\Models\Vendor;
use App\Models\WoBushing;
use Illuminate\Support\Facades\Cache;
use App\Models\Unit;
//use App\Models\Wo_Code;
//use App\Models\WoCode;
use App\Models\Workorder;
use App\Models\NdtCadCsv;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class TdrController extends Controller
{
    const DEFAULT_QTY = 1;
    const DEFAULT_PROCESS = 1;
    const PROCESS_TYPE_NDT = 'ndt';
    const PROCESS_TYPE_CAD = 'cad';
    const PROCESS_TYPE_LOG = 'log';

    /**
     * Нормализует IPL номер, убирая буквенные суффиксы для сравнения
     * Например: 5-90A -> 5-90, 1-1190B -> 1-1190
     *
     * @param string $iplNum
     * @return string
     */
    private function normalizeIplNum($iplNum)
    {
        if (empty($iplNum)) {
            return '';
        }

        // Убираем буквенные суффиксы в конце (A, B, C, и т.д.)
        // Паттерн: удаляем буквы в конце после последнего дефиса или в конце строки
        return preg_replace('/[A-Z]+$/', '', trim($iplNum));
    }

    /**
     * Рассчитывает пагинацию компонентов с учетом manual-строк и пустых строк
     *
     * @param array $components Массив компонентов
     * @param int $targetRows Целевое количество строк на странице (включая manual и пустые)
     * @return array Массив chunks, каждый chunk содержит:
     *   - 'components': массив компонентов
     *   - 'manual_rows': количество manual-строк
     *   - 'data_rows': количество строк с данными
     *   - 'empty_rows': количество пустых строк для добавления
     *   - 'total_rows': общее количество строк
     *   - 'previous_manual': последний manual в chunk (для следующего chunk)
     */
    private function paginateComponentsWithEmptyRows($components, $targetRows = 18)
    {
        $chunks = [];
        $currentChunk = [];
        $previousManual = null;
        $previousChunkLastManual = null;

        foreach ($components as $component) {
            $currentManual = $component->manual ?? null;
            $hasManual = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);

            // Подсчитываем количество строк в текущем chunk БЕЗ нового компонента
            $rowsInChunk = count($currentChunk);
            $manualRowsInChunk = 0;
            $tempPreviousManual = $previousChunkLastManual ?? $previousManual;

            // Считаем manual-строки в текущем chunk (уже добавленных компонентов)
            foreach ($currentChunk as $chunkComponent) {
                $chunkManual = $chunkComponent->manual ?? null;
                if ($chunkManual !== null && $chunkManual !== '' && $chunkManual !== $tempPreviousManual) {
                    $manualRowsInChunk++;
                    $tempPreviousManual = $chunkManual;
                } else if ($chunkManual !== null && $chunkManual !== '') {
                    $tempPreviousManual = $chunkManual;
                }
            }

            // Если добавляем этот компонент, будет ли новая manual-строка?
            if ($hasManual) {
                $manualRowsInChunk++;
            }

            // Общее количество строк в chunk С новым компонентом
            $totalRowsInChunk = $rowsInChunk + $manualRowsInChunk + 1;

            // Если добавление этого компонента превысит лимит, сохраняем текущий chunk
            if ($totalRowsInChunk > $targetRows && !empty($currentChunk)) {
                // Рассчитываем пустые строки для текущего chunk
                $chunkInfo = $this->calculateChunkInfo($currentChunk, $targetRows, $previousChunkLastManual ?? $previousManual, false);
                $chunks[] = $chunkInfo;
                $previousChunkLastManual = $chunkInfo['previous_manual'];

                // Начинаем новый chunk
                $currentChunk = [];
                $previousManual = $previousChunkLastManual;
            }

            // Добавляем компонент в текущий chunk
            $currentChunk[] = $component;

            // Обновляем previousManual для следующей итерации
            if ($currentManual !== null && $currentManual !== '') {
                $previousManual = $currentManual;
            }
        }

        // Добавляем последний chunk, если он не пустой
        if (!empty($currentChunk)) {
            $chunkInfo = $this->calculateChunkInfo($currentChunk, $targetRows, $previousChunkLastManual ?? $previousManual, true);
            $chunks[] = $chunkInfo;
        }

        return $chunks;
    }

    /**
     * Рассчитывает информацию о chunk: количество manual-строк, data-строк и пустых строк
     *
     * @param array $chunk Массив компонентов в chunk
     * @param int $targetRows Целевое количество строк
     * @param string|null $previousManual Manual из предыдущего chunk
     * @param bool $isLastPage Является ли это последней страницей
     * @return array
     */
    private function calculateChunkInfo($chunk, $targetRows, $previousManual = null, $isLastPage = false)
    {
        $manualRows = 0;
        $dataRows = count($chunk);
        $tempPreviousManual = $previousManual;
        $lastManual = null;

        // Считаем manual-строки
        foreach ($chunk as $component) {
            $currentManual = $component->manual ?? null;
            if ($currentManual !== null && $currentManual !== '' && $currentManual !== $tempPreviousManual) {
                $manualRows++;
                $tempPreviousManual = $currentManual;
                $lastManual = $currentManual;
            } else if ($currentManual !== null && $currentManual !== '') {
                $tempPreviousManual = $currentManual;
                $lastManual = $currentManual;
            }
        }

        $totalDataRows = $dataRows + $manualRows;

        // Добавляем пустые строки до targetRows на всех страницах (включая последнюю)
        $emptyRows = max(0, $targetRows - $totalDataRows);

        return [
            'components' => $chunk,
            'manual_rows' => $manualRows,
            'data_rows' => $dataRows,
            'empty_rows' => $emptyRows,
            'total_rows' => $totalDataRows + $emptyRows,
            'previous_manual' => $lastManual ?? $previousManual,
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index()
    {
        $orders = Workorder::all();
        $manuals = Manual::all();
        $units = Unit::with('manuals')->get();
        $tdrs = Tdr::all();
        return view('admin.tdrs.index', compact('orders', 'units', 'manuals', 'tdrs'));
    }

    public function create()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function inspectionUnit($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        // Получение уже введённых условий для этого workorder
        $existing_condition_ids = Tdr::where('workorder_id', $workorder_id)
            ->pluck('conditions_id')
            ->filter()
            ->unique()
            ->toArray();

        // Условия для Unit
        $unit_conditions = Condition::where('unit', true)
            ->whereNotIn('id', $existing_condition_ids)
            ->get();

        // Другие необходимые данные (например, manuals, units, customers, и т.п.)
        // ...

        return view('admin.tdrs.unit-inspection', compact('current_wo', 'unit_conditions' /*, ...*/));
    }

    public function inspectionComponent($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        // Компоненты для данного manual
        $components = Component::where('manual_id', $manual_id)
            ->select('id', 'part_number', 'assy_part_number', 'name', 'ipl_num')
            ->get();

        // Условия для Component - без фильтрации
        $component_conditions = Condition::where('unit', false)->get();

        // Получаем коды и necessaries
        $codes = Code::all();
        $necessaries = Necessary::all();
        $manuals=Manual::all();

        return view('admin.tdrs.component-inspection', compact('current_wo', 'component_conditions',
            'components', 'codes', 'necessaries', 'manual_id', 'manuals'));
    }

    public function getComponentsByManual(Request $request)
    {
        $manual_id = $request->get('manual_id');

        if (!$manual_id) {
            return response()->json(['components' => []]);
        }

        $components = Component::where('manual_id', $manual_id)
            ->select('id', 'part_number', 'assy_part_number', 'name', 'ipl_num')
            ->get();

        return response()->json(['components' => $components]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string|max:255',
            'assy_serial_number' => 'nullable|string|max:255',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id',
            'qty' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'order_component_id' => 'nullable|exists:components,id',
        ]);

        // Установка значений по умолчанию для флагов
        $use_tdr = $request->boolean('use_tdr', false);
        $use_process_forms = $request->boolean('use_process_forms', false);
        $qty = (int)($validated['qty'] ?? 1);

        // Загружаем необходимые сущности один раз
        $workorder = Workorder::findOrFail($validated['workorder_id']);
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();
        $repairNecessary = Necessary::where('name', 'Repair')->first();

        // Валидация: Missing требует обязательный component_id
        if ($code && $validated['codes_id'] == $code->id) {
            if (empty($validated['component_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['component_id' => 'Component ID is required when code is Missing']);
            }

            // Валидация: Missing требует обязательный necessaries_id = Order New (ID = 2)
            if (empty($validated['necessaries_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Necessary is required for Missing code']);
            }
            
            if (!$necessary || $validated['necessaries_id'] != $necessary->id) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Missing code can only have Order New necessary']);
            }
        }

        // Валидация: для других codes (не Missing) necessaries_id обязателен и должен быть Repair или Order New
        if ($code && $validated['codes_id'] && $validated['codes_id'] != $code->id) {
            if (empty($validated['necessaries_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Necessary is required for non-Missing codes']);
            }
            
            $isValidNecessary = false;
            if ($necessary && $validated['necessaries_id'] == $necessary->id) {
                $isValidNecessary = true;
            }
            if ($repairNecessary && $validated['necessaries_id'] == $repairNecessary->id) {
                $isValidNecessary = true;
            }
            if (!$isValidNecessary) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'For non-Missing codes, necessary must be Repair or Order New']);
            }
        }

        // Проверяем наличие записей с Missing до создания (для оптимизации)
        $hasExistingMissing = false;
        if ($code && $validated['codes_id'] === $code->id) {
            $hasExistingMissing = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->exists();
        }

        // Если codes_id равно Missing, автоматически устанавливаем conditions_id=1 (PARTS MISSING UPON ARRIVAL)
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        if ($code && $validated['codes_id'] === $code->id && $missingCondition) {
            // Если conditions_id не установлен или равен null, устанавливаем его в missingCondition->id
            if (empty($validated['conditions_id']) || $validated['conditions_id'] === null) {
                $validated['conditions_id'] = $missingCondition->id;
                \Log::info('Auto-set conditions_id to missingCondition', [
                    'workorder_id' => $workorder->id,
                    'codes_id' => $validated['codes_id'],
                    'conditions_id' => $missingCondition->id
                ]);
            }
        }

        try {
            // Сохранение в таблице tdrs
            $tdr = Tdr::create([
                'workorder_id' => $validated['workorder_id'],
                'component_id' => $validated['component_id'],
                'serial_number' => $validated['serial_number'] ?? 'NSN',
                'assy_serial_number' => $validated['assy_serial_number'],
                'codes_id' => $validated['codes_id'],
                'conditions_id' => $validated['conditions_id'],
                'necessaries_id' => $validated['necessaries_id'],
                'description' => $validated['description'],
                'qty' => $qty,
                'use_tdr' => $use_tdr,
                'use_process_forms' => $use_process_forms,
                'order_component_id' => $validated['order_component_id'],
            ]);
            
            \Log::info('TDR created', [
                'tdr_id' => $tdr->id,
                'workorder_id' => $tdr->workorder_id,
                'codes_id' => $tdr->codes_id,
                'conditions_id' => $tdr->conditions_id,
                'component_id' => $tdr->component_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating TDR', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create TDR record']);
        }

        // Если codes_id равно Missing, обновляем поле part_missing в workorders
        // Используем приведение типов для сравнения, т.к. codes_id может быть строкой из формы
        $codesIdInt = $validated['codes_id'] ? (int)$validated['codes_id'] : null;
        $codeIdInt = $code ? (int)$code->id : null;
        
        \Log::info('Checking if codes_id is Missing', [
            'workorder_id' => $workorder->id,
            'codes_id' => $validated['codes_id'],
            'codes_id_int' => $codesIdInt,
            'code_id' => $code ? $code->id : null,
            'code_id_int' => $codeIdInt,
            'code_found' => $code ? true : false,
            'match' => ($code && $codesIdInt === $codeIdInt)
        ]);
        
        if ($code && $codesIdInt === $codeIdInt) {
            // Проверяем количество записей с Missing после создания (включая только что созданную)
            $missingCount = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->count();

            \Log::info('Checking part_missing flag', [
                'workorder_id' => $workorder->id,
                'missing_count' => $missingCount,
                'current_part_missing' => $workorder->part_missing,
                'codes_id' => $validated['codes_id'],
                'part_missing_type' => gettype($workorder->part_missing)
            ]);

            // Если это первая запись с Missing (count == 1) или флаг еще не установлен (0 или false)
            if ($missingCount == 1 || $workorder->part_missing == 0 || $workorder->part_missing === false || !$workorder->part_missing) {
                $workorder->part_missing = true;
                $workorder->save();
                \Log::info('Set part_missing to true', [
                    'workorder_id' => $workorder->id,
                    'missing_count' => $missingCount
                ]);
            } else {
                \Log::info('part_missing not changed', [
                    'workorder_id' => $workorder->id,
                    'missing_count' => $missingCount,
                    'part_missing' => $workorder->part_missing
                ]);
            }
        }

        // Второе условие: если codes_id не равно Missing и necessaries_id равно Order New
        // new_parts=true устанавливается только когда у workorder есть компоненты (tdr записи) с necessary = Order New
        if ($code && $necessary &&
            $codesIdInt !== $codeIdInt &&
            $validated['necessaries_id'] === $necessary->id) {

            // Проверяем количество записей с Order New после создания (включая только что созданную)
            $orderNewCount = Tdr::where('workorder_id', $workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->count();

            // Если это первая запись с Order New (count == 1) или флаг еще не установлен
            if ($orderNewCount == 1 || $workorder->new_parts === false || $workorder->new_parts == 0) {
                $workorder->new_parts = true;
                $workorder->save();
                \Log::info('Set new_parts to true', [
                    'workorder_id' => $workorder->id,
                    'order_new_count' => $orderNewCount
                ]);
            }
        }

        return redirect()
            ->route('tdrs.show', ['id' => $workorder->id])
            ->with('success', 'TDR record created successfully');
    }
    public function store_old(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string|max:255',
            'assy_serial_number' => 'nullable|string|max:255',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id',
            'qty' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'order_component_id' => 'nullable|exists:components,id',
        ]);

        // Установка значений по умолчанию для флагов
        $use_tdr = $request->boolean('use_tdr', false);
        $use_process_forms = $request->boolean('use_process_forms', false);
        $qty = (int)($validated['qty'] ?? 1);

        // Загружаем необходимые сущности один раз
        $workorder = Workorder::findOrFail($validated['workorder_id']);
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        // Проверяем наличие записей с Missing до создания (для оптимизации)
        $hasExistingMissing = false;
        if ($code && $validated['codes_id'] === $code->id) {
            $hasExistingMissing = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->exists();
        }

        // Если codes_id равно Missing, автоматически устанавливаем conditions_id=1 (PARTS MISSING UPON ARRIVAL)
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        if ($code && $validated['codes_id'] === $code->id && $missingCondition) {
            // Если conditions_id не установлен, устанавливаем его в missingCondition->id
            if (empty($validated['conditions_id'])) {
                $validated['conditions_id'] = $missingCondition->id;
            }
        }

        try {
            // Сохранение в таблице tdrs
            $tdr = Tdr::create([
                'workorder_id' => $validated['workorder_id'],
                'component_id' => $validated['component_id'],
                'serial_number' => $validated['serial_number'] ?? 'NSN',
                'assy_serial_number' => $validated['assy_serial_number'],
                'codes_id' => $validated['codes_id'],
                'conditions_id' => $validated['conditions_id'],
                'necessaries_id' => $validated['necessaries_id'],
                'description' => $validated['description'],
                'qty' => $qty,
                'use_tdr' => $use_tdr,
                'use_process_forms' => $use_process_forms,
                'order_component_id' => $validated['order_component_id'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating TDR', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create TDR record']);
        }

        // Если codes_id равно Missing, обновляем поле part_missing в workorders
        if ($code && $validated['codes_id'] === $code->id) {
            // Проверяем количество записей с Missing после создания (включая только что созданную)
            $missingCount = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->count();

            // Если это первая запись с Missing (count == 1) или флаг еще не установлен
            if ($missingCount == 1 || $workorder->part_missing === false) {
                $workorder->part_missing = true;
                $workorder->save();
            }
        }

        // Второе условие: если codes_id не равно Missing и necessaries_id равно Order New
        if ($code && $necessary &&
            $validated['codes_id'] !== $code->id &&
            $validated['necessaries_id'] === $necessary->id) {

            if ($workorder->new_parts === false) {
                $workorder->new_parts = true;
                $workorder->save();
            }
        }

        return redirect()
            ->route('tdrs.show', ['id' => $workorder->id])
            ->with('success', 'TDR record created successfully');
    }

    /**
     * Store multiple unit inspections
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUnitInspections(Request $request)
    {
        $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'conditions' => 'required|array',
        ]);

        $workorderId = $request->workorder_id;
        $conditions = $request->conditions;

        try {
            // Получаем все существующие unit inspections для данного workorder
            $existingTdrs = Tdr::where('workorder_id', $workorderId)
                ->where('use_tdr', true)
                ->where('use_process_forms', false)
                ->whereNull('component_id')
                ->get()
                ->keyBy('conditions_id');

            $processedConditionIds = [];

            // Обрабатываем каждое выбранное condition
            foreach ($conditions as $conditionId => $conditionData) {
                if (isset($conditionData['selected']) && $conditionData['selected']) {
                    $processedConditionIds[] = $conditionId;

                    $notes = $conditionData['notes'] ?? '';
                    $tdrId = $conditionData['tdr_id'] ?? null;

                    // Если есть существующая запись - обновляем
                    if ($tdrId && $existingTdrs->has($conditionId)) {
                        $tdr = $existingTdrs->get($conditionId);
                        if ($tdr->id == $tdrId) {
                            $tdr->description = $notes;
                            $tdr->save();
                            continue;
                        }
                    }

                    // Если есть существующая запись с таким condition_id, но другим id - обновляем её
                    if ($existingTdrs->has($conditionId)) {
                        $tdr = $existingTdrs->get($conditionId);
                        $tdr->description = $notes;
                        $tdr->save();
                        continue;
                    }

                    // Создаём новую запись
                    Tdr::create([
                        'workorder_id' => $workorderId,
                        'component_id' => null,
                        'conditions_id' => $conditionId,
                        'description' => $notes,
                        'qty' => 1,
                        'serial_number' => 'NSN',
                        'assy_serial_number' => ' ',
                        'codes_id' => null,
                        'necessaries_id' => null,
                        'use_tdr' => true,
                        'use_process_forms' => false,
                    ]);
                }
            }

            // Удаляем записи для conditions, которые не были выбраны
            foreach ($existingTdrs as $conditionId => $tdr) {
                if (!in_array($conditionId, $processedConditionIds)) {
                    // Проверяем, что это не "PARTS MISSING UPON ARRIVAL" condition
                    $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                        ->where('id', $conditionId)
                        ->first();

                    if (!$missingCondition) {
                        $tdr->delete();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('Unit inspections saved successfully.')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while saving unit inspections.') . ' ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */

    public function processes($id)
    {

        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        $necessary = Necessary::where('name', 'Order New')->first();

        $manuals = Manual::all();  // или можно отфильтровать только тот, который связан с unit

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Ограничиваем процессы только текущим Workorder: берём id связанных TDR
        $tdrIds = Tdr::where('workorder_id', $current_wo->id)
            ->pluck('id');

        // Загружаем только процессы для этих TDR, с сортировкой и названием процесса
        $tdrProcesses = TdrProcess::whereIn('tdrs_id', $tdrIds)
            ->with('processName')
            ->orderBy('sort_order')
            ->get();

        $proces = Process::all()->keyBy('id');
        $vendors = Vendor::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=',null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->where('use_process_forms', true)
            ->with('component')
            ->get();

        // Группируем процессы для создания кнопок групповых форм
        $processGroups = [];
        $totalQty = 0;

        foreach ($tdrs as $tdr) {
            if (!$tdr->component) {
                continue;
            }

            // Получаем все процессы для этого TDR
            $tdrProcessesForTdr = $tdrProcesses->where('tdrs_id', $tdr->id);

            foreach ($tdrProcessesForTdr as $tdrProcess) {
                if (!$tdrProcess->processName) {
                    continue;
                }

                $processName = $tdrProcess->processName;
                // Используем processNameId как ключ группы для всех процессов, включая NDT
                // Это позволяет разделять NDT-1, NDT-4 и другие типы NDT в отдельные группы
                $groupKey = $processName->id;

                if (!isset($processGroups[$groupKey])) {
                    $processGroups[$groupKey] = [
                        'process_name' => $processName,
                        'components_qty' => [],
                        'components' => []
                    ];
                }

                // Добавляем/обновляем количество по компоненту (для TDR обычно qty = 1, но можно использовать serial_number)
                $qty = 1; // По умолчанию 1, можно изменить если есть поле qty в TDR

                // Создаем составной ключ для группировки: ipl_num + part_number + serial_number
                // Это позволяет различать компоненты с одинаковыми ipl_num и part_number, но разными serial_number
                $componentKey = sprintf(
                    '%s_%s_%s',
                    $tdr->component->ipl_num ?? '',
                    $tdr->component->part_number ?? '',
                    $tdr->serial_number ?? ''
                );

                $processGroups[$groupKey]['components_qty'][$componentKey] =
                    ($processGroups[$groupKey]['components_qty'][$componentKey] ?? 0) + $qty;

                // Сохраняем информацию о компоненте
                if (!isset($processGroups[$groupKey]['components'][$componentKey])) {
                    $processGroups[$groupKey]['components'][$componentKey] = [
                        'id' => $tdr->component->id,
                        'name' => $tdr->component->name,
                        'ipl_num' => $tdr->component->ipl_num,
                        'part_number' => $tdr->component->part_number,
                        'serial_number' => $tdr->serial_number,
                        'tdr_id' => $tdr->id, // Добавляем TDR ID для точной фильтрации
                        'qty' => $qty
                    ];
                } else {
                    // Обновляем qty если компонент уже есть
                    $processGroups[$groupKey]['components'][$componentKey]['qty'] += $qty;
                    // Если TDR ID еще не сохранен, добавляем его
                    if (!isset($processGroups[$groupKey]['components'][$componentKey]['tdr_ids'])) {
                        $processGroups[$groupKey]['components'][$componentKey]['tdr_ids'] = [];
                    }
                    $processGroups[$groupKey]['components'][$componentKey]['tdr_ids'][] = $tdr->id;
                }

                $totalQty += $qty;
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
            unset($group['components_qty']); // Удаляем техническое поле
        }

        return view('admin.tdrs.processes', compact('current_wo',
            'tdrs','components',
            'manuals','tdrProcesses','proces','vendors','processGroups','totalQty'
        ));
    }

    /**
     * Display grouped forms for all TDR processes by process name.
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
        $manual_id = $current_wo->unit->manual_id;
        $necessary = Necessary::where('name', 'Order New')->first();

        // Проверяем, передан ли tdrId для фильтрации по конкретному компоненту
        $tdrId = $request->input('tdrId');

        if ($tdrId) {
            // Если передан tdrId, фильтруем только процессы для этого компонента
            $tdr = Tdr::findOrFail($tdrId);
            // Проверяем, что TDR принадлежит этому workorder
            if ($tdr->workorder_id != $current_wo->id) {
                abort(403, 'TDR does not belong to this workorder');
            }
            $tdrIds = collect([$tdrId]);
        } else {
            // Получаем все TDR для этого work order
            $tdrIds = Tdr::where('workorder_id', $current_wo->id)
                ->where('component_id', '!=', null)
                ->when($necessary, function ($query) use ($necessary) {
                    return $query->where('necessaries_id', '!=', $necessary->id);
                })
                ->where('use_process_forms', true)
                ->pluck('id');
        }

        // Получаем все TdrProcess для этих TDR
        $tdrProcesses = TdrProcess::whereIn('tdrs_id', $tdrIds)
            ->with(['tdr.component', 'processName'])
            ->orderBy('sort_order')
            ->get();

        // Фильтруем TdrProcess по process_name_id
        // Теперь каждый тип NDT (NDT-1, NDT-4 и т.д.) обрабатывается отдельно по его processNameId
        $filteredTdrProcesses = collect();

        foreach ($tdrProcesses as $tdrProcess) {
            if (!$tdrProcess->tdr || !$tdrProcess->tdr->component || !$tdrProcess->processName) {
                continue;
            }

            $currentProcessName = $tdrProcess->processName;

            // Обрабатываем только процессы с указанным processNameId
            // Это работает для всех типов процессов, включая NDT-1, NDT-4 и т.д.
            if ($currentProcessName->id == $processNameId) {
                $filteredTdrProcesses->push($tdrProcess);
            }
        }

        // Получаем связанные данные
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Получаем выбранного vendor (если передан)
        $selectedVendor = null;
        $vendorId = $request->input('vendor_id');
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Фильтруем компоненты по выбранным component_ids и serial_numbers (если переданы)
        // Теперь учитываем не только component_id, но и serial_number для точной идентификации
        // Если передан tdrId, то фильтрация по component_ids не нужна, так как уже фильтруем по одному компоненту
        $componentIds = $request->input('component_ids');
        $serialNumbers = $request->input('serial_numbers');
        $iplNums = $request->input('ipl_nums');
        $partNumbers = $request->input('part_numbers');

        // Если передан tdrId, пропускаем фильтрацию по component_ids, так как уже фильтруем по одному компоненту
        if ($componentIds && !$tdrId) {
            // Разбиваем строки на массивы
            $filteredComponentIds = is_array($componentIds)
                ? array_map('intval', $componentIds)
                : array_map('intval', explode(',', $componentIds));

            $filteredSerialNumbers = [];
            if ($serialNumbers) {
                $filteredSerialNumbers = is_array($serialNumbers)
                    ? $serialNumbers
                    : explode(',', $serialNumbers);
            }

            $filteredIplNums = [];
            if ($iplNums) {
                $filteredIplNums = is_array($iplNums)
                    ? $iplNums
                    : explode(',', $iplNums);
            }

            $filteredPartNumbers = [];
            if ($partNumbers) {
                $filteredPartNumbers = is_array($partNumbers)
                    ? $partNumbers
                    : explode(',', $partNumbers);
            }

            // Фильтруем TdrProcess по выбранным component_id, ipl_num, part_number и serial_number
            $filteredTdrProcesses = $filteredTdrProcesses->filter(function($tdrProcess) use (
                $filteredComponentIds,
                $filteredSerialNumbers,
                $filteredIplNums,
                $filteredPartNumbers
            ) {
                if (!$tdrProcess->tdr || !$tdrProcess->tdr->component) {
                    return false;
                }

                // Проверяем, соответствует ли component_id
                if (!in_array($tdrProcess->tdr->component->id, $filteredComponentIds)) {
                    return false;
                }

                // Если переданы serial_numbers, проверяем их
                if (!empty($filteredSerialNumbers)) {
                    $tdrSerialNumber = $tdrProcess->tdr->serial_number ?? '';
                    if (!in_array($tdrSerialNumber, $filteredSerialNumbers)) {
                        return false;
                    }
                }

                // Если переданы ipl_nums, проверяем их
                if (!empty($filteredIplNums)) {
                    $tdrIplNum = $tdrProcess->tdr->component->ipl_num ?? '';
                    if (!in_array($tdrIplNum, $filteredIplNums)) {
                        return false;
                    }
                }

                // Если переданы part_numbers, проверяем их
                if (!empty($filteredPartNumbers)) {
                    $tdrPartNumber = $tdrProcess->tdr->component->part_number ?? '';
                    if (!in_array($tdrPartNumber, $filteredPartNumbers)) {
                        return false;
                    }
                }

                return true;
            });
        }

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,
            'components' => $components,
            'manuals' => Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $processName,
            'selectedVendor' => $selectedVendor,
            'tdrs' => $tdrIds->toArray()
        ];

        // Добавляем первый компонент для заголовка формы (если есть компоненты)
        $firstTdrProcess = $filteredTdrProcesses->first();
        if ($firstTdrProcess && $firstTdrProcess->tdr && $firstTdrProcess->tdr->component) {
            $viewData['component'] = $firstTdrProcess->tdr->component;
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

            return view('admin.tdr-processes.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'selectedVendor' => $selectedVendor,
                'ndt_components' => $filteredTdrProcesses,
                'current_ndt_id' => $processName->id
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processNameId)
            ->get();

        return view('admin.tdr-processes.processesForm', array_merge($viewData, [
            'process_components' => $process_components,
            'selectedVendor' => $selectedVendor,
            'process_tdr_components' => $filteredTdrProcesses
        ]));
    }

    public function show($id)
    {
        $current_wo = Workorder::with(['unit.manuals.builder', 'instruction'])->findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();

        $user_wo = $current_wo->user_id;
        $customers = Customer::all();


        // Получаем manual_id из связанного unit
        $manual_id = $current_wo->unit->manual_id;

        $form_type = 112;
        $trainings = Training::where('manuals_id', $manual_id)
            ->where('user_id',$user_wo)
            ->where('form_type',$form_type)
            ->orderBy('date_training', 'desc')  // Сортирует по id по убыванию
            ->first();  // Получает первую (самую последнюю) запись

        // Извлекаем все manuals для отображения
        $manuals = Manual::all();

        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();
        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->first();

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();
        $code = Code::where('name', 'Missing')->first();

        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        $conditions = Condition::all();

        $necessary = Necessary::where('name', 'Order New')->first();

        $processParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=', null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->with(['component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        // Проверка наличия записей с codes_id=Missing для данного workorder
        $hasMissingParts = false;
        if ($code) {
            $hasMissingParts = Tdr::where('workorder_id', $current_wo->id)
                ->where('codes_id', $code->id)
                ->exists();
        }

        // Unit Inspections: 
        // 1. Записи с component_id=null (обычные unit inspections)
        // 2. Записи компонентов с codes_id != Missing и necessaries_id = Order New
        // Записи компонентов с missing НЕ включаются в $inspectsUnit - они отображаются отдельной строкой
        $inspectsUnit = Tdr::where('workorder_id', $current_wo->id)
            ->where(function($query) use ($code, $necessary) {
                // Обычные unit inspections (component_id = null)
                $query->whereNull('component_id');
                
                // ИЛИ записи компонентов с codes_id != Missing и necessaries_id = Order New
                if ($code && $necessary) {
                    $query->orWhere(function($q) use ($code, $necessary) {
                        $q->whereNotNull('component_id')
                          ->where('codes_id', '!=', $code->id)
                          ->where('necessaries_id', $necessary->id);
                    });
                }
            })
            ->with([
                'conditions' => function($query) {
                    $query->select('id', 'name');
                },
                'necessaries' => function($query) {
                    $query->select('id', 'name');
                },
                'component' => function($query) {
                    $query->select('id', 'name', 'ipl_num');
                }
            ])
            ->get();

        // Логирование для отладки
        \Log::info('Unit Inspections query', [
            'workorder_id' => $current_wo->id,
            'code_id' => $code ? $code->id : null,
            'missing_condition_id' => $missingCondition ? $missingCondition->id : null,
            'inspects_unit_count' => $inspectsUnit->count(),
            'inspects_unit_ids' => $inspectsUnit->pluck('id')->toArray(),
            'inspects_unit_details' => $inspectsUnit->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'component_id' => $unit->component_id,
                    'codes_id' => $unit->codes_id,
                    'conditions_id' => $unit->conditions_id,
                    'conditions_loaded' => $unit->relationLoaded('conditions'),
                    'conditions_name' => $unit->conditions ? $unit->conditions->name : 'NULL'
                ];
            })->toArray()
        ]);

        // Получаем Missing компоненты (codes_id = 7 или код "Missing")
        $missingParts = Tdr::where('workorder_id', $current_wo->id)
            ->where(function($query) use ($code) {
                if ($code) {
                    $query->where('codes_id', $code->id);
                } else {
                    // Если код не найден, используем ID = 7 напрямую (стандартный ID для Missing)
                    $query->where('codes_id', 7);
                }
            })
            ->with([
                'component' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number');
                },
                'orderComponent' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number');
                }
            ])
            ->get();

        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $code->id)
            ->where('necessaries_id', $necessary->id)
            ->with(['codes', 'component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        $ordersPartsNew = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $code->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNotNull('order_component_id')
            ->with(['codes', 'orderComponent' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        $prl_parts=Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->with([
                'component' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num');
                },
                'orderComponent' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num');
                }
            ])
            ->get();

        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $necessaries = Necessary::all();
        $unit_conditions = Condition::where('unit', true)->get();
        $component_conditions = Condition::where('unit', false)->get();
        $codes = Code::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->with([
                'component' => function($query) {
                    $query->select('id', 'name', 'part_number', 'ipl_num');
                },
                'conditions'
            ])
            ->get();

         $tdr_proc = TdrProcess::where('ec',1)->get();

        // Transfers existence flag for current workorder
        $hasTransfers = \App\Models\Transfer::where('workorder_id', $current_wo->id)
            ->orWhere('workorder_source', $current_wo->id)
            ->exists();

        return view('admin.tdrs.show', compact(
            'current_wo', 'tdrs', 'units', 'components', 'user', 'customers',
            'manuals', 'builders', 'planes', 'instruction', 'necessary',
            'necessaries', 'unit_conditions', 'component_conditions',
            'codes', 'conditions', 'missingParts', 'ordersParts', 'inspectsUnit',
            'processParts', 'ordersPartsNew','trainings','user_wo', 'manual_id','log_card','woBushing','prl_parts','tdr_proc','hasTransfers',
            'hasMissingParts', 'missingCondition'
        ));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {

        $current_tdr = Tdr::findOrFail($id);

        $manuals = Manual::all();
        $units = Unit::all();

        $workorder = Workorder::where('id', $current_tdr) ->get();


        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();
        $components =Tdr::where('id', 'component_id')
            ->with('codes')
            ->with('component')
            ->with('necessaries')
            ->with('conditions')
            ->get();

//            $current_wo = $current_tdr->workorder->id;


        return view('admin.tdrs.edit', compact('current_tdr', 'workorder', 'units', 'necessaries', 'conditions', 'codes','components','manuals'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Находим запись Tdr по ID
        $tdr = Tdr::findOrFail($id);

        // Валидация входных данных
        $validated = $request->validate([
            'serial_number' => 'nullable|string',
            'assy_serial_number' => 'nullable|string',
            'codes_id' => 'nullable|exists:codes,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'description'=>'nullable|string',
        ]);

        // Проверяем, если выбран необходимый пункт "Order New"
        $necessary = Necessary::where('name', 'Order New')->first();

        if ($necessary && $validated['necessaries_id'] == $necessary->id) {
            $validated['use_process_forms'] = false; // Исправлено присваивание
        }

        // Обновляем запись Tdr
        $tdr->update($validated);

        // Перенаправляем на страницу просмотра с сообщением об успехе
        return redirect()
            ->route('tdrs.show', ['id' => $request->workorder_id])
            ->with('success', 'TDR for Component updated successfully');
    }

    public function prlForm(Request $request, $id){
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $builders = Builder::all();
        $codes = Code::all();
        $necessaries = Necessary::all();

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();

        $manuals = Manual::where('id', $manual_id)
            ->with('builder')
            ->get();

        // Получаем TDR записи с непустым order_component_id
        $ordersPartsNew = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNotNull('order_component_id')
            ->with(['codes', 'orderComponent' => function($query) {
                // Добавляем manual_id в select и загружаем связь manual
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num', 'manual_id')
                      ->with('manual:id,number'); // Загружаем только id и number из Manual
            }])
            ->get();

        // Получаем TDR записи без order_component_id
        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNull('order_component_id')
            ->with(['codes', 'component' => function($query) {
                // Добавляем manual_id в select и загружаем связь manual
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num', 'manual_id')
                      ->with('manual:id,number'); // Загружаем только id и number из Manual
            }])
            ->get();

        // Объединяем коллекции
        $ordersParts = $ordersPartsNew->concat($ordersParts);

        // Добавляем поле manual (номер manual) к каждой TDR записи
        $ordersParts = $ordersParts->map(function($tdr) {
            // Определяем, какой компонент использовать: orderComponent или component
            $component = $tdr->orderComponent ?? $tdr->component;

            // Получаем номер manual из связанного компонента
            if ($component && $component->manual) {
                $tdr->manual = $component->manual->number; // Номер manual (например, "32-11-12")
            } else {
                $tdr->manual = null; // Если manual нет, устанавливаем null
            }

            return $tdr;
        });

        // Сортируем TDR записи: сначала по manual (если есть), потом по IPL номерам компонентов
        $ordersParts = $ordersParts->sort(function($a, $b) {
            // Сначала сравниваем по manual
            $manualA = $a->manual ?? '';
            $manualB = $b->manual ?? '';
            $manualCompare = strnatcasecmp($manualA, $manualB);
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            // Если manual одинаковые или оба null, сравниваем по IPL номерам
            $componentA = $a->orderComponent ?? $a->component;
            $componentB = $b->orderComponent ?? $b->component;

            // Используем assy_ipl_num если есть, иначе ipl_num
            $iplA = ($componentA && isset($componentA->assy_ipl_num) && $componentA->assy_ipl_num !== null && $componentA->assy_ipl_num !== '')
                ? $componentA->assy_ipl_num
                : ($componentA->ipl_num ?? '');
            $iplB = ($componentB && isset($componentB->assy_ipl_num) && $componentB->assy_ipl_num !== null && $componentB->assy_ipl_num !== '')
                ? $componentB->assy_ipl_num
                : ($componentB->ipl_num ?? '');

            // Разбиваем IPL номер на части (например, "1-65" -> ["1", "65"])
            $aParts = explode('-', $iplA);
            $bParts = explode('-', $iplB);

            // Сравниваем первую часть (до -)
            $aFirst = (int)($aParts[0] ?? 0);
            $bFirst = (int)($bParts[0] ?? 0);

            if ($aFirst !== $bFirst) {
                return $aFirst - $bFirst;
            }

            // Если первая часть одинаковая, сравниваем вторую часть (после -)
            $aSecond = (int)($aParts[1] ?? 0);
            $bSecond = (int)($bParts[1] ?? 0);

            return $aSecond - $bSecond;
        })->values(); // values() переиндексирует коллекцию после сортировки

        // Собираем все уникальные номера manual из компонентов
        $uniqueManuals = $ordersParts->map(function($tdr) {
            return $tdr->manual ?? null;
        })->filter(function($manual) {
            return $manual !== null && $manual !== '';
        })->unique()->values()->toArray();

        // Определяем, есть ли несколько manual
        $hasMultipleManuals = count($uniqueManuals) > 1;

        // Преобразуем коллекцию в массив для совместимости с paginateComponentsWithEmptyRows
        // НЕ используем toArray(), так как он преобразует Eloquent модели в массивы
        // Используем all(), который возвращает массив объектов
        $ordersPartsArray = $ordersParts->all();

        // Рассчитываем пагинацию с пустыми строками на бэкенде (20 строк на страницу для PRL)
        $componentChunks = $this->paginateComponentsWithEmptyRows($ordersPartsArray, 20);

        return view('admin.tdrs.prlForm', compact('current_wo', 'components','manuals', 'builders', 'codes','necessaries', 'ordersParts', 'componentChunks', 'uniqueManuals', 'hasMultipleManuals'));
    }

    public function ndtForm(Request $request, $id)
    {
        // Загрузка Workorder с необходимыми отношениями
        $current_wo = Workorder::findOrFail($id);

        // Получаем manual_id через отношения
        $manual_id = $current_wo->unit->manual_id;

        // Получаем компоненты для manual
        $components = Component::where('manual_id', $manual_id)->get();

        // Получаем TDR записи с непустым component_id
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->whereNotNull('component_id')
            ->get(['id', 'component_id']); // Выбираем только нужные поля

        // Получаем ID process names одним запросом
        $processNames = ProcessName::whereIn('name', [
            'NDT-1',
            'NDT-4',
            'Eddy Current Test',
            'BNI'
        ])->pluck('id', 'name');

        // Проверяем, что все process names найдены
        if ($processNames->count() !== 4) {
            abort(500, 'Не все Process Names найдены');
        }

        // Извлекаем ID по именам
        $ndt1_name_id = $processNames['NDT-1'];
        $ndt4_name_id = $processNames['NDT-4'];
        $ndt6_name_id = $processNames['Eddy Current Test'];
        $ndt5_name_id = $processNames['BNI'];

        // Получаем manual processes
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Получаем form_number
        $form_number = ProcessName::where('process_sheet_name', 'NDT')
            ->value('form_number');

        // Получаем NDT processes
        $ndt_processes = Process::whereIn('id', $manualProcesses)
            ->whereIn('process_names_id', [
                $ndt1_name_id,
                $ndt4_name_id,
                $ndt5_name_id,
                $ndt6_name_id
            ])
            ->get();

        // Получаем NDT components
        $ndt_components = TdrProcess::whereIn('tdrs_id', $tdrs->pluck('id'))
            ->whereIn('process_names_id', [
                $ndt1_name_id,
                $ndt4_name_id,
                $ndt5_name_id,
                $ndt6_name_id
            ])
            ->with(['tdr', 'processName'])
            ->get();

        return view('admin.tdrs.ndtForm', [
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => $tdrs,
            'manuals' => Manual::where('id', $manual_id)->get(), // Оставлено для совместимости
            'ndt_processes' => $ndt_processes,
            'ndt1_name_id' => $ndt1_name_id,
            'ndt4_name_id' => $ndt4_name_id,
            'ndt5_name_id' => $ndt5_name_id,
            'ndt6_name_id' => $ndt6_name_id,
            'ndt_components' => $ndt_components,
            'form_number' => $form_number
        ]);
    }

    // Не забудьте добавить use League\Csv\Reader; вверху файла!
    public function ndtStd($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual = $current_wo->unit->manuals;

        // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
        $ndtCadCsv = $current_wo->ndtCadCsv;
        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
        }

        // Получаем ID process names для NDT
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

        // Получаем manual processes
        $manualProcesses = ManualProcess::where('manual_id', $manual->id)
            ->pluck('processes_id');

        // Получаем NDT processes
        $ndt_processes = Process::whereIn('id', $manualProcesses)
            ->whereIn('process_names_id', $ndt_ids)
            ->get();

        // Подготовка данных TDR для последующего сопоставления (учитываем совмещённые/нормализованные IPL)
        $tdrItems = Tdr::where('workorder_id', $workorder_id)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num')
            ->get()
            ->map(function ($tdr) {
                return [
                    'ipl_num' => $tdr->component->ipl_num ?? null,
                    'qty' => (int)($tdr->qty ?? 0),
                ];
            })
            ->filter(function ($item) {
                return !empty($item['ipl_num']) && $item['qty'] > 0;
            })
            ->values()
            ->toArray();

        // 2) Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
        // Приоритет: компоненты из текущего manual
        // Это необходимо, т.к. в CSV и TDR могут быть компоненты из других manuals
        $unitsAssyByIpl = [];

        // Получаем все компоненты, сортируя так, чтобы сначала шли компоненты из текущего manual
        $allComponents = Component::select('ipl_num', 'units_assy', 'manual_id')
            ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
            ->get();

        foreach ($allComponents as $component) {
            if ($component->ipl_num) {
                $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                if (!empty($normalizedIpl)) {
                    // Добавляем только если еще нет в мапе
                    // Благодаря сортировке, компоненты из текущего manual будут добавлены первыми
                    if (!isset($unitsAssyByIpl[$normalizedIpl])) {
                        $num = (int)($component->units_assy ?? 1);
                        $unitsAssy = $num > 0 ? $num : 1;
                        $unitsAssyByIpl[$normalizedIpl] = $unitsAssy;
                    }
                }
            }
        }

        // 3) Получаем количество компонентов со статусами Missing, Repair, Order New
        // Создаем мапу excludedQtyByIpl - количество исключенных компонентов по IPL
        $excludedQtyByIpl = [];

        // Получаем ID для Missing, Repair, Order New
        $missingCode = Code::where('name', 'Missing')->first();
        $repairCode = Code::where('name', 'Repair')->first();
        $orderNewNecessary = Necessary::where('name', 'Order New')->first();

        // Получаем TDR записи с этими статусами
        $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num');

        $excludedConditions = [];
        if ($missingCode) {
            $excludedConditions[] = ['codes_id', $missingCode->id];
        }
        if ($repairCode) {
            $excludedConditions[] = ['codes_id', $repairCode->id];
        }
        if ($orderNewNecessary) {
            $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
        }

        if (!empty($excludedConditions)) {
            $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                foreach ($excludedConditions as $condition) {
                    $query->orWhere($condition[0], $condition[1]);
                }
            });

            $excludedTdrs = $excludedTdrQuery->get();
            foreach ($excludedTdrs as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        // Суммируем количество исключенных компонентов
                        if (!isset($excludedQtyByIpl[$normalizedIpl])) {
                            $excludedQtyByIpl[$normalizedIpl] = 0;
                        }
                        $excludedQtyByIpl[$normalizedIpl] += (int)($tdr->qty ?? 0);
                    }
                }
            }
        }

        // Фильтруем компоненты из NdtCadCsv с учетом remaining quantity
        $ndt_components = [];

        // Создаем мапу TDR items по IPL для быстрого поиска (с нормализацией)
        // ВАЖНО: учитываем только TDR записи БЕЗ статусов Missing, Repair, Order New
        $tdrItemsMap = [];
        foreach ($tdrItems as $item) {
            $iplNum = $item['ipl_num'];
            // Нормализуем IPL номер для группировки
            $normalizedIpl = $this->normalizeIplNum($iplNum);
            if (!empty($normalizedIpl)) {
                // Пропускаем компоненты со статусами Missing, Repair, Order New
                // (они уже учтены в excludedQtyByIpl)
                if (isset($excludedQtyByIpl[$normalizedIpl])) {
                    continue;
                }

                if (!isset($tdrItemsMap[$normalizedIpl])) {
                    $tdrItemsMap[$normalizedIpl] = 0;
                }
                $tdrItemsMap[$normalizedIpl] += $item['qty'];
            }
        }

        foreach ($ndtCadCsv->ndt_components as $component) {
            $iplNum = $component['ipl_num'] ?? '';
            if (empty($iplNum)) {
                continue; // Пропускаем компоненты без IPL номера
            }

            // Нормализуем IPL номер для сравнения
            $normalizedIpl = $this->normalizeIplNum($iplNum);

            // Получаем данные для расчета
            $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
            $tdrQty = $tdrItemsMap[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Repair, Order New
            $excludedQty = $excludedQtyByIpl[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Repair, Order New

            // Определяем units_assy: если в CSV есть поле manual (manual->number),
            // ищем компонент в соответствующем manual, иначе используем общую мапу
            $unitsAssy = 1;
            if (!empty($component['manual'])) {
                // Ищем manual по number из CSV (например, "32-11-12")
                $componentManual = Manual::where('number', $component['manual'])->first();
                if ($componentManual) {
                    // Ищем компонент в этом manual
                    $componentRecord = Component::where('manual_id', $componentManual->id)
                        ->where('ipl_num', $iplNum)
                        ->first();
                    if ($componentRecord && $componentRecord->units_assy) {
                        $num = (int)$componentRecord->units_assy;
                        $unitsAssy = $num > 0 ? $num : 1;
                    } else {
                        // Если не найдено в указанном manual, используем общую мапу
                        $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                    }
                } else {
                    // Если manual не найден, используем общую мапу
                    $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                }
            } else {
                // Если поле manual отсутствует, используем общую мапу
                $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
            }

            // Логирование для отладки компонента 1-65
            if (stripos($iplNum, '1-65') !== false) {
                \Log::info('NDT Component 1-65 debug', [
                    'ipl_num' => $iplNum,
                    'normalized_ipl' => $normalizedIpl,
                    'csvQty' => $csvQty,
                    'tdrQty' => $tdrQty,
                    'excludedQty' => $excludedQty,
                    'unitsAssy' => $unitsAssy,
                    'remaining' => $unitsAssy - $excludedQty - $tdrQty,
                    'in_tdr_map' => isset($tdrItemsMap[$normalizedIpl]),
                    'in_excluded_map' => isset($excludedQtyByIpl[$normalizedIpl])
                ]);
            }

            // Логика для NDT (после фильтрации по Missing, Repair, Order New):
            // 1. Вычитаем из unitsAssy количество исключенных компонентов (Missing, Repair, Order New)
            //    excludedQty суммирует все компоненты со статусами Missing, Repair, Order New
            // 2. Затем вычитаем количество компонентов из TDR (без статусов Missing/Repair/Order New)
            // 3. Если результат <= 0, компонент скрывается
            // 4. Если результат > 0, показываем с qty = результат

            // Пример 1: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 0
            // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

            // Пример 2: unitsAssy = 2, excludedQty = 1 (Repair), tdrQty = 0
            // remaining = 2 - 1 - 0 = 1 → показываем с qty = 1

            // Пример 3: unitsAssy = 2, excludedQty = 1 (Order New), tdrQty = 0
            // remaining = 2 - 1 - 0 = 1 → показываем с qty = 1

            // Пример 4: unitsAssy = 4, excludedQty = 1 (Missing) + 1 (Repair) = 2, tdrQty = 0
            // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

            // Пример 5: unitsAssy = 4, excludedQty = 0, tdrQty = 4
            // remaining = 4 - 0 - 4 = 0 → скрываем

            // Пример 6: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 1
            // remaining = 4 - 2 - 1 = 1 → показываем с qty = 1

            $remaining = $unitsAssy - $excludedQty - $tdrQty;

            // Если результат <= 0, компонент скрывается
            if ($remaining <= 0) {
                continue;
            }

            // Если результат > 0, показываем с qty = remaining
            $displayQty = $remaining;

            // Преобразуем в объект для совместимости с существующим кодом
            $componentObj = new \stdClass();
            $componentObj->ipl_num = $iplNum;
            $componentObj->part_number = $component['part_number'] ?? '';
            $componentObj->name = $component['description'] ?? '';
            // Для NDT показываем количество из CSV (csvQty), т.к. компонент не найден в TDR
            $componentObj->qty = $displayQty;
            $componentObj->process_name = $component['process'] ?? '1';
            // Добавляем поле manual, если оно есть
            $componentObj->manual = $component['manual'] ?? null;

            $ndt_components[] = $componentObj;
        }

        // Сортируем NDT компоненты: сначала по manual (если есть), потом по ipl_num
        usort($ndt_components, function($a, $b) {
            // Сначала сравниваем по manual
            $manualA = $a->manual ?? '';
            $manualB = $b->manual ?? '';
            $manualCompare = strnatcasecmp($manualA, $manualB);
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            // Если manual одинаковые, сравниваем по ipl_num
            $aParts = explode('-', $a->ipl_num ?? '');
            $bParts = explode('-', $b->ipl_num ?? '');

            // Сравниваем первую часть (до -)
            $aFirst = (int)($aParts[0] ?? 0);
            $bFirst = (int)($bParts[0] ?? 0);

            if ($aFirst !== $bFirst) {
                return $aFirst - $bFirst;
            }

            // Если первая часть одинаковая, сравниваем вторую часть (после -)
            $aSecond = (int)($aParts[1] ?? 0);
            $bSecond = (int)($bParts[1] ?? 0);

            return $aSecond - $bSecond;
        });

        $form_number = 'NDT-STD';

        // Рассчитываем пагинацию с пустыми строками на бэкенде
        $componentChunks = $this->paginateComponentsWithEmptyRows($ndt_components, 16);

        return view('admin.tdrs.ndtFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'ndt_components' => $ndt_components,
                'ndt_processes' => $ndt_processes,
                'form_number' => $form_number,
                'manuals' => [$manual], // Для совместимости с существующим кодом
                'componentChunks' => $componentChunks,
            ] + $ndt_ids); // Добавляем ID процессов NDT
    }

    public function cadStd($workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Автозагрузка Stress компонентов из Manual CSV при их отсутствии
//            $stressEmpty = empty($ndtCadCsv->stress_components)
//                || (is_array($ndtCadCsv->stress_components) && count($ndtCadCsv->stress_components) === 0);

//            if ($stressEmpty) {
//                \Log::info('Stress components are empty. Attempting auto-load from Manual CSV', [
//                    'workorder_id' => $workorder_id,
//                    'before_count' => is_array($ndtCadCsv->stress_components) ? count($ndtCadCsv->stress_components) : 0,
//                ]);
//                $ndtCadCsv = NdtCadCsv::loadComponentsFromManual($workorder_id, $ndtCadCsv);
//                \Log::info('Auto-load completed', [
//                    'after_count' => is_array($ndtCadCsv->stress_components) ? count($ndtCadCsv->stress_components) : 0,
//                ]);
//            }

            // Получаем ID process names для CAD
            $processNames = ProcessName::whereIn('name', ['Cad plate'])->pluck('id', 'name');

            if (!isset($processNames['Cad plate'])) {
                throw new \RuntimeException('CAD process name not found');
            }

            // Извлекаем ID по именам
            $cad_ids = [
                'cad_name_id' => $processNames['Cad plate']
            ];

            // Получаем manual processes
            $manualProcesses = ManualProcess::where('manual_id', $manual->id)
                ->pluck('processes_id');

            // Получаем CAD processes
            $cad_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $cad_ids)
                ->get();

            // Получаем существующие IPL номера
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            // Получаем все процессы для данного manual и process_name
            $validProcesses = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $cad_ids['cad_name_id'])
                ->pluck('process')
                ->toArray();

            // Подготовка данных TDR и units_assy для расчёта остатков (CAD)
            $tdrItemsCad = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num')
                ->get()
                ->map(function ($tdr) {
                    return [
                        'ipl_num' => $tdr->component->ipl_num ?? null,
                        'qty' => (int)($tdr->qty ?? 0),
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['ipl_num']) && $item['qty'] > 0;
                })
                ->values()
                ->toArray();

            // Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
            // Приоритет: компоненты из текущего manual
            // Это необходимо, т.к. в CSV и TDR могут быть компоненты из других manuals
            $unitsAssyByIplCad = [];

            // Получаем все компоненты, сортируя так, чтобы сначала шли компоненты из текущего manual
            $allComponentsCad = Component::select('ipl_num', 'units_assy', 'manual_id')
                ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
                ->get();

            foreach ($allComponentsCad as $component) {
                if ($component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        // Добавляем только если еще нет в мапе
                        // Благодаря сортировке, компоненты из текущего manual будут добавлены первыми
                        if (!isset($unitsAssyByIplCad[$normalizedIpl])) {
                            $num = (int)($component->units_assy ?? 1);
                            $unitsAssy = $num > 0 ? $num : 1;
                            $unitsAssyByIplCad[$normalizedIpl] = $unitsAssy;
                        }
                    }
                }
            }

            // Получаем количество компонентов со статусами Missing, Repair, Order New
            // Создаем мапу excludedQtyByIplCad - количество исключенных компонентов по IPL
            $excludedQtyByIplCad = [];

            // Получаем ID для Missing, Repair, Order New
            // Missing - в таблице codes
            $missingCode = Code::where('name', 'Missing')->first();

            // Repair - в таблице necessaries (ID = 1)
            $repairNecessary = Necessary::find(1);
            if (!$repairNecessary || stripos($repairNecessary->name, 'repair') === false) {
                // Пробуем найти по имени
                $repairNecessary = Necessary::where('name', 'Repair')->first();
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'REPAIR')->first();
                }
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'repair')->first();
                }
            }

            // Order New - в таблице necessaries (ID = 2)
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                // Пробуем найти по имени
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            \Log::info('CAD Filtering - Codes and Necessaries', [
                'missing_code_id' => $missingCode ? $missingCode->id : null,
                'missing_code_name' => $missingCode ? $missingCode->name : null,
                'repair_necessary_id' => $repairNecessary ? $repairNecessary->id : null,
                'repair_necessary_name' => $repairNecessary ? $repairNecessary->name : null,
                'order_new_necessary_id' => $orderNewNecessary ? $orderNewNecessary->id : null,
                'order_new_necessary_name' => $orderNewNecessary ? $orderNewNecessary->name : null,
            ]);

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairNecessary) {
                $excludedConditions[] = ['necessaries_id', $repairNecessary->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            \Log::info('CAD Filtering - Excluded conditions', [
                'conditions_count' => count($excludedConditions),
                'conditions' => $excludedConditions
            ]);

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                \Log::info('CAD Filtering - Excluded TDRs found', [
                    'count' => $excludedTdrs->count(),
                    'tdrs' => $excludedTdrs->map(function($tdr) {
                        $code = $tdr->codes_id ? Code::find($tdr->codes_id) : null;
                        $necessary = $tdr->necessaries_id ? Necessary::find($tdr->necessaries_id) : null;
                        return [
                            'id' => $tdr->id,
                            'ipl_num' => $tdr->component->ipl_num ?? null,
                            'codes_id' => $tdr->codes_id,
                            'code_name' => $code ? $code->name : null,
                            'necessaries_id' => $tdr->necessaries_id,
                            'necessary_name' => $necessary ? $necessary->name : null,
                            'qty' => $tdr->qty ?? 0,
                        ];
                    })->toArray()
                ]);

                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            // Суммируем количество исключенных компонентов
                            if (!isset($excludedQtyByIplCad[$normalizedIpl])) {
                                $excludedQtyByIplCad[$normalizedIpl] = 0;
                            }
                            $excludedQtyByIplCad[$normalizedIpl] += (int)($tdr->qty ?? 0);
                        }
                    }
                }

                \Log::info('CAD Filtering - Excluded QTY by IPL', [
                    'excluded_qty_count' => count($excludedQtyByIplCad),
                    'excluded_qty_by_ipl' => $excludedQtyByIplCad
                ]);
            } else {
                \Log::warning('CAD Filtering - No excluded conditions found!', [
                    'missing_code' => $missingCode ? $missingCode->id : null,
                    'repair_necessary' => $repairNecessary ? $repairNecessary->id : null,
                    'order_new' => $orderNewNecessary ? $orderNewNecessary->id : null,
                ]);
            }

            // Создаем мапу TDR items по IPL для быстрого поиска (с нормализацией)
            // ВАЖНО: учитываем только TDR записи БЕЗ статусов Missing, Repair, Order New
            $tdrItemsMapCad = [];
            foreach ($tdrItemsCad as $item) {
                $iplNum = $item['ipl_num'];
                // Нормализуем IPL номер для группировки
                $normalizedIpl = $this->normalizeIplNum($iplNum);
                if (!empty($normalizedIpl)) {
                    // Пропускаем компоненты со статусами Missing, Repair, Order New
                    // (они уже учтены в excludedQtyByIplCad)
                    if (isset($excludedQtyByIplCad[$normalizedIpl])) {
                        continue;
                    }

                    if (!isset($tdrItemsMapCad[$normalizedIpl])) {
                        $tdrItemsMapCad[$normalizedIpl] = 0;
                    }
                    $tdrItemsMapCad[$normalizedIpl] += $item['qty'];
                }
            }

            // Фильтруем компоненты из NdtCadCsv
            $cad_components = [];
            foreach ($ndtCadCsv->cad_components as $component) {
                $itemNo = $component['ipl_num'];
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Логируем компоненты, которые проходят фильтрацию (для отладки)
                if (stripos($itemNo, '5-90') !== false) {
                    \Log::info('CAD Filtering - Component NOT excluded (5-90 variant)', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl,
                        'in_excluded_list' => isset($excludedQtyByIplCad[$normalizedIpl]),
                        'excluded_qty' => $excludedQtyByIplCad[$normalizedIpl] ?? 0,
                        'excluded_ipls' => array_keys($excludedQtyByIplCad)
                    ]);
                }

                // Проверяем и создаем процесс, если его нет
                if (!empty($processName)) {
                    // Проверяем существование процесса
                    $process = Process::where('process', $processName)
                        ->where('process_names_id', $cad_ids['cad_name_id'])
                        ->first();

                    if (!$process) {
                        // Создаем новый процесс
                        $process = Process::create([
                            'process' => $processName,
                            'process_names_id' => $cad_ids['cad_name_id']
                        ]);
                        \Log::info('Created new process:', ['process' => $processName]);
                    }

                    // Проверяем привязку к manual
                    $manualProcess = ManualProcess::where('manual_id', $manual->id)
                        ->where('processes_id', $process->id)
                        ->first();

                    if (!$manualProcess) {
                        // Создаем привязку к manual
                        ManualProcess::create([
                            'manual_id' => $manual->id,
                            'processes_id' => $process->id
                        ]);
                        \Log::info('Created manual-process binding:', [
                            'manual_id' => $manual->id,
                            'process_id' => $process->id
                        ]);
                    }

                    // Обновляем список валидных процессов
                    $validProcesses[] = $processName;
                }

                if (!in_array($processName, $validProcesses)) {
                    \Log::warning('Invalid process found in ModCsv:', [
                        'process' => $processName,
                        'item_no' => $itemNo,
                        'valid_processes' => $validProcesses
                    ]);
                    continue;
                }

                // Получаем данные для расчета
                $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMapCad[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Repair, Order New
                $excludedQty = $excludedQtyByIplCad[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Repair, Order New

                // Определяем units_assy: если в CSV есть поле manual (manual->number),
                // ищем компонент в соответствующем manual, иначе используем общую мапу
                $unitsAssy = 1;
                if (!empty($component['manual'])) {
                    // Ищем manual по number из CSV (например, "32-11-12")
                    $componentManual = Manual::where('number', $component['manual'])->first();
                    if ($componentManual) {
                        // Ищем компонент в этом manual
                        $componentRecord = Component::where('manual_id', $componentManual->id)
                            ->where('ipl_num', $itemNo)
                            ->first();
                        if ($componentRecord && $componentRecord->units_assy) {
                            $num = (int)$componentRecord->units_assy;
                            $unitsAssy = $num > 0 ? $num : 1;
                        } else {
                            // Если не найдено в указанном manual, используем общую мапу
                            $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                        }
                    } else {
                        // Если manual не найден, используем общую мапу
                        $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                    }
                } else {
                    // Если поле manual отсутствует, используем общую мапу
                    $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                }

                // Логика для CAD (после фильтрации по Missing, Repair, Order New):
                // 1. Вычитаем из unitsAssy количество исключенных компонентов (Missing, Repair, Order New)
                // 2. Затем вычитаем количество компонентов из TDR (без статусов Missing/Repair/Order New)
                // 3. Если результат <= 0, компонент скрывается
                // 4. Если результат > 0, показываем с qty = результат

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 0
                // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

                // Пример: unitsAssy = 4, excludedQty = 0, tdrQty = 4
                // remaining = 4 - 0 - 4 = 0 → скрываем

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 1
                // remaining = 4 - 2 - 1 = 1 → показываем с qty = 1

                $remaining = $unitsAssy - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                // Если результат > 0, показываем с qty = remaining
                $displayQty = $remaining;

                // Пропускаем компоненты с qty <= 0
                if ($displayQty <= 0) {
                    continue;
                }

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = $displayQty;
                $componentObj->process_name = $processName;
                // Добавляем поле manual, если оно есть
                $componentObj->manual = $component['manual'] ?? null;

                $cad_components[] = $componentObj;
            }

            // Сортируем CAD компоненты: сначала по manual (если есть), потом по ipl_num
            usort($cad_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            $form_number = 'CAD-STD';

            // Обновляем список процессов после возможного добавления новых
            $cad_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $cad_ids)
                ->get();

            // Рассчитываем общее количество деталей на основе отфильтрованных компонентов
            $cadSum = $this->calcCadSumsFromComponents($cad_components);

            // Рассчитываем пагинацию с пустыми строками на бэкенде
            $componentChunks = $this->paginateComponentsWithEmptyRows($cad_components, 18);

            return view('admin.tdrs.cadFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'cad_components' => $cad_components,
                    'cad_processes' => $cad_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'componentChunks' => $componentChunks,
                    'process_name' => ProcessName::where('name', 'Cad plate')->first(),
                    'cadSum' => $cadSum,
                ] + $cad_ids);

        } catch (\Exception $e) {
            \Log::error('Error in CAD processing:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function paintStd($workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Получаем ID process names для Paint (ID = 25)
            $paintProcessName = ProcessName::find(25);

            if (!$paintProcessName) {
                throw new \RuntimeException('Paint process name not found (ID 25)');
            }

            // Извлекаем ID по именам
            $paint_ids = [
                'paint_name_id' => 25
            ];

            // Получаем manual processes через связь с processes
            $paint_processes = ManualProcess::where('manual_id', $manual->id)
                ->whereHas('process', function($query) use ($paint_ids) {
                    $query->where('process_names_id', $paint_ids['paint_name_id']);
                })
                ->with('process')
                ->get();

            // Получаем IPL номера компонентов, которые должны быть исключены из paintFormStd
            // Исключаем компоненты, присутствующие в TDR со статусами: Missing, Repair, Order New
            $excludedIplNumsPaint = [];

            // Получаем ID для Missing, Repair, Order New
            $missingCode = Code::where('name', 'Missing')->first();
            $repairCode = Code::where('name', 'Repair')->first();
            $orderNewNecessary = Necessary::where('name', 'Order New')->first();

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairCode) {
                $excludedConditions[] = ['codes_id', $repairCode->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNumsPaint[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем paint компоненты из NdtCadCsv
            // Примечание: QTY берется напрямую из CSV, все компоненты включаются без фильтрации
            $paint_components = collect($ndtCadCsv->paint_components ?? [])
                ->filter(function ($component) use ($excludedIplNumsPaint) {
                    $iplNum = $component['ipl_num'] ?? '';
                    // Нормализуем IPL номер для сравнения
                    $normalizedIpl = $this->normalizeIplNum($iplNum);
                    // Исключаем компоненты, присутствующие в TDR со статусами Missing, Repair, Order New
                    return !isset($excludedIplNumsPaint[$normalizedIpl]);
                })
                ->map(function ($component) use ($paint_processes) {
                $process = $paint_processes->first(function ($p) use ($component) {
                    return $p->process->id == $component['process'];
                });

                $obj = new \stdClass();
                $obj->ipl_num = $component['ipl_num'] ?? '';
                $obj->part_number = $component['part_number'] ?? '';
                $obj->name = $component['description'] ?? '';
                $obj->process_name = $process ? $process->process->name :  $component['process'];
                // Количество в форме = значение QTY из CSV
                $obj->qty = (int)($component['qty'] ?? 1);
                // Добавляем поле manual, если оно есть
                $obj->manual = $component['manual'] ?? null;

                return $obj;
            })->toArray();

            // Сортируем Paint компоненты: сначала по manual (если есть), потом по ipl_num
            usort($paint_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            // Генерируем номер формы
            $form_number = '014';

            // Рассчитываем общее количество деталей
            $paintSum = $this->calcPaintSums($workorder_id);

            // Рассчитываем пагинацию с пустыми строками на бэкенде
            $componentChunks = $this->paginateComponentsWithEmptyRows($paint_components, 19);

            return view('admin.tdrs.paintFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'paint_components' => $paint_components,
                    'paint_processes' => $paint_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => $paintProcessName,
                    'paintSum' => $paintSum,
                    'componentChunks' => $componentChunks,
                ] + $paint_ids);

        } catch (\Exception $e) {
            \Log::error('Error in Paint processing:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function stressStd($workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Получаем ID process names для Stress (Bake Stress Realive)
            $processNames = ProcessName::where('id', 3)->pluck('id', 'name');

            if (!$processNames->count()) {
                throw new \RuntimeException('Stress process name not found');
            }

            // Извлекаем ID по именам
            $stress_ids = [
                'stress_name_id' => 3 // Bake (Stress Realive)
            ];

            // Получаем manual processes
            $manualProcesses = ManualProcess::where('manual_id', $manual->id)
                ->pluck('processes_id');

            // Получаем Stress processes
            $stress_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $stress_ids)
                ->get();

            // Получаем существующие IPL номера
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            // Получаем все процессы для данного manual и process_name
            $validProcesses = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $stress_ids['stress_name_id'])
                ->pluck('process')
                ->toArray();

            // Подготовка данных TDR и units_assy для расчёта остатков (STRESS)
            $tdrItemsStress = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num')
                ->get()
                ->map(function ($tdr) {
                    return [
                        'ipl_num' => $tdr->component->ipl_num ?? null,
                        'qty' => (int)($tdr->qty ?? 0),
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['ipl_num']) && $item['qty'] > 0;
                })
                ->values()
                ->toArray();

            // Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
            // Приоритет: компоненты из текущего manual
            // Это необходимо, т.к. в CSV и TDR могут быть компоненты из других manuals
            $unitsAssyByIplStress = [];

            // Получаем все компоненты, сортируя так, чтобы сначала шли компоненты из текущего manual
            $allComponentsStress = Component::select('ipl_num', 'units_assy', 'manual_id')
                ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
                ->get();

            foreach ($allComponentsStress as $component) {
                if ($component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        // Добавляем только если еще нет в мапе
                        // Благодаря сортировке, компоненты из текущего manual будут добавлены первыми
                        if (!isset($unitsAssyByIplStress[$normalizedIpl])) {
                            $num = (int)($component->units_assy ?? 1);
                            $unitsAssy = $num > 0 ? $num : 1;
                            $unitsAssyByIplStress[$normalizedIpl] = $unitsAssy;
                        }
                    }
                }
            }

            // Получаем количество компонентов со статусами Missing, Order New
            // Создаем мапу excludedQtyByIplStress - количество исключенных компонентов по IPL
            // ВАЖНО: Repair НЕ исключаем для Stress
            $excludedQtyByIplStress = [];

            // Получаем ID для Missing и Order New (Repair НЕ исключаем)
            $missingCode = Code::where('name', 'Missing')->first();
            $orderNewNecessary = Necessary::where('name', 'Order New')->first();

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            // Repair НЕ добавляем в исключения для Stress
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                \Log::info('Stress Filtering - Excluded TDRs found', [
                    'count' => $excludedTdrs->count(),
                    'tdrs' => $excludedTdrs->map(function($tdr) {
                        $code = $tdr->codes_id ? Code::find($tdr->codes_id) : null;
                        $necessary = $tdr->necessaries_id ? Necessary::find($tdr->necessaries_id) : null;
                        return [
                            'id' => $tdr->id,
                            'ipl_num' => $tdr->component->ipl_num ?? null,
                            'codes_id' => $tdr->codes_id,
                            'code_name' => $code ? $code->name : null,
                            'necessaries_id' => $tdr->necessaries_id,
                            'necessary_name' => $necessary ? $necessary->name : null,
                            'qty' => $tdr->qty ?? 0,
                        ];
                    })->toArray()
                ]);

                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            // Суммируем количество исключенных компонентов
                            if (!isset($excludedQtyByIplStress[$normalizedIpl])) {
                                $excludedQtyByIplStress[$normalizedIpl] = 0;
                            }
                            $excludedQtyByIplStress[$normalizedIpl] += (int)($tdr->qty ?? 0);
                        }
                    }
                }

                \Log::info('Stress Filtering - Excluded QTY by IPL', [
                    'excluded_qty_count' => count($excludedQtyByIplStress),
                    'excluded_qty_by_ipl' => $excludedQtyByIplStress
                ]);
            }

            // Создаем мапу TDR items по IPL для быстрого поиска (с нормализацией)
            // ВАЖНО: учитываем только TDR записи БЕЗ статусов Missing, Order New
            $tdrItemsMapStress = [];
            foreach ($tdrItemsStress as $item) {
                $iplNum = $item['ipl_num'];
                // Нормализуем IPL номер для группировки
                $normalizedIpl = $this->normalizeIplNum($iplNum);
                if (!empty($normalizedIpl)) {
                    // Пропускаем компоненты со статусами Missing, Order New
                    // (они уже учтены в excludedQtyByIplStress)
                    if (isset($excludedQtyByIplStress[$normalizedIpl])) {
                        continue;
                    }

                    if (!isset($tdrItemsMapStress[$normalizedIpl])) {
                        $tdrItemsMapStress[$normalizedIpl] = 0;
                    }
                    $tdrItemsMapStress[$normalizedIpl] += $item['qty'];
                }
            }

            // Фильтруем компоненты из NdtCadCsv
            $stress_components = [];
            foreach ($ndtCadCsv->stress_components as $component) {
                $itemNo = $component['ipl_num'];
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Проверяем и создаем процесс, если его нет
                if (!empty($processName)) {
                    // Проверяем существование процесса
                    $process = Process::where('process', $processName)
                        ->where('process_names_id', $stress_ids['stress_name_id'])
                        ->first();

                    if (!$process) {
                        // Создаем новый процесс
                        $process = Process::create([
                            'process' => $processName,
                            'process_names_id' => $stress_ids['stress_name_id']
                        ]);
                        \Log::info('Created new stress process:', ['process' => $processName]);
                    }

                    // Проверяем привязку к manual
                    $manualProcess = ManualProcess::where('manual_id', $manual->id)
                        ->where('processes_id', $process->id)
                        ->first();

                    if (!$manualProcess) {
                        // Создаем привязку к manual
                        ManualProcess::create([
                            'manual_id' => $manual->id,
                            'processes_id' => $process->id
                        ]);
                        \Log::info('Created manual-stress process binding:', [
                            'manual_id' => $manual->id,
                            'process_id' => $process->id
                        ]);
                    }

                    // Обновляем список валидных процессов
                    $validProcesses[] = $processName;
                }

                if (!in_array($processName, $validProcesses)) {
                    \Log::warning('Invalid stress process found in ModCsv:', [
                        'process' => $processName,
                        'item_no' => $itemNo,
                        'valid_processes' => $validProcesses
                    ]);
                    continue;
                }

                // Получаем данные для расчета
                $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMapStress[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Order New
                $excludedQty = $excludedQtyByIplStress[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Order New

                // Определяем units_assy: если в CSV есть поле manual (manual->number),
                // ищем компонент в соответствующем manual, иначе используем общую мапу
                $unitsAssy = 1;
                if (!empty($component['manual'])) {
                    // Ищем manual по number из CSV (например, "32-11-12")
                    $componentManual = Manual::where('number', $component['manual'])->first();
                    if ($componentManual) {
                        // Ищем компонент в этом manual
                        $componentRecord = Component::where('manual_id', $componentManual->id)
                            ->where('ipl_num', $itemNo)
                            ->first();
                        if ($componentRecord && $componentRecord->units_assy) {
                            $num = (int)$componentRecord->units_assy;
                            $unitsAssy = $num > 0 ? $num : 1;
                        } else {
                            // Если не найдено в указанном manual, используем общую мапу
                            $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                        }
                    } else {
                        // Если manual не найден, используем общую мапу
                        $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                    }
                } else {
                    // Если поле manual отсутствует, используем общую мапу
                    $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                }

                // Логика для Stress (после фильтрации по Missing, Order New, БЕЗ Repair):
                // 1. Вычитаем из unitsAssy количество исключенных компонентов (Missing, Order New)
                // 2. Затем вычитаем количество компонентов из TDR (без статусов Missing/Order New)
                // 3. Если результат <= 0, компонент скрывается
                // 4. Если результат > 0, показываем с qty = результат

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 0
                // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

                // Пример: unitsAssy = 4, excludedQty = 0, tdrQty = 4
                // remaining = 4 - 0 - 4 = 0 → скрываем

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 1
                // remaining = 4 - 2 - 1 = 1 → показываем с qty = 1

                $remaining = $unitsAssy - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                // Если результат > 0, показываем с qty = remaining
                $displayQty = $remaining;

                // Пропускаем компоненты с qty <= 0
                if ($displayQty <= 0) {
                    continue;
                }

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = $displayQty;
                $componentObj->process_name = $processName;
                // Добавляем поле manual, если оно есть
                $componentObj->manual = $component['manual'] ?? null;

                $stress_components[] = $componentObj;
            }

            // Сортируем Stress компоненты: сначала по manual (если есть), потом по ipl_num
            usort($stress_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            $form_number = 'STRESS-STD';

            // Обновляем список процессов после возможного добавления новых
            $stress_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $stress_ids)
                ->get();

            // Рассчитываем общее количество деталей
            $stressSum = $this->calcStressSums($workorder_id);

            // Рассчитываем пагинацию с пустыми строками на бэкенде
            $componentChunks = $this->paginateComponentsWithEmptyRows($stress_components, 18);

            return view('admin.tdrs.stressFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'stress_components' => $stress_components,
                    'stress_processes' => $stress_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => ProcessName::where('id', 3)->first(),
                    'stressSum' => $stressSum,
                    'componentChunks' => $componentChunks,
                ] + $stress_ids);

        } catch (\Exception $e) {
            \Log::error('Error in Stress processing:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Находит индекс колонки по возможным названиям
     */

    public function specProcessForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Получаем NDT суммы
        $ndtSums = $this->calcNdtSums($id);
        $cadSum = $this->calcCadSums($id);

        $proNameId = ProcessName::where('name', 'Cad plate')->value('id');

        $cadSum_ex = \App\Models\ExtraProcess::where('workorder_id', $current_wo->id)
            ->whereRaw("JSON_SEARCH(processes, 'one', CAST(? AS CHAR), NULL, '$[*].process_name_id') IS NOT NULL",[(string)$proNameId]
            )->sum('qty');

        $tdr_ws = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('component')
            ->get();
        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $processNames = ProcessName::where(function ($query) {
            $query->where('name', 'NOT LIKE', '%NDT%')
//                ->where('name', 'NOT LIKE', '%Paint%');
                ->where('name', 'NOT LIKE', 'EC');
        })->get();

        // Получаем Tdr, где use_process_form = true, с предварительной загрузкой TdrProcess
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with(['tdrProcesses' => function($query) {
                $query->orderBy('sort_order')->with('processName');
            }]) // Предварительная загрузка TdrProcess с сортировкой и processName
            ->with('component')
            ->get();

        // Получаем ID процессов с именем 'EC' для исключения из подсчёта number_line
        $ecProcessIds = ProcessName::where('name', 'LIKE', 'EC')->pluck('id');

        // Создаем коллекцию для результата
        $result = collect();

        // Обрабатываем каждый Tdr
        foreach ($tdrs as $tdr) {
            // Получаем связанные процессы (processName уже загружен)
            $groupedProcesses = $tdr->tdrProcesses;

            // Счётчик для number_line (не учитывает процессы с именем 'EC')
            $lineNumber = 0;

            // Обрабатываем каждый процесс
            $groupedProcesses->each(function ($process) use (&$result, &$lineNumber, $tdr, $ecProcessIds) {
                // Проверяем, является ли процесс процессом с именем 'EC'
                $isEcProcess = $ecProcessIds->contains($process->process_names_id);

                // Увеличиваем счётчик только для процессов, не являющихся 'EC'
                if (!$isEcProcess) {
                    $lineNumber++;
                }

                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $isEcProcess ? null : $lineNumber, // null для EC процессов, иначе номер строки
                    'ec' => $process->ec, // Добавляем поле EC
                ]);
            });
        }
// Получаем все ID процессов, где name содержит 'NDT'
        $ndtIds = ProcessName::where('name', 'LIKE', '%NDT%')->pluck('id');

// Фильтруем коллекцию processes, оставляя только те записи, где process_name_id есть в $ndtIds
        $ndt_processes = $result->filter(function ($item) use ($ndtIds) {
            return $ndtIds->contains($item['process_name_id']);
        })->map(function ($item) {
            // Преобразуем каждую запись в нужный формат
            return [
                'tdrs_id' => $item['tdrs_id'],
                'number_line' => $item['number_line'],
            ];
        });
        // Передаем данные в представление
        return view('admin.tdrs.specProcessForm', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
        ], compact('tdrs', 'tdr_ws','processNames','cadSum_ex'));
    }

    public function specProcessFormEmp(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Получаем NDT суммы
        $ndtSums = $this->calcNdtSums($id);
        $cadSum = $this->calcCadSums($id);

        $proNameId = ProcessName::where('name', 'Cad plate')->value('id');

        $cadSum_ex = \App\Models\ExtraProcess::where('workorder_id', $current_wo->id)
            ->whereRaw("JSON_SEARCH(processes, 'one', CAST(? AS CHAR), NULL, '$[*].process_name_id') IS NOT NULL",[(string)$proNameId]
            )->sum('qty');

        $tdr_ws = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('component')
            ->get();
        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $processNames = ProcessName::where(function ($query) {
            $query->where('name', 'NOT LIKE', '%NDT%')
//                ->where('name', 'NOT LIKE', '%Paint%');
                ->where('name', 'NOT LIKE', 'EC');
        })->get();

        // Получаем Tdr, где use_process_form = true, с предварительной загрузкой TdrProcess
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with(['tdrProcesses' => function($query) {
                $query->orderBy('sort_order');
            }]) // Предварительная загрузка TdrProcess с сортировкой
            ->with('component')
            ->get();

        // Создаем коллекцию для результата
        $result = collect();

        // Обрабатываем каждый Tdr
        foreach ($tdrs as $tdr) {
            // Получаем связанные процессы
            $groupedProcesses = $tdr->tdrProcesses;

            // Обрабатываем каждый процесс
            $groupedProcesses->each(function ($process, $index) use (&$result, $tdr) {
                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $index + 1, // Номер строки
                ]);
            });
        }
// Получаем все ID процессов, где name содержит 'NDT'
        $ndtIds = ProcessName::where('name', 'LIKE', '%NDT%')->pluck('id');

// Фильтруем коллекцию processes, оставляя только те записи, где process_name_id есть в $ndtIds
        $ndt_processes = $result->filter(function ($item) use ($ndtIds) {
            return $ndtIds->contains($item['process_name_id']);
        })->map(function ($item) {
            // Преобразуем каждую запись в нужный формат
            return [
                'tdrs_id' => $item['tdrs_id'],
                'number_line' => $item['number_line'],
            ];
        });
        // Передаем данные в представление
        return view('admin.tdrs.specProcessFormEmp', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
        ], compact('tdrs', 'tdr_ws','processNames','cadSum_ex'));
    }

    public function logCardForm(Request $request, $id)
    {
//    dd($request, $id);
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);
        // Получаем данные о manual, связанном с этим Workorder
        $manual = $current_wo->unit->manual_id;
        $manual_wo = $current_wo->unit->manuals;
        $builders = Builder::all();
// dd($manual);
        $manuals = Manual::where('id', $manual)
            ->with('builder')
            ->get();

//dd($manuals, $manual);

// Получаем CSV-файл с process_type = 'log'
        $csvMedia = $manual_wo->getMedia('csv_files')->first(function ($media) {
            return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_LOG;
        });

//    dd($csvMedia);

        return view('admin.tdrs.logCardForm', compact('current_wo','manuals', 'builders'));

    }

    public function tdrForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Загружаем необходимые данные для всех записей
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

        // Жадная загрузка данных для tdrs, включая все связи с компонентами, состояниями, необходимостями и кодами
        $current_wo->load('tdrs.component', 'tdrs.conditions', 'tdrs.necessaries', 'tdrs.codes');

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();

        // Массивы для хранения разных типов строк
        $nullComponentConditions = []; // Для строк, где component_id == null
        $groupedByConditions = []; // Для строк, где component_id !== null и necessaries_id == Order New
        $necessaryComponents = []; // Для строк, где component_id !== null и necessaries_id !== Order New

        foreach ($current_wo->tdrs as $tdr) {
            // Пропускаем строки с codes_id == Missing
            if ($tdr->codes_id == $code->id) {
                continue;
            }

            // Строки с component_id == null
            if ($tdr->component_id === null) {
                $conditions = $tdr->conditions; // Получаем данные о состоянии
                if ($conditions) {
                    $description = trim((string) $tdr->description);
                    $conditionString = $conditions->name;
                    if ($description !== '') {
                        $conditionString .= ' ' . $description;
                    }

                    // Добавляем состояние в массив
                    $nullComponentConditions[] = $conditionString;
                }
            } elseif ($tdr->component_id !== null && $tdr->necessaries_id == $necessary->id) {
                // Группируем компоненты по состояниям, если necessaries_id == 2 ('Order New')
                $component = $tdr->component; // Получаем связанные данные о компоненте
                $conditions = $tdr->conditions; // Получаем связанные данные о состоянии
                if ($component && $conditions) {
                    // Формируем строку для компонента
                    if (!empty($tdr->description)) {
                        // Если description не пустой, выводим с описанием
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b>: ( %s)", // Номер компонента и его имя
                            strtoupper($component->ipl_num), // Номер компонента
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs', //Если qty == 1, то пустая строка, иначе добавляем qty и "pcs"
                            strtoupper($component->name), // Имя компонента
                            strtoupper($tdr->description),
                        );
                    } else {
                        // Если description пустой или null, выводим без описания
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b> ", // Номер компонента и его имя
                            strtoupper($component->ipl_num), // Номер компонента
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs', //Если qty == 1, то пустая строка, иначе добавляем qty и "pcs"
                            strtoupper($component->name), // Имя компонента
                        );
                    }

                    // Инициализируем массив для состояния, если он еще не существует
                    if (!isset($groupedByConditions[$conditions->name])) {
                        $groupedByConditions[$conditions->name] = [];
                    }

                    // Получаем последнюю строку в группе
                    $lastKey = count($groupedByConditions[$conditions->name]) - 1;
                    $lastString = $lastKey >= 0 ? $groupedByConditions[$conditions->name][$lastKey] : '';

                    // Проверяем длину строки
                    if (strlen($lastString . ', ' . $componentString) <= 120) {
                        // Если длина не превышает 120 символов, добавляем к последней строке
                        if ($lastKey >= 0) {
                            $groupedByConditions[$conditions->name][$lastKey] .= ', ' . $componentString;
                        } else {
                            $groupedByConditions[$conditions->name][] = $conditions->name . ' (scrap): ' . $componentString;
                        }
                    } else {
                        // Если длина превышает 120 символов, создаем новую строку
                        $groupedByConditions[$conditions->name][] = $conditions->name . ' (scrap): ' . $componentString;
                    }
                }
            } elseif ($tdr->component_id !== null && $tdr->necessaries_id !== $necessary->id) {
                // Для всех остальных компонентов, где necessaries_id != Order New
                $component = $tdr->component; // Получаем данные о компоненте
                $necessaries = $tdr->necessaries; // Получаем данные о необходимости
                $codes = $tdr->codes; // Получаем данные о кодах
                $description = $tdr->description; // Description
                if ($component && $necessaries && $codes) {
                    // Строим строку в нужном формате
                    if (!empty($description)) {
                        // Если description не пустой, выводим с описанием
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ( %s )", // Формат вывода
                            strtoupper($component->ipl_num), // Номер компонента
                            strtoupper($component->name), // Имя компонента
                            strtoupper($necessaries->name), // Название необходимости
                            strtoupper($codes->name), // Название кода
                            strtoupper($description), // Название кода
                        );
                    } else {
                        // Если description пустой или null, выводим без описания
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ", // Формат вывода
                            strtoupper($component->ipl_num), // Номер компонента
                            strtoupper($component->name), // Имя компонента
                            strtoupper($necessaries->name), // Название необходимости
                            strtoupper($codes->name), // Название кода
                        );
                    }
                }
            }

        }

// Объединяем все строки в правильном порядке
        $tdrInspections = [];

// Добавляем строки с component_id == null
        if (!empty($nullComponentConditions)) {
            $tdrInspections = array_merge($tdrInspections, $nullComponentConditions);
        }

// Добавляем группированные строки по состояниям
        foreach ($groupedByConditions as $conditionName => $components) {
            foreach ($components as $componentLine) {
                $tdrInspections[] = $componentLine;
            }
        }

// Добавляем строки с necessaries_id != Order New
        if (!empty($necessaryComponents)) {
            $tdrInspections = array_merge($tdrInspections, $necessaryComponents);
        }

//// Выводим результат
//        foreach ($tdrInspections as $inspection) {
//            echo $inspection . "\n";
//        }

        // Возвращаем данные в представление
        return view('admin.tdrs.tdrForm', compact('current_wo', 'components',
            'necessaries', 'conditions', 'codes', 'tdrInspections'));
    }

    public function wo_Process_Form($id)
    {
        $current_wo = Workorder::findOrFail($id);
        return view('admin.tdrs.wo_ProcessForm', compact('current_wo'));
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Логируем начало метода
        Log::info('Начало удаления записи TDR с ID: ' . $id);

        // Найти запись Tdr по ID
        $tdr = Tdr::findOrFail($id);

        // Запомнить workorder_id и codes_id для дальнейшего использования
        $workorderId = $tdr->workorder_id;
        $tdrCodesId = $tdr->codes_id;

        // Логируем workorder_id
        Log::info('Workorder ID: ' . $workorderId);

        // Удалить связанные записи из tdr_processes
        TdrProcess::where('tdrs_id', $id)->delete();
        Log::info('Удалены связанные процессы для TDR с ID: ' . $id);

        // Определяем component_id для поиска transfers
        $componentId = $tdr->order_component_id ?? $tdr->component_id;

        // Удалить связанные записи из transfers и клонированные TDR в WO-источниках (если есть)
        if ($componentId) {
            // Находим все transfers, связанные с этим TDR
            $transfers = Transfer::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->get();

            $deletedTransfers = 0;
            $deletedClonedTdrs = 0;

            // Код Missing (для управления флагом part_missing в workorders источников)
            $missingCode = Code::where('name', 'Missing')->first();

            foreach ($transfers as $transfer) {
                // Для каждого transfer пытаемся удалить "клонированный" TDR в workorder_source
                if ($transfer->workorder_source) {
                    $cloned = Tdr::where('workorder_id', $transfer->workorder_source)
                        ->where(function ($q) use ($tdr) {
                            // Пытаемся найти запись, максимально похожую на исходный TDR
                            $q->where('component_id', $tdr->component_id)
                                ->where('order_component_id', $tdr->order_component_id)
                                ->where('codes_id', $tdr->codes_id)
                                ->where('conditions_id', $tdr->conditions_id)
                                ->where('necessaries_id', $tdr->necessaries_id)
                                ->where('qty', $tdr->qty)
                                ->where('serial_number', $tdr->serial_number);
                        })
                        ->where('id', '!=', $tdr->id)
                        ->orderByDesc('id') // берём самую "свежую" как вероятный клон
                        ->first();

                    if ($cloned) {
                        $cloned->delete();
                        $deletedClonedTdrs++;
                        Log::info('Удалён клонированный TDR с ID: ' . $cloned->id . ' в WO-источнике: ' . $transfer->workorder_source);

                        // Если это была запись с кодом Missing, возможно нужно обновить part_missing для WO-источника
                        if ($missingCode && $tdr->codes_id === $missingCode->id) {
                            $remainingMissingForSource = Tdr::where('workorder_id', $transfer->workorder_source)
                                ->where('codes_id', $missingCode->id)
                                ->count();

                            if ($remainingMissingForSource === 0) {
                                $sourceWo = Workorder::find($transfer->workorder_source);
                                if ($sourceWo && $sourceWo->part_missing) {
                                    $sourceWo->part_missing = false;
                                    $sourceWo->save();
                                    Log::info('Флаг part_missing для WO-источника ' . $transfer->workorder_source . ' обновлён на false (после удаления клонированного Missing TDR).');
                                }

                            }
                        }
                    }
                }

                $transfer->delete();
                $deletedTransfers++;
            }

            if ($deletedTransfers > 0) {
                Log::info('Удалены связанные transfers для TDR с ID: ' . $id . ' (удалено transfers: ' . $deletedTransfers . ', удалено клонированных TDR: ' . $deletedClonedTdrs . ')');
            }
        }

        // Удалить запись Tdr
        $tdr->delete();
        Log::info('Запись Tdr с ID: ' . $id . ' была удалена.');



        // Найти necessary с именем 'Missing'
        $necessary = Necessary::where('name', 'Order New')->first();
        Log::info('Найден necessary с именем "Order New": ' . ($necessary ? 'Да' : 'Нет'));

        if ($necessary) {
            // Проверить, если это последняя запись с necessaries_id = $necessary->id
            $remainingPartsWithNecessary = Tdr::where('workorder_id', $workorderId)
                ->where('necessaries_id', $necessary->id)
                ->count();
            Log::info('Оставшиеся записи с кодом Order New для workorder_id ' . $workorderId . ': ' .
                $remainingPartsWithNecessary);
            if ($remainingPartsWithNecessary == 0) {
                // Обновляем поле part_missing в workorder
                $workorder = Workorder::find($workorderId);
                if ($workorder && $workorder->new_parts == true) {
                    // Меняем на false, если part_missing равно true
                    $workorder->new_parts = false;
                    $workorder->save();
                    Log::info('Поле new_parts для workorder_id ' . $workorderId . ' обновлено на false');
                } else {
                    Log::info('Поле new_parts для workorder_id ' . $workorderId . ' уже false или workorder не найден.');
                }

            }
        }

        // Найти код с именем 'Missing'
        $code = Code::where('name', 'Missing')->first();
        Log::info('Найден код с именем "Missing": ' . ($code ? 'Да' : 'Нет'));

        // Проверяем, была ли удаляемая запись с кодом Missing
        $wasMissingRecord = $code && $tdrCodesId === $code->id;
        Log::info('Удаляемая запись была с кодом Missing: ' . ($wasMissingRecord ? 'Да' : 'Нет') . ' (codes_id: ' . $tdrCodesId . ')');

        if ($code) {
            // Проверить, если это последняя запись с codes_id = $code->id
            // Запись уже удалена выше, поэтому проверяем оставшиеся
            $remainingPartsWithCodes7 = Tdr::where('workorder_id', $workorderId)
                ->where('codes_id', $code->id)
                ->count();

            Log::info('Оставшиеся записи с кодом Missing для workorder_id ' . $workorderId . ': ' . $remainingPartsWithCodes7);

            // Если это была последняя запись с таким кодом, обновляем поле part_missing в workorder
            if ($remainingPartsWithCodes7 == 0) {
                // Обновляем поле part_missing в workorder
                $workorder = Workorder::find($workorderId);

                if ($workorder && $workorder->part_missing === true) {
                    // Меняем на false, если part_missing равно true
                    $workorder->part_missing = false;
                    $workorder->save();
                    Log::info('Поле part_missing для workorder_id ' . $workorderId . ' обновлено на false');
                } else {
                    Log::info('Поле part_missing для workorder_id ' . $workorderId . ' уже false или workorder не найден.');
                }

                // Удаляем старые пустые записи с missingCondition (созданные до изменений)
                $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
                if ($missingCondition) {
                    $emptyMissingRecords = Tdr::where('workorder_id', $workorderId)
                        ->where('conditions_id', $missingCondition->id)
                        ->whereNull('component_id')
                        ->whereNull('codes_id')
                        ->get();

                    foreach ($emptyMissingRecords as $emptyRecord) {
                        $emptyRecord->delete();
                        Log::info('Удалена старая пустая запись с condition_id ' . $missingCondition->id . ' для workorder_id ' . $workorderId);
                    }
                }
            }
        }

        // Перенаправить с сообщением об успехе
        return redirect()->route('tdrs.show', ['id' => $workorderId])
            ->with('success', 'Запись успешно удалена.');
    }


    /**
     * Расчет сумм NDT из данных CSV для рабочего заказа
     * Использует ту же логику, что и ndtStd: учитывает unitsAssy, excludedQty, tdrQty
     *
     * @param int $workorder_id ID рабочего заказа
     * @return array{total: int, mpi: int, fpi: int} Массив с общими суммами,
     *     MPI и FPI
     */
    private function calcNdtSums(int $workorder_id): array
    {
        // Инициализация счетчиков
        $total = 0;
        $mpi = 0;
        $fpi = 0;

        try {
            // Получение рабочего заказа и связанных данных
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                \Log::error('Manual not found for workorder', ['workorder_id' => $workorder_id]);
                return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
            }

            // Получение данных из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                \Log::info('NdtCadCsv not found for workorder, creating new record', [
                    'workorder_id' => $workorder_id,
                    'workorder_number' => $current_wo->number ?? 'unknown'
                ]);

                // Создаем новую запись NdtCadCsv с автоматической загрузкой из Manual
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);

                if (!$ndtCadCsv) {
                    \Log::warning('Failed to create NdtCadCsv record', ['workorder_id' => $workorder_id]);
                    return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
                }
            }

            \Log::info('Found NdtCadCsv record', [
                'ndt_cad_csv_id' => $ndtCadCsv->id,
                'workorder_id' => $workorder_id
            ]);

            // Получение NDT компонентов из JSON поля
            $ndtComponents = $ndtCadCsv->ndt_components ?? [];

            if (empty($ndtComponents)) {
                \Log::info('No NDT components found in NdtCadCsv', ['workorder_id' => $workorder_id]);
                return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
            }

            // Подготовка данных TDR для последующего сопоставления (та же логика, что в ndtStd)
            $tdrItems = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num')
                ->get()
                ->map(function ($tdr) {
                    return [
                        'ipl_num' => $tdr->component->ipl_num ?? null,
                        'qty' => (int)($tdr->qty ?? 0),
                        'codes_id' => $tdr->codes_id,
                        'necessaries_id' => $tdr->necessaries_id,
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['ipl_num']) && $item['qty'] > 0;
                })
                ->values()
                ->toArray();

            // Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
            $unitsAssyByIpl = [];
            $allComponents = Component::select('ipl_num', 'units_assy', 'manual_id')
                ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
                ->get();

            foreach ($allComponents as $component) {
                if ($component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        if (!isset($unitsAssyByIpl[$normalizedIpl])) {
                            $num = (int)($component->units_assy ?? 1);
                            $unitsAssy = $num > 0 ? $num : 1;
                            $unitsAssyByIpl[$normalizedIpl] = $unitsAssy;
                        }
                    }
                }
            }

            // Получаем количество компонентов со статусами Missing, Repair, Order New
            $excludedQtyByIpl = [];
            $missingCode = Code::where('name', 'Missing')->first();
            $repairCode = Code::where('name', 'Repair')->first();
            $orderNewNecessary = Necessary::where('name', 'Order New')->first();

            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairCode) {
                $excludedConditions[] = ['codes_id', $repairCode->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            if (!isset($excludedQtyByIpl[$normalizedIpl])) {
                                $excludedQtyByIpl[$normalizedIpl] = 0;
                            }
                            $excludedQtyByIpl[$normalizedIpl] += (int)($tdr->qty ?? 0);
                        }
                    }
                }
            }

            // Создаем мапу TDR items по IPL для быстрого поиска (БЕЗ статусов Missing, Repair, Order New)
            $tdrItemsMap = [];
            foreach ($tdrItems as $item) {
                $iplNum = $item['ipl_num'];
                $normalizedIpl = $this->normalizeIplNum($iplNum);
                if (!empty($normalizedIpl)) {
                    // Пропускаем компоненты со статусами Missing, Repair, Order New
                    if (isset($excludedQtyByIpl[$normalizedIpl])) {
                        continue;
                    }

                    if (!isset($tdrItemsMap[$normalizedIpl])) {
                        $tdrItemsMap[$normalizedIpl] = 0;
                    }
                    $tdrItemsMap[$normalizedIpl] += $item['qty'];
                }
            }

            \Log::info('Processing NDT components from ndt_cad_csv table (with filtering)', [
                'workorder_id' => $workorder_id,
                'components_count' => count($ndtComponents),
                'excluded_qty_count' => count($excludedQtyByIpl),
                'tdr_items_count' => count($tdrItemsMap)
            ]);

            // Обработка NDT компонентов из JSON поля (та же логика, что в ndtStd)
            foreach ($ndtComponents as $index => $component) {
                // Проверяем наличие обязательных полей
                if (!isset($component['ipl_num']) || empty($component['ipl_num'])) {
                    continue;
                }

                $iplNum = $component['ipl_num'];
                $normalizedIpl = $this->normalizeIplNum($iplNum);

                // Получаем данные для расчета
                $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMap[$normalizedIpl] ?? 0;
                $excludedQty = $excludedQtyByIpl[$normalizedIpl] ?? 0;

                // Определяем units_assy
                $unitsAssy = 1;
                if (!empty($component['manual'])) {
                    $componentManual = Manual::where('number', $component['manual'])->first();
                    if ($componentManual) {
                        $componentRecord = Component::where('manual_id', $componentManual->id)
                            ->where('ipl_num', $iplNum)
                            ->first();
                        if ($componentRecord && $componentRecord->units_assy) {
                            $num = (int)$componentRecord->units_assy;
                            $unitsAssy = $num > 0 ? $num : 1;
                        } else {
                            $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                        }
                    } else {
                        $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                    }
                } else {
                    $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                }

                // Рассчитываем remaining (та же логика, что в ndtStd)
                $remaining = $unitsAssy - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                $displayQty = $remaining;

                // Получаем процесс для определения MPI/FPI
                $process = $component['process'] ?? self::DEFAULT_PROCESS;

                // Вычисление сумм
                $total += $displayQty;

                if (strpos($process, '1') !== false) {
                    $mpi += $displayQty;
                } else {
                    $fpi += $displayQty;
                }
            }

            \Log::info('NDT sums calculated from ndt_cad_csv table (with filtering)', [
                'workorder_id' => $workorder_id,
                'total' => $total,
                'mpi' => $mpi,
                'fpi' => $fpi
            ]);

            return [
                'total' => $total,
                'mpi' => $mpi,
                'fpi' => $fpi
            ];

        } catch (\Exception $e) {
            \Log::error('Ошибка при обработке NDT компонентов из таблицы ndt_cad_csv:', [
                'workorder_id' => $workorder_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
        }
    }

    private function calcCadSumsFromComponents($cad_components)
    {
        try {
            $totalQty = 0;
            $totalComponents = 0;
            $componentList = [];

            foreach ($cad_components as $component) {
                $qty = (int)($component->qty ?? 1);
                $totalQty += $qty;
                $totalComponents++;
                $componentList[] = [
                    'ipl_num' => $component->ipl_num ?? '',
                    'qty' => $qty
                ];
            }

            \Log::info('CAD calcCadSumsFromComponents - Summary', [
                'total_components' => $totalComponents,
                'total_qty' => $totalQty,
                'components_count' => count($componentList),
                'components' => $componentList,
                'ipl_numbers' => array_column($componentList, 'ipl_num')
            ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];
        } catch (\Exception $e) {
            \Log::error('Error in CAD sums calculation from components:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcCadSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            \Log::info('Starting CAD sums calculation', [
                'workorder_id' => $workorder_id
            ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            \Log::info('Found NdtCadCsv record', [
                'ndt_cad_csv_id' => $ndtCadCsv->id,
                'cad_components_count' => count($ndtCadCsv->cad_components ?? [])
            ]);

            // Получаем CAD компоненты из JSON поля
            $cadComponents = $ndtCadCsv->cad_components ?? [];

            if (empty($cadComponents)) {
                \Log::warning('No CAD components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 1.5. Получаем валидные процессы (упрощенная логика - все процессы из CSV считаются валидными)
            // В cadStd процессы создаются динамически, поэтому в calcCadSums мы просто принимаем все процессы из CSV
            $validProcesses = [];
            foreach ($cadComponents as $component) {
                $processName = $component['process'] ?? '';
                if (!empty($processName) && !in_array($processName, $validProcesses)) {
                    $validProcesses[] = $processName;
                }
            }

            \Log::info('CAD calcCadSums - Valid processes (from CSV)', [
                'valid_processes_count' => count($validProcesses),
                'valid_processes' => $validProcesses
            ]);

            // 2. Получаем ID для Missing, Repair, Order New (та же логика, что в cadStd)
            $missingCode = Code::where('name', 'Missing')->first();
            $repairNecessary = Necessary::find(1);
            if (!$repairNecessary || stripos($repairNecessary->name, 'repair') === false) {
                $repairNecessary = Necessary::where('name', 'Repair')->first();
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'REPAIR')->first();
                }
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'repair')->first();
                }
            }
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            // Получаем TDR записи только с исключаемыми статусами (Missing, Repair, Order New)
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairNecessary) {
                $excludedConditions[] = ['necessaries_id', $repairNecessary->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            // Создаем мапу исключенных IPL номеров (только с Missing/Repair/Order New)
            $excludedIplNums = [];
            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNums[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем все TDR компоненты для создания мапы (для логирования)
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR (только для логирования)
            $tdrIplMap = [];
            foreach ($tdrComponents as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        if (!isset($tdrIplMap[$normalizedIpl])) {
                            $tdrIplMap[$normalizedIpl] = 0;
                        }
                        $tdrIplMap[$normalizedIpl] += (int)($tdr->qty ?? 0);
                    }
                }
            }

            \Log::info('TDR Components (normalized):', [
                'count' => count($tdrIplMap),
                'ipl_numbers' => array_keys($tdrIplMap),
                'excluded_ipl_count' => count($excludedIplNums),
                'excluded_ipls' => array_keys($excludedIplNums)
            ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $skippedByProcess = 0;
            $combinedSkippedCount = 0;

            \Log::info('Starting CAD calculation loop', [
                'total_cad_components' => count($cadComponents),
                'tdr_ipl_count' => count($tdrIplMap)
            ]);

            foreach ($cadComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения (5-90A -> 5-90)
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                \Log::debug('Processing CAD component', [
                    'index' => $index,
                    'item_no' => $itemNo,
                    'normalized_ipl' => $normalizedIpl,
                    'qty' => $qty,
                    'process' => $processName,
                    'component' => $component
                ]);

                // Исключаем компоненты только с статусами Missing, Repair, Order New
                if (!empty($normalizedIpl) && isset($excludedIplNums[$normalizedIpl])) {
                    $skippedCount++;
                    \Log::debug('Skipping component as it has excluded status (Missing/Repair/Order New):', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl
                    ]);
                    continue;
                }

                // Исключаем компоненты с невалидными процессами (та же логика, что в cadStd)
                if (!empty($processName) && !in_array($processName, $validProcesses)) {
                    $skippedByProcess++;
                    \Log::debug('Skipping component as it has invalid process:', [
                        'ipl_num' => $itemNo,
                        'process' => $processName,
                        'valid_processes' => $validProcesses
                    ]);
                    continue;
                }

                // Если нормализованный IPL номер еще не был обработан (используем нормализованный для проверки дубликатов)
                if (!empty($normalizedIpl) && !in_array($normalizedIpl, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $normalizedIpl; // Сохраняем нормализованный IPL
                    \Log::debug('Adding component from NdtCadCsv:', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl,
                        'qty' => $qty
                    ]);
                } else if (!empty($normalizedIpl) && in_array($normalizedIpl, $processedIpls)) {
                    \Log::debug('Skipping duplicate component (normalized):', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl
                    ]);
                }
            }

            \Log::info('CAD calculation loop completed', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'skipped_count' => $skippedCount,
                'combined_skipped_count' => $combinedSkippedCount,
                'processed_ipls_count' => count($processedIpls),
                'processed_ipls' => $processedIpls
            ]);

            \Log::info('CAD calcCadSums - Summary', [
                'total_cad_components_in_csv' => count($cadComponents),
                'excluded_by_status' => $skippedCount,
                'excluded_by_process' => $skippedByProcess,
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'valid_processes_count' => count($validProcesses),
                'valid_processes' => $validProcesses
            ]);

            \Log::info('CAD calculation results:', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'processed_ipls' => $processedIpls
            ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            \Log::error('Error in CAD sums calculation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcStressSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            \Log::info('Starting Stress sums calculation', [
                'workorder_id' => $workorder_id
            ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            \Log::info('Found NdtCadCsv record', [
                'ndt_cad_csv_id' => $ndtCadCsv->id,
                'stress_components_count' => count($ndtCadCsv->stress_components ?? [])
            ]);

            // Получаем Stress компоненты из JSON поля
            $stressComponents = $ndtCadCsv->stress_components ?? [];

            if (empty($stressComponents)) {
                \Log::warning('No Stress components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 2. Получаем ID для Missing, Order New (та же логика, что в stressStd, но Repair НЕ исключаем)
            $missingCode = Code::where('name', 'Missing')->first();
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            // Получаем TDR записи только с исключаемыми статусами (Missing, Order New, но НЕ Repair)
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            // Создаем мапу исключенных IPL номеров (только с Missing/Order New)
            $excludedIplNums = [];
            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNums[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем все TDR компоненты для создания мапы (только для логирования)
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR (только для логирования)
            $tdrIplMap = [];
            foreach ($tdrComponents as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        if (!isset($tdrIplMap[$normalizedIpl])) {
                            $tdrIplMap[$normalizedIpl] = 0;
                        }
                        $tdrIplMap[$normalizedIpl] += (int)($tdr->qty ?? 0);
                    }
                }
            }

            \Log::info('TDR Components (normalized):', [
                'count' => count($tdrIplMap),
                'ipl_numbers' => array_keys($tdrIplMap),
                'excluded_ipl_count' => count($excludedIplNums),
                'excluded_ipls' => array_keys($excludedIplNums)
            ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $combinedSkippedCount = 0;

            \Log::info('Starting Stress calculation loop', [
                'total_stress_components' => count($stressComponents),
                'tdr_ipl_count' => count($tdrIplMap),
                'excluded_ipl_count' => count($excludedIplNums)
            ]);

            foreach ($stressComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                // Нормализуем IPL номер для сравнения (5-90A -> 5-90)
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Исключаем компоненты только с статусами Missing, Order New (Repair НЕ исключаем)
                if (!empty($normalizedIpl) && isset($excludedIplNums[$normalizedIpl])) {
                    $skippedCount++;
                    \Log::debug('Skipping Stress component as it has excluded status (Missing/Order New):', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl
                    ]);
                    continue;
                }

                // Если IPL номер пустой, пропускаем
                if (empty($itemNo)) {
                    \Log::debug('Skipping Stress component with empty IPL:', ['component' => $component]);
                    continue;
                }

                // ВАЖНО: Для Stress НЕ используем нормализацию для проверки дубликатов
                // Каждый компонент из CSV должен считаться отдельно, даже если IPL отличается только суффиксом
                // Используем оригинальный IPL для подсчета компонентов
                if (!in_array($itemNo, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $itemNo; // Сохраняем оригинальный IPL
                    \Log::debug('Adding Stress component from NdtCadCsv:', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl,
                        'qty' => $qty
                    ]);
                } else {
                    \Log::debug('Skipping duplicate Stress component (by original IPL):', [
                        'ipl_num' => $itemNo,
                        'normalized_ipl' => $normalizedIpl
                    ]);
                }
            }

            \Log::info('Stress calculation loop completed', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'skipped_count' => $skippedCount,
                'processed_ipls_count' => count($processedIpls),
                'processed_ipls' => $processedIpls
            ]);

            \Log::info('Stress calculation results:', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'processed_ipls' => $processedIpls
            ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            \Log::error('Error in Stress sums calculation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcPaintSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            \Log::info('Starting Paint sums calculation', [
                'workorder_id' => $workorder_id
            ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            \Log::info('Found NdtCadCsv record', [
                'ndt_cad_csv_id' => $ndtCadCsv->id,
                'paint_components_count' => count($ndtCadCsv->paint_components ?? [])
            ]);

            // Получаем Paint компоненты из JSON поля
            $paintComponents = $ndtCadCsv->paint_components ?? [];

            if (empty($paintComponents)) {
                \Log::warning('No Paint components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 2. Чтение TDR компонентов
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR
            $tdrIplMap = $tdrComponents->pluck('qty', 'component.ipl_num')->toArray();

            \Log::info('TDR Components:', [
                'count' => count($tdrIplMap),
                'ipl_numbers' => array_keys($tdrIplMap)
            ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $combinedSkippedCount = 0;

            \Log::info('Starting Paint calculation loop', [
                'total_paint_components' => count($paintComponents),
                'tdr_ipl_count' => count($tdrIplMap)
            ]);

            foreach ($paintComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                \Log::debug('Processing Paint component', [
                    'index' => $index,
                    'item_no' => $itemNo,
                    'qty' => $qty,
                    'component' => $component
                ]);

                // Если IPL номер есть в TDR - пропускаем
                if (isset($tdrIplMap[$itemNo])) {
                    $skippedCount++;
                    \Log::debug('Skipping component as it exists in TDR:', ['ipl_num' => $itemNo]);
                    continue;
                }

                // Проверяем совмещенные значения
                if ($this->shouldSkipItem($itemNo, array_keys($tdrIplMap))) {
                    $combinedSkippedCount++;
                    \Log::debug('Skipping Paint component due to combined value match:', [
                        'item_no' => $itemNo,
                        'existing_ipls' => array_keys($tdrIplMap)
                    ]);
                    continue;
                }

                // Если IPL номер еще не был обработан
                if (!in_array($itemNo, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $itemNo;
                    \Log::debug('Adding component from NdtCadCsv:', ['ipl_num' => $itemNo, 'qty' => $qty]);
                }
            }

            \Log::info('Paint calculation loop completed', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'skipped_count' => $skippedCount,
                'combined_skipped_count' => $combinedSkippedCount,
                'processed_ipls' => $processedIpls
            ]);

            \Log::info('Paint calculation results:', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'processed_ipls' => $processedIpls
            ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            \Log::error('Error in Paint sums calculation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function shouldSkipItem(string $itemNo, array $existingIplNums): bool
    {
        // Нормализация исходного значения из CSV (приведение кириллицы к латинице, верхний регистр)
        $rawItemNo = $this->normalizeAlphaNum($itemNo);

        foreach ($existingIplNums as $iplNum) {
            if (empty($iplNum)) continue;

            // Очистка строк от неалфавитно-цифровых символов для сравнения
            $cleanItemNo = $rawItemNo;
            $cleanIplNum = $this->normalizeAlphaNum($iplNum);

            // Если номера полностью совпадают, пропускаем
            if ($cleanItemNo === $cleanIplNum) {
                return true;
            }

            // Проверяем совмещенные значения в CSV (например, 1-140/1-140A, 1-140/140А)
            if (strpos($itemNo, '/') !== false) {
                $csvParts = explode('/', $itemNo);
                foreach ($csvParts as $part) {
                    $part = trim($part);
                    $cleanPart = $this->normalizeAlphaNum($part);

                    // Прямое сравнение
                    if ($cleanPart === $cleanIplNum) {
                        return true;
                    }

                    // Нормализация: если часть не содержит префикс, добавляем его из первой части
                    if (preg_match('/^(\d+-)/', $itemNo, $matches)) {
                        $prefix = $matches[1]; // например, "1-"
                        if (!preg_match('/^\d+-/', $part)) {
                            $normalizedPart = $prefix . ltrim($part);
                            $cleanNormalizedPart = $this->normalizeAlphaNum($normalizedPart);
                            if ($cleanNormalizedPart === $cleanIplNum) {
                                return true;
                            }
                        }
                    }
                }
            }

            // Проверяем совмещенные значения в базе данных
            if (strpos($iplNum, '/') !== false) {
                $dbParts = explode('/', $iplNum);
                foreach ($dbParts as $part) {
                    $part = trim($part);
                    $cleanPart = $this->normalizeAlphaNum($part);

                    // Прямое сравнение
                    if ($cleanPart === $cleanItemNo) {
                        return true;
                    }

                    // Нормализация: если часть не содержит префикс, добавляем его из первой части
                    if (preg_match('/^(\d+-)/', $iplNum, $matches)) {
                        $prefix = $matches[1];
                        if (!preg_match('/^\d+-/', $part)) {
                            $normalizedPart = $prefix . ltrim($part);
                            $cleanNormalizedPart = $this->normalizeAlphaNum($normalizedPart);
                            if ($cleanNormalizedPart === $cleanItemNo) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Приводит строку к сопоставимому виду: заменяет кириллицу на латиницу,
     * переводит в верхний регистр и удаляет все не буквенно-цифровые символы
     */
    private function normalizeAlphaNum(string $value): string
    {
        // Карта похожих символов Кириллица -> Латиница
        $map = [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
            'О' => 'O', 'Р' => 'P', 'С' => 'S', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
            'а' => 'A', 'в' => 'B', 'е' => 'E', 'к' => 'K', 'м' => 'M', 'н' => 'H',
            'о' => 'O', 'р' => 'P', 'с' => 'S', 'т' => 'T', 'у' => 'Y', 'х' => 'X'
        ];
        $converted = strtr($value, $map);
        $upper = strtoupper($converted);
        return preg_replace('/[^A-Z0-9]/', '', $upper);
    }
    /**
     * Проверяет, нужно ли пропустить элемент на основе существующих IPL номеров
     */

    /**
     * Обновление po_num или received для записи Tdr
     */
    public function updatePartField(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|in:po_num,received',
            'value' => 'nullable|string'
        ]);

        $tdr = Tdr::findOrFail($id);

        $field = $request->input('field');
        $value = $request->input('value');

        // Если поле received и значение пустое, устанавливаем null
        if ($field === 'received' && empty($value)) {
            $tdr->received = null;
        } else {
            $tdr->$field = $value;
        }

        $tdr->save();

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully'
        ]);
    }

}
