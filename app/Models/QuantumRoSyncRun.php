<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuantumRoSyncRun extends Model
{
    protected $fillable = [
        'source',
        'bridge_id',
        'status',
        'filters',
        'rows_received',
        'rows_inserted',
        'rows_updated',
        'rows_unchanged',
        'started_at',
        'finished_at',
        'message',
    ];

    protected $casts = [
        'filters' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(QuantumRoLine::class, 'last_sync_run_id');
    }
}
