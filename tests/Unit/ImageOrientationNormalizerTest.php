<?php

namespace Tests\Unit;

use App\Services\Media\ImageOrientationNormalizer;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageOrientationNormalizerTest extends TestCase
{
    public function test_it_bakes_jpeg_exif_orientation_into_the_image_pixels(): void
    {
        if (! function_exists('imagecreate') || ! function_exists('imagecreatefromjpeg') || ! function_exists('imagejpeg') || ! function_exists('imageflip') || ! function_exists('imagerotate') || ! function_exists('exif_read_data')) {
            $this->markTestSkipped('GD and EXIF are required for image-orientation normalization.');
        }

        $source = tempnam(sys_get_temp_dir(), 'orientation-source-');
        $this->assertNotFalse($source);
        $source .= '.jpg';

        $image = imagecreate(2, 3);
        imagecolorallocate($image, 255, 255, 255);
        imagejpeg($image, $source, 92);
        imagedestroy($image);

        file_put_contents($source, $this->jpegWithOrientation((string) file_get_contents($source), 6));

        $file = new UploadedFile($source, 'camera.jpg', 'image/jpeg', null, true);
        $normalized = app(ImageOrientationNormalizer::class)->normalize($file);

        $this->assertSame('image/jpeg', $normalized->getMimeType());
        $dimensions = getimagesize($normalized->getPathname());
        $this->assertSame(3, $dimensions[0]);
        $this->assertSame(2, $dimensions[1]);
        $exif = @exif_read_data($normalized->getPathname());
        $this->assertNotSame(6, (int) ($exif['Orientation'] ?? 1));

        @unlink($normalized->getPathname());
        @unlink($source);
    }

    private function jpegWithOrientation(string $jpeg, int $orientation): string
    {
        $tiff = "II\x2A\x00\x08\x00\x00\x00"
            . "\x01\x00"
            . "\x12\x01\x03\x00\x01\x00\x00\x00"
            . pack('v', $orientation) . "\x00\x00"
            . "\x00\x00\x00\x00";
        $exif = "Exif\x00\x00" . $tiff;
        $app1 = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;

        return substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
    }
}
