<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;

/**
 * Makes JPEG pixels match the EXIF orientation before a file is persisted.
 *
 * Mobile cameras often store a landscape sensor image plus an EXIF Orientation
 * tag instead of rotating the pixels. Normalizing at the upload boundary keeps
 * every later consumer (browser, thumbnail generator, PDF, native app) in sync.
 */
class ImageOrientationNormalizer
{
    public function normalize(UploadedFile $file): UploadedFile
    {
        if (! $this->canNormalize($file)) {
            return $file;
        }

        $orientation = $this->orientation($file->getPathname());
        if ($orientation < 2 || $orientation > 8) {
            return $file;
        }

        $image = @imagecreatefromjpeg($file->getPathname());
        if ($image === false) {
            return $file;
        }

        $normalized = $this->applyOrientation($image, $orientation);
        if ($normalized === false) {
            imagedestroy($image);

            return $file;
        }

        if ($normalized !== $image) {
            imagedestroy($image);
        }

        $path = tempnam(sys_get_temp_dir(), 'avia-photo-');
        if ($path === false) {
            imagedestroy($normalized);

            return $file;
        }

        $jpegPath = $path . '.jpg';
        @unlink($path);
        $written = @imagejpeg($normalized, $jpegPath, 92);
        imagedestroy($normalized);

        if (! $written || ! is_file($jpegPath)) {
            @unlink($jpegPath);

            return $file;
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'photo';

        return new UploadedFile($jpegPath, $baseName . '.jpg', 'image/jpeg', null, true);
    }

    private function canNormalize(UploadedFile $file): bool
    {
        return function_exists('exif_read_data')
            && function_exists('imagecreatefromjpeg')
            && function_exists('imagejpeg')
            && function_exists('imagerotate')
            && function_exists('imageflip')
            && in_array(strtolower((string) $file->getMimeType()), ['image/jpeg', 'image/pjpeg'], true)
            && is_file($file->getPathname());
    }

    private function orientation(string $path): int
    {
        $exif = @exif_read_data($path);

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    /**
     * @param \GdImage|resource $image
     * @return \GdImage|resource|false
     */
    private function applyOrientation($image, int $orientation)
    {
        $rotation = null;
        $flipHorizontal = false;
        $flipVertical = false;

        switch ($orientation) {
            case 2:
                $flipHorizontal = true;
                break;
            case 3:
                $rotation = 180;
                break;
            case 4:
                $flipVertical = true;
                break;
            case 5:
                $flipHorizontal = true;
                $rotation = 270;
                break;
            case 6:
                $rotation = 270;
                break;
            case 7:
                $flipHorizontal = true;
                $rotation = 90;
                break;
            case 8:
                $rotation = 90;
                break;
        }

        if ($flipHorizontal) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        if ($flipVertical) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }
        if ($rotation === null) {
            return $image;
        }

        $background = imagecolorallocate($image, 255, 255, 255);

        return @imagerotate($image, $rotation, $background === false ? 0 : $background);
    }
}
