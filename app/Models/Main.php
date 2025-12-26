<?php

namespace App\Models;

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
}
