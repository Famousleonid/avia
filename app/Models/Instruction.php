<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['name'];

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }


}
