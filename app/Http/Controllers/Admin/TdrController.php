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
use App\Models\ManualProcess;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Unit;
//use App\Models\Wo_Code;
//use App\Models\WoCode;
use App\Models\Workorder;
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
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        // Получаем ID уже введенных условий для этого workorder
        $existing_condition_ids = Tdr::where('workorder_id', $workorder_id)
            ->pluck('conditions_id')
            ->filter()
            ->unique()
            ->toArray();

        // Получаем условия для unit, исключая уже введенные
        $unit_conditions = Condition::where('unit', true)
            ->whereNotIn('id', $existing_condition_ids)
            ->get();

        // Получаем условия для компонентов, исключая уже введенные
        $component_conditions = Condition::where('unit', false)
            ->whereNotIn('id', $existing_condition_ids)
            ->get();

        // Остальной код остается без изменений
        $components = Component::where('manual_id', $manual_id)->get();
        $manuals = Manual::all();
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();
        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

        return view('admin.tdrs.inspection', compact(
            'current_wo', 'manual_id',
            'manuals', 'components', 'units', 'user', 'customers',
            'planes', 'builders', 'instruction',
            'necessaries', 'conditions', 'codes', 'unit_conditions', 'component_conditions'
        ));
    }

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

        $components = Component::where('manual_id', $manual_id)
            ->select('id', 'part_number', 'assy_part_number', 'name', 'ipl_num')
            ->get();

        // Условия для Component - без фильтрации
        $component_conditions = Condition::where('unit', false)->get();

        // Получаем компоненты, коды, necessaries и т.п.
        $components = Component::where('manual_id', $manual_id)->get();
        $codes = Code::all();
        $necessaries = Necessary::all();

        return view('admin.tdrs.component-inspection', compact('current_wo', 'component_conditions',
            'components', 'codes', 'necessaries'));
    }


    public function inspection_new($workorder_id, $type)
    {
//        dd($type,$workorder_id);

        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        // Получаем ID уже введенных условий для этого workorder
        $existing_condition_ids = Tdr::where('workorder_id', $workorder_id)
            ->pluck('conditions_id')
            ->filter()
            ->unique()
            ->toArray();

        // Получаем условия для unit, исключая уже введенные
        $unit_conditions = Condition::where('unit', true)
            ->whereNotIn('id', $existing_condition_ids)
            ->get();

        // Получаем условия для компонентов, исключая уже введенные
        $component_conditions = Condition::where('unit', false)
            ->whereNotIn('id', $existing_condition_ids)
            ->get();

        $components = Component::where('manual_id', $manual_id)->get();
        $manuals = Manual::all();
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();
        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

        // Определяем какую страницу показывать
        if ($type === 'component') {
            return view('admin.tdrs.component-inspection', compact(
                'current_wo', 'manual_id', 'components', 'codes',
                'necessaries', 'component_conditions'
            ));
        } elseif ($type === 'unit') {
            return view('admin.tdrs.unit-inspection', compact(
                'current_wo', 'manual_id', 'unit_conditions'
            ));
        }

        // Старая страница с выбором (можно оставить или удалить)
        return view('admin.tdrs.inspection', compact(
            'current_wo', 'manual_id', 'manuals', 'components', 'units', 'user',
            'customers', 'planes', 'builders', 'instruction', 'necessaries',
            'conditions', 'codes', 'unit_conditions', 'component_conditions'
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

        // Валидация данных
        $validated = $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string',
            'assy_serial_number' => 'nullable|string',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id', // Валидация для  codes_id
            'qty' => 'nullable|integer',
            'description' => 'nullable|string',
            'order_component_id' => 'nullable|exists:components,id', // Добавляем валидацию для order_component_id
        ]);
//dd($validated);
        // Установка значений по умолчанию для флагов
//        $use_tdr = $request->has('use_tdr');
//        $use_process_forms = $request->has('use_process_forms');
        $use_tdr = $request->input('use_tdr');
        $use_process_forms = $request->input('use_process_forms');

        $use_log_card = $request->has('use_log_card');
        $use_extra_forms = $request->has('use_extra_forms');

        $qty = (int)$validated['qty'] ?? 1; // Приведение к целому числу

//dd($request->all());


        // Сохранение в таблице tdrs
        Tdr::create([
            'workorder_id' => $request->workorder_id, // Получаем workorder_id из формы
            'component_id' => $validated['component_id'],
            'serial_number' => $validated['serial_number'] ?? 'NSN',
            'assy_serial_number' => $validated['assy_serial_number'],
            'codes_id' => $validated['codes_id'],  // Обработка передачи
//             codes_id
            'conditions_id' => $validated['conditions_id'],
            'necessaries_id' => $validated['necessaries_id'],
            'description' =>$validated['description'],
            'qty' => $qty,
            'use_tdr' => $use_tdr,
            'use_process_forms' => $use_process_forms,
            'use_log_card' => $use_log_card,
            'use_extra_forms' => $use_extra_forms,
            'order_component_id' => $validated['order_component_id'], // Добавляем сохранение order_component_id
        ]);

        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

//dd($code->id, $validated['codes_id']);

        // Если codes_id равно $code->id, обновляем поле part_missing в workorders
        if ($validated['codes_id'] == $code->id) {
//dd('true');
            $workorder = Workorder::find($request->workorder_id);

//            dd($workorder->part_missing);

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


        return redirect()->route('tdrs.show', ['tdr' => $current_wo]);

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

        $tdrProcesses = TdrProcess::all();

        $proces = Process::all()->keyBy('id');

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=',null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->where('use_process_forms', true)
            ->with('component')
            ->get();



        return view('admin.tdrs.processes', compact('current_wo',
            'tdrs','components',
            'manuals','tdrProcesses','proces'
        ));
    }

    public function show($id)
    {
        $current_wo = Workorder::with(['unit.manuals.builder', 'instruction'])->findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();

        // Получаем manual_id из связанного unit
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем все manuals для отображения
        $manuals = Manual::all();

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

        $inspectsUnit = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', null)
            ->when($missingCondition, function ($query) use ($missingCondition) {
                return $query->where('conditions_id', '!=', $missingCondition->id);
            })
            ->with(['conditions', 'necessaries'])
            ->get();

        $missingParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', $code->id)
            ->with(['component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
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

        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $necessaries = Necessary::all();
        $unit_conditions = Condition::where('unit', true)->get();
        $component_conditions = Condition::where('unit', false)->get();
        $codes = Code::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->with(['component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        return view('admin.tdrs.show', compact(
            'current_wo', 'tdrs', 'units', 'components', 'user', 'customers',
            'manuals', 'builders', 'planes', 'instruction', 'necessary',
            'necessaries', 'unit_conditions', 'component_conditions',
            'codes', 'conditions', 'missingParts', 'ordersParts', 'inspectsUnit',
            'processParts', 'ordersPartsNew'
        ));
    }
    public function show_($id)
    {
        // Основной запрос с жадной загрузкой всех необходимых отношений
        $current_wo = Workorder::with([
            'unit.manuals.builder',
            'instruction',
            'tdrs' => function($query) {
                $query->with([
                    'component',
                    'conditions',
                    'necessaries',
                    'codes'
                ]);
            }
        ])->findOrFail($id);

        // Проверка наличия связанных данных
        if (!$current_wo->unit || !$current_wo->unit->manual_id) {
            abort(404, 'Unit or Manual not found for this Workorder');
        }

        // Получаем специальные записи
        $code = Code::where('name', 'Missing')->first();
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        // Фильтрация данных через коллекции (меньше запросов к БД)
        $components = Component::where('manual_id', $current_wo->unit->manual_id)->get();

        $processParts = $current_wo->tdrs
            ->where('component_id', '!=', null)
            ->when($necessary, fn($collection) => $collection->where('necessaries_id', '!=', $necessary->id));

        $inspectsUnit = $current_wo->tdrs
            ->where('component_id', null)
            ->when($missingCondition, fn($collection) => $collection->where('conditions_id', '!=', $missingCondition->id));

        $missingParts = $current_wo->tdrs->where('codes_id', $code->id ?? null);

        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $code->id)
            ->where('necessaries_id', $necessary->id)
            ->with('codes')
            ->with('component')  // Используем связь, а не коллекцию компонентов
            ->get();

        // Справочные данные (можно оптимизировать, если они используются редко)
        $data = [
            'units' => Unit::all(),
            'customers' => Customer::all(),
            'manuals' => Manual::all(),
            'planes' => Plane::all(),
            'builders' => Builder::all(),
            'instruction' => Instruction::all(),
            'necessaries' => Necessary::all(),
            'codes' => Code::all(),
            'conditions' => Condition::all(),
            'unit_conditions' => Condition::where('unit', true)->get(),
            'component_conditions' => Condition::where('unit', false)->get(),
        ];

        return view('admin.tdrs.show', array_merge(
            [
                'current_wo' => $current_wo,
                'user' => Auth::user(),
                'necessary' => $necessary,
                'components' => $components,
                'processParts' => $processParts,
                'inspectsUnit' => $inspectsUnit,
                'missingParts' => $missingParts,
                'ordersParts' => $ordersParts,
                'tdrs' => $current_wo->tdrs
            ],
            $data
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
            ->route('admin.tdrs.show', ['tdr' => $request->workorder_id])
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
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num');
            }])
            ->get();

        // Получаем TDR записи без order_component_id
        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNull('order_component_id')
            ->with(['codes', 'component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num');
            }])
            ->get();

        // Объединяем коллекции
        $ordersParts = $ordersPartsNew->concat($ordersParts);

        return view('admin.tdrs.prlForm', compact('current_wo', 'components','manuals', 'builders', 'codes','necessaries', 'ordersParts'));
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

        // Получаем CSV-файл с process_type = 'ndt'
        $csvMedia = $manual->getMedia('csv_files')->first(function ($media) {
            return $media->getCustomProperty('process_type') === 'ndt';
        });

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

        $ndt_components = [];
        $form_number = 'NDT-STD';

        if ($csvMedia) {
            try {
                $csvPath = $csvMedia->getPath();
                $csv = Reader::createFromPath($csvPath, 'r');
                $csv->setHeaderOffset(0);

                // Получаем заголовки CSV файла
                $headers = $csv->getHeader();
                \Log::info('CSV Headers:', $headers);

                // Получаем все записи из CSV
                $records = iterator_to_array($csv->getRecords());
                \Log::info('Total records in CSV:', ['count' => count($records)]);

                // Получаем все ipl_num из tdrs для этого workorder
                $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                    ->whereNotNull('component_id')
                    ->with('component')
                    ->get()
                    ->pluck('component.ipl_num')
                    ->filter()
                    ->unique()
                    ->toArray();

                \Log::info('Existing IPL numbers:', $existingIplNums);

                // Фильтруем и преобразуем записи из CSV
                foreach ($records as $row) {
                    // Проверяем наличие необходимых данных
                    if (!isset($row['ITEM   No.'])) {
                        \Log::warning('Missing item number in row:', $row);
                        continue;
                    }

                    $itemNo = $row['ITEM   No.'];
                    $shouldSkip = false;

                    // Проверяем каждый существующий ipl_num
                    foreach ($existingIplNums as $iplNum) {
                        if (empty($iplNum)) continue;

                        // Очищаем строки от неалфавитно-цифровых символов для сравнения
                        $cleanItemNo = preg_replace('/[^A-Za-z0-9]/', '', $itemNo);
                        $cleanIplNum = preg_replace('/[^A-Za-z0-9]/', '', $iplNum);

                        // Если один номер содержит другой, пропускаем эту запись
                        if (strpos($cleanItemNo, $cleanIplNum) !== false ||
                            strpos($cleanIplNum, $cleanItemNo) !== false) {
                            \Log::info('Skipping record due to existing IPL:', [
                                'item_no' => $itemNo,
                                'existing_ipl' => $iplNum
                            ]);
                            $shouldSkip = true;
                            break;
                        }
                    }

                    if ($shouldSkip) continue;

                    // Если запись не была пропущена, создаем объект компонента
                    $component = new \stdClass();
                    $component->ipl_num = $itemNo;
                    $component->part_number = $row['PART No.'] ?? '';
                    $component->name = $row['DESCRIPTION'] ?? '';
                    $component->qty = $row['QTY'] ?? 1;
                    $component->process_name = $row['PROCESS No.'] ?? '1';

                    $ndt_components[] = $component;
                }

                \Log::info('Total components after filtering:', ['count' => count($ndt_components)]);

            } catch (\Exception $e) {
                \Log::error('Error processing CSV file:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return view('admin.tdrs.ndtFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'ndt_components' => $ndt_components,
                'ndt_processes' => $ndt_processes,
                'form_number' => $form_number,
                'manuals' => [$manual], // Для совместимости с существующим кодом
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

            $cadSum = $this->calcCadSums($workorder_id);

            // Получаем CSV-файл с process_type = 'cad'
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_CAD;
            });

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

            $cad_components = [];
            $form_number = 'CAD-STD';

            if ($csvMedia) {
                $csvPath = $csvMedia->getPath();
                $csv = Reader::createFromPath($csvPath, 'r');
                $csv->setHeaderOffset(0);

                // Проверяем обязательные заголовки
                $requiredHeaders = ['ITEM   No.', 'PART No.', 'DESCRIPTION', 'QTY', 'PROCESS No.'];
                $headers = $csv->getHeader();
                $missingHeaders = array_diff($requiredHeaders, $headers);

                if (!empty($missingHeaders)) {
                    throw new \RuntimeException('Отсутствуют обязательные заголовки CSV: ' . implode(', ', $missingHeaders));
                }

                // Получаем все записи из CSV
                $records = iterator_to_array($csv->getRecords());
                \Log::info('Total CAD records in CSV:', ['count' => count($records)]);

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

                \Log::info('Valid processes for CAD:', ['processes' => $validProcesses]);

                // Обрабатываем записи
                foreach ($records as $row) {
                    if (!isset($row['ITEM   No.'])) {
                        \Log::warning('Missing item number in CAD row:', $row);
                        continue;
                    }

                    $itemNo = $row['ITEM   No.'];

                    if ($this->shouldSkipItem($itemNo, $existingIplNums)) {
                        continue;
                    }

                    // Проверяем и создаем процесс, если его нет
                    $processName = $row['PROCESS No.'] ?? '';
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
                        \Log::warning('Invalid process found in CSV:', [
                            'process' => $processName,
                            'item_no' => $itemNo,
                            'valid_processes' => $validProcesses
                        ]);
                        continue;
                    }

                    // Создаем объект компонента
                    $component = new \stdClass();
                    $component->ipl_num = $itemNo;
                    $component->part_number = $row['PART No.'] ?? '';
                    $component->name = $row['DESCRIPTION'] ?? '';
                    $component->qty = (int)($row['QTY'] ?? self::DEFAULT_QTY);
                    $component->process_name = $processName;

                    $cad_components[] = $component;
                }

                \Log::info('Total CAD components after filtering:', ['count' => count($cad_components)]);
            }

            // Обновляем список процессов после возможного добавления новых
            $cad_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $cad_ids)
                ->get();

            // Рассчитываем общее количество деталей
            $totalQuantities = $this->calcCadSums($workorder_id);

            return view('admin.tdrs.cadFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'cad_components' => $cad_components,
                'cad_processes' => $cad_processes,
                'form_number' => $form_number,
                'manuals' => [$manual],
                'process_name' => ProcessName::where('name', 'Cad plate')->first(),
                'total_quantities' => $totalQuantities,
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

    /**
     * Находит индекс колонки по возможным названиям
     */
    private function findColumnIndex($headers, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $headers);
            if ($index !== false) {
                return $name;
            }
        }
        return null;
    }

    public function specProcessForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Получаем NDT суммы
        $ndtSums = $this->calcNdtSums($id);
        $cadSum = $this->calcCadSums($id);

        $tdr_ws = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('component')
            ->get();
        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $processNames = ProcessName::where(function ($query) {
            $query->where('name', 'NOT LIKE', '%NDT%');
//                ->where('name', 'NOT LIKE', '%Paint%');
        })->get();

        // Получаем Tdr, где use_process_form = true, с предварительной загрузкой TdrProcess
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('tdrProcesses') // Предварительная загрузка TdrProcess
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
        return view('admin.tdrs.specProcessForm', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
        ], compact('tdrs', 'tdr_ws','processNames'));
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
                    // Добавляем состояние в массив
                    $nullComponentConditions[] = $conditions->name;
                }
            } elseif ($tdr->component_id !== null && $tdr->necessaries_id == $necessary->id) {
                // Группируем компоненты по состояниям, если necessaries_id == 2 ('Order New')
                $component = $tdr->component; // Получаем связанные данные о компоненте
                $conditions = $tdr->conditions; // Получаем связанные данные о состоянии
                if ($component && $conditions) {
                    // Формируем строку для компонента
                    $componentString = sprintf(
                        "<b>%s</b> (%s%s)", // Номер компонента и его имя
                        strtoupper($component->name), // Имя компонента
                        strtoupper($component->ipl_num), // Номер компонента
                        $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs' // Если qty == 1, то пустая строка, иначе добавляем qty
                    // и "pcs"
                    );

                    // Инициализируем массив для состояния, если он еще не существует
                    if (!isset($groupedByConditions[$conditions->name])) {
                        $groupedByConditions[$conditions->name] = [];
                    }

                    // Получаем последнюю строку в группе
                    $lastKey = count($groupedByConditions[$conditions->name]) - 1;
                    $lastString = $lastKey >= 0 ? $groupedByConditions[$conditions->name][$lastKey] : '';

                    // Проверяем длину строки
                    if (strlen($lastString . ', ' . $componentString) <= 180) {
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
                    $necessaryComponents[] = sprintf(
                        "(%s) <b>%s</b> IS NECESSARY: %s - %s ( %s )", // Формат вывода
                        strtoupper($component->ipl_num), // Номер компонента
                        strtoupper($component->name), // Имя компонента
                        strtoupper($necessaries->name), // Название необходимости
                        strtoupper($codes->name), // Название кода
                        strtoupper($description), // Название кода

                    );
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

        // Удалить связанные записи из tdr_processes
        TdrProcess::where('tdrs_id', $id)->delete();
        Log::info('Удалены связанные процессы для TDR с ID: ' . $id);

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
        return redirect()->route('tdrs.show', ['tdr' => $workorderId])
            ->with('success', 'Запись успешно удалена.');
    }


    /**
     * Расчет сумм NDT из данных CSV для рабочего заказа
     *
     * @param int $workorder_id ID рабочего заказа
     * @return array{total: int, mpi: int, fpi: int} Массив с общими суммами, MPI и FPI
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

            // Получение CSV файла NDT
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_NDT;
            });

            // Если CSV файл не найден, возвращаем нулевые значения
            if (!$csvMedia) {
                return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
            }

            // Получение существующих номеров IPL
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            // Обработка CSV файла
            $csvPath = $csvMedia->getPath();
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            // Проверка заголовков CSV
            $requiredHeaders = ['ITEM   No.', 'PART No.', 'DESCRIPTION', 'QTY', 'PROCESS No.'];
            $headers = $csv->getHeader();
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (!empty($missingHeaders)) {
                throw new \RuntimeException('Отсутствуют обязательные заголовки CSV: ' . implode(', ', $missingHeaders));
            }

            // Обработка записей
            $ndt_components = [];
            foreach ($csv->getRecords() as $row) {
                if (!isset($row['ITEM   No.'])) {
                    \Log::warning('Отсутствует номер элемента в строке:', $row);
                    continue;
                }

                $itemNo = $row['ITEM   No.'];

                // Пропуск, если номер элемента совпадает с существующим IPL
                if ($this->shouldSkipItem($itemNo, $existingIplNums)) {
                    continue;
                }

                // Создание объекта компонента
                $component = new \stdClass();
                $component->ipl_num = $itemNo;
                $component->part_number = $row['PART No.'] ?? '';
                $component->name = $row['DESCRIPTION'] ?? '';
                $component->qty = (int)($row['QTY'] ?? self::DEFAULT_QTY);
                $component->process_name = $row['PROCESS No.'] ?? self::DEFAULT_PROCESS;

                $ndt_components[] = $component;
            }

            // Вычисление сумм за один проход
            foreach ($ndt_components as $component) {
                $qty = $component->qty;
                $total += $qty;

                if (strpos($component->process_name, '1') !== false) {
                    $mpi += $qty;
                } else {
                    $fpi += $qty;
                }
            }

            return [
                'total' => $total,
                'mpi' => $mpi,
                'fpi' => $fpi
            ];

        } catch (\Exception $e) {
            \Log::error('Ошибка при обработке CSV файла:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
        }
    }
    private function calcCadSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // 1. Чтение CSV файла
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_CAD;
            });

            if (!$csvMedia) {
                throw new \RuntimeException('CSV file not found');
            }

            // Читаем CSV файл
            $csvPath = $csvMedia->getPath();
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            // Получаем все записи из CSV
            $records = iterator_to_array($csv->getRecords());
            \Log::info('Total CAD records in CSV:', ['count' => count($records)]);

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

            foreach ($records as $record) {
                $itemNo = trim($record['ITEM   No.']);

                // Если IPL номер есть в TDR - пропускаем
                if (isset($tdrIplMap[$itemNo])) {
                    \Log::info('Skipping component as it exists in TDR:', ['ipl_num' => $itemNo]);
                    continue;
                }

                // Если IPL номер еще не был обработан
                if (!in_array($itemNo, $processedIpls)) {
                    $totalQty += 1; // Предполагаем qty = 1 для компонентов из CSV
                    $totalComponents++;
                    $processedIpls[] = $itemNo;
                    \Log::info('Adding component from CSV:', ['ipl_num' => $itemNo]);
                }
            }

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

    /**
     * Проверяет, нужно ли пропустить элемент на основе существующих IPL номеров
     */
    private function shouldSkipItem(string $itemNo, array $existingIplNums): bool
    {
        foreach ($existingIplNums as $iplNum) {
            if (empty($iplNum)) continue;

            // Очистка строк от неалфавитно-цифровых символов для сравнения
            $cleanItemNo = preg_replace('/[^A-Za-z0-9]/', '', $itemNo);
            $cleanIplNum = preg_replace('/[^A-Za-z0-9]/', '', $iplNum);

            // Если один номер содержит другой, пропускаем
            if (strpos($cleanItemNo, $cleanIplNum) !== false ||
                strpos($cleanIplNum, $cleanItemNo) !== false) {
                return true;
            }
        }
        return false;
    }
    /**
     * Проверяет, нужно ли пропустить элемент на основе существующих IPL номеров
     */



}
