<?php

namespace App\Console\Commands;

use App\Models\Workorder;
use App\Services\Media\GalleryThumbnailService;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WarmPhotoPreviews extends Command
{
    protected $signature = 'photos:warm-previews {--after=0 : Resume after this media ID}';
    protected $description = 'Prepare cached 80 and 128 px previews for existing workorder photos';

    public function handle(GalleryThumbnailService $thumbnails): int
    {
        if (!ctype_digit((string) $this->option('after'))) {
            $this->error('--after must be a non-negative media ID.');
            return self::FAILURE;
        }
        $ready = $failed = 0;
        $this->info('Preparing 80 and 128 px workorder previews...');
        Media::query()->where('model_type', (new Workorder())->getMorphClass())
            ->where('mime_type', 'like', 'image/%')->where('id', '>', (int) $this->option('after'))
            ->chunkById(100, function ($items) use ($thumbnails, &$ready, &$failed) {
                foreach ($items as $media) {
                    try {
                        if (!$thumbnails->warmSmall($media)) throw new \RuntimeException('Source missing or unsupported image.');
                        $ready++;
                    } catch (\Throwable $error) {
                        $failed++;
                        $this->warn('Media '.$media->id.': '.$error->getMessage());
                    }
                }
                $this->line('Through media '.$items->last()->id.': ready '.$ready.', failed '.$failed);
            });
        $this->info('Finished: ready '.$ready.', failed '.$failed.'. Existing previews reused; originals unchanged.');
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
