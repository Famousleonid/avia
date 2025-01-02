<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
//<<<<<<< HEAD
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UnitController extends Controller
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
        // Получаем все units и связанные с ними manuals
        $units = Unit::with('manuals')->get();

        // Проверка загруженных данных
        if ($units->isEmpty()) {
            // Если юнитов нет, возвращаем представление с сообщением
            return view('admin.units.index', [
                'message' => 'No units available at the moment.', // Сообщение о том, что юнитов нет
                'restManuals' => Manual::whereNotIn('id', [])->get(),
                'manuals' => Manual::all(),
                'planes' => Plane::pluck('type', 'id'),
                'builders' => Builder::pluck('name', 'id'),
                'scopes' => Scope::pluck('scope', 'id'),
                'groupedUnits' => collect() // Пустая коллекция
            ]);
        }
        // Если юниты есть, продолжаем обработку
        $manualIdsInUnits = $units->pluck('manuals_id')->toArray();

        // Если юниты есть, продолжаем обработку
//        $manualIdsInUnits = $units->pluck('manuals_id')->toArray();
        $groupedUnits = $units->groupBy(function ($unit) {
            return $unit->manuals ? $unit->manuals->number : 'No CMM';
        });

        // Подготовка общих данных для отображения в виде
        $restManuals = Manual::whereNotIn('id', $manualIdsInUnits)->get();
        $manuals = Manual::all();
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');

        // Передаем данные в представление
        return view('admin.units.index', compact('groupedUnits', 'restManuals', 'manuals', 'planes', 'builders', 'scopes'));
    }


    /**
     * Show the forms for creating a new resource.
     */
    public function create()
    {
        $manuals = Manual::all();
        $planes = Plane::all(); // Получить все объекты AirCraft
        $builders = Builder::all(); // Получить все объекты MFR
        $scopes = Scope::all(); // Получить все объекты Scope

        return view('admin.units.create', compact('manuals', 'planes', 'builders',
            'scopes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cmm_id' => 'required|exists:manuals,id',
            'part_numbers' => 'required|array',
            'part_numbers.*' => 'string|distinct',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->part_numbers as $partNumber) {
                Unit::create([
                    'manuals_id' => $request->cmm_id,
                    'part_number' => $partNumber,
                    'verified' => 1, // Устанавливаем verified в 1
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

//    public function storeWorkorder(Request $request)
//    {
//        // Валидация данных
//        $request->validate([
//            'manual_id' => 'required|exists:manuals,id',
//            'part_number' => 'required|string|distinct',
//        ]);
//
//        try {
//            // Сохранение нового юнита
//            $unit = Unit::create([
//                'manuals_id' => $request->manual_id,
//                'part_number' => $request->part_number,
//                'verified' => false,
//            ]);
//
//            return response()->json(['success' => true, 'id' => $unit->id, 'part_number' => $unit->part_number]);
//        } catch (\Exception $e) {
//            \Log::error('Error saving unit: ' . $e->getMessage());
//            return response()->json(['success' => false, 'error' => 'An error occurred while saving the unit.'], 500);
//        }
//    }

    public function toggleVerified(Request $request, Unit $unit)
    {
        try {
            $unit->verified = $request->input('verified');
            $unit->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error toggling verified status: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'An error occurred while updating verified status.'], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $manualId)
    {
        // Убедитесь, что вы правильно получаете юниты
        $units = Unit::where('manuals_id', $manualId)->get();

        // Возвращаем данные в формате JSON
        return response()->json(['units' => $units]);
    }

    /**
     * Show the forms for editing the specified resource.
     */
    public function edit($manualsId)
    {
        // Проверяем, что manual существует
        $manual = Manual::findOrFail($manualsId);

        // Получаем все units, связанные с данным manuals_id
        $units = Unit::where('manuals_id', $manualsId)->get();

        if ($units->isEmpty()) {
            return redirect()->back()->with('error', 'No units found for the selected manual.');
        }

        return view('admin.units.edit', compact('manual', 'units'));
    }



    public function update(Request $request, $manualId)
    {
        \Log::info('Request received for update:', [
            'manual_id' => $manualId,
            'units' => $request->input('units')
        ]);

        try {
            $manual = Manual::findOrFail($manualId);

            // Извлекаем входящие данные
            $units = $request->input('units', []);

            if (!is_array($units)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid units data format.'
                ], 400);
            }

            $existingPartNumbers = $manual->units()->pluck('part_number')->toArray();

            foreach ($units as $unitData) {
                if (!isset($unitData['part_number']) || !isset($unitData['verified'])) {
                    continue; // Пропускаем некорректные записи
                }

                Unit::updateOrCreate(
                    ['manuals_id' => $manualId, 'part_number' => $unitData['part_number']],
                    ['verified' => $unitData['verified']]
                );
            }

            $incomingPartNumbers = array_column($units, 'part_number');
            Unit::where('manuals_id', $manualId)
                ->whereNotIn('part_number', $incomingPartNumbers)
                ->delete();

            return response()->json([
                'success' => true,
                'updated_units' => $manual->units()->get()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating units:', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while updating units.'
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */



    public function destroy(string $manualId)
    {
        // Получаем мануал по полю 'number'
        $manual = Manual::where('number', $manualId)->first();

        // Если мануал найден, удаляем связанные юниты
        if ($manual) {
            // Удаляем все юниты, связанные с выбранным мануалом
            Unit::where('manuals_id', $manual->id)->delete();

            // Перенаправляем на индекс с сообщением об успешном удалении
            return redirect()->route('admin.units.index')->with('success', 'Все юниты успешно удалены.');
        }

        // Если мануал не найден, возвращаем ошибку
        return redirect()->route('admin.units.index')->with('error', 'Мануал не найден.');
    }
}
