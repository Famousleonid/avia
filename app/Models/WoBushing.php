<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoBushing extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'bush_data',
    ];

    protected $casts = [
        'bush_data' => 'array',
    ];

    public function workorder()
    {
        return $this->belongsTo(Workorder::class);
    }
}
