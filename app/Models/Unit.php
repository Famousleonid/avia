<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Unit extends Model
{

    public const SCOPE_FULL_UNIT = 'full_unit';
    public const SCOPE_COMPONENT = 'component';
    public const SCOPE_PART_GROUP_OPTION = 'part_group_option';

    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'part_number',
        'verified',
        'eff_code',
        'manual_id',
        'name',
        'description',
        'default_scope_type',
        'default_scope_component_id',
        'default_scope_part_group_option_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('unit')
            ->logOnly([
                'part_number',
                'verified',
                'eff_code',
                'manual_id',
                'name',
                'description',
                'default_scope_type',
                'default_scope_component_id',
                'default_scope_part_group_option_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }



    public function manual()
    {

        return $this->belongsTo(\App\Models\Manual::class, 'manual_id', 'id');
    }

    public function manuals()
    {
        return $this->manual();
    }

    /**
     * Compatibility helper: the package belongs to the primary Manual, never
     * to an individual Unit.
     *
     * @return list<int>
     */
    public function additionalManualIds(): array
    {
        return $this->manual?->additionalManualIds() ?? [];
    }

    /** @return list<int> */
    public function manualPackageIds(): array
    {
        return $this->manual?->manualPackageIds() ?? array_values(array_filter([(int) $this->manual_id]));
    }

    public function workorders()
    {
        return $this->hasMany(\App\Models\Workorder::class, 'unit_id', 'id');
    }

    public static function validScopeTypes(): array
    {
        return [
            self::SCOPE_FULL_UNIT,
            self::SCOPE_COMPONENT,
            self::SCOPE_PART_GROUP_OPTION,
        ];
    }

    public function defaultScopeComponent()
    {
        return $this->belongsTo(Component::class, 'default_scope_component_id')->withTrashed();
    }

    public function defaultScopePartGroupOption()
    {
        return $this->belongsTo(ManualPartGroupOption::class, 'default_scope_part_group_option_id')->withTrashed();
    }

    /** @return array{scope_type:string,scope_component_id:?int,scope_part_group_option_id:?int} */
    public function workorderScopeSnapshot(): array
    {
        $type = in_array($this->default_scope_type, self::validScopeTypes(), true)
            ? $this->default_scope_type
            : self::SCOPE_FULL_UNIT;

        return [
            'scope_type' => $type,
            'scope_component_id' => $type === self::SCOPE_COMPONENT
                ? ($this->default_scope_component_id ? (int) $this->default_scope_component_id : null)
                : null,
            'scope_part_group_option_id' => $type === self::SCOPE_PART_GROUP_OPTION
                ? ($this->default_scope_part_group_option_id ? (int) $this->default_scope_part_group_option_id : null)
                : null,
        ];
    }

}
