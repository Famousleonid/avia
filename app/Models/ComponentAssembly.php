<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ComponentAssembly extends Model implements HasMedia
{
    use HasMediaHelpers, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'component_id',
        'assy_part_number',
        'assy_ipl_num',
        'units_assy',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'component_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('component_assembly')
            ->logOnly([
                'component_id',
                'assy_part_number',
                'assy_ipl_num',
                'units_assy',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->fit('crop', 100, 100)
            ->nonOptimized();
    }
}
