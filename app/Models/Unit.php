<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Unit extends Model
{

    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'part_number',
        'verified',
        'eff_code',
        'manual_id',
        'additional_manual_ids',
        'name',
        'description',
    ];

    protected $casts = [
        'additional_manual_ids' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('unit')
            ->logOnly([
                'part_number',
                'verified',
                'eff_code',
                'manual_id',
                'additional_manual_ids',
                'name',
                'description',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }



    public function manual()
    {

        return $this->belongsTo(\App\Models\Manual::class, 'manual_id', 'id');
    }

    public function manuals()
    {
        return $this->manual();
    }

    /**
     * Additional CMM ids configured for future workorders of this unit.
     * The legacy manual_id remains the primary CMM and is never duplicated here.
     *
     * @return list<int>
     */
    public function additionalManualIds(): array
    {
        $primaryManualId = (int) ($this->manual_id ?? 0);

        return collect($this->additional_manual_ids ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== $primaryManualId)
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<int> */
    public function manualPackageIds(): array
    {
        $primaryManualId = (int) ($this->manual_id ?? 0);

        return collect([$primaryManualId])
            ->merge($this->additionalManualIds())
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function workorders()
    {
        return $this->hasMany(\App\Models\Workorder::class, 'unit_id', 'id');
    }

}
