<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['partnumber', 'description', 'lib', 'manufacturer', 'aircraft'];


    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }


}