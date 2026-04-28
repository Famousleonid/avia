<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vendor extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasMediaHelpers;

    protected $fillable = [
        'name',
        'is_trusted',
        'description',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
    ];

    public $timestamps = false;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(80)
            ->height(80)
            ->nonOptimized();
    }
}
