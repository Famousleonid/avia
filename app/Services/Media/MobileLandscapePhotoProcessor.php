<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class MobileLandscapePhotoProcessor
{
    public const VALIDATION_MESSAGE = 'Turn your phone sideways before taking the photo. Mobile photos must be landscape.';

    public function __construct(private readonly ImageOrientationNormalizer $orientationNormalizer)
    {
    }

    public function prepare(UploadedFile $file, string $attribute = 'photo'): UploadedFile
    {
        $normalized = $this->orientationNormalizer->normalize($file);
        $dimensions = @getimagesize($normalized->getPathname());
        $width = is_array($dimensions) ? (int) ($dimensions[0] ?? 0) : 0;
        $height = is_array($dimensions) ? (int) ($dimensions[1] ?? 0) : 0;

        if ($width <= 0 || $height <= 0 || $width <= $height) {
            $this->discardTemporaryFile($file, $normalized);

            throw ValidationException::withMessages([
                $attribute => [self::VALIDATION_MESSAGE],
            ]);
        }

        return $normalized;
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, UploadedFile>
     */
    public function prepareMany(array $files, string $attribute = 'photos'): array
    {
        $prepared = [];

        try {
            foreach ($files as $index => $file) {
                $prepared[$index] = $this->prepare($file, $attribute.'.'.$index);
            }
        } catch (ValidationException $exception) {
            foreach ($prepared as $index => $normalized) {
                $this->discardTemporaryFile($files[$index], $normalized);
            }

            throw $exception;
        }

        return $prepared;
    }

    public function discardTemporaryFile(UploadedFile $original, UploadedFile $prepared): void
    {
        if ($original->getPathname() === $prepared->getPathname()) {
            return;
        }

        $preparedPath = $prepared->getPathname();
        $temporaryRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($preparedPath, $temporaryRoot) && is_file($preparedPath)) {
            @unlink($preparedPath);
        }
    }
}
