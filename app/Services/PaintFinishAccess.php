<?php

namespace App\Services;

use App\Models\User;

class PaintFinishAccess
{
    public function authorizeUpdate(?User $user, iterable $processes, array $data, bool $traveler = false): void
    {
        if ($user?->canEditPaintFinishDate()) {
            return;
        }

        foreach ($processes as $process) {
            if (strtolower(trim((string) $process->processName?->identityName())) !== 'paint') {
                continue;
            }

            // Clearing a Traveler start also clears its finish, so protect that path too.
            $clearsFinish = $traveler && array_key_exists('date_start', $data)
                && empty($data['date_start']) && $process->date_finish !== null;
            abort_if(array_key_exists('date_finish', $data) || $clearsFinish, 403,
                'Only Admin or technicians from Never stop`s team can edit the Paint finish date.');
        }
    }
}
