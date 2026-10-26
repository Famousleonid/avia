<?php

namespace App\Services\Quality;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Str;

class ShipmentReleaseTechnician
{
    public function resolve(Workorder $workorder): ?User
    {
        $override = data_get($workorder->certificate_data, 'shipment_release.technician_id');
        if ($override && ($user = User::withTrashed()->find($override))) {
            return $user;
        }

        $submission = $workorder->main()->with(['task', 'user'])->get()
            ->filter(function ($row): bool {
                $name = Str::lower((string) $row->task?->name);
                return ! $row->ignore_row && $row->user
                    && ($row->date_finish || $row->date_start)
                    && Str::contains($name, 'submitted') && Str::contains($name, 'final');
            })
            ->sortByDesc(fn ($row) => ($row->date_finish ?? $row->date_start)->format('Y-m-d').' '.sprintf('%020d', $row->id))
            ->first();

        return $submission?->user ?? $workorder->user;
    }
}
