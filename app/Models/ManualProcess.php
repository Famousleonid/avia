<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualProcess extends Model
{
    use HasFactory;
    protected $fillable = [

        'manual_id',
        'processes_id',
    ];
}
