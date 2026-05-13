<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualServiceBulletin;
use App\Models\Workorder;
use App\Models\WorkorderServiceBulletinLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceBulletinLogController extends Controller
{
    public function show(Workorder $workorder): View
    {
        $workorder->loadMissing(['unit.manuals', 'serviceBulletinLogs.stampUser']);
        $manual = $workorder->unit?->manuals;

        $serviceBulletins = collect();
        if ($manual) {
            $serviceBulletins = ManualServiceBulletin::query()
                ->where('manual_id', $manual->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $logsByBulletin = $workorder->serviceBulletinLogs
            ->keyBy('manual_service_bulletin_id');

        return view('admin.tdrs.serviceBulletinLog', [
            'current_wo' => $workorder,
            'manual' => $manual,
            'serviceBulletins' => $serviceBulletins,
            'logsByBulletin' => $logsByBulletin,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Workorder $workorder): RedirectResponse
    {
        $workorder->loadMissing(['unit.manuals']);
        $manual = $workorder->unit?->manuals;
        abort_if(! $manual, 404);

        $statusValues = implode(',', array_keys($this->statusOptions()));

        $data = $request->validate([
            'rows' => ['array'],
            'rows.*.status' => ['nullable', 'in:'.$statusValues],
            'rows.*.notes' => ['nullable', 'string'],
            'clear_status_bulletin_id' => ['nullable', 'integer'],
        ]);

        $allowedBulletinIds = ManualServiceBulletin::query()
            ->where('manual_id', $manual->id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $clearStatusBulletinId = (int) ($data['clear_status_bulletin_id'] ?? 0);

        foreach (($data['rows'] ?? []) as $bulletinId => $row) {
            $bulletinId = (int) $bulletinId;
            if (! in_array($bulletinId, $allowedBulletinIds, true)) {
                continue;
            }

            $status = $bulletinId === $clearStatusBulletinId ? null : ($row['status'] ?? null);
            $notes = trim((string) ($row['notes'] ?? ''));

            if ($status === null && $notes === '') {
                WorkorderServiceBulletinLog::query()
                    ->where('workorder_id', $workorder->id)
                    ->where('manual_service_bulletin_id', $bulletinId)
                    ->delete();
                continue;
            }

            WorkorderServiceBulletinLog::query()->updateOrCreate(
                [
                    'workorder_id' => $workorder->id,
                    'manual_service_bulletin_id' => $bulletinId,
                ],
                [
                    'status' => $status,
                    'stamp_user_id' => $status ? Auth::id() : null,
                    'stamped_at' => $status ? now() : null,
                    'notes' => $notes !== '' ? $notes : null,
                ]
            );
        }

        return redirect()
            ->route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id])
            ->with('success', 'Service Bulletin Log saved.');
    }

    private function statusOptions(): array
    {
        return [
            WorkorderServiceBulletinLog::STATUS_NOT_CARRIED_OUT => 'Not Carried out.',
            WorkorderServiceBulletinLog::STATUS_PREVIOUSLY_CARRIED_OUT => 'Previously Carried Out',
            WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT => 'AT Carried Out',
        ];
    }
}
