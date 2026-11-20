<?php

namespace App\Services\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GalleryThumbnailService
{
    public function warmSmall(Media $media): bool
    {
        $path = $media->getPath();
        if (!is_file($path)) return false;
        $ready = true;
        foreach ([80, 128] as $size) {
            $ready = ($this->generate($media, $path, $size) !== null) && $ready;
        }
        return $ready;
    }

    public function generate(Media $media, string $originalPath, ?int $previewSize = null): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $sourceMtime = file_exists($originalPath) ? (int) filemtime($originalPath) : 0;
        $targetDir = storage_path('app/generated-thumbnails');
        $variant = $previewSize ? '_square_v2_' . $previewSize : '';
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . 'media_' . (int) $media->id . '_' . $sourceMtime . $variant . '.jpg';

        if (file_exists($targetPath)) {
            return $targetPath;
        }

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return null;
        }

        $info = @getimagesize($originalPath);
        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return null;
        }

        // Preserve camera orientation in the preview instead of serving the
        // multi-megabyte original for a small gallery tile.
        $orientation = 1;
        if ($previewSize && $info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($originalPath);
            $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        }

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($originalPath),
            'image/png' => @imagecreatefrompng($originalPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($originalPath) : false,
            default => false,
        };

        if (!$source) {
            return null;
        }

        $width = (int) imagesx($source);
        $height = (int) imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        $maxSide = 480;
        $scale = min($maxSide / $width, $maxSide / $height, 1);
        $thumbWidth = max(1, (int) round($width * $scale));
        $thumbHeight = max(1, (int) round($height * $scale));
        if ($previewSize) {
            // The gallery uses square object-fit:cover tiles. A long-side-only
            // resize would still stretch the short side on portrait photos.
            $thumbWidth = $thumbHeight = min($previewSize, $width, $height);
        }

        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        if (!$thumb) {
            imagedestroy($source);

            return null;
        }

        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
        $cropSide = min($width, $height);
        imagecopyresampled($thumb, $source, 0, 0,
            $previewSize ? (int) floor(($width - $cropSide) / 2) : 0,
            $previewSize ? (int) floor(($height - $cropSide) / 2) : 0,
            $thumbWidth, $thumbHeight,
            $previewSize ? $cropSide : $width, $previewSize ? $cropSide : $height);
        // A centered square crop commutes with EXIF rotation/reflection, so
        // transform the small preview rather than allocating another full image.
        if ($orientation >= 2 && $orientation <= 8) {
            $oriented = app(\App\Services\Media\ImageOrientationNormalizer::class)
                ->applyOrientation($thumb, $orientation);
            if ($oriented === false) {
                imagedestroy($thumb);
                imagedestroy($source);

                return null;
            }
            if ($oriented !== $thumb) imagedestroy($thumb);
            $thumb = $oriented;
        }
        imageinterlace($thumb, true);
        $temporaryPath = tempnam($targetDir, 'preview_');
        $quality = $previewSize ? match (true) {
            $previewSize <= 80 => 45,
            $previewSize <= 128 => 55,
            $previewSize <= 192 => 70,
            $previewSize <= 256 => 80,
            default => 88,
        } : 75;
        $saved = $temporaryPath !== false && imagejpeg($thumb, $temporaryPath, $quality);
        if ($saved) $saved = rename($temporaryPath, $targetPath);
        if ($temporaryPath && file_exists($temporaryPath)) @unlink($temporaryPath);

        imagedestroy($thumb);
        imagedestroy($source);

        return $saved && file_exists($targetPath) ? $targetPath : null;
    }

}
