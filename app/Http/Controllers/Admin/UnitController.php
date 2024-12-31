<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $units = Unit::with('manual')->get();

        return view('admin.units.index', compact('units'));

    }

    public function create()
    {
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

        return view('admin.manuals.create', compact('planes', 'builders', 'scopes'));
    }

    public function store(Request $request)
    {
        {
            $validatedData = $request->validate([
                'number' => 'required',
                'title' => 'required',
                'revision_date' => 'required',
                'unit_name' => 'nullable',
                'unit_name_training' => 'nullable',
                'training_hours' => 'nullable',

                'planes_id' => 'required|exists:planes,id',
                'builders_id' => 'required|exists:builders,id',
                'scopes_id' => 'required|exists:scopes,id',
                'lib' => 'required'
            ]);

            $manual = Manual::create($validatedData);

            if ($request->hasFile('img')) {
                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
            }

            return redirect()->route('admin.manuals.index')->with('success', 'Manual success created.');
        }
    }

    public function show(string $id)
    {
    }

    public function edit($id)
    {
        $cmm = Manual::findOrFail($id);
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

        return view('admin.manuals.edit', compact('cmm', 'planes', 'builders', 'scopes'));
    }

    public function update(Request $request, $id)
    {
        $cmm = Manual::findOrFail($id);

        $validatedData = $request->validate([
            'number' => 'required',
            'title' => 'required',
            'revision_date' => 'required',
            'unit_name' => 'nullable',
            'unit_name_training' => 'nullable',
            'training_hours' => 'nullable',
            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
        ]);

        if ($request->hasFile('img')) {
            if ($cmm->getMedia('manuals')->isNotEmpty()) {
                $cmm->getMedia('manuals')->first()->delete();
            }

            $cmm->addMedia($request->file('img'))->toMediaCollection('manuals');
        }

        $cmm->update($validatedData);

        return redirect()->route('admin.manuals.index')->with('success', 'Manual updated successfully');
    }

    public function destroy($id)
    {

        $cmm = Manual::findOrFail($id);
        if ($cmm->getMedia('manuals')->isNotEmpty()) {
            $cmm->getMedia('manuals')->first()->delete();
        }
        $cmm->delete();

        return redirect()->route('admin.manuals.index')->with('success', 'Manual deleted successfully');
    }
}


