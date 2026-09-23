<?php

namespace App\Services;

use App\Models\Workorder;

class PartReceiptAudit
{
    public static function authorize(): void
    {
        abort_unless(auth()->user()?->hasAnyRole('Admin|Manager'), 403);
    }

    public static function record(Workorder $workorder, string $rowKey, array $old, array $new): void
    {
        $before = $after = [];
        foreach ($new as $field => $value) {
            $previous = $old[$field] ?? null;
            if ($previous instanceof \DateTimeInterface) $previous = $previous->format('Y-m-d');
            if ($value instanceof \DateTimeInterface) $value = $value->format('Y-m-d');
            if ((string) $previous === (string) $value) continue;
            $before[$field] = $previous;
            $after[$field] = $value;
        }
        if (!$after) return;
        activity('part_receipt')->performedOn($workorder)->causedBy(auth()->user())
            ->event('updated')->withProperties([
                'workorder_id' => $workorder->id, 'workorder_number' => $workorder->number,
                'row_key' => $rowKey, 'old' => $before, 'attributes' => $after,
            ])->log('Parts receipt changed');
    }
}
