<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customer')
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge(['customer_name' => $this->name]);
    }

    public function workorder()
    {
        return $this->hasMany(Workorder::class);
    }

    public function workorders(): HasMany
    {
        return $this->hasMany(Workorder::class);
    }

    public function marketingProfile(): HasOne
    {
        return $this->hasOne(CustomerMarketingProfile::class);
    }

    public function marketingAircraft(): HasMany
    {
        return $this->hasMany(CustomerAircraft::class);
    }

    public function marketingContacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class)
            ->orderByRaw("
                CASE contact_type
                    WHEN 'WO Estimates' THEN 0
                    WHEN 'WO Estimates/ Invoices' THEN 1
                    WHEN 'Invoices' THEN 2
                    WHEN 'Other' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function marketingNotes(): HasMany
    {
        return $this->hasMany(CustomerInteractionNote::class)->latest('interaction_at')->latest('id');
    }

}
