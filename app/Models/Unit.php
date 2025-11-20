<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{

    use HasFactory;
    protected $fillable = [
        'part_number',
        'verified',
        'eff_code',
        'manual_id',
        'name',
        'description',
    ];



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
