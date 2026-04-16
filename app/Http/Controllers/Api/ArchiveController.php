<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArchiveController extends Controller
{
    public function pendingMedia(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 100);
        $limit = max(1, min($limit, 500));

        $collections = array_keys(config('workorder_media.groups', []));

        $items = Media::query()
            ->where('model_type', (new Workorder())->getMorphClass())
            ->whereNull('archive_synced_at')
            ->whereIn('collection_name', $collections)
            ->where('mime_type', 'like', 'image/%')
            ->with('model')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->filter(function (Media $media): bool {
                return $media->model instanceof Workorder
                    && $this->mediaFileExists($media);
            })
            ->map(function (Media $media): array {
                /** @var Workorder $workorder */
                $workorder = $media->model;

                return [
                    'id' => $media->id,
                    'workorder_number' => (string) $workorder->number,
                    'collection_name' => $media->collection_name,
                    'filename' => $media->file_name,
                    'size' => (int) $media->size,
                    'download_url' => route('archive.download', ['media' => $media->id]),
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function download(Media $media): BinaryFileResponse|JsonResponse
    {
        $error = $this->validateArchiveMedia($media);

        if ($error) {
            return $error;
        }

        $path = $media->getPath();

        return response()->download($path, $media->file_name, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function markSynced(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $media = Media::find($data['id']);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $error = $this->validateArchiveMedia($media, requireUnsynced: false);

        if ($error) {
            return $error;
        }

        $syncedAt = now();

        $media->forceFill([
            'archive_synced_at' => $syncedAt,
        ])->save();

        return response()->json([
            'success' => true,
            'id' => $media->id,
            'archive_synced_at' => $syncedAt->toISOString(),
        ]);
    }

    private function validateArchiveMedia(Media $media, bool $requireUnsynced = true): ?JsonResponse
    {
        if ($media->model_type !== (new Workorder())->getMorphClass()) {
            return response()->json(['message' => 'Media is not attached to a workorder'], 422);
        }

        if ($requireUnsynced && $media->archive_synced_at) {
            return response()->json(['message' => 'Media is already synced'], 409);
        }

        $collections = array_keys(config('workorder_media.groups', []));

        if (!in_array($media->collection_name, $collections, true)) {
            return response()->json(['message' => 'Media collection is not archivable'], 422);
        }

        if (!$media->mime_type || !Str::startsWith($media->mime_type, 'image/')) {
            return response()->json(['message' => 'Media is not an image'], 422);
        }

        if (!$media->model instanceof Workorder) {
            return response()->json(['message' => 'Workorder not found'], 404);
        }

        if (!$this->mediaFileExists($media)) {
            return response()->json(['message' => 'Media file not found'], 404);
        }

        return null;
    }

    private function mediaFileExists(Media $media): bool
    {
        $path = $media->getPath();

        return $path && is_file($path);
    }
}
