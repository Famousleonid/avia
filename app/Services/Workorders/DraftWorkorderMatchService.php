<?php

namespace App\Services\Workorders;

use App\Models\Unit;
use App\Models\Workorder;

class DraftWorkorderMatchService
{
    /**
     * Find active Shipping drafts whose P/N or S/N matches the WO being created.
     *
     * @return array<int, array<string, mixed>>
     */
    public function find(?int $unitId, ?string $serialNumber, int $limit = 10): array
    {
        $unit = $unitId ? Unit::query()->find($unitId) : null;
        $partNumberKey = $this->normalizeIdentifier($unit?->part_number);
        $serialNumberKey = $this->normalizeIdentifier($serialNumber);

        if ($partNumberKey === '' && $serialNumberKey === '') {
            return [];
        }

        return Workorder::onlyDrafts()
            ->with(['unit', 'customer', 'user', 'media'])
            ->orderByDesc('open_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Workorder $draft) use ($partNumberKey, $serialNumberKey): ?array {
                $matchedBy = [];

                if (
                    $partNumberKey !== ''
                    && $partNumberKey === $this->normalizeIdentifier($draft->unit?->part_number)
                ) {
                    $matchedBy[] = 'P/N';
                }

                if (
                    $serialNumberKey !== ''
                    && $serialNumberKey === $this->normalizeIdentifier($draft->serial_number)
                ) {
                    $matchedBy[] = 'S/N';
                }

                if ($matchedBy === []) {
                    return null;
                }

                $photos = $draft->media
                    ->filter(fn ($media) => str_starts_with(strtolower((string) $media->mime_type), 'image/'))
                    ->values()
                    ->map(function ($media) use ($draft): array {
                        $collection = (string) $media->collection_name;

                        return [
                            'id' => (int) $media->id,
                            'label' => trim((string) ($media->name ?? $media->file_name ?? 'Photo')) ?: 'Photo',
                            'collection' => $collection,
                            'thumb_url' => route('image.show.thumb', [
                                'mediaId' => $media->id,
                                'modelId' => $draft->id,
                                'mediaName' => $collection,
                            ]),
                            'big_url' => route('image.show.big', [
                                'mediaId' => $media->id,
                                'modelId' => $draft->id,
                                'mediaName' => $collection,
                            ]),
                        ];
                    })
                    ->all();

                return [
                    'id' => (int) $draft->id,
                    'number' => (int) ($draft->draft_number ?: $draft->number),
                    'part_number' => trim((string) ($draft->unit?->part_number ?? '')) ?: null,
                    'serial_number' => trim((string) ($draft->serial_number ?? '')) ?: null,
                    'description' => trim((string) ($draft->description ?? '')) ?: null,
                    'customer' => trim((string) ($draft->customer?->name ?? '')) ?: null,
                    'created_by' => trim((string) ($draft->user?->selection_name ?? '')) ?: null,
                    'open_date' => format_project_date($draft->open_at),
                    'photo_count' => count($photos),
                    'photos' => $photos,
                    'matched_by' => $matchedBy,
                    'edit_url' => route('workorders.edit', $draft->id),
                ];
            })
            ->filter()
            ->take(max(1, $limit))
            ->values()
            ->all();
    }

    private function normalizeIdentifier(?string $value): string
    {
        $normalized = preg_replace('/[^\pL\pN]+/u', '', mb_strtoupper(trim((string) $value)));

        return $normalized ?? '';
    }
}
