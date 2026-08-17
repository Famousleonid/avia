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
        'name',
        'description',
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
     * Compatibility helper: the package belongs to the primary Manual, never
     * to an individual Unit.
     *
     * @return list<int>
     */
    public function additionalManualIds(): array
    {
        return $this->manual?->additionalManualIds() ?? [];
    }

    /** @return list<int> */
    public function manualPackageIds(): array
    {
        return $this->manual?->manualPackageIds() ?? array_values(array_filter([(int) $this->manual_id]));
    }

    public function workorders()
    {
        return $this->hasMany(\App\Models\Workorder::class, 'unit_id', 'id');
    }

}
