<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_sca'];

    protected $casts = ['is_sca' => 'boolean'];

    public function rows()
    {
        return $this->hasMany(TrainingMatrixRow::class)->orderBy('sort_order');
    }
}
