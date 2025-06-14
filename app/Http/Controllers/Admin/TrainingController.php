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

        // Если тип формы не 132, создаем еще одну запись для формы 132
        if ($form_type != 132) {
            Training::create([
                'user_id' => $userId, // Текущий пользователь
                'manuals_id' => $validatedData['manuals_id'],
                'date_training' => $dateTraining132,
                'form_type' => 132,
            ]);
        }

        // Вычисление даты для формы 112
        $dateTraining112 = \Carbon\Carbon::parse($dateTraining132)
            ->next(\Carbon\Carbon::FRIDAY); // Находим следующую пятницу

        // Создаем запись для формы 112
        Training::create([
            'user_id' => $userId, // Текущий пользователь
            'manuals_id' => $validatedData['manuals_id'],
            'date_training' => $dateTraining112,
            'form_type' => 112,
        ]);

        return redirect()->route('trainings.index')->with('success', 'Unit added for trainings.');
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

            foreach ($validatedData['manuals_id'] as $key => $manualId) {
                Training::create([
                    'user_id' => $userId,
                    'manuals_id' => $manualId,
                    'date_training' => $validatedData['date_training'][$key],
                    'form_type' => $validatedData['form_type'][$key],
                ]);
            }

            return response()->json(['success' => true, 'message' => 'The trainings have been successfully created.']);
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
