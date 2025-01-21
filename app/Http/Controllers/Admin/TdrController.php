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

        $codes = Code::all();
        $necessaries = Necessary::all();
        $conditions = Condition::all();


        // Отправляем данные в представление
        return view('admin.tdrs.inspection', compact(
            'current_wo', 'manual_id',
            'manuals', 'components', 'units', 'user', 'customers',
            'planes', 'builders', 'instruction',
            'codes','necessaries','conditions',
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
//        dd($request->all());

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
        $manuals = Manual::all();
        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $components = Component::with('manuals')->get();

        $tdrs =Tdr::where('current_wo');
//        $tdrs = Tdr::with('current_wo')->get(); // --- ? ---

        return view('admin.tdrs.show', compact(  'current_wo','tdrs','units','components','user','customers',
        'manuals','builders',
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
