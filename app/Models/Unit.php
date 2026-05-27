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

    public function workorders()
    {
        return $this->hasMany(\App\Models\Workorder::class, 'unit_id', 'id');
    }

}
