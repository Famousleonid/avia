<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ManualDateRepairOrderSync
{
    public const REPAIR_ORDER = 'AT';

    public function sync(Model $row, bool $isManualDateRow): void
    {
        if (! $isManualDateRow) {
            return;
        }

        $hasAnyDate = ! empty($row->date_start) || ! empty($row->date_finish);

        if ($hasAnyDate) {
            $row->repair_order = self::REPAIR_ORDER;
            return;
        }

        if (trim((string) $row->repair_order) === self::REPAIR_ORDER) {
            $row->repair_order = null;
        }
    }
}
