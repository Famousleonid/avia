<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Component;
use Illuminate\Http\Request;

class ComponentController extends Controller
{

    public function index()
    {
        $components = Component::all();

        return View('admin.component.index', compact('components'));
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        $answer = Component::destroy($id);

        return redirect()->route('component.index')->with('success', 'Component deleted successful.');
    }
}
