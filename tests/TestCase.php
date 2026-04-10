<?php

namespace Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $runtimePath = base_path('codex-test-runtime');
        $viewPath = $runtimePath . DIRECTORY_SEPARATOR . 'views';
        $publicDiskPath = $runtimePath . DIRECTORY_SEPARATOR . 'disks' . DIRECTORY_SEPARATOR . 'public';
        $temporaryMediaPath = $runtimePath . DIRECTORY_SEPARATOR . 'temp-media';

        File::ensureDirectoryExists($viewPath);
        File::ensureDirectoryExists($publicDiskPath);
        File::ensureDirectoryExists($temporaryMediaPath);

        config()->set('view.compiled', $viewPath);
        config()->set('filesystems.disks.public.root', $publicDiskPath);
        config()->set('media-library.disk_name', 'public');
        config()->set('media-library.temporary_directory_path', $temporaryMediaPath);
    }

    protected function makeUploadedImage(string $fileName): UploadedFile
    {
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn4x0sAAAAASUVORK5CYII='
        );

        return $this->makeUploadedFile($fileName, $pngBytes, 'image/png');
    }

    protected function makeUploadedFile(string $fileName, string $content, string $mimeType): UploadedFile
    {
        $uploadDir = base_path('codex-test-runtime/uploads');
        File::ensureDirectoryExists($uploadDir);

        $path = $uploadDir . DIRECTORY_SEPARATOR . uniqid('upload_', true) . '_' . $fileName;
        File::put($path, $content);

        return new UploadedFile($path, $fileName, $mimeType, null, true);
    }
}
