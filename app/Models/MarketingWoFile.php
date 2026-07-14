<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MarketingWoFile extends Model
{
    use SoftDeletes;

    public const COLLECTION = 'marketing_manager_files';

    public const CATEGORIES = [
        'estimate' => 'Estimate',
        'customer_approval' => 'Customer Approval',
        'po_ro' => 'PO / RO',
        'invoice' => 'Invoice',
        'shipping_awb' => 'Shipping / AWB',
        'correspondence' => 'Correspondence',
        'other' => 'Other',
    ];

    protected $fillable = [
        'workorder_id',
        'media_id',
        'uploaded_by_user_id',
        'category',
        'display_name',
        'comment',
        'version_group',
        'version_number',
    ];

    protected $casts = [
        'version_number' => 'integer',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id')->withTrashed();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MarketingWoFileRecipient::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MarketingWoFileRead::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Other';
    }
}
