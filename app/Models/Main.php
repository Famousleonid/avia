<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Main extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'workorder_id', 'general_task_id', 'task_id', 'description', 'date_start', 'date_finish','ignore_row'];

    protected $casts = [
        'date_start' =>'date:Y-m-d',
        'date_finish' => 'date:Y-m-d',
        'ignore_row'  => 'boolean',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function workorder() { return $this->belongsTo(Workorder::class); }
    public function task()      { return $this->belongsTo(Task::class); }

    public function getGeneralTaskAttribute()
    {
        return $this->task?->generalTask; // Task::belongsTo(GeneralTask::class)
    }

    public function generalTaskRelation()
    {
        return $this->belongsTo(GeneralTask::class, 'general_task_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('main')
            ->logOnly([
                'workorder_id',
                'general_task_id',
                'task_id',
                'user_id',
                'description',
                'date_start',
                'date_finish',
                'ignore_row',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // =========================
    // Кастомизация логов
    // =========================
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'task' => [
                'general' => $this->task?->generalTask?->name,
                'name'    => $this->task?->name,
            ],
            'workorder_id' => $this->workorder_id,
        ]);

        // ignore_row
        if ($eventName === 'updated' && $this->wasChanged('ignore_row')) {
            $activity->event = 'ignore_row_toggled';
        }

        // очистка дат
        if ($eventName === 'updated') {
            $changes = [];

            if ($this->wasChanged('date_start') && empty($this->date_start)) {
                $changes[] = 'date_start cleared';
            }

            if ($this->wasChanged('date_finish') && empty($this->date_finish)) {
                $changes[] = 'date_finish cleared';
            }

            if ($changes) {
                $activity->properties = $activity->properties->merge([
                    'cleared' => $changes,
                ]);
            }
        }
    }

    /**
     * Нормализует и валидирует даты для store/update.
     *
     * @param  array       $data      Валидированные данные (date_start/date_finish могут отсутствовать!)
     * @param  \App\Models\Task $task  Таск текущей строки
     * @param  \App\Models\Main|null $existing Текущая запись (для update), чтобы взять старые даты
     * @param  bool        $ignoreRow Текущее значение ignore_row (после клика)
     * @param  bool        $hasStart  Пришло ли поле date_start в запросе ($request->has('date_start'))
     * @param  bool        $hasFinish Пришло ли поле date_finish в запросе ($request->has('date_finish'))
     *
     * @return array{date_start: Carbon|null, date_finish: Carbon|null}
     */
    public static function validateAndResolveDates(
        array $data,
        \App\Models\Task $task,
        ?self $existing,
        bool $ignoreRow,
        bool $hasStart,
        bool $hasFinish
    ): array {

        $requiresStart = (bool) ($task->task_has_start_date ?? false);

        // Если start не нужен — всегда null (и никакие проверки start/finish не нужны)
        if (!$requiresStart) {
            return [
                'date_start'  => null,
                'date_finish' => $hasFinish
                    ? (empty($data['date_finish']) ? null : Carbon::parse($data['date_finish']))
                    : ($existing?->date_finish ? Carbon::parse($existing->date_finish) : null),
            ];
        }

        // Считаем "итоговые" значения с учётом того, что поле могло не прийти (update-кейс)
        $newStart = $hasStart
            ? (empty($data['date_start']) ? null : Carbon::parse($data['date_start']))
            : ($existing?->date_start ? Carbon::parse($existing->date_start) : null);

        $newFinish = $hasFinish
            ? (empty($data['date_finish']) ? null : Carbon::parse($data['date_finish']))
            : ($existing?->date_finish ? Carbon::parse($existing->date_finish) : null);

        // Если строка игнорируется — обычно проверки дат не нужны
        if (!$ignoreRow) {
            // finish нельзя без start
            if ($hasFinish && $newFinish && !$newStart) {
                throw ValidationException::withMessages([
                    'date_finish' => 'Set the start date before the finish date.',
                ]);
            }

            // finish не раньше start
            if ($newStart && $newFinish && $newFinish->lt($newStart)) {
                throw ValidationException::withMessages([
                    'date_finish' => 'Finish cannot be before Start',
                ]);
            }
        }

        return [
            'date_start'  => $newStart,
            'date_finish' => $newFinish,
        ];
    }


}
