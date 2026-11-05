<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    use HasFactory;
    public $timestamps = false;

    /** Seeded condition name for parts missing upon arrival. */
    public const NAME_PARTS_MISSING = 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST';

    protected $fillable = [
        'name','unit',
    ];

    protected $casts = ['archived_at' => 'datetime'];

    // History relations deliberately include archived versions. Only pickers filter them.
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeAvailableForInspection($query, $workorderId)
    {
        return $query->where('unit', true)->where(function ($query) use ($workorderId) {
            $query->active()->orWhereIn('id', WorkorderUnitInspection::query()
                ->where('workorder_id', $workorderId)->select('condition_id'));
        });
    }

    public function tdr()
    {
        return $this->hasMany(Tdr::class, 'conditions_id');
    }

    /** The seeded "parts missing upon arrival" condition. */
    public static function partsMissing(): ?self
    {
        return static::where('name', self::NAME_PARTS_MISSING)->first();
    }
}
