<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scope extends Model
{

    protected $fillable = ['scope'];
    public $timestamps = false;

    public function manual()
    {
        return $this->hasMany(Manual::class);
    }
}
