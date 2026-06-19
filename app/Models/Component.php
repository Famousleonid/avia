<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
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
        'manual_id',
        'img',
        'assy_img',
        'bush_ipl_num',
        'is_bush',
        'kit',
        'np',
        'kit_prl_choice_group',
        'kit_e',
        'ndt_list',
        'cad_list',
        'stress_relief_list',
        'paint_list',
    ];

    protected $casts = [
        'log_card' => 'boolean',
        'is_bush' => 'boolean',
        'kit' => 'boolean',
        'np' => 'boolean',
        'kit_e' => 'boolean',
        'ndt_list' => 'boolean',
        'cad_list' => 'boolean',
        'stress_relief_list' => 'boolean',
        'paint_list' => 'boolean',
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

    public function assemblies(): HasMany
    {
        return $this->hasMany(ComponentAssembly::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::saving(function (Component $component): void {
            $component->ipl_num = static::normalizeIpl($component->ipl_num);

            if (! $component->manual_id || $component->ipl_num === '') {
                return;
            }

            if (
                $component->exists
                && ! $component->isDirty('manual_id')
                && ! $component->isDirty('ipl_num')
            ) {
                return;
            }

            $duplicate = static::query()
                ->where('manual_id', $component->manual_id)
                ->where('ipl_num', $component->ipl_num)
                ->when($component->exists, fn ($query) => $query->whereKeyNot($component->getKey()))
                ->first(['id', 'part_number', 'name', 'ipl_num']);

            if (! $duplicate) {
                return;
            }

            throw ValidationException::withMessages([
                'ipl_num' => sprintf(
                    'Component IPL "%s" already exists in this manual as #%d (%s %s).',
                    $component->ipl_num,
                    $duplicate->id,
                    trim((string) $duplicate->part_number) ?: '-',
                    trim((string) $duplicate->name) ?: '-'
                ),
            ]);
        });
    }

    public static function normalizeIpl(?string $ipl): string
    {
        return trim((string) $ipl);
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
