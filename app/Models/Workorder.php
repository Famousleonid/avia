<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Workorder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use LogsActivity;

    protected $fillable = ['number', 'user_id', 'unit_id', 'instruction_id', 'customer_id', 'approve', 'approve_at', 'description', 'notes', 'manual', 'serial_number', 'place', 'created_at'];

    protected $dates = ['approve_at'];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnly(['number', 'user_id', 'unit_id', 'instruction_id', 'customer_id', 'approve', 'description', 'notes', 'manual', 'serial_number', 'place'])
            ->logOnlyDirty();

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