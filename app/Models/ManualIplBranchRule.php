<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualIplBranchRule extends Model
{
    protected $fillable = [
        'manual_id',
        'is_default',
        'unit_match_value',
        'include_prefix',
        'exclude_prefix',
    ];

    protected $casts = [
        'is_default' => 'bool',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function displayLabel(): string
    {
        $include = trim((string) $this->include_prefix);
        $exclude = trim((string) $this->exclude_prefix);

        if ($this->is_default) {
            return 'Default -> '.$include.' / '.$exclude;
        }

        return trim((string) $this->unit_match_value).' -> '.$include.' / '.$exclude;
    }
}
