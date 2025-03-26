<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'name','unit',
    ];

    public function tdr()
    {
        return $this->hasMany(Tdr::class);
    }
}
