<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderPartGroupSelection extends Model
{
    protected $fillable = [
        'workorder_id',
        'manual_part_group_id',
        'manual_part_group_option_id',
        'qty',
        'selected_by_user_id',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroup::class, 'manual_part_group_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'manual_part_group_option_id');
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_user_id');
    }
}
