<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class ManualProcess extends Model
{
    use HasFactory, LogsActivity, \Illuminate\Database\Eloquent\SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (self $link) {
            if ($link->isForceDeleting()) throw new \LogicException('Processes may only be archived.');
        });
        static::updating(function (self $link) {
            if ($link->isDirty(['manual_id', 'processes_id'])) {
                $link->assertCanRemoveTemplateLink();
            }
        });
    }

    public function assertCanRemoveTemplateLink(): void
    {
        $manualId = $this->getRawOriginal('manual_id');
        $processId = $this->getRawOriginal('processes_id');
        if ($this->versions()->where('process_id', $processId)->exists()
            && !$this->isDirty('manual_id')) return;
        // Removing a duplicate does not disconnect the process from the manual.
        if (self::where('manual_id', $manualId)->where('processes_id', $processId)
            ->where('id', '!=', $this->id)->exists()) return;
        $templates = TravelerProcessTemplate::where('manual_id', $manualId)
            ->whereHas('steps', fn ($query) => $query->where('process_id', $processId))
            ->orderBy('id')->get(['id', 'name']);
        if ($templates->isNotEmpty()) {
            throw ValidationException::withMessages(['process' =>
                'This process is used by Traveler template(s): '
                .$templates->map(fn ($template) => '#'.$template->id.' '.$template->name)->implode('; ')
                .'. Update their steps before removing or replacing the manual link.',
            ]);
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('manual_process')
            ->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (auth()->check()) $activity->causer()->associate(auth()->user());
        $snapshot = function ($manualId, $processId): array {
            $manual = Manual::withTrashed()->find($manualId);
            $process = Process::with('process_name')->find($processId);
            return [
                'manual_id' => $manualId, 'manual_number' => $manual?->number,
                'manual_lib' => $manual?->lib, 'process_id' => $processId,
                'process_name' => $process?->process_name?->name,
                'specification' => $process?->process,
            ];
        };
        // Preserve identities after deletion or renaming of their source records.
        $before = $eventName === 'created' ? null : $snapshot(
            $this->getRawOriginal('manual_id'), $this->getRawOriginal('processes_id')
        );
        $after = $eventName === 'deleted' ? null : $snapshot($this->manual_id, $this->processes_id);
        $activity->properties = $activity->properties->merge([
            'manual_process_before' => $before, 'manual_process_after' => $after,
            'causer_name' => auth()->user()?->selection_name,
        ]);
    }

    protected $fillable = [

        'manual_id',
        'processes_id',
        'process_comment',
        'requires_fig',
        'requires_zone',
        'is_locked',
        'locked_by_user_id',
        'locked_at',
        'archived_by',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'requires_fig' => 'boolean',
        'requires_zone' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(Process::class, 'processes_id');
    }

    public function versions()
    {
        return $this->hasMany(ManualProcessVersion::class);
    }

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manual_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function processNameLock()
    {
        $processNameId = $this->process?->process_names_id;

        if (! $this->manual_id || ! $processNameId) {
            return null;
        }

        return ManualProcessNameLock::query()
            ->where('manual_id', $this->manual_id)
            ->where('process_name_id', $processNameId)
            ->first();
    }

    public function isGroupLocked(): bool
    {
        return $this->processNameLock() !== null;
    }

    public function isEffectivelyLocked(): bool
    {
        return $this->is_locked;
    }
}
