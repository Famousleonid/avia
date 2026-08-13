<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManualPartGroup extends Model
{
    use SoftDeletes;

    public const BEHAVIOR_CHOOSE_ONE = 'choose_one';
    public const BEHAVIOR_BUNDLE = 'bundle';

    public const TYPE_ALTERNATIVE = 'alternative_pn';
    public const TYPE_OVERSIZE = 'oversize';
    public const TYPE_ASSY = 'assy';
    public const TYPE_SB_KIT = 'sb_kit';

    public const SCOPE_PRL = 'prl';
    public const SCOPE_NDT = 'ndt';
    public const SCOPE_CAD = 'cad';
    public const SCOPE_STRESS = 'stress';
    public const SCOPE_PAINT = 'paint';

    protected $fillable = [
        'manual_id',
        'manual_service_bulletin_id',
        'code',
        'name',
        'behavior',
        'type',
        'applies_to',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'applies_to' => 'array',
    ];

    public static function validTypes(): array
    {
        return [self::TYPE_ALTERNATIVE, self::TYPE_OVERSIZE, self::TYPE_ASSY, self::TYPE_SB_KIT];
    }

    public static function validScopes(): array
    {
        return [self::SCOPE_PRL, self::SCOPE_NDT, self::SCOPE_CAD, self::SCOPE_STRESS, self::SCOPE_PAINT];
    }

    public static function behaviorForType(string $type): string
    {
        return in_array($type, [self::TYPE_ASSY, self::TYPE_SB_KIT], true)
            ? self::BEHAVIOR_BUNDLE
            : self::BEHAVIOR_CHOOSE_ONE;
    }

    public function appliesTo(string $scope): bool
    {
        return in_array($scope, $this->applies_to ?: self::validScopes(), true);
    }

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function serviceBulletin(): BelongsTo
    {
        return $this->belongsTo(ManualServiceBulletin::class, 'manual_service_bulletin_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ManualPartGroupOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(WorkorderPartGroupSelection::class);
    }
}
