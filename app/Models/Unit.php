<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{

    protected $fillable = ['part_number', 'verified', 'manual_id'];


    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }


}
