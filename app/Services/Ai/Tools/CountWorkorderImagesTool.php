<?php

namespace App\Services\Ai\Tools;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Support\Facades\DB;

class CountWorkorderImagesTool
{
    public function run(User $user, array $args): array
    {
        $workorderId = (int)($args['workorder_id'] ?? 0);
        $workorderNumber = trim((string)($args['workorder_number'] ?? ''));
        $limit = (int)($args['limit'] ?? 10);
        $limit = max(1, min(50, $limit));

        if ($workorderId > 0 || $workorderNumber !== '') {
            return $this->singleWorkorderCount($user, $workorderId, $workorderNumber);
        }

        return $this->topWorkordersByImages($user, $limit);
    }

    public function schema(): array
    {
        return [
            'type' => 'function',
            'name' => 'countWorkorderImages',
            'description' => 'Count workorder images/photos. Use it to answer how many pictures a specific workorder has, or to list top workorders with the most images/photos. Read-only. Never expose internal database IDs.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'workorder_id' => [
                        'type' => 'integer',
                        'description' => 'Internal workorder id only when supplied by current page context. Do not ask the user for it.',
                    ],
                    'workorder_number' => [
                        'type' => 'string',
                        'description' => 'Human workorder number, e.g. 108400. Use this when the user names a specific WO.',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'For top list mode, max number of workorders to return. Default 10, max 50.',
                    ],
                ],
                'additionalProperties' => false,
            ],
        ];
    }

    private function singleWorkorderCount(User $user, int $workorderId, string $workorderNumber): array
    {
        $query = Workorder::withDrafts()->with(['customer', 'unit']);
        $workorder = $workorderId > 0
            ? $query->find($workorderId)
            : $query->where('number', $workorderNumber)->first();

        if (! $workorder) {
            return [
                'ok' => false,
                'message' => 'Workorder not found.',
            ];
        }

        if (! $user->can('workorders.view', $workorder)) {
            return [
                'ok' => false,
                'message' => 'You do not have permission to view this workorder.',
            ];
        }

        return [
            'ok' => true,
            'workorder' => $this->formatWorkorderRow($workorder, $this->imageCountForWorkorder((int)$workorder->id)),
            'instruction_for_model' => 'Answer with the WO number and image count. Make the WO number a markdown link. Never mention internal database IDs.',
        ];
    }

    private function topWorkordersByImages(User $user, int $limit): array
    {
        $counts = DB::table('media')
            ->select('model_id', DB::raw('COUNT(*) as image_count'))
            ->where('model_type', Workorder::class)
            ->where('mime_type', 'like', 'image/%')
            ->when($this->workorderMediaCollections() !== [], function ($query): void {
                $query->whereIn('collection_name', $this->workorderMediaCollections());
            })
            ->groupBy('model_id')
            ->orderByDesc('image_count')
            ->limit(500)
            ->get();

        $ids = $counts->pluck('model_id')->map(fn ($id) => (int)$id)->all();
        $workorders = Workorder::withDrafts()
            ->with(['customer', 'unit'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $rows = $counts
            ->map(function ($row) use ($workorders, $user) {
                $workorder = $workorders->get((int)$row->model_id);
                if (! $workorder || ! $user->can('workorders.view', $workorder)) {
                    return null;
                }

                return $this->formatWorkorderRow($workorder, (int)$row->image_count);
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();

        return [
            'ok' => true,
            'count' => count($rows),
            'workorders' => $rows,
            'instruction_for_model' => 'List one result per line. Only the workorder number is a markdown link, then plain text with image_count, customer/unit if useful. Never mention internal database IDs. If count is 0, say no workorders with images were found.',
        ];
    }

    private function imageCountForWorkorder(int $workorderId): int
    {
        return (int) DB::table('media')
            ->where('model_type', Workorder::class)
            ->where('model_id', $workorderId)
            ->where('mime_type', 'like', 'image/%')
            ->when($this->workorderMediaCollections() !== [], function ($query): void {
                $query->whereIn('collection_name', $this->workorderMediaCollections());
            })
            ->count();
    }

    private function workorderMediaCollections(): array
    {
        $groups = config('workorder_media.groups', ['photos' => 'Photos']);

        return is_array($groups) ? array_keys($groups) : ['photos'];
    }

    private function formatWorkorderRow(Workorder $workorder, int $imageCount): array
    {
        return [
            'number' => $workorder->number,
            'image_count' => $imageCount,
            'customer' => $workorder->customer?->name,
            'unit' => $workorder->unit?->name ?: $workorder->unit?->part_number,
            'url' => route('mains.show', $workorder->id),
        ];
    }
}
