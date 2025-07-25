<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogCard extends Model
{
    use HasFactory;
    protected $fillable = [
        'workorder_id',
        'component_data',
    ];
    protected $casts = [
        'components_data' => 'array',
    ];
}
