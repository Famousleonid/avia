<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualBushingGuide extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['document' => 'array', 'revision' => 'integer'];

    public function manuals()
    {
        return $this->belongsToMany(Manual::class, 'manual_bushing_guide_manual', 'guide_id', 'manual_id');
    }

    public static function forManual(?int $manualId): ?self
    {
        if (!$manualId) return null;
        return static::whereHas('manuals', fn ($q) => $q->where('manuals.id', $manualId))->first();
    }
}
