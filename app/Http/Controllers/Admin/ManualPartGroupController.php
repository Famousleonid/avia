<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupCoverage;
use App\Models\ManualPartGroupOption;
use App\Models\WorkorderPartGroupSelection;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualPartGroupController extends Controller
{
    public function store(Request $request, Manual $manual): JsonResponse
    {
        $this->authorizeManualUpdate($request, $manual);
        $data = $this->validatedData($request, $manual);

        $group = DB::transaction(function () use ($request, $manual, $data): ManualPartGroup {
            $group = ManualPartGroup::query()->create([
                'manual_id' => $manual->id,
                'manual_service_bulletin_id' => $data['manual_service_bulletin_id'] ?? null,
                'code' => $this->nextCode(),
                'name' => $data['name'],
                'behavior' => ManualPartGroup::behaviorForType($data['type']),
                'type' => $data['type'],
                'applies_to' => array_values($data['applies_to']),
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $request->user()?->id,
            ]);

            $this->replaceOptions($group, $data, false);

            return $group;
        });

        app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manual->id);

        return response()->json([
            'success' => true,
            'message' => 'Part group created.',
            'group' => $this->serializeGroup($group->fresh([
                'options.coverages.component',
                'options.coverages.coveredOption.group',
                'options.coverages.coveredOption.coverages',
                'serviceBulletin',
            ])),
        ]);
    }

    public function update(Request $request, Manual $manual, ManualPartGroup $partGroup): JsonResponse
    {
        $this->authorizeManualUpdate($request, $manual);
        $this->ensureGroupBelongsToManual($partGroup, $manual);
        $data = $this->validatedData($request, $manual);

        DB::transaction(function () use ($partGroup, $data): void {
            $oldBehavior = $partGroup->behavior;
            $oldType = $partGroup->type;
            $newBehavior = ManualPartGroup::behaviorForType($data['type']);

            if ($oldType === ManualPartGroup::TYPE_ASSY && $data['type'] !== ManualPartGroup::TYPE_ASSY) {
                $this->removeIncomingOptionCoverages($partGroup->options()->pluck('id')->all());
            }

            $partGroup->update([
                'manual_service_bulletin_id' => $data['manual_service_bulletin_id'] ?? null,
                'name' => $data['name'],
                'behavior' => $newBehavior,
                'type' => $data['type'],
                'applies_to' => array_values($data['applies_to']),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($oldBehavior !== $newBehavior) {
                $this->removeIncomingOptionCoverages($partGroup->options()->pluck('id')->all());
                WorkorderPartGroupSelection::query()
                    ->where('manual_part_group_id', $partGroup->id)
                    ->delete();
                $partGroup->options()->delete();
            }

            $this->replaceOptions($partGroup, $data, true);
        });

        app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manual->id);

        return response()->json([
            'success' => true,
            'message' => 'Part group updated.',
            'group' => $this->serializeGroup($partGroup->fresh([
                'options.coverages.component',
                'options.coverages.coveredOption.group',
                'options.coverages.coveredOption.coverages',
                'serviceBulletin',
            ])),
        ]);
    }

    public function destroy(Request $request, Manual $manual, ManualPartGroup $partGroup): JsonResponse
    {
        $this->authorizeManualUpdate($request, $manual);
        $this->ensureGroupBelongsToManual($partGroup, $manual);
        $this->removeIncomingOptionCoverages($partGroup->options()->pluck('id')->all());
        $partGroup->delete();
        app(WorkorderStdProcessItemsService::class)->invalidateForManual((int) $manual->id);

        return response()->json(['success' => true, 'message' => 'Part group deleted.']);
    }

    private function validatedData(Request $request, Manual $manual): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(ManualPartGroup::validTypes())],
            'applies_to' => ['required', 'array', 'min:1'],
            'applies_to.*' => [Rule::in(ManualPartGroup::validScopes())],
            'manual_service_bulletin_id' => ['nullable', 'integer', 'exists:manual_service_bulletins,id'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'component_ids' => ['nullable', 'array'],
            'component_ids.*' => ['integer', 'distinct', 'exists:components,id'],
            'default_component_id' => ['nullable', 'integer'],
            'included_group_option_ids' => ['nullable', 'array'],
            'included_group_option_ids.*' => ['integer', 'distinct', 'exists:manual_part_group_options,id'],
            'included_group_qty' => ['nullable', 'array'],
            'included_group_qty.*' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'order_part_number' => ['nullable', 'string', 'max:100'],
            'order_ipl_num' => ['nullable', 'string', 'max:50'],
            'member_qty' => ['nullable', 'array'],
            'member_qty.*' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'member_applies_to' => ['nullable', 'array'],
            'member_applies_to.*' => ['nullable', 'array', 'min:1'],
            'member_applies_to.*.*' => [Rule::in(ManualPartGroup::validScopes())],
        ]);

        $componentIds = collect($data['component_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $includedOptionIds = collect($data['included_group_option_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        if (Component::query()->where('manual_id', $manual->id)->whereIn('id', $componentIds)->count() !== $componentIds->count()) {
            throw ValidationException::withMessages(['component_ids' => 'Every selected part must belong to this manual.']);
        }

        $behavior = ManualPartGroup::behaviorForType($data['type']);
        if ($behavior === ManualPartGroup::BEHAVIOR_CHOOSE_ONE && $componentIds->count() < 2) {
            throw ValidationException::withMessages(['component_ids' => 'Select at least two alternatives.']);
        }
        if ($behavior === ManualPartGroup::BEHAVIOR_CHOOSE_ONE
            && ! empty($data['default_component_id'])
            && ! $componentIds->contains((int) $data['default_component_id'])) {
            throw ValidationException::withMessages(['default_component_id' => 'The default option must be one of the selected parts.']);
        }
        if ($data['type'] === ManualPartGroup::TYPE_ASSY
            && (! $componentIds->contains((int) ($data['default_component_id'] ?? 0)))) {
            throw ValidationException::withMessages(['default_component_id' => 'Select the original/base part for the ASSY.']);
        }
        if ($behavior === ManualPartGroup::BEHAVIOR_BUNDLE && trim((string) ($data['order_part_number'] ?? '')) === '') {
            throw ValidationException::withMessages(['order_part_number' => 'New order P/N is required for ASSY and KIT.']);
        }
        if ($data['type'] === ManualPartGroup::TYPE_ASSY && $componentIds->isEmpty()) {
            throw ValidationException::withMessages(['component_ids' => 'ASSY must contain the original part and any included parts.']);
        }
        if ($data['type'] !== ManualPartGroup::TYPE_KIT && $includedOptionIds->isNotEmpty()) {
            throw ValidationException::withMessages(['included_group_option_ids' => 'Only a KIT can include an ASSY group.']);
        }
        if ($data['type'] === ManualPartGroup::TYPE_KIT && $componentIds->isEmpty() && $includedOptionIds->isEmpty()) {
            throw ValidationException::withMessages(['component_ids' => 'KIT must contain at least one part or ASSY.']);
        }

        if ($includedOptionIds->isNotEmpty()) {
            $validAssyOptions = ManualPartGroupOption::query()
                ->whereIn('id', $includedOptionIds)
                ->whereHas('group', fn ($group) => $group
                    ->where('manual_id', $manual->id)
                    ->where('type', ManualPartGroup::TYPE_ASSY))
                ->count();
            if ($validAssyOptions !== $includedOptionIds->count()) {
                throw ValidationException::withMessages(['included_group_option_ids' => 'Every included ASSY must belong to this manual.']);
            }
        }

        if ($data['type'] === ManualPartGroup::TYPE_OVERSIZE) {
            $bushings = Component::query()->whereIn('id', $componentIds)->get(['id', 'ipl_num', 'bush_ipl_num', 'is_bush']);
            $bushIplValues = $bushings
                ->map(fn (Component $component): string => mb_strtoupper(trim((string) $component->bush_ipl_num)))
                ->filter()
                ->unique();
            $hasOriginal = $bushings->contains(fn (Component $component): bool =>
                trim((string) $component->ipl_num) !== ''
                && strcasecmp(trim((string) $component->ipl_num), trim((string) $component->bush_ipl_num)) === 0
            );
            if ($bushings->contains(fn (Component $component): bool => ! $component->is_bush)
                || $bushIplValues->count() !== 1
                || ! $hasOriginal) {
                throw ValidationException::withMessages([
                    'component_ids' => 'A bushing group must contain one original and its oversizes linked by the same Initial Bushing IPL Number.',
                ]);
            }
        }
        if (! empty($data['manual_service_bulletin_id']) && ! $manual->serviceBulletins()->whereKey($data['manual_service_bulletin_id'])->exists()) {
            throw ValidationException::withMessages(['manual_service_bulletin_id' => 'The Service Bulletin must belong to this manual.']);
        }

        $data['component_ids'] = $componentIds->all();
        $data['included_group_option_ids'] = $includedOptionIds->all();

        return $data;
    }

    private function replaceOptions(ManualPartGroup $group, array $data, bool $preserveExisting): void
    {
        $components = Component::query()->whereIn('id', $data['component_ids'])->get()->keyBy('id');

        if ($group->behavior === ManualPartGroup::BEHAVIOR_CHOOSE_ONE) {
            $existing = $preserveExisting
                ? $group->options()->get()->keyBy(fn (ManualPartGroupOption $option): int => (int) $option->component_id)
                : collect();
            $keptOptionIds = [];

            foreach ($data['component_ids'] as $index => $componentId) {
                $component = $components->get($componentId);
                $option = $existing->get($componentId) ?: new ManualPartGroupOption([
                    'manual_part_group_id' => $group->id,
                ]);
                $option->fill([
                    'component_id' => $componentId,
                    'part_number' => (string) $component->part_number,
                    'ipl_num' => (string) $component->ipl_num,
                    'option_kind' => $group->type === ManualPartGroup::TYPE_OVERSIZE
                        ? (strcasecmp(trim((string) $component->ipl_num), trim((string) $component->bush_ipl_num)) === 0 ? 'original' : 'oversize')
                        : 'alternate',
                    'is_default' => (int) ($data['default_component_id'] ?? $data['component_ids'][0]) === $componentId,
                    'sort_order' => $index,
                ])->save();
                $keptOptionIds[] = (int) $option->id;
            }

            $this->removeUnusedOptions($group, $keptOptionIds);

            return;
        }

        $option = $preserveExisting ? $group->options()->first() : null;
        $option ??= new ManualPartGroupOption(['manual_part_group_id' => $group->id]);
        $option->fill([
            'component_id' => $group->type === ManualPartGroup::TYPE_ASSY
                ? (int) $data['default_component_id']
                : null,
            'part_number' => trim((string) $data['order_part_number']),
            'ipl_num' => trim((string) ($data['order_ipl_num'] ?? '')) ?: null,
            'option_kind' => $group->type === ManualPartGroup::TYPE_ASSY ? 'assy' : 'kit',
            'is_default' => true,
            'sort_order' => 0,
        ])->save();

        $this->removeUnusedOptions($group, [(int) $option->id]);
        $keptCoverageIds = [];
        $existingComponentCoverages = $option->coverages()->whereNotNull('component_id')->get()->keyBy('component_id');
        $existingAssyCoverages = $option->coverages()->whereNotNull('covered_manual_part_group_option_id')->get()->keyBy('covered_manual_part_group_option_id');

        foreach ($data['component_ids'] as $componentId) {
            $coverage = $existingComponentCoverages->get($componentId) ?: $option->coverages()->make([
                'component_id' => $componentId,
            ]);
            $coverage->fill([
                'covered_manual_part_group_option_id' => null,
                'qty' => max(1, (int) ($data['member_qty'][$componentId] ?? 1)),
                'applies_to' => array_values($data['member_applies_to'][$componentId] ?? $data['applies_to']),
            ])->save();
            $keptCoverageIds[] = (int) $coverage->id;
        }

        foreach ($data['included_group_option_ids'] as $includedOptionId) {
            $coverage = $existingAssyCoverages->get($includedOptionId) ?: $option->coverages()->make([
                'covered_manual_part_group_option_id' => $includedOptionId,
            ]);
            $coverage->fill([
                'component_id' => null,
                'qty' => max(1, (int) ($data['included_group_qty'][$includedOptionId] ?? 1)),
                'applies_to' => array_values($data['applies_to']),
            ])->save();
            $keptCoverageIds[] = (int) $coverage->id;
        }

        $option->coverages()->whereNotIn('id', $keptCoverageIds)->delete();
    }

    private function removeUnusedOptions(ManualPartGroup $group, array $keptOptionIds): void
    {
        $removedOptionIds = $group->options()->whereNotIn('id', $keptOptionIds)->pluck('id');
        if ($removedOptionIds->isEmpty()) {
            return;
        }

        WorkorderPartGroupSelection::query()
            ->whereIn('manual_part_group_option_id', $removedOptionIds)
            ->delete();
        $this->removeIncomingOptionCoverages($removedOptionIds->all());
        $group->options()->whereIn('id', $removedOptionIds)->delete();
    }

    private function removeIncomingOptionCoverages(array $optionIds): void
    {
        if ($optionIds === []) {
            return;
        }

        ManualPartGroupCoverage::query()
            ->whereIn('covered_manual_part_group_option_id', $optionIds)
            ->delete();
    }

    private function authorizeManualUpdate(Request $request, Manual $manual): void
    {
        abort_unless($request->user() && ($request->user()->roleIs('Admin') || $request->user()->can('manuals.update', $manual)), 403);
        $manual->loadMissing('partLock');
        abort_if($manual->partLock && ! $request->user()->canManageLockedManualParts(), 423, 'Manual parts are locked.');
    }

    private function ensureGroupBelongsToManual(ManualPartGroup $group, Manual $manual): void
    {
        abort_unless((int) $group->manual_id === (int) $manual->id, 404);
    }

    private function nextCode(): string
    {
        do {
            $code = 'MPG-'.Str::upper(Str::random(8));
        } while (ManualPartGroup::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    private function serializeGroup(ManualPartGroup $group): array
    {
        return [
            'id' => (int) $group->id,
            'code' => $group->code,
            'name' => $group->name,
            'type' => $group->type,
            'behavior' => $group->behavior,
            'applies_to' => $group->applies_to,
            'service_bulletin_id' => $group->manual_service_bulletin_id,
            'notes' => $group->notes,
            'options' => $group->options->map(fn (ManualPartGroupOption $option): array => [
                'id' => (int) $option->id,
                'component_id' => $option->component_id ? (int) $option->component_id : null,
                'part_number' => $option->part_number,
                'ipl_num' => $option->ipl_num,
                'is_default' => (bool) $option->is_default,
                'coverages' => $option->coverages->map(fn ($coverage): array => [
                    'component_id' => $coverage->component_id ? (int) $coverage->component_id : null,
                    'covered_option_id' => $coverage->covered_manual_part_group_option_id
                        ? (int) $coverage->covered_manual_part_group_option_id
                        : null,
                    'qty' => (int) $coverage->qty,
                    'applies_to' => $coverage->applies_to,
                    'part_number' => $coverage->component?->part_number,
                    'ipl_num' => $coverage->component?->ipl_num,
                    'covered_option' => $coverage->coveredOption ? [
                        'part_number' => $coverage->coveredOption->part_number,
                        'ipl_num' => $coverage->coveredOption->ipl_num,
                        'group_name' => $coverage->coveredOption->group?->name,
                        'component_ids' => $coverage->coveredOption->coverages
                            ->pluck('component_id')
                            ->filter()
                            ->map(fn ($componentId): int => (int) $componentId)
                            ->values()
                            ->all(),
                    ] : null,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
