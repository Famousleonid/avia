<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintMark extends Model
{
    protected $fillable = [
        'token',
        'workorder_id',
        'workorder_number',
        'form_name',
        'requirement_warnings',
        'printed_by_user_id',
        'printed_by_name',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'requirement_warnings' => 'array',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_user_id');
    }
}
