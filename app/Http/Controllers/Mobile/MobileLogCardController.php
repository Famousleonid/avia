<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Services\Ai\NameplateRecognitionService;
use App\Services\Media\ImageOrientationNormalizer;
use App\Services\MobileReviewAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MobileLogCardController extends Controller
{
    public function __construct(
        private readonly ImageOrientationNormalizer $imageOrientationNormalizer,
        private readonly NameplateRecognitionService $recognitionService,
    ) {
    }

    public function index(Request $request, Workorder $workorder)
    {
        $workorder->load(['unit.manual', 'main.task', 'media']);
        abort_unless(app(MobileReviewAccess::class)->canAccessWorkorder($request->user(), $workorder), 404);

        return view('mobile.pages.log-card', [
            'workorder' => $workorder,
            'logCardPhotos' => $this->photoPayloads($workorder),
        ]);
    }

    public function storePhoto(Request $request, Workorder $workorder): JsonResponse
    {
        $workorder->load(['unit.manual', 'main.task']);
        abort_unless(app(MobileReviewAccess::class)->canAccessWorkorder($request->user(), $workorder), 404);

        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'row_index' => ['nullable', 'integer', 'min:0', 'max:999'],
            'expected_part_number' => ['nullable', 'string', 'max:255'],
            'expected_assy_part_number' => ['nullable', 'string', 'max:255'],
            'recognize' => ['nullable', 'boolean'],
        ]);

        $photo = $this->imageOrientationNormalizer->normalize($request->file('photo'));
        $recognition = null;
        $recognitionError = null;
        $shouldRecognize = ! array_key_exists('recognize', $data) || (bool) $data['recognize'];
        $media = null;

        if ($shouldRecognize) {
            try {
                $recognition = $this->recognitionService->recognize(
                    $photo->getPathname(),
                    (string) $photo->getMimeType(),
                    [
                        'expected_part_number' => (string) ($data['expected_part_number'] ?? ''),
                        'expected_assy_part_number' => (string) ($data['expected_assy_part_number'] ?? ''),
                    ]
                );
            } catch (Throwable $e) {
                Log::warning('Mobile Log Card nameplate recognition failed', [
                    'workorder_id' => $workorder->id,
                    'exception' => $e,
                ]);
                $recognitionError = 'Avi could not read the nameplate. Enter the numbers manually or retake the photo. The photo will be saved only after Confirm.';
            } finally {
                $this->discardNormalizedTemporaryFile($request->file('photo'), $photo);
            }
        } else {
            $extension = strtolower((string) ($photo->getClientOriginalExtension() ?: 'jpg'));
            $filename = 'wo_'.$workorder->number.'_log_card_'.now()->format('Ymd_His').'_'.Str::random(4).'.'.$extension;
            $media = $workorder->addMedia($photo)
                ->usingFileName($filename)
                ->withCustomProperties(array_filter([
                    'source' => 'mobile_log_card',
                    'log_card_row_index' => $data['row_index'] ?? null,
                ], static fn ($value) => $value !== null))
                ->toMediaCollection('logs');
        }

        $workorder->load('media');
        $photos = $this->photoPayloads($workorder);

        return response()->json([
            'success' => true,
            'message' => $recognitionError ?: ($shouldRecognize
                ? 'Photo read by Avi. Confirm the numbers to save it in Log Card Photos.'
                : 'Log Card photo saved.'),
            'recognition' => $recognition,
            'recognition_error' => $recognitionError,
            'photo' => $media ? collect($photos)->firstWhere('id', (int) $media->id) : null,
            'photos' => $photos,
            'photo_count' => $workorder->getMedia('logs')->count(),
            'pending_confirmation' => $shouldRecognize,
        ]);
    }

    private function discardNormalizedTemporaryFile(\Illuminate\Http\UploadedFile $original, \Illuminate\Http\UploadedFile $normalized): void
    {
        if ($original->getPathname() === $normalized->getPathname()) {
            return;
        }

        $normalizedPath = $normalized->getPathname();
        $temporaryRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (str_starts_with($normalizedPath, $temporaryRoot) && is_file($normalizedPath)) {
            @unlink($normalizedPath);
        }
    }

    /** @return list<array{id: int, big_url: string, thumb_url: string, alt: string}> */
    private function photoPayloads(Workorder $workorder): array
    {
        return $workorder->getMedia('logs')->map(fn ($media): array => [
            'id' => (int) $media->id,
            'big_url' => route('image.show.big', [
                'mediaId' => $media->id,
                'modelId' => $workorder->id,
                'mediaName' => 'logs',
            ]),
            'thumb_url' => route('image.show.thumb', [
                'mediaId' => $media->id,
                'modelId' => $workorder->id,
                'mediaName' => 'logs',
            ]),
            'alt' => (string) ($media->name ?: 'Log Card photo'),
        ])->values()->all();
    }
}
