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
    ];



    public function manuals()
    {
        return $this->belongsTo(Manual::class,'manual_id');
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }

}
