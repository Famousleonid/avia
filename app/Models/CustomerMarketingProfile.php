<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMarketingProfile extends Model
{
    public const STATUS_EXISTING = 'existing';
    public const STATUS_POTENTIAL = 'potential';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'customer_id',
        'lifecycle_status',
        'country_id',
        'country',
        'address',
        'city',
        'state_province',
        'street_address',
        'company_notes',
        'company_type_id',
        'segment_id',
        'terms_label',
        'owner_user_id',
        'last_contact_at',
        'next_follow_up_at',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'last_contact_at' => 'date',
        'next_follow_up_at' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyType(): BelongsTo
    {
        return $this->belongsTo(MarketingCompanyType::class, 'company_type_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(MarketingSegment::class, 'segment_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function countryRef(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
