<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RmReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'manual_id',
        'part_description',
        'mod_repair',
        'description',
        'ident_method',

    ];
}
