<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Manual extends Model implements  hasMedia
{
    use softDeletes, InteractsWithMedia, HasMediaHelpers;

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
    ];

    protected $dates = ['deleted_at'];

    public $mediaUrlName = 'manuals';

    // Отношение с моделью AirCraft
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
