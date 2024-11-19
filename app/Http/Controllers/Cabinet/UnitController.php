<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::all();

        if (Auth::user()->isAdmin()) {
            return view('admin.master')->with('content', view('admin.unit.index', compact('units')));
        } else {
            return view('cabinet.master')->with('content', view('admin.unit.index', compact('units')));
        }


        // return View('admin.unit.index', compact('units'));
    }

    public function create()
    {
        return view('cabinet.pages.create_unit');
    }

    public function store(Request $request)
    {
        $request->validate([
            'partnumber' => 'required|unique:units,partnumber,',
            'description' => 'required',
            'lib' => 'required',

        ]);

        $data = $request->all();
        Unit::create($data);

        return redirect()->route('workorder.create');


    }

    public function show($id)
    {
    }

    public function edit($id)
    {
    }

    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
        $answer = Unit::destroy($id);

        return redirect()->route('unit.index')->with('success', 'Unit deleted successful.');
    }
}
