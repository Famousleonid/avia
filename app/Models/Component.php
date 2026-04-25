<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Component extends Model implements  hasMedia
{
    use  InteractsWithMedia, HasMediaHelpers, LogsActivity, SoftDeletes;

    protected $fillable = [
        'part_number',
        'assy_part_number',
        'name',
        'ipl_num',
        'assy_ipl_num',
        'eff_code',
        'units_assy',
        'log_card',
        'repair',
        'manual_id',
        'img',
        'assy_img',
        'bush_ipl_num',
        'is_bush',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('component')
            ->logOnly([
                'part_number',
                'name',
                'ipl_num',
                'manual_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    public function manual()
    {
        return $this->belongsTo(Manual::class,'manual_id');
    }

    public function tdrs()
    {
        return $this->hasMany(\App\Models\Tdr::class, 'component_id', 'id');
    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->fit('crop', 100, 100)
            ->nonOptimized();
    }

}
