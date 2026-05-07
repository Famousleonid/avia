<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPartLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'manual_id',
        'locked_by_user_id',
        'locked_at',
        'notes',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
