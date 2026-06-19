<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerContact extends Model
{
    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'phone',
        'is_primary',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerInteractionNote::class, 'contact_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim((string) $this->first_name . ' ' . (string) $this->last_name);
    }
}
