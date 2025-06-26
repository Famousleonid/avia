<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogCard extends Model
{
    use HasFactory;
    protected $fillable = [
        'manuals_id',
        'workorder_id',
        'log_card_data',
    ];

}
