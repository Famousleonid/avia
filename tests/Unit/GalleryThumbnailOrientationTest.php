<?php

namespace Tests\Unit;

use App\Http\Controllers\General\MediaController;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class GalleryThumbnailOrientationTest extends TestCase
{
    public function test_rotated_camera_photo_gets_a_small_correctly_oriented_cached_preview(): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('exif_read_data')) {
            $this->markTestSkipped('GD and EXIF required.');
        }
        $source = tempnam(sys_get_temp_dir(), 'gallery-test-');
        $preview = null;
        try {
            $image = imagecreatetruecolor(800, 600);
            imagefilledrectangle($image, 0, 0, 399, 599, imagecolorallocate($image, 255, 0, 0));
            imagefilledrectangle($image, 400, 0, 799, 599, imagecolorallocate($image, 0, 0, 255));
            imagejpeg($image, $source, 95);
            imagedestroy($image);
            $jpeg = file_get_contents($source);
            $exif = "Exif\x00\x00II\x2A\x00\x08\x00\x00\x00\x01\x00"
                . "\x12\x01\x03\x00\x01\x00\x00\x00\x06\x00\x00\x00\x00\x00\x00\x00";
            file_put_contents($source, substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2));
            $hash = hash_file('sha256', $source);
            $media = new Media();
            $media->id = -random_int(100000, 999999999);
            $method = new \ReflectionMethod(MediaController::class, 'generatedThumbPath');
            $method->setAccessible(true);
            $controller = app(MediaController::class);
            $preview = $method->invoke($controller, $media, $source, 128);
            $this->assertNotNull($preview, 'EXIF must not cause fallback to the original.');
            $this->assertSame([128, 128], array_slice(getimagesize($preview), 0, 2));
            $this->assertLessThan(filesize($source), filesize($preview));
            $pixels = imagecreatefromjpeg($preview);
            $top = imagecolorsforindex($pixels, imagecolorat($pixels, 64, 20));
            $bottom = imagecolorsforindex($pixels, imagecolorat($pixels, 64, 108));
            $this->assertGreaterThan(220, $top['red']);
            $this->assertGreaterThan(220, $bottom['blue']);
            imagedestroy($pixels);
            $this->assertSame($preview, $method->invoke($controller, $media, $source, 128));
            $this->assertSame($hash, hash_file('sha256', $source));
        } finally {
            @unlink($source);
            if ($preview) @unlink($preview);
        }
    }
}
