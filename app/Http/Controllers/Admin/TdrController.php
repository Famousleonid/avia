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

class TdrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index()
    {
        $orders=Workorder::all();
        $manuals = Manual::all();
        $units =Unit::with('manuals')->get();
        $tdrs=Tdr::all();
        return view('admin.tdrs.index', compact('orders','units','manuals','tdrs'));
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
        $unit_conditions = Condition::where('unit',true)->get();
        $component_conditions = Condition::where('unit',false)->get();


        // Отправляем данные в представление
        return view('admin.tdrs.inspection', compact(
            'current_wo', 'manual_id',
            'manuals', 'components', 'units', 'user', 'customers',
            'planes', 'builders', 'instruction',
            'necessaries','conditions','codes','unit_conditions','component_conditions'
        ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
            'codes_id' => 'nullable|exists:codes,id', // Валидация для
            // codes_id
        ]);
//dd($validated);
        // Установка значений по умолчанию для флагов
        $use_tdr = $request->has('use_tdr');
        $use_process_forms = $request->has('use_process_forms');
        $use_log_card = $request->has('use_log_card');
        $use_extra_forms = $request->has('use_extra_forms');

//dd($request->all(), $validated,$use_tdr,$request->has('use_tdr'),$use_process_forms,$request->has('use_process_forms'));


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
            'use_tdr' => $use_tdr,
            'use_process_forms' => $use_process_forms,
            'use_log_card' => $use_log_card,
            'use_extra_forms' => $use_extra_forms,
        ]);

        // Если codes_id равно 7, обновляем поле part_missing в workorders
        if ($validated['codes_id'] == 7) {
            $workorder = Workorder::find($request->workorder_id);
            if ($workorder) {
                $workorder->part_missing = true;
                $workorder->save();
            }
        }

        $current_wo = $request->workorder_id;



        return redirect()->route('admin.tdrs.show', ['tdr' => $current_wo]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
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


        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();

        $necessaries = Necessary::all();
        $unit_conditions = Condition::where('unit',true)->get();
        $component_conditions = Condition::where('unit',false)->get();
        $conditions =Condition::all();

        $codes = Code::all();

        $tdrs =Tdr::where('workorder_id',$current_wo->id)->get();
//        $tdrs = Tdr::with('current_wo')->get(); // --- ? ---

        return view('admin.tdrs.show', compact(  'current_wo','tdrs','units',
            'components','user','customers',
        'manuals','builders','planes','instruction',
        'necessaries','unit_conditions','component_conditions','codes','conditions',));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
//        $current_wo = Workorder::findOrFail($id);
//        $units = Unit::all();
//        $user = Auth::user();
//        $customers = Customer::all();
//        $manuals = Manual::all();
//        $planes = Plane::all();
//        $builders = Builder::all();
//
//        return view('admin.tdrs.edit', compact(  'current_wo','units','user','customers','manuals','builders','planes'));

            $current_tdr = Tdr::findOrFail($id);
            $workorder = Workorder::where('id',$current_tdr);
            $units = Unit::all();
            $necessaries = Necessary::all();
            $conditions = Condition::all();
            $codes = Code::all();

//            $current_wo = $current_tdr->workorder->id;


        return view('admin.tdrs.edit', compact(  'current_tdr','workorder','units','necessaries','conditions','codes'));

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
        //
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
}
