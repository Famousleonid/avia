<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TdrProcess extends Model
{
    use HasFactory;
    protected $fillable = [

        'tdrs_id','processes_id',
       ' date_start','date_finish',
    ];
}

