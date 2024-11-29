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
            // Валидация входящих данных
            $validatedData = $request->validate([
                'number' => 'required',
                'title' => 'required',
                'img' => 'image|nullable',
                'revision_date' => 'required',
                'units_pn' => 'nullable',
                'units_tr' => 'nullable',
                'planes_id' => 'required|exists:planes,id',
                'builders_id' => 'required|exists:builders,id',
                'scopes_id' => 'required|exists:scopes,id',
                'lib' => 'required'
            ]);


            // Если изображение присутствует в запросе
            if ($request->hasFile('img')) {
                // Генерация уникального имени для файла изображения
                $imgName = time() . '.' . $request->img->getClientOriginalExtension();
                // Перемещение изображения в директорию storage/app/public/image/cmm
                $request->img->storeAs('image/cmm', $imgName, 'public');
                // Добавление имени файла изображения в массив данных для создания записи
                $validatedData['img'] = $imgName;
            }

            try {

                // Создание новой записи в базе данных
                Manual::create($validatedData);
                // Перенаправление пользователя на страницу со списком CMM с сообщением об успешном создании
                return redirect()->route('admin.manuals.index')->with('success', 'Инструкция успешно создана.');
            } catch (\Exception $e) {
                // Обработка ошибки при вставке данных в базу данных
                return redirect()->back()->withInput()->withErrors(['error' => 'Не удалось создать инструкцию: ' . $e->getMessage()]);
            }
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

        return view('admin.manuals.edit', compact('cmm', 'planes', 'builders', 'scopes'));
    }


// Update method
    public function update(Request $request, $id)
    {
        $cmm = Manual::findOrFail($id);
        $validatedData = $request->validate([
            'number' => 'required',
            'title' => 'required',
            'img' => 'image|nullable',
            'revision_date' => 'required',
            'units_pn' => 'nullable',
            'units_tr' => 'nullable',
            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
        ]);

        if ($request->hasFile('img')) {
            // Если загружено новое изображение, удаляем старое, если оно существует
            if ($cmm->img) {
                Storage::disk('public')->delete('image/cmm/' . $cmm->img);
            }

            // Генерируем уникальное имя для изображения
            $imgName = time() . '.' . $request->img->getClientOriginalExtension();
            // Сохраняем изображение в указанной директории
            $request->img->storeAs('image/cmm', $imgName, 'public');
            // Добавляем имя изображения в массив данных для валидации
            $validatedData['img'] = $imgName;
        }

        // Обновляем запись в модели с использованием валидированных данных
        $cmm->update($validatedData);

        return redirect()->route('admin.manuals.index')->with('success', 'Manual updated successfully');
    }


// Destroy method
    public function destroy($id)
    {
        $cmm = Manual::findOrFail($id);
        $cmm->delete();
        return redirect()->route('admin.manuals.index')->with('success', 'Manual deleted successfully');
    }
}


