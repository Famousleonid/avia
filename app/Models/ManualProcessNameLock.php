<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualProcessNameLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'manual_id',
        'process_name_id',
        'locked_by_user_id',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }

    public function processName()
    {
        return $this->belongsTo(ProcessName::class, 'process_name_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
