<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Component extends Model implements  hasMedia
{
    use  InteractsWithMedia, HasMediaHelpers;

    protected $fillable = [
        'part_number',
        'assy_part_number',
        'name',
        'ipl_num',
        'assy_ipl_num',
        'log_card',
        'repair',
        'manual_id',
        'img',
        'assy_img',
    ];


    public function manuals()
    {
        return $this->belongsTo(Manual::class,'manual_id');
    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();

    }

}
