<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DateNotification extends Model
{
    protected $fillable = [
        'name',
        'run_month',
        'run_day',
        'repeats_yearly',
        'run_year',
        'enabled',
        'title',
        'message',
        'respect_user_preferences',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'respect_user_preferences' => 'boolean',
        'run_month' => 'integer',
        'run_day' => 'integer',
        'repeats_yearly' => 'boolean',
        'run_year' => 'integer',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(DateNotificationRecipient::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DateNotificationLog::class);
    }
}
