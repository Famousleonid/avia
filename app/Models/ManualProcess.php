<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualProcess extends Model
{
    use HasFactory;
    protected $fillable = [

        'manual_id',
        'processes_id',
        'process_comment',
        'is_locked',
        'locked_by_user_id',
        'locked_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(Process::class, 'processes_id');
    }

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manual_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function processNameLock()
    {
        $processNameId = $this->process?->process_names_id;

        if (! $this->manual_id || ! $processNameId) {
            return null;
        }

        return ManualProcessNameLock::query()
            ->where('manual_id', $this->manual_id)
            ->where('process_name_id', $processNameId)
            ->first();
    }

    public function isGroupLocked(): bool
    {
        return $this->processNameLock() !== null;
    }

    public function isEffectivelyLocked(): bool
    {
        return $this->is_locked;
    }
}
