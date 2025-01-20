<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Workorder extends Model implements HasMedia
{
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['number', 'user_id', 'unit_id', 'instruction_id',
        'external_damage','received_disassembly','nameplate_missing','disassembly_upon_arrival',
        'preliminary_test_false','part_missing','extra_parts',
        'open_at', 'customer_id', 'approve', 'approve_at',
        'description', 'manual', 'serial_number', 'place', 'created_at','amdt'];

    protected $dates = ['approve_at','deleted_at','open_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnly(['number', 'user_id', 'unit_id', 'instruction_id', 'customer_id', 'approve', 'description', 'notes', 'manual', 'serial_number', 'place', 'open_at','amdt'])
            ->logOnlyDirty();

    }
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

    public function registerAllMediaConversions(): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonOptimized();

    }
}
