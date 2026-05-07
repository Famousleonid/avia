<?php

namespace App\Services\Quality;

use App\Models\Training;
use App\Models\Workorder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QualityAssuranceService
{
    private Collection $trainingLookup;

    public function __construct(
        private readonly int $trainingRenewalThresholdDays = 340,
    ) {
        $this->trainingLookup = collect();
    }

    public function buildWorkorderQaRows(Collection $workorders): Collection
    {
        $this->primeTrainingLookup($workorders);

        return $workorders
            ->map(fn (Workorder $workorder) => $this->analyzeWorkorder($workorder))
            ->values();
    }

    public function analyzeWorkorder(Workorder $workorder): array
    {
        $isDone = $workorder->isDone();
        $manual = $workorder->unit?->manual;
        $tdrCollection = $workorder->tdrs ?? collect();
        $firstComponent = $tdrCollection->first()?->component;

        $processes = $this->checkProcesses($workorder);
        $photos = $this->checkPhotos($workorder);
        $training = $this->checkTraining($workorder);
        $qualityDocuments = $this->checkQualityDocuments($workorder);

        $warnings = [];
        $criticals = [];

        if ($processes['counts']['incomplete'] > 0) {
            $message = $isDone ? 'Process not finished' : 'Incomplete processes';
            if ($isDone) {
                $criticals[] = $message;
            } else {
                $warnings[] = $message;
            }
        }

        if ($processes['counts']['finished_without_start'] > 0) {
            $warnings[] = 'Process finished without start date';
        }

        if ($processes['counts']['missing_ro'] > 0) {
            $warnings[] = 'Missing RO';
        }

        if ($photos['missing_any']) {
            $warnings[] = 'Missing photos';
        }

        if ($photos['missing_damage']) {
            $warnings[] = 'Missing damage photos';
        }

        if (! $training['available']) {
            $warnings[] = 'Training check not available';
        } elseif ($training['missing']) {
            $warnings[] = 'No training record';
        } elseif ($training['expired']) {
            $warnings[] = 'Training expired';
        }

        if ($qualityDocuments['missing']) {
            if ($isDone) {
                $criticals[] = 'No quality documents';
            } else {
                $warnings[] = 'No quality documents';
            }
        }

        if (! $workorder->approve_at) {
            if ($isDone) {
                $criticals[] = 'Not approved';
            } else {
                $warnings[] = 'Not approved';
            }
        }

        if (blank($workorder->customer_po)) {
            $warnings[] = 'Missing customer PO';
        }

        if (blank($workorder->serial_number) || $tdrCollection->contains(fn ($tdr) => blank($tdr->serial_number))) {
            $warnings[] = 'Missing serial number';
        }

        $tdrMissingData = $tdrCollection->contains(function ($tdr) {
            return blank($tdr->conditions_id) || blank($tdr->necessaries_id) || blank($tdr->codes_id);
        });
        if ($tdrMissingData) {
            $warnings[] = 'Missing TDR data';
        }

        $manualMissing = ! $manual || blank($manual->number) || blank($manual->lib) || blank($manual->revision_date);
        if ($manualMissing) {
            $warnings[] = 'Manual / revision info missing';
        }

        if (! $isDone && $workorder->approve_at) {
            $warnings[] = 'Completed task not finished';
        }

        $warnings = array_values(array_unique(array_filter($warnings)));
        $criticals = array_values(array_unique(array_filter($criticals)));
        $status = $this->calculateStatus(['warnings' => $warnings, 'criticals' => $criticals]);

        return [
            'id' => $workorder->id,
            'workorder' => $workorder,
            'url' => route('mains.show', $workorder->id),
            'number' => $workorder->number,
            'customer_name' => $workorder->customer?->name ?? '—',
            'component_pn' => $workorder->unit?->part_number ?? $firstComponent?->part_number ?? '—',
            'serial_number' => $workorder->serial_number ?: ($tdrCollection->first()?->serial_number ?: '—'),
            'manual_number' => $manual?->number ?? '—',
            'manual_revision' => $manual?->revision_date ? Carbon::parse($manual->revision_date)->format('Y-m-d') : '—',
            'manual_lib' => $manual?->lib ?? '—',
            'open_date' => $workorder->open_at?->format('Y-m-d'),
            'approved' => (bool) $workorder->approve_at,
            'approved_at' => $workorder->approve_at?->format('Y-m-d'),
            'is_done' => $isDone,
            'photos' => $photos,
            'processes' => $processes,
            'training' => $training,
            'quality_documents' => $qualityDocuments,
            'warnings' => $warnings,
            'criticals' => $criticals,
            'all_messages' => array_values(array_unique(array_merge($criticals, $warnings))),
            'status' => $status,
            'status_badge' => $this->statusBadgeClass($status),
            'missing_flags' => [
                'photos' => $photos['missing_any'],
                'ro' => $processes['counts']['missing_ro'] > 0,
                'incomplete_processes' => $processes['counts']['incomplete'] > 0 || $processes['counts']['finished_without_start'] > 0,
                'quality_documents' => $qualityDocuments['missing'],
            ],
            'submitted_inspections' => $this->checkSubmittedInspections($workorder),
        ];
    }

    public function checkProcesses(Workorder $workorder): array
    {
        $rows = [];
        $counts = [
            'total' => 0,
            'incomplete' => 0,
            'missing_ro' => 0,
            'finished_without_start' => 0,
        ];

        foreach (($workorder->tdrs ?? collect()) as $tdr) {
            foreach (($tdr->tdrProcesses ?? collect()) as $process) {
                if ($process->ignore_row) {
                    continue;
                }

                $counts['total']++;

                $hasStart = ! is_null($process->date_start);
                $hasFinish = ! is_null($process->date_finish);
                $missingRo = ($hasStart || $hasFinish) && blank($process->repair_order);
                $incomplete = ($hasStart && ! $hasFinish) || ($workorder->isDone() && ! $hasFinish);
                $finishedWithoutStart = $hasFinish && ! $hasStart;

                if ($missingRo) {
                    $counts['missing_ro']++;
                }

                if ($incomplete) {
                    $counts['incomplete']++;
                }

                if ($finishedWithoutStart) {
                    $counts['finished_without_start']++;
                }

                $warning = collect([
                    $incomplete ? 'Process not finished' : null,
                    $finishedWithoutStart ? 'Finished without start' : null,
                    $missingRo ? 'Missing RO' : null,
                ])->filter()->implode(', ');

                $rows[] = [
                    'wo_id' => $workorder->id,
                    'wo_number' => $workorder->number,
                    'wo_url' => route('mains.show', $workorder->id),
                    'component' => $tdr->component?->part_number ?? $workorder->unit?->part_number ?? '—',
                    'process_name' => $process->processName?->name ?? '—',
                    'date_start' => $process->date_start?->format('Y-m-d'),
                    'date_finish' => $process->date_finish?->format('Y-m-d'),
                    'repair_order' => $process->repair_order ?: '—',
                    'status' => $finishedWithoutStart
                        ? 'warning'
                        : ($incomplete ? 'warning' : ($hasFinish ? 'ok' : 'na')),
                    'warning' => $warning !== '' ? $warning : '—',
                ];
            }
        }

        return [
            'counts' => $counts,
            'rows' => collect($rows),
            'status' => $counts['incomplete'] > 0 || $counts['finished_without_start'] > 0 || $counts['missing_ro'] > 0 ? 'warning' : 'ok',
        ];
    }

    public function checkPhotos(Workorder $workorder): array
    {
        $media = $workorder->media ?? collect();
        $photoCollections = array_keys(config('workorder_media.groups', []));
        $photoMedia = $media->whereIn('collection_name', $photoCollections);

        $photosCount = $photoMedia->count();
        $damageCount = $photoMedia->where('collection_name', 'damages')->count();
        $logsCount = $photoMedia->where('collection_name', 'logs')->count();

        $damageExpected = (bool) ($workorder->external_damage || $workorder->part_missing || $workorder->nameplate_missing);

        return [
            'count' => $photosCount,
            'damage_count' => $damageCount,
            'logs_count' => $logsCount,
            'missing_any' => $photosCount === 0,
            'missing_damage' => $damageExpected && $damageCount === 0,
            'status' => $photosCount === 0 ? 'warning' : 'ok',
            'warning' => $photosCount === 0
                ? 'Missing photos'
                : (($damageExpected && $damageCount === 0) ? 'Missing damage photos' : '—'),
        ];
    }

    public function checkTraining(Workorder $workorder): array
    {
        $user = $workorder->user;
        $manualId = $workorder->unit?->manual?->id;

        if (! $user || ! $manualId) {
            return [
                'available' => false,
                'missing' => false,
                'expired' => false,
                'status' => 'na',
                'warning' => 'Training check not available',
                'user_name' => $user?->name ?? '—',
                'last_training' => null,
                'days_since' => null,
            ];
        }

        $trainings = $this->trainingLookup->get($this->trainingKey($user->id, $manualId), collect());
        $lastTraining = $trainings->sortByDesc('date_training')->first();

        if (! $lastTraining) {
            return [
                'available' => true,
                'missing' => true,
                'expired' => false,
                'status' => 'warning',
                'warning' => 'No training record',
                'user_name' => $user->name,
                'last_training' => null,
                'days_since' => null,
            ];
        }

        $lastDate = Carbon::parse($lastTraining->date_training);
        $daysSince = $lastDate->diffInDays(now());
        $expired = $daysSince >= $this->trainingRenewalThresholdDays;

        return [
            'available' => true,
            'missing' => false,
            'expired' => $expired,
            'status' => $expired ? 'warning' : 'ok',
            'warning' => $expired ? 'Training expired' : '—',
            'user_name' => $user->name,
            'last_training' => $lastDate->format('Y-m-d'),
            'days_since' => $daysSince,
        ];
    }

    public function checkQualityDocuments(Workorder $workorder): array
    {
        $documents = ($workorder->media ?? collect())
            ->where('collection_name', 'quality')
            ->sortByDesc('created_at')
            ->values();

        $latest = $documents->first();

        return [
            'count' => $documents->count(),
            'missing' => $documents->isEmpty(),
            'latest_at' => $latest?->created_at?->format('Y-m-d H:i'),
            'latest_name' => $latest?->name ?: $latest?->file_name,
            'documents' => $documents,
            'status' => $documents->isEmpty() ? 'warning' : 'ok',
            'warning' => $documents->isEmpty() ? 'No quality documents' : '—',
        ];
    }

    public function calculateStatus(array $warnings): string
    {
        if (! empty($warnings['criticals'] ?? [])) {
            return 'critical';
        }

        if (! empty($warnings['warnings'] ?? [])) {
            return 'warning';
        }

        return 'ok';
    }

    public function filterRows(Collection $rows, array $filters): Collection
    {
        return $rows->filter(function (array $row) use ($filters) {
            if ($filters['q'] !== '') {
                $needle = $this->normalizeWorkorderSearch((string) $filters['q']);

                if (! Str::contains((string) $row['number'], $needle, true)) {
                    return false;
                }
            }

            if ($filters['status'] !== 'all' && $row['status'] !== $filters['status']) {
                return false;
            }

            if ($filters['customer_id'] !== '' && (int) $row['workorder']->customer_id !== (int) $filters['customer_id']) {
                return false;
            }

            if (($filters['metric'] ?? '') !== '' && ! $this->rowMatchesMetric($row, $filters['metric'])) {
                return false;
            }

            return true;
        })->values();
    }

    private function normalizeWorkorderSearch(string $query): string
    {
        $query = trim($query);

        return preg_match('/\d/', $query) === 1
            ? preg_replace('/\D+/', '', $query)
            : $query;
    }

    public function buildSummary(Collection $rows): array
    {
        return [
            'open_workorders' => $rows->where('is_done', false)->count(),
            'qa_warnings' => $rows->whereIn('status', ['warning', 'critical'])->count(),
            'missing_photos' => $rows->filter(fn ($row) => $row['photos']['missing_any'])->count(),
            'incomplete_processes' => $rows->filter(fn ($row) => $row['missing_flags']['incomplete_processes'])->count(),
            'missing_ro' => $rows->filter(fn ($row) => $row['missing_flags']['ro'])->count(),
            'submitted_workorders' => $this->buildSubmittedInspectionRows($rows)
                ->filter(fn (array $row) => filled($row['submitted_date'] ?? null))
                ->pluck('wo_id')
                ->unique()
                ->count(),
            'training_alerts' => $rows->filter(function ($row) {
                return $row['training']['status'] === 'warning' || ! $row['training']['available'];
            })->count(),
            'quality_documents' => $rows->sum(fn ($row) => $row['quality_documents']['count']),
        ];
    }

    public function buildProcessTabRows(Collection $rows): Collection
    {
        return $rows->flatMap(fn ($row) => $row['processes']['rows'])->values();
    }

    public function buildPhotoTabRows(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            return [
                'wo_number' => $row['number'],
                'wo_url' => $row['url'],
                'photos_count' => $row['photos']['count'],
                'damage_photos_count' => $row['photos']['damage_count'],
                'logs_count' => $row['photos']['logs_count'],
                'status' => $row['photos']['status'],
                'warning' => $row['photos']['warning'],
            ];
        })->values();
    }

    public function buildSubmittedInspectionRows(Collection $rows): Collection
    {
        return $rows
            ->flatMap(function ($row) {
                return collect($row['submitted_inspections']['pending'] ?? [])
                    ->map(fn ($pending) => [
                        'wo_id' => $row['id'],
                        'wo_number' => $row['number'],
                        'wo_url' => $row['url'],
                        'customer_name' => $row['customer_name'],
                        'component_pn' => $row['component_pn'],
                        'serial_number' => $row['serial_number'],
                        'open_date' => $row['open_date'],
                        'sort_order' => $pending['sort_order'] ?? 999,
                        'submitted_step' => $pending['submitted_step'],
                        'submitted_date' => $pending['submitted_date'],
                        'missing_inspection' => $pending['missing_inspection'],
                        'inspection_date' => $pending['inspection_date'] ?? null,
                        'inspection_done' => filled($pending['inspection_date'] ?? null),
                    ]);
            })
            ->sortBy('sort_order')
            ->values();
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'ok' => 'success',
            'critical' => 'danger',
            default => 'warning',
        };
    }

    private function primeTrainingLookup(Collection $workorders): void
    {
        $userIds = $workorders->pluck('user_id')->filter()->unique()->values();
        $manualIds = $workorders->map(fn (Workorder $workorder) => $workorder->unit?->manual?->id)->filter()->unique()->values();

        if ($userIds->isEmpty() || $manualIds->isEmpty()) {
            $this->trainingLookup = collect();

            return;
        }

        $this->trainingLookup = Training::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('manuals_id', $manualIds)
            ->get()
            ->groupBy(fn (Training $training) => $this->trainingKey($training->user_id, $training->manuals_id));
    }

    private function trainingKey(int $userId, int $manualId): string
    {
        return $userId . '|' . $manualId;
    }

    private function rowMatchesMetric(array $row, string $metric): bool
    {
        return match ($metric) {
            'open' => ! $row['is_done'],
            'qa_warnings' => in_array($row['status'], ['warning', 'critical'], true),
            'submitted' => ($row['submitted_inspections']['count'] ?? 0) > 0,
            'missing_photos' => $row['missing_flags']['photos'],
            'missing_ro' => $row['missing_flags']['ro'],
            default => true,
        };
    }

    private function checkSubmittedInspections(Workorder $workorder): array
    {
        $mainRows = $workorder->main ?? collect();
        $pending = [];

        $postSubmitted = $this->firstMainDate($mainRows, function (?string $name) {
            $name = $this->normalizeTaskName($name);

            return Str::contains($name, 'submitted')
                && Str::contains($name, 'inspection')
                && ! Str::contains($name, 'final');
        });

        $postInspection = $this->firstMainDate($mainRows, fn (?string $name) => $this->normalizeTaskName($name) === 'post disassembly inspection', true);

        $pending[] = [
            'sort_order' => 1,
            'submitted_step' => 'WO Submitted to inspection',
            'submitted_date' => $postSubmitted['date'] ?? null,
            'missing_inspection' => 'Post Disassembly inspection',
            'inspection_date' => $postInspection['date'] ?? null,
        ];

        $finalSubmitted = $this->firstMainDate($mainRows, function (?string $name) {
            $name = $this->normalizeTaskName($name);

            return Str::contains($name, 'submitted')
                && Str::contains($name, 'final');
        });

        $finalInspection = $this->firstMainDate($mainRows, fn (?string $name) => $this->normalizeTaskName($name) === 'final inspection', true);

        $pending[] = [
            'sort_order' => 2,
            'submitted_step' => 'WO Submitted for Final Inspection',
            'submitted_date' => $finalSubmitted['date'] ?? null,
            'missing_inspection' => 'Final inspection',
            'inspection_date' => $finalInspection['date'] ?? null,
        ];

        return [
            'pending' => $pending,
            'count' => collect($pending)->filter(fn (array $row) => filled($row['submitted_date'] ?? null))->count(),
        ];
    }

    private function firstMainDate(Collection $mainRows, callable $matches, bool $finishOnly = false): ?array
    {
        $main = $mainRows
            ->filter(function ($main) use ($matches, $finishOnly) {
                if ($main->ignore_row) {
                    return false;
                }

                $date = $finishOnly ? $main->date_finish : ($main->date_finish ?? $main->date_start);

                return $date !== null && $matches($main->task?->name);
            })
            ->sortByDesc(fn ($main) => $finishOnly ? $main->date_finish : ($main->date_finish ?? $main->date_start))
            ->first();

        if ($main === null) {
            return null;
        }

        $date = $finishOnly ? $main->date_finish : ($main->date_finish ?? $main->date_start);

        return [
            'task_name' => $main->task?->name ?? '-',
            'date' => $date?->format('Y-m-d'),
        ];
    }

    private function normalizeTaskName(?string $name): string
    {
        $name = strtr((string) $name, [
            'с' => 'c',
            'С' => 'c',
        ]);

        return trim(Str::lower($name));
    }
}
