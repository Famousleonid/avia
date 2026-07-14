<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingWoFileRead extends Model
{
    protected $fillable = [
        'marketing_wo_file_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(MarketingWoFile::class, 'marketing_wo_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
