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

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create($workorder_id)
    {
//        dd($workorder_id);
        // Находим текущий рабочий заказ по переданному ID
        $current_wo = Workorder::findOrFail($workorder_id);

        // Получаем manual_id из связанного unit
        $manual_id = $current_wo->unit->manual_id;  // предполагаем, что в workorder есть связь с unit

        // Извлекаем компоненты, которые связаны с этим manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Извлекаем все manuals для отображения (если нужно отфильтровать, можно это сделать)
        $manuals = Manual::all();  // или можно отфильтровать только тот, который связан с unit

        $units = Unit::all();

//        $user = Auth::user();
        $customers = Customer::all();

        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $conditions = Condition::all();
        $necessaries = Necessary::all();
        $codes = Code::all();

        $tdrs = Tdr::where('workorder_id', $workorder_id)->get(); // Фильтрация TDR по workorder_id

        return view('admin.tdrs.create', compact(  'current_wo',
            'tdrs','units','components','customers',
            'manuals','builders','conditions','necessaries','codes',
            'planes','instruction'));


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//        dd($request->all());
        // Валидация данных
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'serial_number' => 'required|string',
            'assy_serial_number' => 'nullable|string',
            'conditions_id' => 'required|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'required|exists:codes,id', // Валидация для codes_id
        ]);

        // Установка значений по умолчанию для флагов
        $use_tdr = $request->has('use_tdr');
        $use_process_form = $request->has('use_process_form');
        $use_log_card = $request->has('use_log_card');
        $use_extra_process_form = $request->has('use_extra_process_form');

//        dd($validated);
        // Сохранение в таблице tdrs
        Tdr::create([
            'workorder_id' => $request->workorder_id, // Получаем workorder_id из формы
            'component_id' => $validated['component_id'],
            'serial_number' => $validated['serial_number'],
            'assy_serial_number' => $validated['assy_serial_number'],
            'codes_id' => $validated['codes_id'],  // Обработка передачи codes_id
            'conditions_id' => $validated['conditions_id'],
            'necessaries_id' => $validated['necessaries_id'],
            'use_tdr' => $use_tdr,
            'use_process_form' => $use_process_form,
            'use_log_card' => $use_log_card,
            'use_extra_process_form' => $use_extra_process_form,
        ]);

        // Перенаправление на страницу с сообщением об успехе
        return redirect()->route('admin.tdrs.index')->with('success', 'TDR успешно добавлен!');
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
        $manuals = Manual::all();
        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $components = Component::with('manuals')->get();
//        $tdrs = Tdr::with('current_wo')->get(); // --- ? ---
        $tdrs = Tdr::where('workorder_id', $current_wo)->get(); // Фильтрация TDR по workorder_id

        return view('admin.tdrs.show', compact(  'current_wo','tdrs','units','components','user','customers','manuals','builders',
            'planes','instruction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $customers = Customer::all();
        $manuals = Manual::all();
        $planes = Plane::all();
        $builders = Builder::all();

        return view('admin.tdrs.edit', compact(  'current_wo','units','user','customers','manuals','builders','planes'));
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
