<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualServiceBulletin extends Model
{
    use HasFactory, SoftDeletes;

    public const REQUIREMENT_OPTIONAL = 'optional';
    public const REQUIREMENT_RECOMMENDED = 'recommended';
    public const REQUIREMENT_MANDATORY = 'mandatory';

    protected $fillable = [
        'manual_id',
        'sort_order',
        'year_introduced',
        'ac_mfg_service_bulletin_no',
        'oem_service_bulletin_no',
        'awd_no',
        'identification_method',
        'description',
        'default_requirement',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function workorderLogs(): HasMany
    {
        return $this->hasMany(WorkorderServiceBulletinLog::class);
    }
}
