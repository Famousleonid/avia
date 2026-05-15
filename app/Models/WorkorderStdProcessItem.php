<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderStdProcessItem extends Model
{
    protected $fillable = [
        'workorder_id',
        'component_id',
        'std_process_id',
        'std_type',
        'ipl_num',
        'part_number',
        'description',
        'process',
        'base_qty',
        'excluded_qty',
        'remaining_qty',
        'manual',
        'eff_code',
        'sort_order',
    ];

    protected $casts = [
        'base_qty' => 'integer',
        'excluded_qty' => 'integer',
        'remaining_qty' => 'integer',
        'sort_order' => 'integer',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function stdProcess(): BelongsTo
    {
        return $this->belongsTo(StdProcess::class);
    }

    /**
     * Shape used by existing STD print forms.
     *
     * @return array<string, mixed>
     */
    public function toSnapshotRow(): array
    {
        return [
            'ipl_num' => $this->ipl_num,
            'part_number' => $this->part_number,
            'description' => $this->description ?? '',
            'process' => (string) $this->process,
            'qty' => (int) $this->remaining_qty,
            'manual' => $this->manual,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($this->eff_code) ?? '',
        ];
    }
}
