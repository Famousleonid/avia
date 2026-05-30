<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuantumRoLine extends Model
{
    protected $fillable = [
        'last_sync_run_id',
        'source_uid',
        'roh_auto_key',
        'rod_auto_key',
        'wob_auto_key',
        'woo_auto_key',
        'pnm_auto_key',
        'ro_number',
        'wo_number',
        'vendor_name',
        'pn',
        'description',
        'class',
        'entry_date',
        'out_date',
        'returned_date',
        'ro_last_modified',
        'detail_last_modified',
        'source_last_modified',
        'qty_repair',
        'qty_reserved',
        'qty_repaired',
        'source_hash',
        'raw_payload',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'out_date' => 'datetime',
        'returned_date' => 'datetime',
        'ro_last_modified' => 'datetime',
        'detail_last_modified' => 'datetime',
        'source_last_modified' => 'datetime',
        'qty_repair' => 'decimal:4',
        'qty_reserved' => 'decimal:4',
        'qty_repaired' => 'decimal:4',
        'raw_payload' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(QuantumRoSyncRun::class, 'last_sync_run_id');
    }
}
