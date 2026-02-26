<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Scope;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manuals.viewAny')->only('index');
        $this->middleware('can:manuals.view')->only('show');
        $this->middleware('can:manuals.create')->only(['create', 'store']);
        $this->middleware('can:manuals.update')->only(['edit', 'update']);
        $this->middleware('can:manuals.delete')->only('destroy');
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
//            return redirect()->route('.manuals.index')->with('success', 'Manual success created.');
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
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',

            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'eff_codes' => 'nullable|array',
            'eff_codes.*' => 'nullable|string|max:255',
            'csv_files' => 'nullable|array',
            'csv_files.*' => 'nullable|file|mimes:csv,txt|max:10240', // 10MB max
            'csv_process_types' => 'nullable|array',
            'csv_process_types.*' => 'nullable|in:ndt,cad,stress_relief,log,other',
        ]);

        DB::transaction(function () use ($request) {
            // Создаем новый CMM
            $manual = Manual::create($request->only([
                'number', 'title', 'revision_date', 'unit_name','unit_name_training','training_hours','ovh_life','reg_sb',
                'planes_id', 'builders_id', 'scopes_id', 'lib',
            ]));

            if ($request->hasFile('img')) {
                $manual->addMedia($request->file('img'))->toMediaCollection('manuals');
            }

            if ($request->hasFile('log_img')) {
                $manual->addMedia($request->file('log_img'))->toMediaCollection('manuals_log');
            }

            // Обрабатываем множественные CSV файлы
            if ($request->hasFile('csv_files') && $request->has('csv_process_types')) {
                $csvFiles = $request->file('csv_files');
                $processTypes = $request->input('csv_process_types', []);

                foreach ($csvFiles as $index => $file) {
                    if ($file && isset($processTypes[$index])) {
                        $media = $manual->addMedia($file)
                            ->toMediaCollection('csv_files');

                        $media->setCustomProperty('process_type', $processTypes[$index]);
                        $media->save();
                    }
                }
            }

            // Если есть юниты, добавляем их
            if ($request->has('units') && is_array($request->units)) {
                \Log::info('Creating units for new manual', [
                    'manual_id' => $manual->id,
                    'request_units' => $request->units,
                    'request_eff_codes' => $request->eff_codes
                ]);

                foreach ($request->units as $index => $partNumber) {
                    // Пропускаем пустые значения
                    if (empty(trim($partNumber))) {
                        continue;
                    }

                    $effCode = $request->eff_codes[$index] ?? '';
                    $newUnit = $manual->units()->create([
                        'part_number' => $partNumber,
                        'eff_code' => $effCode,
                        'manual_id' => $manual->id,
                        'verified' => 1,
                    ]);

                    \Log::info('Created new unit', [
                        'unit_id' => $newUnit->id,
                        'part_number' => $partNumber,
                        'eff_code' => $effCode
                    ]);
                }
            }
        });

        $message = 'CMM created successfully';
        if ($request->has('units') && is_array($request->units)) {
            $unitCount = count(array_filter($request->units, function($unit) {
                return !empty(trim($unit));
            }));
            if ($unitCount > 0) {
                $message .= " with {$unitCount} unit(s)";
            }
        }

        return redirect()->route('manuals.index')->with('success', $message);
    }



    public function show(string $id)
    {
        $cmm = Manual::findOrFail($id);

        $planes = Plane::all();
        $builders = Builder::all();
        $scopes = Scope::all();

//Components CMM
        $units = Unit::where('manual_id', $cmm->id)->get();

// Parts (sorted by IPL Number in natural order: 1-10, 1-20, 1-20A, 1-30, ...)
        $parts = Component::where('manual_id', $cmm->id)->get()->sortBy(function ($part) {
            $ipl = $part->ipl_num ?? '';

            // Ожидаемый формат: "1-10", "1-20A" и т.п.
            if (!preg_match('/^(\d+)-(\d+)([A-Za-z]?)$/', $ipl, $m)) {
                // Неизвестный формат отправляем в конец
                return PHP_INT_MAX;
            }

            $section = (int)$m[1];      // число до дефиса (1)
            $number = (int)$m[2];       // число после дефиса (10, 20, 100)
            $suffix = strtoupper($m[3] ?? ''); // суффикс A, B и т.п.

            // Без суффикса должны идти раньше, чем с суффиксом
            $suffixVal = $suffix === '' ? 0 : (ord($suffix) - 64); // A=1, B=2...

            // Строим общее числовое значение для сортировки
            return $section * 1_000_000 + $number * 100 + $suffixVal;
        })->values();


        // Processes: процессы руководства с подгруженным именем, сортировка по ProcessName (abc)
        $manualProcesses = ManualProcess::where('manual_id', $cmm->id)
            ->with(['process.process_name'])
            ->get()
            ->sortBy(function ($mp) {
                return $mp->process && $mp->process->process_name
                    ? $mp->process->process_name->name
                    : '';
            })
            ->values();

        return view('admin.manuals.show', compact('cmm','planes','builders','scopes',
        'units','parts','manualProcesses'
        ));

    }

    public function edit($id)
    {
        $cmm = Manual::with('units')->findOrFail($id);
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
            'ovh_life' => 'nullable',
            'reg_sb' => 'nullable',
            'planes_id' => 'required|exists:planes,id',
            'builders_id' => 'required|exists:builders,id',
            'scopes_id' => 'required|exists:scopes,id',
            'lib' => 'required',
            'units' => 'nullable|array',
            'units.*' => 'required|string|max:255',
            'eff_codes' => 'nullable|array',
            'eff_codes.*' => 'nullable|string|max:255',
            // CSV файлы теперь загружаются только через AJAX
            // 'csv_files.*' => 'nullable|file|mimes:csv,txt|max:10240', // 10MB max
            // 'process_type' => 'nullable|in:ndt,cad,stress_relief,other',
        ]);

        if ($request->hasFile('img')) {
            if ($cmm->getMedia('manuals')->isNotEmpty()) {
                $cmm->getMedia('manuals')->first()->delete();
            }
            $cmm->addMedia($request->file('img'))->toMediaCollection('manuals');
        }
        if ($request->hasFile('log_img')) {
            if ($cmm->getMedia('manuals_log')->isNotEmpty()) {
                $cmm->getMedia('manuals_log')->first()->delete();
            }
            $cmm->addMedia($request->file('log_img'))->toMediaCollection('manuals_log');
        }

        // CSV файлы теперь загружаются только через AJAX (ManualCsvController)
        // Удаляем обработку csv_files из основной формы

        $cmm->update($validatedData);

        // Обновляем units если они предоставлены
        if ($request->has('units') && is_array($request->units)) {
            $existingUnits = $cmm->units()->pluck('id')->toArray();
            $newUnits = [];

            \Log::info('Updating units for manual', [
                'manual_id' => $cmm->id,
                'existing_units' => $existingUnits,
                'request_units' => $request->units,
                'request_eff_codes' => $request->eff_codes
            ]);

            // Обрабатываем каждый unit
            foreach ($request->units as $index => $partNumber) {
                // Пропускаем пустые значения
                if (empty(trim($partNumber))) {
                    continue;
                }

                $effCode = $request->eff_codes[$index] ?? '';

                // Если у нас есть существующий unit с таким же part_number, обновляем его
                $existingUnit = $cmm->units()->where('part_number', $partNumber)->first();

                if ($existingUnit) {
                    $existingUnit->update([
                        'eff_code' => $effCode,
                    ]);
                    $newUnits[] = $existingUnit->id;
                    \Log::info('Updated existing unit', [
                        'unit_id' => $existingUnit->id,
                        'part_number' => $partNumber,
                        'eff_code' => $effCode
                    ]);
                } else {
                    // Создаем новый unit
                    $newUnit = $cmm->units()->create([
                        'part_number' => $partNumber,
                        'eff_code' => $effCode,
                        'manual_id' => $cmm->id,
                        'verified' => 1,
                    ]);
                    $newUnits[] = $newUnit->id;
                }
            }

            // Удаляем только те units, которые больше не используются
            $unitsToDelete = array_diff($existingUnits, $newUnits);
            if (!empty($unitsToDelete)) {


                // Проверяем, есть ли связанные workorders
                foreach ($unitsToDelete as $unitId) {
                    $unit = Unit::find($unitId);
                    if ($unit) {
                        $workorderCount = $unit->workorders()->count();

                        if ($workorderCount == 0) {
                            $unit->delete();
                        } else {
                        }
                    }
                }
            }
        }

        $message = 'Manual updated successfully';
        if ($request->has('units') && is_array($request->units)) {
            $unitCount = count(array_filter($request->units, function($unit) {
                return !empty(trim($unit));
            }));
            if ($unitCount > 0) {
                $message .= " with {$unitCount} unit(s)";
            }
        }

        return redirect()->route('manuals.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $cmm = Manual::findOrFail($id);
        if ($cmm->getMedia('manuals')->isNotEmpty()) {
            $cmm->getMedia('manuals')->first()->delete();
        }
        if ($cmm->getMedia('csv_files')->isNotEmpty()) {
            $cmm->getMedia('csv_files')->first()->delete();
        }
        $cmm->delete();

        return redirect()->route('manuals.index')->with('success', 'Manual deleted successfully');
    }
}


