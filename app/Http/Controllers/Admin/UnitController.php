<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;


class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Получаем все units и связанные с ними manuals
        $units = Unit::with('manual')->get();
        $units_all = $units;

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
        $manualIdsInUnits = $units->pluck('manual_id')->toArray();
        $groupedUnits = $units->groupBy(function ($unit) {
            return $unit->manuals ? $unit->manuals->number : 'No CMM';
        });

        // Подготовка общих данных для отображения в виде
        $restManuals = Manual::whereNotIn('id', $manualIdsInUnits)->get();
        $manuals = Manual::all();
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');

        return view('admin.units.index', compact('groupedUnits', 'restManuals', 'manuals', 'planes', 'builders', 'scopes','units_all'));
    }


    public function store(Request $request): JsonResponse
    {
        try {
            if (!$request->ajax()) {
                Log::channel('avia')->warning('Non-AJAX request to UnitController@store');
                return response()->json(['error' => 'Invalid request type'], 400);
            }

            Log::channel('avia')->info('Unit store raw payload', $request->all());

            // Ветка 1: фронт прислал ОДИН юнит (manual_id + part_number)
            if ($request->has(['manual_id', 'part_number'])) {
                $data = $request->validate([
                    'manual_id'   => 'required|exists:manuals,id',
                    'part_number' => 'required|string|max:255',
                    'eff_code'    => 'nullable|string|max:255',
                ]);

                $unit = Unit::create([
                    'manual_id'   => $data['manual_id'],
                    'part_number' => $data['part_number'],
                    'eff_code'    => $data['eff_code'] ?? null,
                    'verified'    => false,
                ]);

                Log::channel('avia')->info('Unit created (single)', ['id' => $unit->id]);

                // Отдаём то, что ожидает фронт при добавлении опции в селект
                return response()->json([
                    'id'           => $unit->id,
                    'part_number'  => $unit->part_number,
                    'manual_title' => optional($unit->manual)->title,
                ], 201);
            }

            // Ветка 2: батч-формат (cmm_id + units[])
            $validated = $request->validate([
                'cmm_id'            => 'required|exists:manuals,id',
                'units'             => 'required|array|min:1',
                'units.*.part_number' => 'required|string|max:255',
                'units.*.eff_code'    => 'nullable|string|max:255',
            ]);

            Log::channel('avia')->info('Unit batch validated', $validated);

            $createdUnits = [];
            foreach ($validated['units'] as $unitData) {
                $createdUnits[] = Unit::create([
                    'part_number' => $unitData['part_number'],
                    'manual_id'   => $validated['cmm_id'],
                    'eff_code'    => $unitData['eff_code'] ?? null,
                    'verified'    => false,
                ]);
            }

            Log::channel('avia')->info('Units created (batch)', [
                'count'     => count($createdUnits),
                'manual_id' => $validated['cmm_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => count($createdUnits) . ' unit(s) created successfully',
                'units'   => $createdUnits,
            ], 201);

        } catch (Throwable $e) {
            Log::channel('avia')->error('Unit store exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(string $manualId)
    {
        $units = Unit::where('manual_id', $manualId)->get();
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
        $units = Unit::where('manual_id', $manualsId)->get();

        if ($units->isEmpty()) {
            return redirect()->back()->with('error', 'No units found for the selected manual.');
        }

        return view('admin.units.edit', compact('manual', 'units'));
    }

    public function getUnitsByManual($manualId)
    {
        $units = Unit::where('manual_id', $manualId)->get();

        return response()->json([
            'units' => $units,
        ]);
    }

    public function update($manualId, Request $request)
    {

        try {
            $manual = Manual::findOrFail($manualId);

            if (!$request->has('part_numbers') || !is_array($request->input('part_numbers'))) {
                return response()->json(['success' => false, 'error' => 'Invalid part_numbers format'], 400);
            }

            $newPartNumbersArray = array_map(function ($unit) {
                return $unit['part_number'];
            }, $request->input('part_numbers'));

            $existingPartNumbers = $manual->units()->pluck('part_number')->toArray();

            Unit::where('manual_id', $manualId)
                ->whereNotIn('part_number', $newPartNumbersArray)
                ->delete();

            foreach ($request->input('part_numbers') as $unit) {

                $result = Unit::updateOrCreate(
                    ['manual_id' => $manualId, 'part_number' => $unit['part_number']],
                    [
                        'verified' => $unit['verified'],
                        'eff_code' => $unit['eff_code'] ?? null
                    ]
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'error' => 'An error occurred while updating units'], 500);
        }
    }





    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $manualId)
    {
        // Получаем мануал по полю 'number'
        $manual = Manual::where('number', $manualId)->first();

        // Если мануал найден, удаляем связанные юниты
        if ($manual) {
            // Удаляем все юниты, связанные с выбранным мануалом
            Unit::where('manual_id', $manual->id)->delete();

            // Перенаправляем на индекс с сообщением об успешном удалении
            return redirect()->route('units.index')->with('success', 'Все юниты успешно удалены.');
        }

        // Если мануал не найден, возвращаем ошибку
        return redirect()->route('units.index')->with('error', 'Мануал не найден.');
    }



}
