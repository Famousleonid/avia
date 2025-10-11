<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
//    public function __construct()
//    {
//        $this->middleware('auth');
//    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Получаем тренировки пользователя с учётом руководства
        $trainingLists = auth()->user()->trainings()->with('manual')->get()->groupBy('manuals_id');

        // Обрабатываем группы тренировок для установки дат
        $formattedTrainingLists = [];
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');

        foreach ($trainingLists as $manualId => $trainings) {
            // Сортируем тренировки по дате
            $sortedTrainings = $trainings->sortBy('date_training');

            // Получаем самую раннюю и самую позднюю даты
            $firstTraining = $sortedTrainings->first();
            $lastTraining = $sortedTrainings->last();

            // Добавляем данные в массив
            $formattedTrainingLists[] = [
                'manuals_id' => $manualId,
                'first_training' => $firstTraining,
                'last_training' => $lastTraining,
                'trainings' => $sortedTrainings, // Добавляем все тренировки в группу
            ];

        }

        return view('admin.trainings.index', compact('formattedTrainingLists',
            'planes', 'builders', 'scopes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = auth()->id();
        $planes = Plane::pluck('type', 'id');

        // Получаем ID юнитов, которые уже добавлены для текущего пользователя
        $addedCmmIds = Training::where('user_id', $userId)->pluck('manuals_id');

        // Получаем юниты, которые:
        // 1. Не добавлены для текущего пользователя
        // 2. Имеют unit_name_training не NULL И не пустую строку
        $manuals = Manual::whereNotIn('id', $addedCmmIds)
            ->where(function($query) {
                $query->whereNotNull('unit_name_training')
                    ->where('unit_name_training', '<>', '');
            })
            ->get();

        return view('admin.trainings.create', compact('manuals', 'planes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Получение значения manual из запроса
        $manual = $request->input('manual');

        // Получение ID текущего пользователя
        $userId = auth()->id();
        $form_type = 112;

        // Выполнение поиска по таблице trainings
        if (Training::where('user_id', $userId)->where('manuals_id', $manual)->first()) {
            $form_type = 132;
        }

        // Валидация входных данных
        $validatedData = $request->validate([
            'manuals_id' => 'required',
            'date_training' => 'nullable|date'
        ]);

        // Устанавливаем дату тренировки для формы 132
        $dateTraining132 = $validatedData['date_training'];
        $manualId = $validatedData['manuals_id'];

        // Проверяем, есть ли уже форма 132 для этого юнита
        $existingForm132 = Training::where('user_id', $userId)
            ->where('manuals_id', $manualId)
            ->where('form_type', 132)
            ->first();

        // Если тип формы не 132, создаем еще одну запись для формы 132
        if ($form_type != 132 && !$existingForm132) {
            Training::create([
                'user_id' => $userId, // Текущий пользователь
                'manuals_id' => $manualId,
                'date_training' => $dateTraining132,
                'form_type' => 132,
            ]);
        }

        // Создаем тренировки за все пропущенные годы
        $this->createMissingTrainings($userId, $manualId, $dateTraining132);

        // Проверяем, есть ли URL для возврата в запросе
        $returnUrl = $request->input('return_url');
        
        // Если есть URL возврата и он содержит TDR, используем его
        if ($returnUrl && str_contains($returnUrl, '/tdrs/')) {
            return redirect($returnUrl)->with('success', 'Unit added for trainings.');
        }
        
        // Проверяем referer как fallback
        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/tdrs/')) {
            return redirect()->back()->with('success', 'Unit added for trainings.');
        }
        
        return redirect()->route('trainings.index')->with('success', 'Unit added for trainings.');
    }

    /**
     * Создает недостающие тренировки за все пропущенные годы
     */
    private function createMissingTrainings($userId, $manualId, $firstTrainingDate)
    {
        $firstTraining = \Carbon\Carbon::parse($firstTrainingDate);
        $firstTrainingYear = $firstTraining->year;
        $firstTrainingWeek = $firstTraining->weekOfYear;
        $currentYear = now()->year;
        $currentDate = now();

        // Создаем тренировки за все годы от первой тренировки до текущего года
        for ($year = $firstTrainingYear; $year <= $currentYear; $year++) {
            $trainingDate = $this->getDateFromWeekAndYear($firstTrainingWeek, $year);
            
            // Проверяем, что дата тренировки не в будущем
            if ($trainingDate <= $currentDate) {
                // Проверяем существование формы 112 для этого года
                $existingTraining112 = Training::where('user_id', $userId)
                    ->where('manuals_id', $manualId)
                    ->where('date_training', $trainingDate->format('Y-m-d'))
                    ->where('form_type', '112')
                    ->first();
                
                if (!$existingTraining112) {
                    Training::create([
                        'user_id' => $userId,
                        'manuals_id' => $manualId,
                        'date_training' => $trainingDate->format('Y-m-d'),
                        'form_type' => '112',
                    ]);
                }
            }
        }
    }

    /**
     * Получает дату из номера недели и года
     */
    private function getDateFromWeekAndYear($week, $year)
    {
        $firstJan = \Carbon\Carbon::create($year, 1, 1);
        $days = ($week - 1) * 7 - $firstJan->dayOfWeek + 1;
        return $firstJan->addDays($days);
    }

    public function createTraining(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'manuals_id.*' => 'required',
                'date_training.*' => 'required|date',
                'form_type.*' => 'required|in:112'
            ]);

            $userId = auth()->id();
            $createdCount = 0;
            $skippedCount = 0;

            // Проверяем, есть ли уже форма 132 для этого юнита
            $manualId = $validatedData['manuals_id'][0]; // Берем первый manual_id (они все одинаковые)
            $existingForm132 = Training::where('user_id', $userId)
                ->where('manuals_id', $manualId)
                ->where('form_type', '132')
                ->first();

            foreach ($validatedData['manuals_id'] as $key => $manualId) {
                $trainingDate = $validatedData['date_training'][$key];
                
                // Проверяем существование тренировки формы 112
                $existingTraining112 = Training::where('user_id', $userId)
                    ->where('manuals_id', $manualId)
                    ->where('date_training', $trainingDate)
                    ->where('form_type', '112')
                    ->first();
                
                if (!$existingTraining112) {
                    // Создаем тренировку формы 112
                    Training::create([
                        'user_id' => $userId,
                        'manuals_id' => $manualId,
                        'date_training' => $trainingDate,
                        'form_type' => '112',
                    ]);
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Создаем форму 132 только если её еще нет для этого юнита
            if (!$existingForm132) {
                // Берем дату первой тренировки для формы 132
                $firstTrainingDate = $validatedData['date_training'][0];
                
                Training::create([
                    'user_id' => $userId,
                    'manuals_id' => $manualId,
                    'date_training' => $firstTrainingDate,
                    'form_type' => '132',
                ]);
                $createdCount++;
            } else {
                $skippedCount++;
            }

            $message = "Created {$createdCount} new trainings";
            if ($skippedCount > 0) {
                $message .= ", skipped {$skippedCount} existing trainings";
            }
            
            // Добавляем информацию о форме 132
            if (!$existingForm132) {
                $message .= " (including Form 132)";
            } else {
                $message .= " (Form 132 already exists)";
            }

        return response()->json([
            'success' => true, 
            'message' => $message,
            'created' => $createdCount,
            'skipped' => $skippedCount
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
    }
}

/**
 * Обновляет тренировку на сегодняшнюю дату
 */
public function updateToToday(Request $request)
{
    try {
        $validatedData = $request->validate([
            'manuals_id.*' => 'required',
            'date_training.*' => 'required|date',
            'form_type.*' => 'required|in:112'
        ]);

        $userId = auth()->id();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($validatedData['manuals_id'] as $key => $manualId) {
            $trainingDate = $validatedData['date_training'][$key];
            
            // Проверяем существование тренировки формы 112 на сегодняшнюю дату
            $existingTraining112 = Training::where('user_id', $userId)
                ->where('manuals_id', $manualId)
                ->where('date_training', $trainingDate)
                ->where('form_type', '112')
                ->first();
            
            if (!$existingTraining112) {
                // Создаем тренировку формы 112 на сегодняшнюю дату
                Training::create([
                    'user_id' => $userId,
                    'manuals_id' => $manualId,
                    'date_training' => $trainingDate,
                    'form_type' => '112',
                ]);
                $createdCount++;
            } else {
                $skippedCount++;
            }
        }

        $message = "Updated training to today";
        if ($skippedCount > 0) {
            $message .= ", skipped {$skippedCount} existing training(s)";
        }

        return response()->json([
            'success' => true, 
            'message' => $message,
            'created' => $createdCount,
            'skipped' => $skippedCount
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
    }
}


    public function showForm112($id, Request $request)
    {
        $showImage = $request->query('showImage', 'false'); // Получаем параметр из запроса
        $training = Training::find($id);

        return view('admin.trainings.form112', compact('training', 'showImage'));
    }

    public function showForm132($id, Request $request)
    {
        $showImage = $request->query('showImage', 'false'); // Получаем параметр из запроса
        $training = Training::find($id);

        return view('admin.trainings.form132', compact('training', 'showImage'));
    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function deleteAll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'manual_id' => 'required|integer'
        ]);

        try {
            $deleted = Training::where('user_id', $request->user_id)
                ->where('manuals_id', $request->manual_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} training records"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
