<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class LogCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'component_data',
        'component_data_out',
        'destruction_certificate_data',
    ];

    protected $casts = [
        'component_data_out' => 'array',
        'destruction_certificate_data' => 'array',
    ];

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    public static function buildActivityChanges(array $fieldPairs): array
    {
        $old = [];
        $attributes = [];

        foreach ($fieldPairs as $field => [$before, $after]) {
            if (in_array($field, ['component_data', 'component_data_out', 'destruction_certificate_data'], true)) {
                [$fieldOld, $fieldNew] = static::diffComplexField($field, $before, $after);
                $old = array_merge($old, $fieldOld);
                $attributes = array_merge($attributes, $fieldNew);
                continue;
            }

            if ($before !== $after) {
                $old[$field] = $before;
                $attributes[$field] = $after;
            }
        }

        return [
            'old' => $old,
            'attributes' => $attributes,
        ];
    }

    public static function summarizeForActivity(?self $logCard): array
    {
        $componentRows = static::decodeActivityValue($logCard?->component_data);
        $componentOutRows = static::decodeActivityValue($logCard?->component_data_out);
        $certificate = static::decodeActivityValue($logCard?->destruction_certificate_data);

        return [
            'workorder_id' => $logCard?->workorder_id,
            'component_rows_count' => is_array($componentRows) ? count($componentRows) : 0,
            'component_out_rows_count' => is_array($componentOutRows) ? count($componentOutRows) : 0,
            'has_destruction_certificate' => is_array($certificate) && $certificate !== [],
        ];
    }

    public function logActivityEvent(string $event, array $old = [], array $attributes = [], array $extra = []): void
    {
        if ($old === [] && $attributes === [] && $extra === []) {
            return;
        }

        $logger = activity('log_card')
            ->performedOn($this)
            ->event($event)
            ->withProperties(array_filter([
                'old' => $old !== [] ? $old : null,
                'attributes' => $attributes !== [] ? $attributes : null,
                'meta' => $extra !== [] ? $extra : null,
            ], static fn ($value) => $value !== null));

        if (auth()->check()) {
            $logger->causedBy(auth()->user());
        }

        $logger->log('log_card_'.$event);
    }

    private static function diffComplexField(string $field, mixed $before, mixed $after): array
    {
        $beforeMap = static::flattenActivityValue(static::decodeActivityValue($before), $field);
        $afterMap = static::flattenActivityValue(static::decodeActivityValue($after), $field);
        $keys = array_values(array_unique(array_merge(array_keys($beforeMap), array_keys($afterMap))));

        $old = [];
        $attributes = [];

        foreach ($keys as $key) {
            $beforeValue = $beforeMap[$key] ?? null;
            $afterValue = $afterMap[$key] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $old[$key] = $beforeValue;
            $attributes[$key] = $afterValue;
        }

        return [$old, $attributes];
    }

    private static function decodeActivityValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    private static function flattenActivityValue(mixed $value, string $prefix): array
    {
        if (! is_array($value)) {
            return [$prefix => $value];
        }

        if ($value === []) {
            return [$prefix => []];
        }

        $flattened = [];
        foreach ($value as $key => $nestedValue) {
            $nestedPrefix = $prefix === ''
                ? (string) $key
                : $prefix.'.'.$key;

            $flattened = array_merge($flattened, static::flattenActivityValue($nestedValue, $nestedPrefix));
        }

        return $flattened;
    }
}
