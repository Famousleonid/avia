<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPartGroupCoverage extends Model
{
    protected $fillable = [
        'manual_part_group_option_id',
        'component_id',
        'covered_manual_part_group_option_id',
        'legacy_component_assembly_id',
        'qty',
        'applies_to',
    ];

    protected $casts = [
        'component_id' => 'integer',
        'covered_manual_part_group_option_id' => 'integer',
        'legacy_component_assembly_id' => 'integer',
        'qty' => 'integer',
        'applies_to' => 'array',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'manual_part_group_option_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function coveredOption(): BelongsTo
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'covered_manual_part_group_option_id');
    }

    public function legacyAssembly(): BelongsTo
    {
        return $this->belongsTo(ComponentAssembly::class, 'legacy_component_assembly_id');
    }

    public function appliesTo(string $scope): bool
    {
        return in_array($scope, $this->applies_to ?: ManualPartGroup::validScopes(), true);
    }
}
