<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Scope;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\TrainingMatrixRow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    /**
     * Любая введённая дата определяет НЕДЕЛЮ обучения: в БД и на формах
     * (112 и 132) дата хранится пятницей — формы печатают период Пн–Пт
     * и подпись пятницей. Будущей пятница быть не может: если пятница
     * недели ещё не наступила (сегодня вторник) — берётся ПРОШЕДШАЯ.
     */
    private function fridayOf($date): string
    {
        $friday = \Carbon\Carbon::parse($date)
            ->startOfWeek(\Carbon\Carbon::MONDAY)
            ->addDays(4)
            ->startOfDay();

        if ($friday->isAfter(\Carbon\Carbon::today())) {
            $friday->subWeek();
        }

        return $friday->format('Y-m-d');
    }


    public function index(Request $request)
    {
        $user = auth()->user();
        $canViewAllUsers = $user->roleIs(['Admin', 'Manager']);

        // Для Admin/Manager: разрешить выбор user_id через query
        $selectedUserId = $user->id;
        if ($canViewAllUsers && $request->filled('user_id')) {
            $requestedUserId = (int) $request->user_id;
            if (User::where('id', $requestedUserId)->exists()) {
                $selectedUserId = $requestedUserId;
            }
        }

        $trainingLists = Training::where('user_id', $selectedUserId)
            ->where('is_legacy', false) // legacy («Old training», X в матрице) — не события, форм у них нет
            ->whereNull('matrix_row_id') // тренинги SCA-курсов живут в матрице, не здесь
            ->with(['manual', 'approvedBy'])
            ->get()
            ->groupBy('manuals_id');

        // Обрабатываем группы тренировок для установки дат
        $formattedTrainingLists = [];
        $planes = Plane::pluck('type', 'id');
        $builders = Builder::pluck('name', 'id');
        $scopes = Scope::pluck('scope', 'id');
        $renewalThresholdDays = (int) config('trainings.renewal_threshold_days', 180);

        foreach ($trainingLists as $manualId => $trainings) {
            $sortedTrainings = $trainings->sortBy('date_training');
            $firstTraining = $sortedTrainings->first();
            $form132 = $sortedTrainings->firstWhere('form_type', '132')
                ?? $sortedTrainings->firstWhere('form_type', 132);
            $trainings112 = $sortedTrainings->filter(function ($training) {
                return (string) $training->form_type === '112';
            })->values();
            $lastTraining112 = $trainings112->last();
            $daysSinceLastTraining112 = $lastTraining112
                ? \Carbon\Carbon::parse($lastTraining112->date_training)->diffInDays(\Carbon\Carbon::now())
                : null;
            $isDueForUpdate = $lastTraining112
                ? $daysSinceLastTraining112 >= $renewalThresholdDays
                : true;

            $formattedTrainingLists[] = [
                'manuals_id' => $manualId,
                'first_training' => $firstTraining,
                'form_132' => $form132,
                'last_training_112' => $lastTraining112,
                'days_since_last_training_112' => $daysSinceLastTraining112,
                'is_due_for_update' => $isDueForUpdate,
                // В модалке 132 всегда первая (перевыпущенная не должна тонуть в списке дат)
                'trainings' => $sortedTrainings->sortBy([
                    fn ($a, $b) => (int) ((string) $b->form_type === '132') <=> (int) ((string) $a->form_type === '132'),
                    fn ($a, $b) => strcmp((string) $a->date_training, (string) $b->date_training),
                ])->values(),
            ];
        }

        $users = collect();
        if ($canViewAllUsers) {
            // Порядок как в матрице: числовые stamp по возрастанию, затем буквенные
            $users = User::whereNotNull('stamp')
                ->where('stamp', '<>', '')
                ->whereNull('deleted_at')
                ->get()
                ->sortBy(fn (User $user) => ctype_digit($user->stamp)
                    ? sprintf('0-%05d', (int) $user->stamp)
                    : '1-' . mb_strtolower($user->stamp))
                ->values();
        }

        return view('admin.trainings.index', compact(
            'formattedTrainingLists',
            'planes',
            'builders',
            'scopes',
            'users',
            'selectedUserId',
            'canViewAllUsers',
            'renewalThresholdDays'
        ));
    }

    public function create(Request $request)
    {
        $userId = $this->resolveTargetUserId($request);

        $planes = Plane::pluck('type', 'id');
        $addedCmmIds = Training::where('user_id', $userId)->pluck('manuals_id');

        // Получаем юниты, которые:
        // 1. Не добавлены для текущего пользователя
        // 2. Имеют unit_name_training не NULL И не пустую строку
        $manuals = Manual::whereNotIn('id', $addedCmmIds)
            ->where(function ($query) {
                $query->whereNotNull('unit_name_training')
                    ->where('unit_name_training', '<>', '');
            })
            ->get();

        return view('admin.trainings.create', compact('manuals', 'planes', 'userId'));
    }

    /**
     * Чей тренинг добавляем: себе — всегда; чужой user_id — Admin/Manager-с-SCA
     * любому, Team Leader — участникам своей team.
     */
    private function resolveTargetUserId(Request $request): int
    {
        $userId = auth()->id();
        if ($request->filled('user_id')) {
            $target = User::find((int) $request->user_id);
            if ($target && auth()->user()->canManageTrainingsFor($target)) {
                $userId = $target->id;
            }
        }

        return $userId;
    }

    public function store(Request $request)
    {
        $userId = $this->resolveTargetUserId($request);

        // «Old training»: обучение было до появления системы, дата неизвестна.
        // Пишутся legacy-записи без дат и без форм 112/132; в матрице — «X».
        if ($request->boolean('is_legacy')) {
            if (!auth()->user()->canManageTrainingMatrix()) {
                abort(403);
            }

            $data = $request->validate([
                'legacy_manuals_ids' => 'required|array|min:1',
                'legacy_manuals_ids.*' => 'integer|exists:manuals,id',
                'create_form_132' => 'nullable|boolean',
                'form_132_date' => 'nullable|date|before_or_equal:today|required_if:create_form_132,1',
            ]);

            // Бланк 132 потерян / нужен перевыпуск — создаём 132 вместе с отметкой
            $withForm132 = $request->boolean('create_form_132');
            $form132Date = $withForm132 ? $this->fridayOf($data['form_132_date']) : null;

            $created = 0;
            foreach ($data['legacy_manuals_ids'] as $manualId) {
                $exists = Training::where('user_id', $userId)
                    ->where('manuals_id', $manualId)
                    ->exists();
                if (!$exists) {
                    Training::create([
                        'user_id' => $userId,
                        'manuals_id' => $manualId,
                        'date_training' => null,
                        'form_type' => null,
                        'is_legacy' => true,
                    ]);
                    if ($withForm132) {
                        Training::create([
                            'user_id' => $userId,
                            'manuals_id' => $manualId,
                            'date_training' => $form132Date,
                            'form_type' => 132,
                        ]);
                    }
                    $created++;
                }
            }

            $indexParams = $userId !== auth()->id() ? ['user_id' => $userId] : [];

            return redirect()->route('trainings.index', $indexParams)
                ->with('success', "Old training marked for {$created} unit(s)." . ($withForm132 ? ' Form 132 created.' : ''));
        }

        $request->merge([
            'training_dates' => array_values(array_filter((array)$request->input('training_dates', []))),
        ]);

        $validatedData = $request->validate([
            'manuals_id' => 'required|exists:manuals,id',
            'date_training' => 'required|date|before_or_equal:today',
            'training_dates' => 'nullable|array',
            'training_dates.*' => 'nullable|date|after:date_training|before_or_equal:today',
            'additional_training_date' => 'nullable|date|after_or_equal:date_training|before_or_equal:today',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $manualId = (int)$validatedData['manuals_id'];
        // даты нормализуются к пятнице своей недели (см. fridayOf)
        $firstDate = \Carbon\Carbon::parse($this->fridayOf($validatedData['date_training']));
        $trainingDates = $validatedData['training_dates'] ?? [];
        $trainingDates = array_unique(array_filter(array_map(function ($d) {
            return $this->fridayOf($d);
        }, $trainingDates)));
        sort($trainingDates);

        $additionalDate = isset($validatedData['additional_training_date'])
            ? $this->fridayOf($validatedData['additional_training_date'])
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

        // Первый тренинг = 132 + 112 на ОДНУ пятницу (неделя первой даты)
        $ensureTraining112($firstDate->format('Y-m-d'));

        // Form 112 на каждую введённую последующую дату
        foreach ($trainingDates as $dateYmd) {
            $ensureTraining112($dateYmd);
        }

        // Дополнительная тренировка (если пользователь выбрал «Да» при последней > 360 дней)
        if ($additionalDate) {
            $ensureTraining112($additionalDate);
        }

        // Записи создаются ТОЛЬКО по фактическим датам: догенерация годовых 112
        // за пропущенные годы удалена сознательно (решение от 21.08.2026).

        $returnUrl = $request->input('return_url');
        if ($returnUrl && (str_contains($returnUrl, '/tdrs/') || str_contains($returnUrl, '/mains/') || str_contains($returnUrl, '/trainings/show-all'))) {
            return redirect($returnUrl)->with('success', 'Unit added for trainings.');
        }

        $referer = request()->header('referer');
        if ($referer && (str_contains($referer, '/tdrs/') || str_contains($referer, '/mains/'))) {
            return redirect()->back()->with('success', 'Unit added for trainings.');
        }

        $indexParams = $userId !== auth()->id() ? ['user_id' => $userId] : [];
        return redirect()->route('trainings.index', $indexParams)->with('success', 'Unit added for trainings.');
    }

    public function createTraining(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'manuals_id.*' => 'required',
                'date_training.*' => 'required|date',
                'form_type.*' => 'required|in:112',
                'user_id' => 'nullable|integer|exists:users,id',
                'create_form_132' => 'nullable|boolean',
            ]);

            $userId = $this->resolveTargetUserId($request);
            $createdCount = 0;
            $skippedCount = 0;

            // Проверяем, есть ли уже форма 132 для этого юнита
            $manualId = $validatedData['manuals_id'][0]; // Берем первый manual_id (они все одинаковые)
            $existingForm132 = Training::where('user_id', $userId)
                ->where('manuals_id', $manualId)
                ->where('form_type', '132')
                ->first();

            // Пара с «Old training» (X): первичное обучение было в бумажную эпоху,
            // 132 не создаём — новая дата это REFRESH-112 (MP-20). Исключение —
            // явный запрос Admin/Manager (бланк потерян / нужен перевыпуск).
            $hasLegacy = Training::where('user_id', $userId)
                ->where('manuals_id', $manualId)
                ->where('is_legacy', true)
                ->exists();
            $forceForm132 = $request->boolean('create_form_132')
                && auth()->user()->canManageTrainingMatrix();

            foreach ($validatedData['manuals_id'] as $key => $manualId) {
                $trainingDate = $this->fridayOf($validatedData['date_training'][$key]);

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
            if (!$existingForm132 && (!$hasLegacy || $forceForm132)) {
                // Берем дату первой тренировки для формы 132
                $firstTrainingDate = $this->fridayOf($validatedData['date_training'][0]);

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
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function exists(Request $request)
    {
        $data = $request->validate([
            'manual_id' => ['required', 'integer'],
        ]);

        $userId = Auth::id();
        $manualId = (int)$data['manual_id'];

        $exists = Training::query()
            ->where('user_id', $userId)
            ->where('manuals_id', $manualId)
            ->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
        ]);
    }

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
                $trainingDate = $this->fridayOf($validatedData['date_training'][$key]);

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
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function showForm112($id, Request $request)
    {
        $training = Training::findOrFail($id);
        $user = $training->user ?? User::find($training->user_id);
        $showImage = $request->query('showImage', 'false'); // Получаем параметр из запроса

        return view('admin.trainings.form112', compact('training', 'showImage', 'user'));
    }

    public function showForm132($id, Request $request)
    {

        $training = Training::findOrFail($id);
        $user = $training->user ?? User::find($training->user_id);
        $showImage = $request->query('showImage', 'false');


        return view('admin.trainings.form132', compact('training', 'showImage', 'user'));
    }

    /** Принять тренинг (одну запись). SCA-Manager/Admin или назначенный. */
    public function approve(string $id)
    {
        abort_unless(auth()->user()->canApproveTrainings(), 403);

        $training = Training::findOrFail($id);
        if (!$training->isApproved()) {
            $training->update(['approved_by' => auth()->id(), 'approved_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /** Принять весь юнит (или курс): все записи пары разом. */
    public function approveUnit(Request $request)
    {
        abort_unless(auth()->user()->canApproveTrainings(), 403);

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'manual_id' => 'nullable|required_without:matrix_row_id|integer|exists:manuals,id',
            'matrix_row_id' => 'nullable|required_without:manual_id|integer|exists:training_matrix_rows,id',
        ]);

        $count = Training::where('user_id', $data['user_id'])
            ->when(isset($data['matrix_row_id']),
                fn ($q) => $q->where('matrix_row_id', $data['matrix_row_id']),
                fn ($q) => $q->where('manuals_id', $data['manual_id'])->whereNull('matrix_row_id'))
            ->whereNull('approved_by')
            ->update(['approved_by' => auth()->id(), 'approved_at' => now()]);

        return response()->json(['success' => true, 'approved' => $count]);
    }

    /**
     * История пары (юнит+сотрудник или курс+сотрудник) для модалки матрицы:
     * список дат со статусами приёмки. Только просмотр; approve — отдельными
     * эндпоинтами, кнопки рендерятся по can_approve.
     */
    public function matrixPairHistory(Request $request)
    {
        $viewer = auth()->user();
        abort_unless($viewer->canViewTrainingMatrix(), 403);

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'manual_id' => 'nullable|required_without:matrix_row_id|integer|exists:manuals,id',
            'matrix_row_id' => 'nullable|required_without:manual_id|integer|exists:training_matrix_rows,id',
        ]);

        $target = User::findOrFail($data['user_id']);
        if (isset($data['matrix_row_id'])) {
            abort_unless($viewer->canManageTrainingMatrix(), 403);
        } else {
            abort_unless($viewer->canManageTrainingMatrix() || $viewer->canManageTrainingsFor($target), 403);
        }

        $records = Training::with('approvedBy')
            ->where('user_id', $target->id)
            ->when(isset($data['matrix_row_id']),
                fn ($q) => $q->where('matrix_row_id', $data['matrix_row_id']),
                fn ($q) => $q->where('manuals_id', $data['manual_id'])->whereNull('matrix_row_id'))
            ->get()
            ->sortBy([
                fn ($a, $b) => (int) $b->is_legacy <=> (int) $a->is_legacy, // legacy первым
                fn ($a, $b) => strcmp((string) $a->date_training, (string) $b->date_training),
            ])
            ->values()
            ->map(fn (Training $t) => [
                'id' => $t->id,
                'label' => $t->is_legacy
                    ? __('Old training (X)')
                    : ($t->form_type ? __('Form') . ' ' . $t->form_type : __('Course')),
                'date' => $t->date_training ? \Carbon\Carbon::parse($t->date_training)->format('M-d-Y') : '—',
                'approved' => $t->isApproved(),
                'approved_by' => $t->approvedBy->selection_name ?? null,
                'approved_at' => $t->approved_at?->format('M-d-Y'),
            ]);

        return response()->json([
            'success' => true,
            'can_approve' => $viewer->canApproveTrainings(),
            'records' => $records,
        ]);
    }

    /** Снять приёмку — только назначенный (can_manage_approved_trainings). */
    public function unapprove(string $id)
    {
        abort_unless(auth()->user()->canManageApprovedTrainings(), 403);

        Training::findOrFail($id)->update(['approved_by' => null, 'approved_at' => null]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, string $id)
    {
        $training = Training::findOrFail($id);

        $canEdit = $training->user_id === auth()->id()
            || auth()->user()->roleIs(['Admin', 'Manager']);

        if (!$canEdit) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 403);
        }

        // Принятый тренинг заморожен — менять может только назначенный
        if ($training->isApproved() && !auth()->user()->canManageApprovedTrainings()) {
            return response()->json([
                'success' => false,
                'message' => __('Training is approved and locked. Contact the designated manager.'),
            ], 422);
        }

        // Дату Form 132 правит только Admin (принятую — только назначенный, см. выше)
        if ((string) $training->form_type === '132'
            && !auth()->user()->roleIs('Admin')
            && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => __('Form 132 date can be edited by Admin only.'),
            ], 422);
        }

        $validated = $request->validate([
            'date_training' => 'required|date|before_or_equal:today',
        ]);

        $training->date_training = $this->fridayOf($validated['date_training']);
        $training->save();

        return response()->json([
            'success' => true,
            'message' => __('Training date updated.'),
        ]);
    }

    public function destroy(string $id)
    {
        $training = Training::findOrFail($id);

        $canDelete = $training->user_id === auth()->id()
            || auth()->user()->roleIs(['Admin', 'Manager']);

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 403);
        }

        // Принятый тренинг заморожен — удалять может только назначенный
        if ($training->isApproved() && !auth()->user()->canManageApprovedTrainings()) {
            return response()->json([
                'success' => false,
                'message' => __('Training is approved and locked. Contact the designated manager.'),
            ], 422);
        }

        // Форму 132 считаем "базовой" и не даём удалить из этого интерфейса,
        // чтобы случайно не сломать историю обучения
        if ($training->form_type == 132) {
            return response()->json([
                'success' => false,
                'message' => __('Form 132 cannot be deleted from this screen.'),
            ], 422);
        }

        try {
            $training->delete();

            return response()->json([
                'success' => true,
                'message' => __('Training date deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'manual_id' => 'required|integer'
        ]);

        $targetUserId = (int) $request->user_id;
        $canDelete = $targetUserId === auth()->id()
            || auth()->user()->roleIs(['Admin', 'Manager']);

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 403);
        }

        // Юнит с принятыми тренингами целиком удаляет только назначенный
        $hasApproved = Training::where('user_id', $targetUserId)
            ->where('manuals_id', $request->manual_id)
            ->whereNotNull('approved_by')
            ->exists();
        if ($hasApproved && !auth()->user()->canManageApprovedTrainings()) {
            return response()->json([
                'success' => false,
                'message' => __('Unit has approved trainings and is locked. Contact the designated manager.'),
            ], 422);
        }

        try {
            $deleted = Training::where('user_id', $targetUserId)
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
     * Матрица допусков «как Excel MINIMUM REQUIREMENTS». Два режима:
     * production (парт-номера, колонки — Personnel) и SCA (курсы, колонки —
     * люди с can_sign_certificates). Видимость по роли смотрящего:
     * Technician/производственник — только своя колонка; Team Leader — своя
     * team (и может добавлять тренинги тимматам); Admin и Manager-с-SCA — всё;
     * Manager без SCA — страница закрыта.
     */
    public function showAll(Request $request)
    {
        $viewer = auth()->user();
        abort_unless($viewer->canViewTrainingMatrix(), 403);

        $canManage = $viewer->canManageTrainingMatrix();

        // Два вида: production (все, кроме SCA-людей; только производственные
        // группы) и SCA (люди с can_sign_certificates; ВСЕ их тренинги —
        // production-группы + курсы). Переключатель только у управляющих.
        $scaMode = $canManage && $request->boolean('sca');

        // Неактивные юниты (сняли галку Active — «с ним пока не работаем»)
        // скрыты; переключателем показываются только управляющим.
        $showInactive = $canManage && $request->boolean('show_inactive');

        $stampOrder = fn (User $user) => ctype_digit($user->stamp)
            ? sprintf('0-%05d', (int) $user->stamp)
            : '1-' . mb_strtolower($user->stamp);

        if ($scaMode) {
            $users = User::whereNotNull('stamp')
                ->where('stamp', '<>', '')
                ->whereNull('deleted_at')
                ->where('can_sign_certificates', true)
                ->get()->sortBy($stampOrder)->values();
        } else {
            $users = User::whereNotNull('stamp')
                ->where('stamp', '<>', '')
                ->whereNull('deleted_at')
                ->where('show_in_training_matrix', true)
                ->where('can_sign_certificates', false) // SCA-люди — в своём виде
                ->get()->sortBy($stampOrder)->values();

            if (!$canManage) {
                $users = $viewer->roleIs('Team Leader')
                    ? $users->filter(fn (User $user) => $viewer->team_id !== null
                        && (int) $user->team_id === (int) $viewer->team_id)->values()
                    : $users->filter(fn (User $user) => $user->id === $viewer->id)->values();
            }
        }

        // SCA-вид: все группы (production + курсы); production-вид: только production
        $categories = TrainingCategory::when(!$scaMode, fn ($q) => $q->where('is_sca', false))
            ->with([
                'rows' => fn ($q) => $q->when(!$showInactive, fn ($qq) => $qq->where('is_active', true))->orderBy('sort_order'),
                'rows.manual',
            ])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->rows->isNotEmpty())
            ->values();

        $rows = $categories->flatMap->rows;
        $rowByManualId = $rows->whereNotNull('manual_id')->keyBy('manual_id');
        $userIds = $users->pluck('id');

        // Тренинги обоих видов: по CMM (production) и по строкам-курсам (SCA)
        $manualIds = $rowByManualId->keys();
        $manualTrainings = $manualIds->isEmpty() ? collect() : Training::whereIn('manuals_id', $manualIds)
            ->whereNull('matrix_row_id')
            ->whereIn('user_id', $userIds)
            ->get();
        $courseRowIds = $rows->filter(fn ($row) => $row->category?->is_sca)->pluck('id');
        $courseTrainings = $courseRowIds->isEmpty() ? collect() : Training::whereIn('matrix_row_id', $courseRowIds)
            ->whereIn('user_id', $userIds)
            ->get();

        $trainings = $manualTrainings->concat($courseTrainings);
        $rowIdOf = fn ($training) => $training->matrix_row_id
            ?? ($rowByManualId[$training->manuals_id]->id ?? null);

        $redAfter = now()->subDays((int) config('trainings.matrix_red_after_days', 350))->startOfDay();
        $legacyAfter = now()->subYears((int) config('trainings.matrix_legacy_after_years', 3))->startOfDay();

        // cells[row_id][user_id] = ['kind' => 'date'|'x', 'date' => ?Carbon, 'red' => bool, 'need132' => bool]
        $cells = [];
        foreach ($trainings->groupBy(fn ($t) => $rowIdOf($t) . '|' . $t->user_id) as $key => $pair) {
            [$rowId, $userId] = explode('|', $key);
            if ($rowId === '') {
                continue;
            }
            $isCoursePair = $pair->first()->matrix_row_id !== null;
            $isLegacyPair = $pair->contains(fn ($t) => $t->is_legacy);
            $lastRecord = $pair->where('is_legacy', false)
                ->whereNotNull('date_training')
                // Перевыпущенная 132 у legacy-пары — не тренинг: X остаётся до реального refresh-112
                ->when(!$isCoursePair && $isLegacyPair, fn ($c) => $c->filter(fn ($t) => (string) $t->form_type === '112'))
                ->sortByDesc('date_training')
                ->first();
            $lastDate = $lastRecord?->date_training;
            $lastApproved = (bool) $lastRecord?->approved_by;

            // Legacy-пара без 132 (бумажная эпоха): 132 не положена, но может быть
            // создана по явному запросу — потеря бланка / перевыпуск.
            $need132 = !$isCoursePair && $isLegacyPair
                && !$pair->contains(fn ($t) => (string) $t->form_type === '132');

            if ($lastDate !== null) {
                $lastDate = \Carbon\Carbon::parse($lastDate);
                $cells[$rowId][$userId] = $lastDate->lt($legacyAfter)
                    ? ['kind' => 'x', 'date' => $lastDate, 'red' => false, 'need132' => $need132, 'approved' => $lastApproved]
                    : ['kind' => 'date', 'date' => $lastDate, 'red' => $lastDate->lt($redAfter), 'need132' => $need132, 'approved' => $lastApproved];
            } elseif ($isLegacyPair) {
                $cells[$rowId][$userId] = ['kind' => 'x', 'date' => null, 'red' => false, 'need132' => $need132, 'approved' => false];
            }
        }

        $unlinkedManuals = collect();
        $uncategorizedCount = 0;
        $inactiveCount = 0;
        if ($canManage) {
            $unlinkedManuals = Manual::whereNotNull('unit_name_training')
                ->where('unit_name_training', '<>', '')
                ->whereDoesntHave('matrixRow')
                ->orderBy('title')
                ->get(['id', 'title', 'unit_name_training']);
            $uncategorizedCount = $unlinkedManuals->count();
            $inactiveCount = TrainingMatrixRow::where('is_active', false)->count();
        }

        $allCategories = TrainingCategory::orderBy('sort_order')->get(['id', 'name', 'is_sca']);

        // Кандидаты в production-колонки для модалки Personnel (SCA-люди — в своём виде)
        $personnel = collect();
        if ($canManage && !$scaMode) {
            $personnel = User::whereNotNull('stamp')
                ->where('stamp', '<>', '')
                ->whereNull('deleted_at')
                ->where('can_sign_certificates', false)
                ->with('role')
                ->get()->sortBy($stampOrder)->values();
        }

        return view('admin.trainings.show_all', compact(
            'categories', 'users', 'cells', 'canManage', 'scaMode', 'unlinkedManuals', 'uncategorizedCount',
            'allCategories', 'showInactive', 'inactiveCount', 'personnel'
        ));
    }

    /** SCA-вкладка: дата тренинга по строке-курсу (без CMM и без форм 112/132). */
    public function matrixCourseDateStore(Request $request)
    {
        $this->ensureCanManageMatrix();

        $data = $request->validate([
            'matrix_row_id' => 'required|integer|exists:training_matrix_rows,id',
            'user_id' => 'required|integer|exists:users,id',
            'date_training' => 'required|date|before_or_equal:today',
        ]);

        $row = TrainingMatrixRow::findOrFail($data['matrix_row_id']);
        abort_unless($row->category?->is_sca, 422);

        // Курсы — только для людей с SCA-квалификацией
        $target = User::findOrFail($data['user_id']);
        abort_unless((bool) $target->can_sign_certificates, 422);

        $date = $this->fridayOf($data['date_training']);
        $exists = Training::where('user_id', $data['user_id'])
            ->where('matrix_row_id', $row->id)
            ->where('date_training', $date)
            ->exists();

        if (!$exists) {
            Training::create([
                'user_id' => $data['user_id'],
                'manuals_id' => null,
                'matrix_row_id' => $row->id,
                'date_training' => $date,
                'form_type' => null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /** Модалка Personnel: отмеченные — в матрице, остальные (со stamp) — нет. */
    public function matrixPersonnelUpdate(Request $request)
    {
        $this->ensureCanManageMatrix();

        $data = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);
        $checked = collect($data['user_ids'] ?? [])->map(fn ($id) => (int) $id);

        User::whereNotNull('stamp')
            ->where('stamp', '<>', '')
            ->whereNull('deleted_at')
            ->where('can_sign_certificates', false) // SCA-колонки квалификацией, не Personnel
            ->get()
            ->each(function (User $user) use ($checked) {
                $include = $checked->contains($user->id);
                if ($user->show_in_training_matrix !== $include) {
                    $user->show_in_training_matrix = $include;
                    $user->save();
                }
            });

        return back()->with('success', __('Matrix personnel updated.'));
    }

    public function matrixRowToggleActive(TrainingMatrixRow $row)
    {
        $this->ensureCanManageMatrix();

        $row->update(['is_active' => !$row->is_active]);

        return back()->with('success', $row->is_active
            ? __('Unit returned to the matrix.')
            : __('Unit hidden from the matrix (not deleted).'));
    }

    // ---- Управление строками матрицы (Admin/Manager) ----

    private function ensureCanManageMatrix(): void
    {
        if (!auth()->user()->canManageTrainingMatrix()) {
            abort(403);
        }
    }

    public function matrixRowStore(Request $request)
    {
        $this->ensureCanManageMatrix();

        $data = $request->validate([
            'training_category_id' => 'nullable|integer|exists:training_categories,id',
            'new_category_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'part_number' => 'required|string|max:255',
            'manual_id' => 'nullable|integer|exists:manuals,id|unique:training_matrix_rows,manual_id',
            'is_sca' => 'nullable|boolean',
        ]);

        if (empty($data['training_category_id']) && empty($data['new_category_name'])) {
            return back()->withErrors(['training_category_id' => __('Select a group or enter a new one.')]);
        }

        $categoryId = $data['training_category_id'] ?? null;
        if (!$categoryId) {
            $category = TrainingCategory::firstOrCreate(
                ['name' => trim($data['new_category_name'])],
                [
                    'sort_order' => (int) TrainingCategory::max('sort_order') + 1,
                    'is_sca' => $request->boolean('is_sca'),
                ]
            );
            $categoryId = $category->id;
        }

        TrainingMatrixRow::create([
            'training_category_id' => $categoryId,
            'description' => $data['description'] ?? null,
            'part_number' => $data['part_number'],
            'sort_order' => (int) TrainingMatrixRow::where('training_category_id', $categoryId)->max('sort_order') + 1,
            'manual_id' => $data['manual_id'] ?? null,
        ]);

        return back()->with('success', __('Matrix row added.'));
    }

    public function matrixRowUpdate(Request $request, TrainingMatrixRow $row)
    {
        $this->ensureCanManageMatrix();

        $data = $request->validate([
            'training_category_id' => 'required|integer|exists:training_categories,id',
            'description' => 'nullable|string|max:255',
            'part_number' => 'required|string|max:255',
            'manual_id' => 'nullable|integer|exists:manuals,id|unique:training_matrix_rows,manual_id,' . $row->id,
        ]);

        if ((int) $data['training_category_id'] !== (int) $row->training_category_id) {
            $data['sort_order'] = (int) TrainingMatrixRow::where('training_category_id', $data['training_category_id'])->max('sort_order') + 1;
        }

        $row->update($data);

        return back()->with('success', __('Matrix row updated.'));
    }

    public function matrixRowDestroy(TrainingMatrixRow $row)
    {
        $this->ensureCanManageMatrix();
        $row->delete();

        return back()->with('success', __('Matrix row deleted.'));
    }

    public function matrixCategoryMove(Request $request, TrainingCategory $category)
    {
        $this->ensureCanManageMatrix();

        $request->validate(['direction' => 'required|in:up,down']);
        $up = $request->input('direction') === 'up';

        $neighbor = TrainingCategory::where('id', '<>', $category->id)
            ->when($up,
                fn ($q) => $q->where('sort_order', '<=', $category->sort_order)->orderByDesc('sort_order')->orderByDesc('id'),
                fn ($q) => $q->where('sort_order', '>=', $category->sort_order)->orderBy('sort_order')->orderBy('id'))
            ->first();

        if ($neighbor) {
            // При равных sort_order (исторические данные) обмен ничего бы не менял — разводим явно
            if ((int) $neighbor->sort_order === (int) $category->sort_order) {
                $up ? $neighbor->sort_order++ : $category->sort_order++;
            }
            [$category->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $category->sort_order];
            $category->save();
            $neighbor->save();
        }

        return back();
    }

    public function matrixRowMove(Request $request, TrainingMatrixRow $row)
    {
        $this->ensureCanManageMatrix();

        $request->validate(['direction' => 'required|in:up,down']);
        $up = $request->input('direction') === 'up';

        $neighbor = TrainingMatrixRow::where('training_category_id', $row->training_category_id)
            ->when($up,
                fn ($q) => $q->where('sort_order', '<', $row->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $row->sort_order)->orderBy('sort_order'))
            ->first();

        if ($neighbor) {
            [$row->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $row->sort_order];
            $row->save();
            $neighbor->save();
        }

        return back();
    }
}
