<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Builder;

class Workorder extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes, HasMediaHelpers;

    protected $fillable = ['number', 'draft_number', 'user_id', 'unit_id', 'instruction_id', 'external_damage','received_disassembly','nameplate_missing','disassembly_upon_arrival',
        'preliminary_test_false','part_missing','extra_parts','new_parts', 'open_at', 'customer_id', 'approve_at', 'description',
        'serial_number', 'place', 'paint_queue_order', 'machining_queue_order', 'amdt', 'rm_report', 'certificate_data', 'customer_po','shipping_freight_forwarder','shipping_awb_no','shipping_shipment_at','shipping_notes','modified','is_draft','storage_rack','storage_level','storage_column',
        'arrival_box_status','arrival_box_notes','arrival_box_recorded_by','arrival_box_recorded_at','torque_values',];

    protected $casts = [
        'approve_at' => 'datetime',
        'done_at' => 'date',
        'shipping_shipment_at' => 'date',
        'open_at'    => 'datetime',
        'draft_number' => 'integer',
        'is_draft'   => 'boolean',
        'approve'    => 'boolean',
        'certificate_data' => 'array',
        'arrival_box_recorded_at' => 'datetime',
        'torque_values' => 'array',
    ];

    public $mediaUrlName = 'workorders';


    public function tdrs()
    {
        return $this->hasMany(Tdr::class,'workorder_id');
    }

    public function stdProcesses(): HasMany
    {
        return $this->hasMany(WorkorderStdProcess::class);
    }

    public function stdProcessItems(): HasMany
    {
        return $this->hasMany(WorkorderStdProcessItem::class);
    }

    public function unitInspections(): HasMany
    {
        return $this->hasMany(WorkorderUnitInspection::class);
    }

    public function serviceBulletinLogs(): HasMany
    {
        return $this->hasMany(WorkorderServiceBulletinLog::class);
    }

    public function woBushingLines(): HasMany
    {
        return $this->hasMany(WoBushingLine::class);
    }

    public function woBushingProcesses(): HasManyThrough
    {
        return $this->hasManyThrough(
            WoBushingProcess::class,
            WoBushingLine::class,
            'workorder_id',
            'wo_bushing_line_id',
            'id',
            'id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'unit_id', 'id');
    }

    public function displayDescription(): ?string
    {
        $unitName = trim((string) ($this->unit?->name ?? ''));

        return $unitName !== '' ? $unitName : null;
    }

    public function doneUser()
    {
        return $this->belongsTo(User::class, 'done_user_id');
    }

    public function instruction()
    {
        return $this->belongsTo(Instruction::class);
    }

    /**
     * Overhaul requires orig (factory) limits; everything else uses wear limits.
     * Instructions: Overhaul → false, Repair/Test & inspect/60M/96M → true.
     */
    public function usesWearLimits(): bool
    {
        return $this->instruction?->name !== 'Overhaul';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(80)
            ->height(80)
            ->nonOptimized();

    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('quality')
            ->acceptsFile(function (File $file) {
                return in_array(strtolower($file->mimeType ?? ''), [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/csv',
                    'text/plain',
                ], true);
            });
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
                'shipping_shipment_at',
                'shipping_freight_forwarder',
                'shipping_awb_no',
                'shipping_notes',
            ])
            ->logExcept(['created_at','updated_at'])
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

    public function toolEntries(): HasMany
    {
        return $this->hasMany(WorkorderTool::class);
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
        $tasksQuery = \App\Models\Task::query()->select('id', 'name', 'general_task_id');

        if ($onlyGeneralTaskId) {
            $tasksQuery->where('general_task_id', $onlyGeneralTaskId);
        }

        $tasksByGeneral = $tasksQuery->get()->groupBy('general_task_id');
        $mainsByTask = \App\Models\Main::where('workorder_id', $this->id)
            ->whereNotNull('task_id')
            ->get()
            ->keyBy('task_id');

        $now = now();
        $userId = auth()->id();

        foreach ($tasksByGeneral as $gtId => $gtTasks) {
            $isDone = $gtTasks->isNotEmpty()
                && $gtTasks->pluck('id')->every(function ($taskId) use ($mainsByTask): bool {
                    $main = $mainsByTask->get($taskId);

                    if (! $main) {
                        return false;
                    }

                    if ($main->ignore_row) {
                        return true;
                    }

                    return ! empty($main->date_finish);
                });

            \App\Models\WorkorderGeneralTaskStatus::updateOrCreate(
                ['workorder_id' => $this->id, 'general_task_id' => $gtId],
                [
                    'is_done' => $isDone,
                    'done_at' => $isDone ? $now : null,
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

    /**
     * Есть хотя бы одна строка Machining с заполненной датой отправки (Date Sent):
     * TDR Machining с date_start или бушинг batch/process machining с датой на соответствующей записи.
     */
    public function scopeWhereMachiningHasDateSent(Builder $query): Builder
    {
        return $query->where(function (Builder $w) {
            $machiningPnIds = ProcessName::machiningMachiningEcMergeProcessNameIds();
            $w->whereHas('tdrs.tdrProcesses', function ($tp) use ($machiningPnIds) {
                if ($machiningPnIds === []) {
                    $tp->whereRaw('1 = 0');

                    return;
                }
                $tp->whereIn('process_names_id', $machiningPnIds)
                    ->whereNotNull('tdr_processes.date_start');
            })->orWhereHas('woBushingProcesses', function ($wbp) {
                $wbp->where(function ($dates) {
                    $dates->where(function ($batchPath) {
                        $batchPath->whereNotNull('wo_bushing_processes.batch_id')
                            ->whereHas('batch', fn ($batch) => $batch->whereNotNull('date_start'));
                    })->orWhere(function ($singlePath) {
                        $singlePath->whereNull('wo_bushing_processes.batch_id')
                            ->whereNotNull('wo_bushing_processes.date_start');
                    });
                })->whereHas('process', function ($proc) {
                    $proc->join('process_names', 'process_names.id', '=', 'processes.process_names_id')
                        ->whereRaw(
                            'INSTR(LOWER(TRIM(CONCAT(COALESCE(process_names.name, ""), " ", COALESCE(processes.process, "")))), ?) > 0',
                            ['machining']
                        );
                });
            });
        });
    }

    /**
     * WO попадает на экран Machining: не черновик, не закрыт по WO, есть Date Sent по machining.
     */
    public function isOpenForMachiningBoard(): bool
    {
        if ($this->done_at !== null || (bool) $this->is_draft) {
            return false;
        }

        return static::query()->whereKey($this->getKey())->whereMachiningHasDateSent()->exists();
    }

    public static function createDraft(array $attributes = []): self
    {
        // На случай редких коллизий (если два человека одновременно)
        $attempts = 0;

        while ($attempts < 5) {
            $attempts++;

            $last = self::withTrashed() // ✅ учитываем deleted_at
            ->withoutGlobalScope('exclude_drafts')
                ->whereNotNull('draft_number')
                ->whereBetween('draft_number', [1, 99999])
                ->max('draft_number');

            $next = (int)($last ?? 0) + 1;

            if ($next > 99999) {
                throw new \RuntimeException('Draft number range 1..99999 exhausted');
            }

            try {
                $wo = new self();
                $wo->fill($attributes);
                $wo->number = $next;
                $wo->draft_number = $next;
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
        $last = self::withTrashed() // ✅ учитываем deleted_at
        ->withoutGlobalScope('exclude_drafts')
            ->whereNotNull('draft_number')
            ->whereBetween('draft_number', [1, 99999])
            ->lockForUpdate()
            ->max('draft_number');

        $next = (int)($last ?? 0) + 1;

        if ($next > 99999) {
            throw new \RuntimeException('Draft number range 1..99999 exhausted');
        }

        return $next;
    }

    public function getStorageLocationAttribute(): ?string
    {
        if (!$this->storage_rack && !$this->storage_level && !$this->storage_column) return null;

        $parts = [];

        if ($this->storage_rack) {
            $parts[] = 'Rack: ' . $this->storage_rack;
        }

        if ($this->storage_level) {
            $parts[] = 'Level: ' . $this->storage_level;
        }

        if ($this->storage_column) {
            $parts[] = 'Column: ' . $this->storage_column;
        }

        return empty($parts) ? null : implode(' _ ', $parts);
    }
}
