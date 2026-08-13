<?php

namespace App\Console\Commands;

use App\Models\Workorder;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AuditArchiveMedia extends Command
{
    protected $signature = 'archive:audit-media
        {--mark-missing : Mark confirmed missing source files so polling can permanently skip them}
        {--older-than=24 : Inspect only records at least this many hours old}
        {--limit=0 : Maximum records to inspect; 0 means all}';

    protected $description = 'Audit unsynced workorder media whose source model or file is missing';

    public function handle(): int
    {
        $olderThanHours = filter_var(
            $this->option('older-than'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );
        $limit = filter_var(
            $this->option('limit'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($olderThanHours === false || $limit === false) {
            $this->error('--older-than and --limit must be non-negative integers.');

            return self::INVALID;
        }

        $markMissing = (bool) $this->option('mark-missing');
        $collections = array_keys(config('workorder_media.groups', []));
        $inspected = 0;
        $missing = 0;
        $marked = 0;

        $query = Media::query()
            ->where('model_type', (new Workorder())->getMorphClass())
            ->whereNull('archive_synced_at')
            ->whereNull('archive_skipped_at')
            ->whereIn('collection_name', $collections)
            ->where('mime_type', 'like', 'image/%')
            ->where('created_at', '<=', now()->subHours($olderThanHours))
            ->with('model');

        $query->chunkById(200, function ($mediaBatch) use (
            &$inspected,
            &$missing,
            &$marked,
            $limit,
            $markMissing
        ): bool {
            foreach ($mediaBatch as $media) {
                if ($limit > 0 && $inspected >= $limit) {
                    return false;
                }

                $inspected++;
                $reason = $this->missingReason($media);

                if ($reason === null) {
                    continue;
                }

                $missing++;
                $this->line("Media {$media->id}: {$reason}");

                if ($markMissing) {
                    $media->forceFill([
                        'archive_skipped_at' => now(),
                        'archive_skip_reason' => $reason,
                    ])->save();
                    $marked++;
                }
            }

            return $limit === 0 || $inspected < $limit;
        });

        $this->newLine();
        $this->info("Inspected: {$inspected}; missing: {$missing}; marked skipped: {$marked}.");

        if (!$markMissing && $missing > 0) {
            $this->warn('Dry run only. Re-run with --mark-missing after checking the listed records.');
        }

        return self::SUCCESS;
    }

    private function missingReason(Media $media): ?string
    {
        if (!$media->model instanceof Workorder) {
            return 'workorder_missing';
        }

        $path = $media->getPath();

        return $path && is_file($path) ? null : 'source_file_missing';
    }
}
