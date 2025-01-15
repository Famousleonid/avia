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
;
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
        $current_components = Component::find($id);
        $manuals = Manual::all();
        return view('admin.components.edit', compact('current_components','manuals'));

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