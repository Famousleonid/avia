<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderAssyConfigurationChoice extends Model
{
    protected $fillable = [
        'workorder_id',
        'parent_option_id',
        'choice_slot',
        'selected_coverage_id',
        'selected_by_user_id',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public function parentOption(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'parent_option_id');
    }

    public function selectedCoverage(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupCoverage::class, 'selected_coverage_id');
    }
}
