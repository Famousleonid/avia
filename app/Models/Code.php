<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
    ];
    public function tdr_component()
    {
        return $this->hasMany(TdrComponent::class, 'codes_id');
    }

}
