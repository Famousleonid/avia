<?php

namespace App\Listeners;

use App\Models\Workorder;
use App\Services\Media\GalleryThumbnailService;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAdded;

class WarmWorkorderPhotoPreviews
{
    public function handle(MediaHasBeenAdded $event): void
    {
        $media = $event->media;
        if ($media->model_type !== (new Workorder())->getMorphClass()
            || !str_starts_with((string) $media->mime_type, 'image/')) return;

        // Synchronous: the upload response must not race the first gallery load.
        // A bad image must not turn an already stored upload into a failed upload.
        try {
            if (!app(GalleryThumbnailService::class)->warmSmall($media)) {
                \Log::warning('Small photo previews could not be prepared', ['media_id' => $media->id]);
            }
        } catch (\Throwable $error) {
            report($error);
        }
    }
}
