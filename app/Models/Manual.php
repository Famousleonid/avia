<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Manual extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasMediaHelpers, LogsActivity;

    protected $fillable = [
        'number',
        'title',
        'img',
        'revision_date',
        'unit_name',
        'unit_name_training',
        'training_hours',
        'lib',
        'planes_id',
        'builders_id',
        'scopes_id',
        'ovh_life',
        'reg_sb',
    ];

    protected $dates = ['deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('manual')
            ->logOnly([
                'number',
                'title',
                'unit_name',
                'lib',
            ])
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public $mediaUrlName = 'manuals';

    public function plane()
    {
        return $this->belongsTo(Plane::class, 'planes_id');
    }

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'manual_processes', 'manual_id', 'processes_id');
    }

    public function builder()
    {
        return $this->belongsTo(Builder::class, 'builders_id');
    }

    public function scope()
    {
        return $this->belongsTo(Scope::class, 'scopes_id');
    }

    public function trainings()
    {
        return $this->hasMany(Training::class, 'manuals_id');
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'manual_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'manual_id');
    }

    public function stdProcesses()
    {
        return $this->hasMany(StdProcess::class, 'manual_id');
    }

    public function permittedUsers()
    {
        return $this->belongsToMany(User::class, 'manual_user_permissions', 'manual_id', 'user_id')
            ->withTimestamps();
    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('csv_files')
            ->acceptsMimeTypes(['text/csv', 'application/csv', 'text/plain']);

        $this->addMediaCollection('component_csv_files')
            ->acceptsMimeTypes(['text/csv', 'application/csv', 'text/plain']);
    }

    public function getCsvFileUrl()
    {
        $media = $this->getMedia('csv_files')->first();

        return $media ? $media->getUrl() : null;
    }

    public function getCsvFileName()
    {
        $media = $this->getMedia('csv_files')->first();

        return $media ? $media->file_name : null;
    }

    /**
     * Все distinct manual_id по work order: основной из unit и из компонентов TDR (порядок сохранён).
     *
     * @return list<int>
     */
    public static function manualIdsForWorkorder(int $workorderId): array
    {
        $workorder = Workorder::findOrFail($workorderId);
        $manualIds = collect();

        if ($workorder->unit && $workorder->unit->manual_id) {
            $manualIds->push($workorder->unit->manual_id);
        }

        $tdrs = Tdr::with('component')->where('workorder_id', $workorderId)->get();
        foreach ($tdrs as $tdr) {
            if ($tdr->component && $tdr->component->manual_id) {
                $manualIds->push($tdr->component->manual_id);
            }
        }

        return $manualIds->unique()->values()->all();
    }

    /**
     * Значения lib по списку manual_id (в том же порядке; несколько одинаковых lib — все показываются).
     *
     * @param  array<int|string|null>  $manualIds
     * @return list<string>
     */
    public static function orderedLibValuesForManualIds(array $manualIds): array
    {
        $values = [];
        foreach ($manualIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $lib = static::query()->whereKey($id)->value('lib');
            if ($lib !== null && $lib !== '') {
                $values[] = is_string($lib) ? trim($lib) : (string) $lib;
            }
        }

        return $values;
    }
}
