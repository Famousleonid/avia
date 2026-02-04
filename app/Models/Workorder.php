<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Builder;

class Workorder extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes, HasMediaHelpers;

    protected $fillable = ['number', 'user_id', 'unit_id', 'instruction_id', 'external_damage','received_disassembly','nameplate_missing','disassembly_upon_arrival',
        'preliminary_test_false','part_missing','extra_parts','new_parts', 'open_at', 'customer_id', 'approve', 'approve_at', 'description', 'manual',
        'serial_number', 'place', 'created_at','amdt', 'rm_report', 'customer_po','modified','is_draft'];

    protected $dates = ['approve_at','deleted_at','open_at'];

    public $mediaUrlName = 'workorders';


    public function tdrs()
    {
        return $this->hasMany(Tdr::class,'workorder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'unit_id', 'id');
    }


    public function instruction()
    {
        return $this->belongsTo(Instruction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function ndtCadCsv()
    {
        return $this->hasOne(NdtCadCsv::class);
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(80)
            ->height(80)
            ->nonOptimized();

    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('workorder')
            ->logOnly([
                'number',
                'unit_id',
                'customer_id',
                'instruction_id',
                'user_id',
                'approve_at',
                'approve_name',
                'description',
                'serial_number',
                'notes',
            ])
            ->logOnlyDirty()                // логировать ТОЛЬКО изменившиеся поля
            ->dontSubmitEmptyLogs();        // не создавать пустые записи
    }

    public function getDoneMainRecord()
    {
        $mains = $this->relationLoaded('main')
            ? $this->main
            : $this->main()->with('task')->get();

        return $mains
            ->filter(function ($m) {
                return !$m->ignore_row                              // не игнорированная строка
                    && $m->task                                     // есть связанная задача
                    && $m->task->name === 'Completed'              // или 'Complete' — как у тебя в БД
                    && $m->date_finish !== null;                   // завершена
            })
            ->sortByDesc('date_finish')    // на всякий случай — самая поздняя дата
            ->first();
    }

    public function isDone(): bool
    {
        return (bool) $this->getDoneMainRecord();
    }

    public function doneDate()
    {
        $done = $this->getDoneMainRecord();
        return $done ? $done->date_finish : null;
    }

    public function generalTaskStatuses()
    {
        return $this->hasMany(\App\Models\WorkorderGeneralTaskStatus::class);
    }

    public function syncDoneByCompletedTask(): void
    {
        // 1) находим id задачи Completed (один раз на вызов; при 20 tasks это ок)
        $completedTaskId = \App\Models\Task::where('name', 'Completed')->value('id');

        if (!$completedTaskId) {
            // если нет задачи — считаем не done
            $this->done_at = null;
            $this->done_user_id = null;
            $this->saveQuietly();
            return;
        }

        // 2) берем main запись по этой задаче
        $main = \App\Models\Main::where('workorder_id', $this->id)
            ->where('task_id', $completedTaskId)
            ->first();

        // DONE только если date_finish есть и строка не игнорируется
        if ($main && !$main->ignore_row && !empty($main->date_finish)) {
            $this->done_at = $main->date_finish;
            $this->done_user_id = $main->user_id;
        } else {
            $this->done_at = null;
            $this->done_user_id = null;
        }

        $this->saveQuietly();
    }

    public function recalcGeneralTaskStatuses(?int $onlyGeneralTaskId = null): void
    {
        // Берём задачи (нужно имя, чтобы найти "Completed")
        $tasksQuery = \App\Models\Task::query()->select('id', 'name', 'general_task_id');

        if ($onlyGeneralTaskId) {
            $tasksQuery->where('general_task_id', $onlyGeneralTaskId);
        }

        $tasksByGeneral = $tasksQuery->get()->groupBy('general_task_id');

        // Берём mains по workorder для task-строк
        $mainsByTask = \App\Models\Main::where('workorder_id', $this->id)
            ->whereNotNull('task_id')
            ->get()
            ->keyBy('task_id');

        $now    = now();
        $userId = auth()->id();

        foreach ($tasksByGeneral as $gtId => $gtTasks) {

            // если в этапе нет задач — не done
            if ($gtTasks->isEmpty()) {
                $isDone = false;
            } else {

                // ✅ Если в этом general_task есть задача "Completed" — этап done = isDone()
                $hasCompletedTask = $gtTasks->contains(fn($t) => $t->name === 'Completed');

                if ($hasCompletedTask) {
                    $isDone = $this->isDone(); // твоя существующая логика (Completed.date_finish)
                } else {
                    // Обычное правило: все задачи этапа закрыты (или ignore_row)
                    $isDone = $gtTasks->pluck('id')->every(function ($taskId) use ($mainsByTask) {
                        $m = $mainsByTask->get($taskId);

                        if (!$m) return false;           // нет строки main -> не done
                        if ($m->ignore_row) return true; // игнор -> считается done

                        return !empty($m->date_finish);  // иначе нужен finish
                    });
                }
            }

            \App\Models\WorkorderGeneralTaskStatus::updateOrCreate(
                ['workorder_id' => $this->id, 'general_task_id' => $gtId],
                [
                    'is_done'      => $isDone,
                    'done_at'      => $isDone ? $now : null,
                    'done_user_id' => $isDone ? $userId : null,
                ]
            );
        }
    }

    protected static function booted(): void
    {
        static::addGlobalScope('exclude_drafts', function (Builder $builder) {
            $builder->where('is_draft', false);
        });
    }

    public function scopeOnlyDrafts(Builder $query): Builder
    {
        return $query
            ->withoutGlobalScope('exclude_drafts')
            ->where('is_draft', true);
    }

    public function scopeWithDrafts(Builder $query): Builder
    {
        return $query->withoutGlobalScope('exclude_drafts');
    }


    public static function createDraft(array $attributes = []): self
    {
        // На случай редких коллизий (если два человека одновременно)
        $attempts = 0;

        while ($attempts < 5) {
            $attempts++;

            $last = self::withoutGlobalScope('exclude_drafts')
                ->where('is_draft', true)
                ->whereBetween('number', [1, 99999])
                ->max('number');

            $next = (int)($last ?? 0) + 1;

            if ($next > 99999) {
                throw new \RuntimeException('Draft number range 1..99999 exhausted');
            }

            try {
                $wo = new self();
                $wo->fill($attributes);
                $wo->number = $next;
                $wo->is_draft = true;

                if (empty($wo->user_id) && auth()->check()) {
                    $wo->user_id = auth()->id();
                }

                $wo->save();

                return $wo;

            } catch (QueryException $e) {
                // MySQL duplicate key
                if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                    usleep(150000);
                    continue;
                }
                throw $e;
            }
        }

        throw new \RuntimeException('Could not allocate a draft number, try again.');
    }


    public static function nextDraftNumber(): int
    {
        $last = self::withoutGlobalScope('exclude_drafts')
            ->where('is_draft', true)
            ->whereBetween('number', [1, 99999])
            ->lockForUpdate()
            ->max('number');

        $next = (int)($last ?? 0) + 1;

        if ($next > 99999) {
            throw new \RuntimeException('Draft number range 1..99999 exhausted');
        }

        return $next;
    }


}
