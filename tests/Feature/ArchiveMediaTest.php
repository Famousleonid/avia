<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ArchiveMediaTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_pending_queue_scans_past_missing_files_before_applying_limit(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        config()->set('archive.sync_token', 'archive-test-token');

        $workorder = $this->createWorkorder();

        $missingMedia = $workorder
            ->addMedia(UploadedFile::fake()->image('missing.png', 20, 20))
            ->toMediaCollection('photos');
        File::delete($missingMedia->getPath());

        $validMedia = $workorder
            ->addMedia(UploadedFile::fake()->image('valid.png', 20, 20))
            ->toMediaCollection('photos');

        $response = $this
            ->withToken('archive-test-token')
            ->getJson(route('archive.pending-media', ['limit' => 1]));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $validMedia->id);
        $response->assertJsonMissing(['id' => $missingMedia->id]);
    }

    public function test_archive_audit_is_dry_run_until_mark_missing_is_requested(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));

        $workorder = $this->createWorkorder();
        $missingMedia = $workorder
            ->addMedia(UploadedFile::fake()->image('orphan.png', 20, 20))
            ->toMediaCollection('photos');
        File::delete($missingMedia->getPath());

        $this->artisan('archive:audit-media', [
            '--older-than' => 0,
        ])->assertExitCode(0);

        $this->assertNull($missingMedia->fresh()->archive_skipped_at);
        $this->assertNull($missingMedia->fresh()->archive_skip_reason);

        $this->artisan('archive:audit-media', [
            '--older-than' => 0,
            '--mark-missing' => true,
        ])->assertExitCode(0);

        $missingMedia->refresh();

        $this->assertNotNull($missingMedia->archive_skipped_at);
        $this->assertSame('source_file_missing', $missingMedia->archive_skip_reason);
    }
}
