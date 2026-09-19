<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmReport extends Model
{
    use HasFactory;
    protected $fillable = [
        'is_admin_template',
        'manual_id',
        'manual_service_bulletin_id',
        'source_assy_option_id',
        'target_assy_option_id',
        'part_description',
        'mod_repair',
        'description',
        'ident_method',

    ];

    protected $casts = ['is_admin_template' => 'boolean'];

    public function scopeTemplatesFirst($query)
    {
        return $query->orderByDesc('is_admin_template')->orderBy('id');
    }

    public function serviceBulletin(): BelongsTo
    {
        return $this->belongsTo(ManualServiceBulletin::class, 'manual_service_bulletin_id');
    }

    public function sourceAssyOption(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'source_assy_option_id')->withTrashed();
    }

    public function targetAssyOption(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'target_assy_option_id')->withTrashed();
    }

    public function changesAssemblyScope(): bool
    {
        return $this->mod_repair === 'SB'
            && $this->source_assy_option_id !== null
            && $this->target_assy_option_id !== null;
    }
}
