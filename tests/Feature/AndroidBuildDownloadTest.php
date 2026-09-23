<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AndroidBuildDownloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('android_builds');
    }

    public function test_config_selects_latest_version_and_downloads_exact_apk_without_login(): void
    {
        $disk = Storage::disk('android_builds');
        $disk->put('aviatechnik-v0.9.0.apk', 'old apk');
        $disk->put('aviatechnik-v0.10.0.apk', 'latest apk');
        $disk->put('aviatechnik-v99.0.apk.bak', 'ignored backup');
        $disk->put('archive/aviatechnik-v100.0.apk', 'ignored nested build');

        $url = route('api.android.public.download', ['filename' => 'aviatechnik-v0.10.0.apk']);
        $this->getJson(route('api.android.public.app-config'))->assertOk()
            ->assertJsonPath('data.app.android.update.version_name', '0.10.0')
            ->assertJsonPath('data.app.android.update.url', $url);

        foreach ([$url, '/app/aviatechnik-v0.10.0.apk'] as $downloadUrl) {
            $response = $this->get($downloadUrl)->assertOk()
                ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
                ->assertDownload('aviatechnik-v0.10.0.apk');
            $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
            $this->assertSame('latest apk', file_get_contents($response->baseResponse->getFile()->getPathname()));
        }
        $this->getJson(route('api.mobile.public.app-config'))->assertOk()
            ->assertJsonMissingPath('data.app.android');
    }

    public function test_missing_builds_and_unrelated_files_are_not_downloadable(): void
    {
        $this->getJson(route('api.android.public.app-config'))->assertOk()
            ->assertJsonPath('data.app.android.update', null);
        Storage::disk('android_builds')->put('private.txt', 'not an APK');
        foreach (['aviatechnik-v1.0.apk', 'private.txt', '..%2Fprivate.txt', 'aviatechnik-v1.0.apk.bak'] as $filename) {
            $this->get('/api/android/public/download/'.$filename)->assertNotFound();
        }
        $this->get('/app/private.txt')->assertNotFound();
    }
}
