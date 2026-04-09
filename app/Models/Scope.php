<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scope extends Model
{

    protected $fillable = ['scope'];
    public $timestamps = false;

    public function manuals()
    {
        return $this->hasMany(Manual::class, 'scopes_id');
    }

    public function manual()
    {
        return $this->manuals();
    }
}
