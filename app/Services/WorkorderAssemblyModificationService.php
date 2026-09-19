<?php

namespace App\Services;

use App\Models\ManualPartGroup;
use App\Models\RmReport;
use App\Models\Workorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkorderAssemblyModificationService
{
    /**
     * Synchronize the effective assembly scope from the R&M records selected
     * for a workorder. The received Work Scope remains unchanged.
     *
     * @param  Collection<int, RmReport>  $selectedRecords
     * @return array{changed:bool,modified:?string,target_option_id:?int,rm_report_id:?int}
     */
    public function sync(Workorder $workorder, Collection $selectedRecords): array
    {
        $conversions = $selectedRecords
            ->filter(fn (RmReport $record): bool => $record->changesAssemblyScope())
            ->values();

        if ($conversions->count() > 1) {
            throw ValidationException::withMessages([
                'selected_records' => __('Select only one assembly-changing SB record for this Workorder.'),
            ]);
        }

        /** @var RmReport|null $conversion */
        $conversion = $conversions->first();
        $oldTargetId = $workorder->modified_scope_part_group_option_id
            ? (int) $workorder->modified_scope_part_group_option_id
            : null;
        $oldRecordId = $workorder->modified_scope_rm_report_id
            ? (int) $workorder->modified_scope_rm_report_id
            : null;

        if (! $conversion) {
            $changed = $oldTargetId !== null || $oldRecordId !== null;
            if ($changed) {
                $previousTargetPartNumber = trim((string) $workorder->modifiedScopePartGroupOption?->part_number);
                $modified = trim((string) $workorder->modified);

                $workorder->forceFill([
                    'modified_scope_part_group_option_id' => null,
                    'modified_scope_rm_report_id' => null,
                    // Clear only the value which this feature populated. Keep
                    // unrelated legacy free-text modifications intact.
                    'modified' => $modified !== '' && strcasecmp($modified, $previousTargetPartNumber) === 0
                        ? null
                        : $workorder->modified,
                ])->save();
                app(WorkorderStdProcessItemsService::class)->rebuild($workorder->fresh(['unit.manuals', 'instruction']));
            }

            return [
                'changed' => $changed,
                'modified' => $workorder->fresh()->modified,
                'target_option_id' => null,
                'rm_report_id' => null,
            ];
        }

        $conversion->loadMissing([
            'sourceAssyOption.group',
            'targetAssyOption.group',
            'serviceBulletin',
        ]);
        $source = $conversion->sourceAssyOption;
        $target = $conversion->targetAssyOption;
        $manualId = (int) ($workorder->unit?->manual_id ?? 0);

        if (! $source || ! $target
            || $source->group?->type !== ManualPartGroup::TYPE_ASSY
            || $target->group?->type !== ManualPartGroup::TYPE_ASSY
            || (int) $source->group->manual_id !== $manualId
            || (int) $target->group->manual_id !== $manualId) {
            throw ValidationException::withMessages([
                'selected_records' => __('The selected SB assembly conversion does not belong to this Workorder manual.'),
            ]);
        }

        if (! $this->sourceMatchesReceivedWorkorder($workorder, $source->id, (string) $source->part_number)) {
            throw ValidationException::withMessages([
                'selected_records' => __('The selected SB conversion source does not match the received Work Scope.'),
            ]);
        }

        $targetId = (int) $target->id;
        $recordId = (int) $conversion->id;
        $changed = $oldTargetId !== $targetId || $oldRecordId !== $recordId;

        if ($changed || strcasecmp(trim((string) $workorder->modified), trim((string) $target->part_number)) !== 0) {
            DB::transaction(function () use ($workorder, $targetId, $recordId, $target): void {
                $workorder->forceFill([
                    'modified_scope_part_group_option_id' => $targetId,
                    'modified_scope_rm_report_id' => $recordId,
                    'modified' => trim((string) $target->part_number),
                ])->save();
            });

            if ($changed) {
                app(WorkorderStdProcessItemsService::class)->rebuild($workorder->fresh(['unit.manuals', 'instruction']));
            }
        }

        return [
            'changed' => $changed,
            'modified' => trim((string) $target->part_number),
            'target_option_id' => $targetId,
            'rm_report_id' => $recordId,
        ];
    }

    private function sourceMatchesReceivedWorkorder(Workorder $workorder, int $sourceOptionId, string $sourcePartNumber): bool
    {
        if ((int) ($workorder->scope_part_group_option_id ?? 0) === $sourceOptionId) {
            return true;
        }

        $normalizedSource = $this->normalizePartNumber($sourcePartNumber);
        if ($normalizedSource === '') {
            return false;
        }

        if ($this->normalizePartNumber($workorder->unit?->part_number) === $normalizedSource) {
            return true;
        }

        return $this->normalizePartNumber($workorder->scopeComponent?->part_number) === $normalizedSource;
    }

    private function normalizePartNumber(?string $partNumber): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', mb_strtoupper(trim((string) $partNumber))) ?? '';
    }
}
