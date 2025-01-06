<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $cmms = Manual::with(['plane', 'builder', 'scope'])->get();

        return view('admin.manuals.index', compact('cmms'));

    }

    public function create()
    {
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

        return view('admin.manuals.create', compact('planes', 'builders', 'scopes'));
    }

//    public function store(Request $request)
//    {
//        {
//            $validatedData = $request->validate([
//                'number' => 'required',
//                'title' => 'required',
//                'revision_date' => 'required',
//                'unit_name' => 'nullable',
//                'unit_name_training' => 'nullable',
//                'training_hours' => 'nullable',
//
//                'planes_id' => 'required|exists:planes,id',
//                'builders_id' => 'required|exists:builders,id',
//                'scopes_id' => 'required|exists:scopes,id',
//                'lib' => 'required'
//
//            ]);
//
//            $manual = Manual::create($validatedData);
//
//            if ($request->hasFile('img')) {
//                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
//            }
//
//            return redirect()->route('admin.manuals.index')->with('success', 'Manual success created.');
//        }
//    }
    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'revision_date' => 'required|date',
            'unit_name' => 'nullable',
                'unit_name_training' => 'nullable',
                'training_hours' => 'nullable',

                'planes_id' => 'required|exists:planes,id',
                'builders_id' => 'required|exists:builders,id',
                'scopes_id' => 'required|exists:scopes,id',
                'lib' => 'required',

            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255', // Валидация для юнитов
        ]);

        DB::transaction(function () use ($request) {
            // Создаем новый CMM
            $manual = Manual::create($request->only([
                'number', 'title', 'revision_date', 'unit_name','unit_name_training','training_hours',
                'planes_id', 'builders_id', 'scopes_id', 'lib',
            ]));


            if ($request->hasFile('img')) {
                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
            }
            // Если есть юниты, добавляем их
            if ($request->has('units')) {
                foreach ($request->units as $partNumber) {
                    $manual->units()->create([
                        'part_number' => $partNumber,
                        'manual_id' => $manual->id,
                        'verified' => 1, // По умолчанию устанавливаем verified = 1
                    ]);
                }
            }
        });


        return redirect()->route('admin.manuals.index')->with('success', 'CMM created successfully along with units!');
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


