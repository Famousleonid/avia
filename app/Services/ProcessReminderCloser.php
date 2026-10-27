<?php

namespace App\Services;

use App\Models\TdrProcess;
use App\Models\User;
use App\Models\WorkorderStdProcess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class ProcessReminderCloser
{
    public function forUser(User $user): void
    {
        $this->close($user->notifications()->getQuery());
    }

    public function afterSave(TdrProcess|WorkorderStdProcess $process): void
    {
        if (! $process->date_start || ! $process->wasChanged('date_start')) return;
        $workorderId = $process instanceof WorkorderStdProcess
            ? $process->workorder_id : $process->tdr?->workorder_id;
        if (! $workorderId) return;
        $this->close(DatabaseNotification::query()->where(function (Builder $query) use ($workorderId): void {
            $query->where('data->ui->workorder->id', $workorderId)
                ->orWhere('data->payload->workorder_id', $workorderId);
        }));
    }

    private function close(Builder $query): void
    {
        $cache = [];
        $ids = [];
        foreach ($query->whereNull('read_at')->where('data->type', 'workorder')
            ->where('data->event', 'process_ready_for_next')->get() as $notification) {
            $data = $notification->data;
            $wo = (int) (data_get($data, 'ui.workorder.id') ?? data_get($data, 'payload.workorder_id'));
            $id = (int) (data_get($data, 'ui.process.id') ?? data_get($data, 'payload.process_id'));
            $type = data_get($data, 'payload.subject_type');
            $subjectId = (int) data_get($data, 'payload.subject_id');
            $name = data_get($data, 'ui.process.name') ?? data_get($data, 'payload.process_name');
            $key = json_encode([$wo, $id, $type, $subjectId, $name]);
            if (! array_key_exists($key, $cache)) {
                $cache[$key] = $this->hasStarted($wo, $id, $type, $subjectId, $name);
            }
            if ($cache[$key]) $ids[] = $notification->id;
        }
        if ($ids) DatabaseNotification::query()->whereIn('id', $ids)->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function hasStarted(int $wo, int $id, ?string $type, int $subjectId, ?string $name): bool
    {
        if (! $wo) return false;
        $tdr = TdrProcess::query()->whereHas('tdr', fn ($q) => $q->where('workorder_id', $wo));
        $std = WorkorderStdProcess::query()->where('workorder_id', $wo);
        // New notices carry the model identity; URL IDs alone can overlap across tables.
        if ($type) {
            if (! $subjectId) return false;
            $process = match ($type) {
                TdrProcess::class => $tdr->find($subjectId),
                WorkorderStdProcess::class => $std->find($subjectId),
                default => null,
            };
            return $process && ! empty($process->date_start);
        }
        if (! $id || ! $name) return false;
        // Legacy notices used source_tdr_process_id for STD links. Keep ambiguous
        // notices open until every matching candidate has actually been sent.
        $matches = $tdr->whereKey($id)->whereHas('processName', fn ($q) => $q->where('name', $name))->get()
            ->concat($std->where(fn ($q) => $q->where('source_tdr_process_id', $id)
                ->orWhere(fn ($q) => $q->whereNull('source_tdr_process_id')->where('id', $id)))
                ->whereHas('processName', fn ($q) => $q->where('name', $name))->get());
        return $matches->isNotEmpty() && $matches->every(fn ($process) => ! empty($process->date_start));
    }
}
