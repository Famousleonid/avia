<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $cmms = Manual::with(['plane', 'builder', 'scope'])->get(); // Загружаем
        // связанные модели
        return view('admin.manuals.index', compact('cmms'));

    }

    /**
     * Show the forms for creating a new resource.
     */
    public function create()
    {
        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

        return view('admin.manuals.create', compact('planes', 'builders', 'scopes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        {
            // dd($request);

            $validatedData = $request->validate([
                'number' => 'required',
                'title' => 'required',
                'revision_date' => 'required',
                'unit_name'=> 'nullable',
                'unit_name_training'=> 'nullable',
                'training_hours'=> 'nullable',

                'planes_id' => 'required|exists:planes,id',
                'builders_id' => 'required|exists:builders,id',
                'scopes_id' => 'required|exists:scopes,id',
                'lib' => 'required'
            ]);

            $manual = Manual::create($validatedData);

            if ($request->hasFile('img')) {
                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
            }

                return redirect()->route('manuals.index')->with('success', 'Manual success created.');

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the forms for editing the specified resource.
     */

    // Edit method
    public function edit($id)
    {
        $cmm = Manual::findOrFail($id);
        $planes = Plane::all(); // Получаем все записи из таблицы AirCraft
        $builders = Builder::all(); // Получаем все записи из таблицы MFR
        $scopes = Scope::all(); // Получаем все записи из таблицы Scope

        return view('admin.manuals.edit', compact('cmm', 'planes', 'builders',
            'scopes'));
    }


// Update method
    public function update(Request $request, $id)
    {
        $cmm = Manual::findOrFail($id);

        $validatedData = $request->validate([
            'number' => 'required',
            'title' => 'required',
            'revision_date' => 'required',

            'unit_name'=> 'nullable',
            'unit_name_training'=> 'nullable',
            'training_hours'=> 'nullable',

            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
        ]);

        // Если загружено новое изображение
        if ($request->hasFile('img')) {
            // Удаляем старое изображение из медиаколлекции, если оно существует
            if ($cmm->getMedia('manuals')->isNotEmpty()) {
                $cmm->getMedia('manuals')->first()->delete();
            }

            // Добавляем новое изображение в медиаколлекцию
            $cmm->addMedia($request->file('img'))->toMediaCollection('manuals');
        }

        // Обновляем остальные данные вручную
        $cmm->update($validatedData);

        return redirect()->route('manuals.index')->with('success', 'Manual updated successfully');
    }



// Destroy method
    public function destroy($id)
    {
        $cmm = Manual::findOrFail($id);
        $cmm->delete();
        return redirect()->route('manuals.index')->with('success', 'Manual deleted successfully');
    }
}


