<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Manual extends Model implements  HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasMediaHelpers,LogsActivity;

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
            ->useLogName('manual')      // log_name в activity_log
            ->logAll()                  // логировать ВСЕ поля
            ->logOnly(['number', 'title', 'unit_name', 'training_hours','lib'])
            ->logExcept(['created_at','updated_at'])
            ->logOnlyDirty()            // только изменившиеся
            ->dontSubmitEmptyLogs();    // не создавать пустых логов
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
    // Отношение с моделью MFR
    public function builder()
    {
        return $this->belongsTo(Builder::class, 'builders_id');
    }

    // Отношение с моделью Scope
    public function scope()
    {
        return $this->belongsTo(Scope::class, 'scopes_id');
    }

    // Отношение с моделью Training
    public function trainings()
    {
        return $this->hasMany(Training::class, 'manuals_id');
    }

    // Отношение с моделью Unit
    public function units()
    {
        return $this->hasMany(Unit::class,'manual_id');
    }
    public function components()
    {
        return $this->hasMany(Component::class, 'manual_id');
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


}
