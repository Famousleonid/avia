<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PhotoPreviewSizeTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_gallery_preview_sizes_use_original_pixels_and_cache_each_size(): void
    {
        Storage::fake('public');
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $media = $wo->addMedia(UploadedFile::fake()->image('portrait.jpg', 1200, 1800))->toMediaCollection('photos', 'public');
        $url = route('image.show.thumb', ['mediaId' => $media->id, 'modelId' => $wo->id, 'mediaName' => 'photos']);
        $originalHash = hash_file('sha256', $media->getPath());
        $paths = [];
        try {
            foreach ([80, 128, 192, 256, 320, 384, 512, 640, 768, 960] as $size) {
                $response = $this->actingAs($admin)->get($url.'?size='.$size)->assertOk();
                $path = $response->baseResponse->getFile()->getPathname();
                $paths[] = $path;
                $this->assertSame([$size, $size], array_slice(getimagesize($path), 0, 2));
                $etag = $response->headers->get('ETag');
                $this->get($url.'?size='.$size, ['If-None-Match' => $etag])->assertStatus(304);
            }
            $this->assertCount(10, array_unique($paths));
            $this->assertSame($originalHash, hash_file('sha256', $media->getPath()));
            $this->get($url.'?size=99999')->assertStatus(422);
            $this->get($url)->assertOk();
        } finally {
            foreach ($paths as $path) @unlink($path);
        }
    }
}
