<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manual extends Model
{
    use HasFactory;
    protected $fillable = [
        'number',
        'title',
        'img',
        'revision_date',
        'active',
        'units_pn',
        'units_tr',
        'lib',
        'planes_id',
        'builders_id',
        'scopes_id',
    ];

    // Отношение с моделью AirCraft
    public function plane()
    {
        return $this->belongsTo(Plane::class, 'planes_id');
    }

    // Отношение с моделью MFR
    public function builder()
    {
        return $this->belongsTo(Builder::class, 'builders_id');
    }

    // Отношение с моделью Scope
    public function scope()
    {
        return $this->belongsTo(Scope::class, 'scopes_id');
    }

    // Отношение с моделью Training
    public function trainings()
    {
        return $this->hasMany(Training::class, 'manuals_id');
    }

    // Отношение с моделью Unit
    public function units()
    {
        return $this->hasMany(Unit::class,'manuals_id');
    }
    public function manuals()
    {
        return $this->belongsTo(Manual::class, 'manuals_id');
    }
}
