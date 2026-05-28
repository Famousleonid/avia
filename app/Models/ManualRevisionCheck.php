<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualRevisionCheck extends Model
{
    public const STATUS_UNCHANGED = 'unchanged';
    public const STATUS_CHANGED = 'changed';

    protected $fillable = [
        'manual_id',
        'revision_number',
        'revision_date',
        'checked_at',
        'checked_by_user_id',
        'checked_by_stamp',
        'status',
        'notes',
    ];

    protected $casts = [
        'revision_date' => 'date',
        'checked_at' => 'date',
    ];

    public function manual()
    {
        return $this->belongsTo(Manual::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }
}
