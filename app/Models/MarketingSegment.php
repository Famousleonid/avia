<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSegment extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(CustomerMarketingProfile::class, 'segment_id');
    }
}
