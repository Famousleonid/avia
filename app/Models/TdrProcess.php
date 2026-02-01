<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class TdrProcess extends Model
{
    use HasFactory, LogsActivity;

    // Поля, которые можно массово назначать
    protected $fillable = [
        'tdrs_id',
        'process_names_id',
        'plus_process', // Дополнительные NDT process_names_id через запятую (например, "2,4")
        'processes', // JSON-поле для хранения массива процессов
        'description',
        'notes',
        'repair_order',
        'sort_order', // Поле для сортировки
        'date_start',
        'date_finish',
        'ec',// Boolean поле для EC
    ];
    protected $casts = [
        'processes'   => 'array',
        'date_start'  => 'date',   // <-- важно
        'date_finish' => 'date',   // <-- важно
    ];


    // Отношение к модели Tdr
    public function tdr()
    {
        return $this->belongsTo(Tdr::class, 'tdrs_id');
    }

    // Отношение к модели ProcessName
    public function processName()
    {
        return $this->belongsTo(ProcessName::class, 'process_names_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tdr_process')
            ->logOnly([
                'date_start',
                'date_finish',
            ])
            ->logOnlyDirty()                // логировать ТОЛЬКО изменившиеся поля
            ->dontSubmitEmptyLogs();        // не создавать пустые записи
    }
    public function tapActivity(Activity $activity, string $eventName)
    {
        if (auth()->check()) {
            $activity->causer()->associate(auth()->user());
        }
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
