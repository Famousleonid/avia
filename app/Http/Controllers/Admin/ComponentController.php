<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $components = Component::all();
        $manuals = Manual::all();
        return view('admin.components.index', compact('components','manuals'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $components = Component::all();
        $manuals = Manual::all();
        return view('admin.components.create', compact('components','manuals'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//dd($request);

        $validated = $request->validate([

            'name' => 'required|string|max:250',
            'manual_id' => 'required|exists:manuals,id',
            'part_number' =>'required|string|max:50',
            'ipl_num' =>'string|max:10',

        ]);

        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['assy_ipl_num'] = $request->assy_ipl_num;

//dd($validated);
        $component = Component::create($validated);


        if ($request->hasFile('img')) {
            $component->addMedia($request->file('img'))->toMediaCollection('component');
        }
        if ($request->hasFile('assy_img')) {

            $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
        }

        return redirect()->route('admin.components.index')->with('success', 'Component created successfully.');

    }


    public function storeFromInspection(Request $request)
    {
//            dd($request);
            $current_wo = $request->current_wo;
//                dd($current_wo);
        try {
            // Валидация данных
            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'manual_id' => 'required|exists:manuals,id',
                'part_number' => 'required|string|max:50',
                'ipl_num' => 'nullable|string|max:10',
//                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//                'assy_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $validated['assy_ipl_num'] = $request->assy_ipl_num;
            $validated['assy_part_number'] = $request->assy_part_number;


            // Создание нового компонента
            $component = Component::create($validated);

            // Добавление изображений, если они есть
            if ($request->hasFile('img')) {
                $component->addMedia($request->file('img'))->toMediaCollection('component');
            }

            if ($request->hasFile('assy_img')) {
                $component->addMedia($request->file('assy_img'))->toMediaCollection('assy_component');
            }

            // Возвращаем успешный ответ с данными компонента
//            return response()->json([
//                'success' => true,
//                'component' => $component
//            ]);
            return redirect()->route('admin.tdrs.inspection.component',['workorder_id' => $current_wo])->with('success', 'Component created successfully.');

        } catch (\Exception $e) {
            // Логирование ошибки
            \Log::error('Error creating component: ' . $e->getMessage());

            // Возвращаем ошибку на фронт
            return response()->json([
                'success' => false,
                'message' => 'Error occurred while adding the component. Please try again.'
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $current_component = Component::find($id);
        $manuals = Manual::all();

        return view('admin.components.edit', compact('current_component','manuals'));

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


        $component = Component::findOrFail($id);


//        dd( $request->all());

        $validated = $request->validate([

            'name' => 'required',
            'manual_id' => 'required|exists:manuals,id',
            'part_number' =>'required',
            'ipl_num' =>'required',

        ]);


        $validated['assy_part_number'] = $request->assy_part_number;
        $validated['assy_ipl_num'] = $request->assy_ipl_num;

//        dd($validated);

        if ($request->hasFile('img')) {
            if ($component->getMedia('component')->isNotEmpty()) {
                $component->getMedia('component')->first()->delete();
            }

            $component->addMedia($request->file('img'))->toMediaCollection('component');
        }
        if ($request->hasFile('assy_img')) {
            if ($component->getMedia('assy_component')->isNotEmpty()) {
                $component->getMedia('assy_component')->first()->delete();
            }

            $component->addMedia($request->file('assy_img'))->toMediaCollection
            ('assy_component');
        }
        $component->update($validated);

        return redirect()->route('admin.components.index')->with('success', 'Manual updated successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $component = Component::findOrFail($id);
        $component->delete();

        return redirect()->route('admin.components.index')
            ->with('success', 'Компонент успешно удален.');
    }

}
