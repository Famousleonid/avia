<?php

namespace App\Services\Ai\Tools;

use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\Main;
use App\Models\Manual;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Models\WorkorderUnitInspection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class SearchActivityLogsTool
{
    private const MAX_CANDIDATES = 1000;

    public function run(User $user, array $args): array
    {
        if (! $user->isSystemAdmin()) {
            return [
                'ok' => false,
                'message' => 'Activity log search is available only to System Admin users.',
            ];
        }

        $filters = $this->normalizeFilters($args);
        if ($filters['error'] !== null) {
            return ['ok' => false, 'message' => $filters['error']];
        }

        if (! $this->hasSearchFilter($filters)) {
            return [
                'ok' => false,
                'message' => 'Provide at least one audit filter: WO number, P/N, user, event, log name, area, text, or date range.',
            ];
        }

        $workorder = $this->resolveWorkorder($filters['workorder_id'], $filters['workorder_number']);
        if (($filters['workorder_id'] > 0 || $filters['workorder_number'] !== '') && ! $workorder) {
            return $this->emptyResult($filters, 'Workorder not found.');
        }

        $manual = $this->resolveManual($filters['manual_number']);
        if ($filters['manual_number'] !== '' && ! $manual) {
            return $this->emptyResult($filters, 'CMM manual not found.');
        }

        $componentIds = $filters['part_number'] !== ''
            ? $this->componentIdsForPartNumber($filters['part_number'], $filters['exact_part_number'])
            : collect();
        $tdrIdsForPart = $componentIds->isEmpty()
            ? collect()
            : Tdr::query()
                ->where(function (Builder $query) use ($componentIds): void {
                    $query->whereIn('component_id', $componentIds)
                        ->orWhereIn('order_component_id', $componentIds);
                })
                ->pluck('id');

        $actorIds = $this->actorIds($filters['actor']);
        if ($filters['actor'] !== '' && $actorIds->isEmpty()) {
            return $this->emptyResult($filters, 'No matching user was found.');
        }

        $workorderSubjectIds = $workorder
            ? $this->subjectIdsForWorkorder((int) $workorder->id)
            : [];
        $manualSubjectIds = $manual
            ? $this->subjectIdsForManual((int) $manual->id)
            : [];

        $query = Activity::query()->latest('id');

        if ($filters['actor'] !== '') {
            $query->where('causer_type', User::class)->whereIn('causer_id', $actorIds);
        }
        if ($filters['event'] !== '') {
            $query->where('event', $filters['event']);
        }
        if ($filters['log_name'] !== '') {
            $query->where('log_name', $filters['log_name']);
        }
        if ($filters['date_from'] !== null) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== null) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if ($filters['text'] !== '') {
            $like = '%'.$this->escapeLike($filters['text']).'%';
            $query->where(function (Builder $query) use ($like): void {
                $query->where('description', 'like', $like)
                    ->orWhere('log_name', 'like', $like)
                    ->orWhere('event', 'like', $like)
                    ->orWhere('properties', 'like', $like);
            });
        }

        $this->applyAreaFilter($query, $filters['area']);

        if ($workorder) {
            $this->applyWorkorderFilter($query, (int) $workorder->id, $workorderSubjectIds);
        }

        if ($manual) {
            $this->applyManualFilter($query, (int) $manual->id, $manualSubjectIds);
        }

        if ($filters['part_number'] !== '') {
            $this->applyPartNumberFilter(
                $query,
                $filters['part_number'],
                $componentIds,
                $tdrIdsForPart
            );
        }

        $candidateLimit = min(
            self::MAX_CANDIDATES,
            max(250, $filters['limit'] * 30)
        );
        $candidates = $query->limit($candidateLimit)->get();
        $candidateCount = $candidates->count();

        if ($workorder) {
            $candidates = $candidates->filter(
                fn (Activity $activity): bool => $this->activityMatchesWorkorder(
                    $activity,
                    (int) $workorder->id,
                    $workorderSubjectIds
                )
            );
        }


        if ($manual) {
            $candidates = $candidates->filter(
                fn (Activity $activity): bool => $this->activityMatchesManual(
                    $activity,
                    (int) $manual->id,
                    $manualSubjectIds
                )
            );
        }

        if ($filters['part_number'] !== '') {
            $candidates = $candidates->filter(
                fn (Activity $activity): bool => $this->activityMatchesPartNumber(
                    $activity,
                    $filters['part_number'],
                    $filters['exact_part_number'],
                    $componentIds,
                    $tdrIdsForPart
                )
            );
        }

        $matchedCount = $candidates->count();
        $activities = $candidates->take($filters['limit'])->values();
        $context = $this->buildResultContext($activities, $manual);

        return [
            'ok' => true,
            'filters' => $this->publicFilters($filters, $workorder, $manual),
            'count' => $activities->count(),
            'has_more' => $matchedCount > $filters['limit'] || $candidateCount >= $candidateLimit,
            'matches' => $activities
                ->map(fn (Activity $activity): array => $this->formatActivity($activity, $context))
                ->all(),
            'note' => $activities->isEmpty()
                ? 'No activity log entries matched every supplied filter.'
                : 'All supplied filters were combined with AND. Results are newest first.',
        ];
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'searchActivityLogs',
            'description' => 'Read-only audit search for who created, changed, or deleted data and when. Supports WO, CMM manual number, exact/partial P/N (including TDR Add Part entries, TDR component ids, and bushing snapshots), actor name/email, event, log name, area, free text, and date range. Every supplied filter is combined with AND. System Admin only. Never expose internal ids.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => [
                        'type' => 'integer',
                        'description' => 'Internal workorder id from page context only; never repeat it to the user.',
                    ],
                    'workorder_number' => [
                        'type' => 'string',
                        'description' => 'Human WO number, with or without WO/W prefix.',
                    ],
                    'manual_number' => [
                        'type' => 'string',
                        'description' => 'Human CMM/manual number, for example 32-11-01. Use it to find who added or changed Parts in that manual.',
                    ],
                    'part_number' => [
                        'type' => 'string',
                        'description' => 'Part number / P/N found directly in logs or through component references.',
                    ],
                    'exact_part_number' => [
                        'type' => 'boolean',
                        'description' => 'Default true. Set false only when the user explicitly asks for a partial P/N search.',
                    ],
                    'actor' => [
                        'type' => 'string',
                        'description' => 'Full or partial user name or email for who performed the action.',
                    ],
                    'event' => [
                        'type' => 'string',
                        'description' => 'Exact audit event, for example created, updated, deleted, purged, or bushing_failed.',
                    ],
                    'log_name' => [
                        'type' => 'string',
                        'description' => 'Exact technical audit category when known, for example tdr, workorder, component, or tdr_process.',
                    ],
                    'area' => [
                        'type' => 'string',
                        'enum' => ['all', 'parts', 'bushings', 'workorders', 'components', 'processes', 'manuals', 'users'],
                        'description' => 'Human audit area. Use parts for TDR/parts plus bushing and component references.',
                    ],
                    'text' => [
                        'type' => 'string',
                        'description' => 'Optional text in description, event, category, or saved change properties.',
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'description' => 'Start date inclusive, preferably YYYY-MM-DD.',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'description' => 'End date inclusive, preferably YYYY-MM-DD.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum results, 1 to 50.',
                    ],
                ],
                'additionalProperties' => false,
            ],
        ];
    }

    private function normalizeFilters(array $args): array
    {
        $dateFrom = $this->normalizeDate($args['date_from'] ?? null);
        $dateTo = $this->normalizeDate($args['date_to'] ?? null);
        $error = null;

        if ($dateFrom === false || $dateTo === false) {
            $error = 'Dates must use YYYY-MM-DD or the project format dd/mmm/yyyy.';
        } elseif ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            $error = 'The start date cannot be after the end date.';
        }

        $area = strtolower(trim((string) ($args['area'] ?? 'all')));
        if (! in_array($area, ['all', 'parts', 'bushings', 'workorders', 'components', 'processes', 'manuals', 'users'], true)) {
            $area = 'all';
        }

        return [
            'workorder_id' => max(0, (int) ($args['workorder_id'] ?? 0)),
            'workorder_number' => $this->normalizeWorkorderNumber((string) ($args['workorder_number'] ?? '')),
            'manual_number' => $this->normalizeManualNumber((string) ($args['manual_number'] ?? '')),
            'part_number' => trim((string) ($args['part_number'] ?? '')),
            'exact_part_number' => array_key_exists('exact_part_number', $args)
                ? (bool) $args['exact_part_number']
                : true,
            'actor' => trim((string) ($args['actor'] ?? '')),
            'event' => strtolower(trim((string) ($args['event'] ?? ''))),
            'log_name' => trim((string) ($args['log_name'] ?? '')),
            'area' => $area,
            'text' => trim((string) ($args['text'] ?? '')),
            'date_from' => $dateFrom === false ? null : $dateFrom,
            'date_to' => $dateTo === false ? null : $dateTo,
            'limit' => max(1, min(50, (int) ($args['limit'] ?? 20))),
            'error' => $error,
        ];
    }

    private function hasSearchFilter(array $filters): bool
    {
        return $filters['workorder_id'] > 0
            || $filters['workorder_number'] !== ''
            || $filters['manual_number'] !== ''
            || $filters['part_number'] !== ''
            || $filters['actor'] !== ''
            || $filters['event'] !== ''
            || $filters['log_name'] !== ''
            || $filters['area'] !== 'all'
            || $filters['text'] !== ''
            || $filters['date_from'] !== null
            || $filters['date_to'] !== null;
    }

    private function resolveWorkorder(int $workorderId, string $workorderNumber): ?Workorder
    {
        if ($workorderId > 0) {
            $workorder = Workorder::withoutGlobalScopes()->find($workorderId);
            if ($workorder) {
                return $workorder;
            }

            $workorder = $this->findWorkorderByNumber((string) $workorderId);
            if ($workorder) {
                return $workorder;
            }
        }

        if ($workorderNumber !== '') {
            return $this->findWorkorderByNumber($workorderNumber);
        }

        return null;
    }

    private function findWorkorderByNumber(string $number): ?Workorder
    {
        $candidates = $this->workorderNumberCandidates($number);
        if ($candidates === []) {
            return null;
        }

        return Workorder::withoutGlobalScopes()
            ->whereIn('number', $candidates)
            ->latest('id')
            ->first();
    }

    private function workorderNumberCandidates(string $number): array
    {
        $number = $this->normalizeWorkorderNumber($number);
        if ($number === '') {
            return [];
        }

        $candidates = [$number];
        if (preg_match('/^W(\d+)$/i', $number, $match)) {
            $candidates[] = $match[1];
        } elseif (ctype_digit($number)) {
            $candidates[] = 'W'.$number;
        }

        return array_values(array_unique($candidates));
    }

    private function resolveManual(string $manualNumber): ?Manual
    {
        if ($manualNumber === '') {
            return null;
        }

        $exact = Manual::withTrashed()
            ->whereRaw('LOWER(TRIM(number)) = ?', [strtolower($manualNumber)])
            ->first();
        if ($exact) {
            return $exact;
        }

        return Manual::withTrashed()
            ->where('number', 'like', $this->escapeLike($manualNumber).'%')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->sortBy(fn (Manual $manual): array => [mb_strlen((string) $manual->number), (int) $manual->id])
            ->first();
    }

    private function componentIdsForPartNumber(string $partNumber, bool $exact): Collection
    {
        $like = '%'.$this->escapeLike($partNumber).'%';
        $ids = Component::withTrashed()
            ->where('part_number', 'like', $like)
            ->limit(500)
            ->get(['id', 'part_number'])
            ->filter(fn (Component $component): bool => $this->partNumberMatches(
                (string) $component->part_number,
                $partNumber,
                $exact
            ))
            ->pluck('id');

        $historicalIds = Activity::query()
            ->where('subject_type', Component::class)
            ->where('properties', 'like', $like)
            ->latest('id')
            ->limit(500)
            ->get(['subject_id', 'properties'])
            ->filter(function (Activity $activity) use ($partNumber, $exact): bool {
                return collect($this->valuesForKeys($this->properties($activity), ['part_number', 'assy_part_number']))
                    ->contains(fn ($value): bool => $this->partNumberMatches((string) $value, $partNumber, $exact));
            })
            ->pluck('subject_id');

        return $ids->merge($historicalIds)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    private function actorIds(string $actor): Collection
    {
        if ($actor === '') {
            return collect();
        }

        $like = '%'.$this->escapeLike($actor).'%';

        return User::withTrashed()
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })
            ->limit(100)
            ->pluck('id');
    }

    private function subjectIdsForWorkorder(int $workorderId): array
    {
        $tdrIds = Tdr::query()->where('workorder_id', $workorderId)->pluck('id');

        return [
            Workorder::class => collect([$workorderId]),
            Tdr::class => $tdrIds,
            TdrProcess::class => $tdrIds->isEmpty()
                ? collect()
                : TdrProcess::query()->whereIn('tdrs_id', $tdrIds)->pluck('id'),
            Main::class => Main::query()->where('workorder_id', $workorderId)->pluck('id'),
            WorkorderStdProcess::class => WorkorderStdProcess::query()->where('workorder_id', $workorderId)->pluck('id'),
            WorkorderUnitInspection::class => WorkorderUnitInspection::query()->where('workorder_id', $workorderId)->pluck('id'),
        ];
    }

    private function subjectIdsForManual(int $manualId): array
    {
        $componentIds = Component::withTrashed()
            ->where('manual_id', $manualId)
            ->pluck('id');

        return [
            Manual::class => collect([$manualId]),
            Component::class => $componentIds,
            ComponentAssembly::class => $componentIds->isEmpty()
                ? collect()
                : ComponentAssembly::withTrashed()->whereIn('component_id', $componentIds)->pluck('id'),
            StdProcess::class => StdProcess::query()->where('manual_id', $manualId)->pluck('id'),
        ];
    }

    private function applyAreaFilter(Builder $query, string $area): void
    {
        if ($area === 'all') {
            return;
        }

        $query->where(function (Builder $query) use ($area): void {
            match ($area) {
                'parts' => $query->whereIn('log_name', ['tdr', 'component', 'component_assembly'])
                    ->orWhere('properties', 'like', '%wo_bushings%'),
                'bushings' => $query->where('properties', 'like', '%wo_bushings%'),
                'workorders' => $query->where('log_name', 'workorder'),
                'components' => $query->whereIn('log_name', ['component', 'component_assembly']),
                'processes' => $query->whereIn('log_name', ['tdr_process', 'workorder_std_process', 'process']),
                'manuals' => $query->whereIn('log_name', ['manual', 'component', 'std_process']),
                'users' => $query->where('log_name', 'user'),
                default => null,
            };
        });
    }

    private function applyWorkorderFilter(Builder $query, int $workorderId, array $subjectIds): void
    {
        $query->where(function (Builder $query) use ($workorderId, $subjectIds): void {
            $query->where('properties', 'like', '%"workorder_id"%'.$workorderId.'%');

            foreach ($subjectIds as $subjectType => $ids) {
                if ($ids->isEmpty()) {
                    continue;
                }
                $query->orWhere(function (Builder $query) use ($subjectType, $ids): void {
                    $query->where('subject_type', $subjectType)->whereIn('subject_id', $ids);
                });
            }
        });
    }

    private function applyManualFilter(Builder $query, int $manualId, array $subjectIds): void
    {
        $query->where(function (Builder $query) use ($manualId, $subjectIds): void {
            $query->where('properties', 'like', '%"manual_id"%'.$manualId.'%');

            foreach ($subjectIds as $subjectType => $ids) {
                if ($ids->isEmpty()) {
                    continue;
                }
                $query->orWhere(function (Builder $query) use ($subjectType, $ids): void {
                    $query->where('subject_type', $subjectType)->whereIn('subject_id', $ids);
                });
            }
        });
    }

    private function applyPartNumberFilter(
        Builder $query,
        string $partNumber,
        Collection $componentIds,
        Collection $tdrIds
    ): void {
        $like = '%'.$this->escapeLike($partNumber).'%';

        $query->where(function (Builder $query) use ($like, $componentIds, $tdrIds): void {
            $query->where('properties', 'like', $like);

            if ($componentIds->isNotEmpty()) {
                $query->orWhere(function (Builder $query) use ($componentIds): void {
                    $query->where('subject_type', Component::class)->whereIn('subject_id', $componentIds);
                });

                foreach ($componentIds as $componentId) {
                    $query->orWhere('properties', 'like', '%"component_id"%'.(int) $componentId.'%')
                        ->orWhere('properties', 'like', '%"order_component_id"%'.(int) $componentId.'%');
                }
            }

            if ($tdrIds->isNotEmpty()) {
                $query->orWhere(function (Builder $query) use ($tdrIds): void {
                    $query->where('subject_type', Tdr::class)->whereIn('subject_id', $tdrIds);
                });
            }
        });
    }

    private function activityMatchesWorkorder(Activity $activity, int $workorderId, array $subjectIds): bool
    {
        if (($subjectIds[$activity->subject_type] ?? collect())->contains((int) $activity->subject_id)) {
            return true;
        }

        return collect($this->valuesForKeys($this->properties($activity), ['workorder_id']))
            ->contains(fn ($value): bool => is_numeric($value) && (int) $value === $workorderId);
    }

    private function activityMatchesManual(Activity $activity, int $manualId, array $subjectIds): bool
    {
        if (($subjectIds[$activity->subject_type] ?? collect())->contains((int) $activity->subject_id)) {
            return true;
        }

        return collect($this->valuesForKeys($this->properties($activity), ['manual_id']))
            ->contains(fn ($value): bool => is_numeric($value) && (int) $value === $manualId);
    }

    private function activityMatchesPartNumber(
        Activity $activity,
        string $partNumber,
        bool $exact,
        Collection $componentIds,
        Collection $tdrIds
    ): bool {
        if ($activity->subject_type === Component::class && $componentIds->contains((int) $activity->subject_id)) {
            return true;
        }
        if ($activity->subject_type === Tdr::class && $tdrIds->contains((int) $activity->subject_id)) {
            return true;
        }

        $properties = $this->properties($activity);
        $hasPartNumber = collect($this->valuesForKeys($properties, ['part_number', 'assy_part_number']))
            ->contains(fn ($value): bool => $this->partNumberMatches((string) $value, $partNumber, $exact));
        if ($hasPartNumber) {
            return true;
        }

        return collect($this->valuesForKeys($properties, ['component_id', 'order_component_id']))
            ->contains(fn ($value): bool => is_numeric($value) && $componentIds->contains((int) $value));
    }

    private function buildResultContext(Collection $activities, ?Manual $targetManual = null): array
    {
        $properties = $activities->mapWithKeys(fn (Activity $activity): array => [
            $activity->id => $this->properties($activity),
        ]);
        $tdrSubjectIds = $activities
            ->where('subject_type', Tdr::class)
            ->pluck('subject_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique();
        $tdrs = $tdrSubjectIds->isEmpty()
            ? collect()
            : Tdr::query()->whereIn('id', $tdrSubjectIds)->get()->keyBy('id');

        $componentIds = $properties
            ->flatMap(fn (array $props): array => $this->valuesForKeys($props, ['component_id', 'order_component_id']))
            ->merge($tdrs->flatMap(fn (Tdr $tdr): array => [$tdr->component_id, $tdr->order_component_id]))
            ->merge($activities->where('subject_type', Component::class)->pluck('subject_id'))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique();
        $components = $componentIds->isEmpty()
            ? collect()
            : Component::withTrashed()->whereIn('id', $componentIds)->get(['id', 'manual_id', 'part_number', 'name'])->keyBy('id');

        $manualIds = $properties
            ->flatMap(fn (array $props): array => $this->valuesForKeys($props, ['manual_id']))
            ->merge($components->pluck('manual_id'))
            ->merge($activities->where('subject_type', Manual::class)->pluck('subject_id'))
            ->when($targetManual, fn (Collection $ids) => $ids->push($targetManual->id))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique();
        $manuals = $manualIds->isEmpty()
            ? collect()
            : Manual::withTrashed()->whereIn('id', $manualIds)->get(['id', 'number', 'title'])->keyBy('id');

        $workorderIds = $properties
            ->flatMap(fn (array $props): array => $this->valuesForKeys($props, ['workorder_id']))
            ->merge($tdrs->pluck('workorder_id'))
            ->merge($activities->where('subject_type', Workorder::class)->pluck('subject_id'))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique();
        $workorders = $workorderIds->isEmpty()
            ? collect()
            : Workorder::withoutGlobalScopes()->whereIn('id', $workorderIds)->get(['id', 'number'])->keyBy('id');

        $actorIds = $activities->pluck('causer_id')->filter()->unique();
        $actors = $actorIds->isEmpty()
            ? collect()
            : User::withTrashed()->whereIn('id', $actorIds)->get(['id', 'name', 'email'])->keyBy('id');

        return compact('properties', 'tdrs', 'components', 'manuals', 'workorders', 'actors', 'targetManual');
    }

    private function formatActivity(Activity $activity, array $context): array
    {
        $properties = $context['properties']->get($activity->id, []);
        $tdr = $activity->subject_type === Tdr::class
            ? $context['tdrs']->get((int) $activity->subject_id)
            : null;

        $workorderIds = collect($this->valuesForKeys($properties, ['workorder_id']))
            ->when($tdr, fn (Collection $ids) => $ids->push($tdr->workorder_id))
            ->when(
                $activity->subject_type === Workorder::class,
                fn (Collection $ids) => $ids->push($activity->subject_id)
            )
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $componentIds = collect($this->valuesForKeys($properties, ['component_id', 'order_component_id']))
            ->when($tdr, fn (Collection $ids) => $ids->push($tdr->component_id)->push($tdr->order_component_id))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique();

        $partNumbers = collect($this->valuesForKeys($properties, ['part_number', 'assy_part_number']))
            ->merge($componentIds->map(fn (int $id) => $context['components']->get($id)?->part_number))
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->map(fn ($value): string => trim((string) $value))
            ->unique()
            ->take(20)
            ->values();

        $manualIds = collect($this->valuesForKeys($properties, ['manual_id']))
            ->merge($componentIds->map(fn (int $id) => $context['components']->get($id)?->manual_id))
            ->when(
                $activity->subject_type === Manual::class,
                fn (Collection $ids) => $ids->push($activity->subject_id)
            )
            ->when($context['targetManual'], fn (Collection $ids) => $ids->push($context['targetManual']->id))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique();
        $manualNumbers = $manualIds
            ->map(fn (int $id) => $context['manuals']->get($id)?->number)
            ->filter()
            ->unique()
            ->values();
        $firstManual = $manualIds
            ->map(fn (int $id) => $context['manuals']->get($id))
            ->filter()
            ->first();

        $workorderNumbers = $workorderIds
            ->map(fn (int $id) => $context['workorders']->get($id)?->number)
            ->filter()
            ->unique()
            ->values();
        $firstWorkorder = $workorderIds
            ->map(fn (int $id) => $context['workorders']->get($id))
            ->filter()
            ->first();
        $actor = $context['actors']->get((int) $activity->causer_id);
        $changedFields = $this->changedFieldLabels($properties);

        return [
            'occurred_at' => (format_project_date($activity->created_at) ?? '') . ' ' . $activity->created_at?->format('H:i:s'),
            'actor' => $actor?->name ?? 'System / unknown user',
            'actor_email' => $actor?->email,
            'event' => $activity->event,
            'area' => $this->subjectLabel((string) $activity->subject_type, (string) $activity->log_name, $properties),
            'source' => data_get($properties, 'source'),
            'description' => $activity->description,
            'workorder_numbers' => $workorderNumbers->all(),
            'manual_numbers' => $manualNumbers->all(),
            'part_numbers' => $partNumbers->all(),
            'changed_fields' => $changedFields,
            'open_url' => $firstWorkorder ? route('mains.show', $firstWorkorder->id) : null,
            'manual_url' => $firstManual ? route('manuals.show', $firstManual->id) : null,
        ];
    }

    private function changedFieldLabels(array $properties): array
    {
        $old = (array) ($properties['old'] ?? data_get($properties, 'changes.old', []));
        $new = (array) ($properties['attributes'] ?? $properties['new'] ?? data_get($properties, 'changes.attributes', []));
        $labels = [
            'workorder_id' => 'workorder',
            'manual_id' => 'CMM manual',
            'component_id' => 'part',
            'order_component_id' => 'ordered part',
            'part_number' => 'P/N',
            'qty' => 'quantity',
            'description' => 'description',
            'serial_number' => 'serial number',
            'assy_serial_number' => 'assembly serial number',
            'bushing_save' => 'bushing selection',
            'date_start' => 'start date',
            'date_finish' => 'finish date',
            'name' => 'name',
        ];

        return collect(array_unique(array_merge(array_keys($old), array_keys($new))))
            ->map(fn (string $field): string => $labels[$field] ?? str_replace('_', ' ', $field))
            ->values()
            ->all();
    }

    private function subjectLabel(string $subjectType, string $logName, array $properties): string
    {
        if (data_get($properties, 'source') === 'tdr_add_part') {
            return 'TDR Add Part';
        }
        if (data_get($properties, 'source') === 'wo_bushings') {
            return 'Bushings';
        }

        return match ($subjectType) {
            Tdr::class => 'Parts / TDR',
            Component::class => 'Part directory',
            ComponentAssembly::class => 'Part assembly',
            Manual::class => 'CMM manual',
            Workorder::class => 'Workorder',
            TdrProcess::class, WorkorderStdProcess::class => 'Process',
            Main::class => 'Task',
            WorkorderUnitInspection::class => 'Unit inspection',
            User::class => 'User',
            default => $logName !== '' ? ucfirst(str_replace('_', ' ', $logName)) : 'Activity',
        };
    }

    private function properties(Activity $activity): array
    {
        $properties = $activity->properties;
        if ($properties instanceof Collection) {
            return $properties->toArray();
        }
        if (is_array($properties)) {
            return $properties;
        }

        return (array) $properties;
    }

    private function valuesForKeys(mixed $value, array $keys): array
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }
        if (! is_array($value)) {
            return [];
        }

        $values = [];
        foreach ($value as $key => $item) {
            if (in_array((string) $key, $keys, true) && ! is_array($item)) {
                $values[] = $item;
            }
            if (is_array($item) || $item instanceof Collection) {
                array_push($values, ...$this->valuesForKeys($item, $keys));
            }
        }

        return $values;
    }

    private function partNumberMatches(string $candidate, string $expected, bool $exact): bool
    {
        $candidate = $this->normalizePartNumber($candidate);
        $expected = $this->normalizePartNumber($expected);
        if ($candidate === '' || $expected === '') {
            return false;
        }

        return $exact ? $candidate === $expected : str_contains($candidate, $expected);
    }

    private function normalizePartNumber(string $value): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($value)));
    }

    private function normalizeWorkorderNumber(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^(?:WO|W\/O|W\.O\.)\s*#?\s*/i', '', $value) ?? $value;

        return trim($value);
    }

    private function normalizeManualNumber(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/^(?:CMM|MANUAL)\s*(?:NO\.?|NUMBER|#)?\s*/i', '', $value) ?? $value;

        return trim($value);
    }

    private function normalizeDate(mixed $value): string|false|null
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return parse_project_date((string) $value);
        } catch (\Throwable) {
            return false;
        }
    }

    private function publicFilters(array $filters, ?Workorder $workorder, ?Manual $manual): array
    {
        return array_filter([
            'workorder_number' => $workorder?->number ?? ($filters['workorder_number'] ?: null),
            'manual_number' => $filters['manual_number'] ?: $manual?->number,
            'part_number' => $filters['part_number'] ?: null,
            'part_number_match' => $filters['part_number'] !== ''
                ? ($filters['exact_part_number'] ? 'exact' : 'contains')
                : null,
            'actor' => $filters['actor'] ?: null,
            'event' => $filters['event'] ?: null,
            'log_name' => $filters['log_name'] ?: null,
            'area' => $filters['area'] !== 'all' ? $filters['area'] : null,
            'text' => $filters['text'] ?: null,
            'date_from' => $filters['date_from'] ? format_project_date($filters['date_from']) : null,
            'date_to' => $filters['date_to'] ? format_project_date($filters['date_to']) : null,
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    private function emptyResult(array $filters, string $note): array
    {
        return [
            'ok' => true,
            'filters' => $this->publicFilters($filters, null, null),
            'count' => 0,
            'has_more' => false,
            'matches' => [],
            'note' => $note,
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
