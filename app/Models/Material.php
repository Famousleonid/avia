<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{

    protected $fillable = ['code', 'material', 'specification', 'ver', 'description'];
    public $timestamps = false;
}
