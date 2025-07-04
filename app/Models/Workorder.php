<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Workorder extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes, HasMediaHelpers;

    protected $fillable = ['number', 'user_id', 'unit_id', 'instruction_id', 'external_damage','received_disassembly','nameplate_missing','disassembly_upon_arrival',
        'preliminary_test_false','part_missing','extra_parts','new_parts', 'open_at', 'customer_id', 'approve', 'approve_at', 'description', 'manual',
        'serial_number', 'place', 'created_at','amdt', 'rm_report'];

    protected $dates = ['approve_at','deleted_at','open_at'];

    public $mediaUrlName = 'workorders';


    public function tdrs()
    {
        return $this->hasMany(Tdr::class,'workorder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function instruction()
    {
        return $this->belongsTo(Instruction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function main()
    {
        return $this->hasMany(Main::class);
    }

    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(80)
            ->height(80)
            ->nonOptimized();

    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnly(['number', 'user_id', 'unit_id', 'instruction_id', 'customer_id', 'approve', 'description', 'notes', 'manual', 'serial_number', 'place', 'open_at','amdt'])
            ->logOnlyDirty();

    }

}
