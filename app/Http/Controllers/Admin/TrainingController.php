<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Training;
use App\Models\User;
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
     * New logic: first training date + list of subsequent dates; optional additional date if last > 360 days ago.
     */
    public function store(Request $request)
    {
        $userId = auth()->id();

        // Убираем пустые значения из списка дат
        $request->merge([
            'training_dates' => array_values(array_filter((array) $request->input('training_dates', []))),
        ]);

        $validatedData = $request->validate([
            'manuals_id' => 'required|exists:manuals,id',
            'date_training' => 'required|date|before_or_equal:today',
            'training_dates' => 'nullable|array',
            'training_dates.*' => 'nullable|date|after:date_training|before_or_equal:today',
            'additional_training_date' => 'nullable|date|after_or_equal:date_training|before_or_equal:today',
        ]);

        $manualId = (int) $validatedData['manuals_id'];
        $firstDate = \Carbon\Carbon::parse($validatedData['date_training'])->startOfDay();
        $trainingDates = $validatedData['training_dates'] ?? [];
        $trainingDates = array_unique(array_filter(array_map(function ($d) {
            return \Carbon\Carbon::parse($d)->format('Y-m-d');
        }, $trainingDates)));
        sort($trainingDates);

        $additionalDate = isset($validatedData['additional_training_date'])
            ? \Carbon\Carbon::parse($validatedData['additional_training_date'])->format('Y-m-d')
            : null;

        // Form 132 — одна на первую дату (если ещё нет для этого юнита)
        $existingForm132 = Training::where('user_id', $userId)
            ->where('manuals_id', $manualId)
            ->where('form_type', 132)
            ->first();

        if (!$existingForm132) {
            Training::create([
                'user_id' => $userId,
                'manuals_id' => $manualId,
                'date_training' => $firstDate->format('Y-m-d'),
                'form_type' => 132,
            ]);
        }

        $ensureTraining112 = function ($dateYmd) use ($userId, $manualId) {
            $exists = Training::where('user_id', $userId)
                ->where('manuals_id', $manualId)
                ->where('date_training', $dateYmd)
                ->where('form_type', '112')
                ->exists();
            if (!$exists) {
                Training::create([
                    'user_id' => $userId,
                    'manuals_id' => $manualId,
                    'date_training' => $dateYmd,
                    'form_type' => '112',
                ]);
            }
        };

        // Form 112 на «следующую пятницу» после первой даты
        $nextFriday = $firstDate->copy()->next(\Carbon\Carbon::FRIDAY);
        if ($nextFriday->lte(now())) {
            $ensureTraining112($nextFriday->format('Y-m-d'));
        }

        // Form 112 на каждую введённую последующую дату
        foreach ($trainingDates as $dateYmd) {
            $ensureTraining112($dateYmd);
        }

        // Дополнительная тренировка (если пользователь выбрал «Да» при последней > 360 дней)
        if ($additionalDate) {
            $ensureTraining112($additionalDate);
        }

        $returnUrl = $request->input('return_url');
        if ($returnUrl && (str_contains($returnUrl, '/tdrs/') || str_contains($returnUrl, '/mains/'))) {
            return redirect($returnUrl)->with('success', 'Unit added for trainings.');
        }

        $referer = request()->header('referer');
        if ($referer && (str_contains($referer, '/tdrs/') || str_contains($referer, '/mains/'))) {
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

        // Создаем тренировки за все годы начиная со следующего года после первой тренировки
        for ($year = $firstTrainingYear + 1; $year <= $currentYear; $year++) {
            // Для формы 112 используем ту же неделю, но в следующем году
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
        $monday = $firstJan->addDays($days);

        // Возвращаем пятницу той же недели
        return $monday->addDays(4);
    }

    /**
     * Создает недостающие тренировки между двумя датами
     */
    private function createMissingTrainingsBetweenDates($userId, $manualId, $firstTrainingDate, $lastTrainingDate)
    {
        $firstTraining = \Carbon\Carbon::parse($firstTrainingDate);
        $lastTraining = \Carbon\Carbon::parse($lastTrainingDate);
        $firstTrainingYear = $firstTraining->year;
        $firstTrainingWeek = $firstTraining->weekOfYear;
        $lastTrainingYear = $lastTraining->year;

        // Создаем тренировки за все годы начиная со следующего года после первой тренировки до года последнего тренинга
        for ($year = $firstTrainingYear + 1; $year <= $lastTrainingYear; $year++) {
            // Для формы 112 используем ту же неделю, но в следующем году
            $trainingDate = $this->getDateFromWeekAndYear($firstTrainingWeek, $year);

            // Проверяем, что дата тренировки не позже последнего тренинга
            if ($trainingDate <= $lastTraining) {
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
        $training = Training::findOrFail($id);
        $user = $training->user ?? User::find($training->user_id);
        $showImage = $request->query('showImage', 'false'); // Получаем параметр из запроса

        return view('admin.trainings.form112', compact('training', 'showImage','user'));
    }

    public function showForm132($id, Request $request)
    {

        $training = Training::findOrFail($id);
        $user = $training->user ?? User::find($training->user_id);
        $showImage = $request->query('showImage', 'false');


        return view('admin.trainings.form132', compact('training', 'showImage','user'));
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
     * Update the specified resource in storage (e.g. edit training date).
     */
    public function update(Request $request, string $id)
    {
        $training = Training::findOrFail($id);

        if ($training->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 403);
        }

        $validated = $request->validate([
            'date_training' => 'required|date|before_or_equal:today',
        ]);

        $training->date_training = \Carbon\Carbon::parse($validated['date_training'])->format('Y-m-d');
        $training->save();

        return response()->json([
            'success' => true,
            'message' => __('Training date updated.'),
        ]);
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

    /**
     * Display all trainings for all users
     */
    public function showAll()
    {
        try {
            // Инициализируем переменные по умолчанию
            $manuals = collect();
            $users = collect();
            $trainingDates = [];
            $error = null;
            
            // Получаем все manuals, где unit_name_training не пустое
            $manuals = Manual::whereNotNull('unit_name_training')
                ->where('unit_name_training', '<>', '')
                ->orderBy('title')
                ->get();

            // Получаем всех пользователей и сортируем по stamp
            // Сначала цифры (по возрастанию), потом буквы (по алфавиту)
            $users = User::whereNotNull('stamp')
                ->where('stamp', '<>', '')
                ->whereNull('deleted_at') // Исключаем удаленных пользователей
                ->get()
                ->sortBy(function ($user) {
                    $stamp = trim($user->stamp ?? '');
                    // Проверяем, начинается ли stamp с цифры
                    if (preg_match('/^\d+/', $stamp, $matches)) {
                        // Извлекаем числовую часть
                        $numericPart = (int)$matches[0];
                        // Для цифр используем числовую сортировку с дополнением нулями
                        return '0_' . str_pad($numericPart, 10, '0', STR_PAD_LEFT) . '_' . $stamp;
                    } else {
                        // Для букв используем алфавитную сортировку
                        return '1_' . strtoupper($stamp);
                    }
                })
                ->values();

            // Получаем все тренинги одним запросом для оптимизации
            $manualIds = $manuals->pluck('id')->toArray();
            $userIds = $users->pluck('id')->toArray();
            
            $trainings = collect();
            if (!empty($manualIds) && !empty($userIds)) {
                $trainings = Training::whereIn('manuals_id', $manualIds)
                    ->whereIn('user_id', $userIds)
                    ->orderBy('date_training', 'desc')
                    ->get();
            }

            // Группируем тренинги и находим последнюю дату для каждой комбинации manual + user
            $trainingDates = [];
            foreach ($trainings as $training) {
                $manualId = $training->manuals_id;
                $userId = $training->user_id;
                
                // Сохраняем только самую последнюю дату для каждой комбинации
                if (!isset($trainingDates[$manualId][$userId]) || 
                    $training->date_training > $trainingDates[$manualId][$userId]) {
                    $trainingDates[$manualId][$userId] = $training->date_training;
                }
            }

            return view('admin.trainings.show_all', compact('manuals', 'users', 'trainingDates', 'error'));
        } catch (\Exception $e) {
            \Log::error('Error in showAll: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            $manuals = collect();
            $users = collect();
            $trainingDates = [];
            $error = $e->getMessage();
            
            return view('admin.trainings.show_all', compact('manuals', 'users', 'trainingDates', 'error'));
        } catch (\Throwable $e) {
            \Log::error('Fatal error in showAll: ' . $e->getMessage());
            
            $manuals = collect();
            $users = collect();
            $trainingDates = [];
            $error = 'Fatal error: ' . $e->getMessage();
            
            return view('admin.trainings.show_all', compact('manuals', 'users', 'trainingDates', 'error'));
        }
    }
}
