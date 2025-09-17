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
use App\Models\ModCsv;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
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

        $tdrProcesses = TdrProcess::orderBy('sort_order')->get();

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



        return view('admin.tdrs.processes', compact('current_wo',
            'tdrs','components',
            'manuals','tdrProcesses','proces','vendors'
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
            ->route('tdrs.show', ['tdr' => $request->workorder_id])
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

        // Получаем все ipl_num из tdrs для этого workorder
        $existingIplNums = Tdr::where('workorder_id', $workorder_id)
            ->whereNotNull('component_id')
            ->with('component')
            ->get()
            ->pluck('component.ipl_num')
            ->filter()
            ->unique()
            ->toArray();

        // Фильтруем компоненты из NdtCadCsv
        $ndt_components = [];
        foreach ($ndtCadCsv->ndt_components as $component) {
            $itemNo = $component['ipl_num'];

            // Используем унифицированную логику сравнения
            if ($this->shouldSkipItem($itemNo, $existingIplNums)) {
                \Log::info('Skipping NDT component due to unified IPL comparison match', [
                    'item_no' => $itemNo,
                    'existing_ipls' => $existingIplNums
                ]);
                continue;
            }

            // Преобразуем в объект для совместимости с существующим кодом
            $componentObj = new \stdClass();
            $componentObj->ipl_num = $component['ipl_num'];
            $componentObj->part_number = $component['part_number'] ?? '';
            $componentObj->name = $component['description'] ?? '';
            $componentObj->qty = $component['qty'] ?? 1;
            $componentObj->process_name = $component['process'] ?? '1';

            $ndt_components[] = $componentObj;
        }

        // Сортируем NDT компоненты по ipl_num
        usort($ndt_components, function($a, $b) {
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

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

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

            // Фильтруем компоненты из NdtCadCsv
            $cad_components = [];
            foreach ($ndtCadCsv->cad_components as $component) {
                $itemNo = $component['ipl_num'];

                if ($this->shouldSkipItem($itemNo, $existingIplNums)) {
                    continue;
                }

                $processName = $component['process'] ?? '';

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

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $componentObj->process_name = $processName;

                $cad_components[] = $componentObj;
            }

            // Сортируем CAD компоненты по ipl_num
            usort($cad_components, function($a, $b) {
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

            // Рассчитываем общее количество деталей
            $cadSum = $this->calcCadSums($workorder_id);

            return view('admin.tdrs.cadFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'cad_components' => $cad_components,
                'cad_processes' => $cad_processes,
                'form_number' => $form_number,
                'manuals' => [$manual],
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

            // Фильтруем компоненты из NdtCadCsv
            $stress_components = [];
            foreach ($ndtCadCsv->stress_components as $component) {
                $itemNo = $component['ipl_num'];

                if ($this->shouldSkipItem($itemNo, $existingIplNums)) {
                    continue;
                }

                $processName = $component['process'] ?? '';

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

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $componentObj->process_name = $processName;

                $stress_components[] = $componentObj;
            }

            // Сортируем Stress компоненты по ipl_num
            usort($stress_components, function($a, $b) {
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

            return view('admin.tdrs.stressFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'stress_components' => $stress_components,
                'stress_processes' => $stress_processes,
                'form_number' => $form_number,
                'manuals' => [$manual],
                'process_name' => ProcessName::where('id', 3)->first(),
                'stressSum' => $stressSum,
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
        return view('admin.tdrs.specProcessForm', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
        ], compact('tdrs', 'tdr_ws','processNames'));
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
        ], compact('tdrs', 'tdr_ws','processNames'));
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
                return ['total' => 0, 'mpi' => 'N/A', 'fpi' => "N/A"];
            }

            // Получение существующих номеров IPL из TDR
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            \Log::info('Processing NDT components from ndt_cad_csv table', [
                'workorder_id' => $workorder_id,
                'components_count' => count($ndtComponents),
                'existing_ipls' => $existingIplNums
            ]);

            // Обработка NDT компонентов из JSON поля
            foreach ($ndtComponents as $index => $component) {
                // Проверяем наличие обязательных полей
                if (!isset($component['ipl_num']) || empty($component['ipl_num'])) {
                    \Log::warning('Missing ipl_num in NDT component:', $component);
                    continue;
                }

                $iplNum = $component['ipl_num'];

                // Пропуск, если номер элемента совпадает с существующим IPL
                if ($this->shouldSkipItem($iplNum, $existingIplNums)) {
                    \Log::info('Skipping NDT component due to existing IPL:', [
                        'ipl_num' => $iplNum,
                        'existing_ipls' => $existingIplNums
                    ]);
                    continue;
                }

                // Получение количества и процесса
                $qty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $process = $component['process'] ?? self::DEFAULT_PROCESS;

                // Вычисление сумм
                $total += $qty;

                if (strpos($process, '1') !== false) {
                    $mpi += $qty;
                } else {
                    $fpi += $qty;
                }
            }

            \Log::info('NDT sums calculated from ndt_cad_csv table', [
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

            \Log::info('Starting CAD calculation loop', [
                'total_cad_components' => count($cadComponents),
                'tdr_ipl_count' => count($tdrIplMap)
            ]);

            foreach ($cadComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                \Log::debug('Processing CAD component', [
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
                    \Log::debug('Skipping CAD component due to combined value match:', [
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

            \Log::info('CAD calculation loop completed', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'skipped_count' => $skippedCount,
                'combined_skipped_count' => $combinedSkippedCount,
                'processed_ipls' => $processedIpls
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

            \Log::info('Starting Stress calculation loop', [
                'total_stress_components' => count($stressComponents),
                'tdr_ipl_count' => count($tdrIplMap)
            ]);

            foreach ($stressComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                \Log::debug('Processing Stress component', [
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
                    \Log::debug('Skipping Stress component due to combined value match:', [
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

            \Log::info('Stress calculation loop completed', [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents,
                'skipped_count' => $skippedCount,
                'combined_skipped_count' => $combinedSkippedCount,
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



}
