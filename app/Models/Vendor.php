<?php

namespace App\Models;

use App\Traits\HasMediaHelpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model implements HasMedia
{
    public const CONTACT_FIELDS = ['address', 'contact_name', 'phone', 'email', 'vendor_number', 'ship_via', 'terms'];

    public static function contactRules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:1500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'vendor_number' => ['nullable', 'string', 'max:100'],
            'ship_via' => ['nullable', 'string', 'max:100'],
            'terms' => ['nullable', 'string', 'max:100'],
        ];
    }
    use HasFactory;
    use InteractsWithMedia;
    use HasMediaHelpers;
    use LogsActivity;

    protected $fillable = [
        'name',
        'is_trusted',
        'description',
        'address', 'contact_name', 'phone', 'email', 'vendor_number', 'ship_via', 'terms',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vendor')
            ->logOnly(array_merge(['name', 'is_trusted', 'description'], self::CONTACT_FIELDS))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge(['vendor_name' => $this->name]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(80)
            ->height(80)
            ->nonOptimized();
    }
}
