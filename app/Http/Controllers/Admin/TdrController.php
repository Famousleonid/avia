<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Tdr;
use App\Models\Unit;
use App\Models\Wo_Code;
use App\Models\WoCode;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TdrController extends Controller
{
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
    public function inspection($workorder_id)
    {

        // Находим текущий рабочий заказ по переданному ID
        $current_wo = Workorder::findOrFail($workorder_id);

        // Получаем manual_id из связанного unit
        $manual_id = $current_wo->unit->manual_id;  // предполагаем, что в workorder есть связь с unit

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Извлекаем все manuals для отображения (если нужно отфильтровать, можно это сделать)
        $manuals = Manual::all();  // или можно отфильтровать только тот, который связан с unit

        // Дополнительные данные для формы
        $units = Unit::all();

        $user = Auth::user();
        $customers = Customer::all();

        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();


        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();
        $unit_conditions = Condition::where('unit', true)->get();
        $component_conditions = Condition::where('unit', false)->get();


        // Отправляем данные в представление
        return view('admin.tdrs.inspection', compact(
            'current_wo', 'manual_id',
            'manuals', 'components', 'units', 'user', 'customers',
            'planes', 'builders', 'instruction',
            'necessaries', 'conditions', 'codes', 'unit_conditions', 'component_conditions'
        ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//        dd($request->all()); // Посмотреть все переданные данные
        // Валидация данных
        $validated = $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string',
            'assy_serial_number' => 'nullable|string',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id', // Валидация для  codes_id
            'qty' => 'nullable|integer',
        ]);
//dd($validated);
        // Установка значений по умолчанию для флагов
        $use_tdr = $request->has('use_tdr');
        $use_process_forms = $request->has('use_process_forms');
        $use_log_card = $request->has('use_log_card');
        $use_extra_forms = $request->has('use_extra_forms');

        $qty = (int)$validated['qty'] ?? 1; // Приведение к целому числу

//dd($request->all(), $qty);


        // Сохранение в таблице tdrs
        Tdr::create([
            'workorder_id' => $request->workorder_id, // Получаем workorder_id из формы
            'component_id' => $validated['component_id'],
            'serial_number' => $validated['serial_number'] ?? 'NSN',
            'assy_serial_number' => $validated['assy_serial_number'],
            'codes_id' => $validated['codes_id'],  // Обработка передачи
            // codes_id
            'conditions_id' => $validated['conditions_id'],
            'necessaries_id' => $validated['necessaries_id'],
            'qty' => $qty,
            'use_tdr' => $use_tdr,
            'use_process_forms' => $use_process_forms,
            'use_log_card' => $use_log_card,
            'use_extra_forms' => $use_extra_forms,
        ]);

        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        // Если codes_id равно $code->id, обновляем поле part_missing в workorders
        if ($validated['codes_id'] == $code->id) {
            $workorder = Workorder::find($request->workorder_id);

            // Проверяем, если part_missing равно false, то меняем на true
            if ($workorder->part_missing == false) {  // Используем строгое сравнение с false
                $workorder->part_missing = true;
                $workorder->save();

                // Создаем запись в таблице tdrs, если part_missing обновлен на true
                Tdr::create([
                    'workorder_id' => $request->workorder_id,
                    'conditions_id' => $missingCondition->id, // Используем ID из найденного condition
                    'use_tdr' => true,
                ]);
            }
        }
            // Второе условие: если codes_id не равно $code->id и necessaries_id равно $necessary->id
        if ($validated['codes_id'] != $code->id && $validated['necessaries_id'] == $necessary->id) {
            $workorder = Workorder::find($request->workorder_id);

            if ($workorder->new_parts == false) {
                $workorder->new_parts = true;
                $workorder->save();
            }
        }

        $current_wo = $request->workorder_id;


        return redirect()->route('admin.tdrs.show', ['tdr' => $current_wo]);

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();

        // Получаем manual_id из связанного unit
        $manual_id = $current_wo->unit->manual_id;  // предполагаем, что в workorder есть связь с unit

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Извлекаем все manuals для отображения (если нужно отфильтровать, можно это сделать)
        $manuals = Manual::all();  // или можно отфильтровать только тот, который связан с unit

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        // Загружаем TDR с жадной загрузкой компонента и фильтруем по нужным условиям
        $missingParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', $code->id)
            ->with('component')  // Используем связь, а не коллекцию компонентов
            ->get();


        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $code->id)  // Заменили '!' на правильное условие "не равно"
            ->where('necessaries_id', $necessary->id)
            ->with('codes')
            ->with('component')  // Используем связь, а не коллекцию компонентов
            ->get();



        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();

        $necessaries = Necessary::all();
        $unit_conditions = Condition::where('unit', true)->get();
        $component_conditions = Condition::where('unit', false)->get();
        $conditions = Condition::all();

        $codes = Code::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)->get();

        return view('admin.tdrs.show', compact('current_wo', 'tdrs', 'units',
            'components', 'user', 'customers',
            'manuals', 'builders', 'planes', 'instruction',
            'necessaries', 'unit_conditions', 'component_conditions',
            'codes', 'conditions', 'missingParts','ordersParts'));
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
        $workorder = Workorder::where('id', $current_tdr);
        $units = Unit::all();
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

//            $current_wo = $current_tdr->workorder->id;


        return view('admin.tdrs.edit', compact('current_tdr', 'workorder', 'units', 'necessaries', 'conditions', 'codes'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

        // Запомнить workorder_id для дальнейшего использования
        $workorderId = $tdr->workorder_id;

        // Логируем workorder_id
        Log::info('Workorder ID: ' . $workorderId);

        // Удалить запись Tdr
        $tdr->delete();
        Log::info('Запись Tdr с ID: ' . $id . ' была удалена.');

        // Найти код с именем 'Missing'
        $code = Code::where('name', 'Missing')->first();
        Log::info('Найден код с именем "Missing": ' . ($code ? 'Да' : 'Нет'));

        // Найти necessary с именем 'Missing'
        $necessary = Necessary::where('name', 'Order New')->first();
        Log::info('Найден necessary с именем "Order New": ' . ($code ? 'Да' : 'Нет'));

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

        if ($code) {
            // Проверить, если это последняя запись с codes_id = $code->id
            $remainingPartsWithCodes7 = Tdr::where('workorder_id', $workorderId)
                ->where('codes_id', $code->id)
                ->count();

            Log::info('Оставшиеся записи с кодом Missing для workorder_id ' . $workorderId . ': ' . $remainingPartsWithCodes7);

            // Если это была последняя запись с таким кодом, обновляем поле part_missing в workorder
            if ($remainingPartsWithCodes7 == 0) {
                // Обновляем поле part_missing в workorder
                $workorder = Workorder::find($workorderId);

                if ($workorder && $workorder->part_missing == true) {
                    // Меняем на false, если part_missing равно true
                    $workorder->part_missing = false;
                    $workorder->save();
                    Log::info('Поле part_missing для workorder_id ' . $workorderId . ' обновлено на false');
                } else {
                    Log::info('Поле part_missing для workorder_id ' . $workorderId . ' уже false или workorder не найден.');
                }

                // Найти условие с именем 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST'
                $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
                Log::info('Найдено условие с именем "PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST": ' . ($missingCondition ? 'Да' : 'Нет'));

                if ($missingCondition) {
                    // Проверка на наличие записи с этим conditions_id в таблице tdrs для данного workorder_id
                    $conditionRecord = Tdr::where('workorder_id', $workorderId)
                        ->where('conditions_id', $missingCondition->id)
                        ->first();

                    Log::info('Найдено ли условие в tdrs с conditions_id ' . $missingCondition->id . ' для workorder_id ' . $workorderId . ': ' . ($conditionRecord ? 'Да' : 'Нет'));

                    if ($conditionRecord) {
                        // Удалить найденную запись
                        $conditionRecord->delete();
                        Log::info('Запись с conditions_id ' . $missingCondition->id . ' для workorder_id ' . $workorderId . ' была удалена.');
                    } else {
                        Log::warning('Запись с conditions_id ' . $missingCondition->id . ' для workorder_id ' . $workorderId . ' не найдена.');
                    }
                }
            }
        }

        // Перенаправить с сообщением об успехе
        return redirect()->route('admin.tdrs.show', ['tdr' => $workorderId])->with('success', 'Запись успешно удалена.');
    }


}
