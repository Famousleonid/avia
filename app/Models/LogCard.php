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
        'component_data_out',
        'destruction_certificate_data',
    ];

    protected $casts = [
        'component_data_out' => 'array',
        'destruction_certificate_data' => 'array',
    ];
}
