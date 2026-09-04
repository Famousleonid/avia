<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Ai\NameplateRecognitionService;
use App\Services\Media\MobileLandscapePhotoProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShippingDraftRecognitionController extends Controller
{
    public function __construct(
        private readonly MobileLandscapePhotoProcessor $landscapePhotoProcessor,
        private readonly NameplateRecognitionService $recognitionService,
    ) {
    }

    public function recognize(Request $request): JsonResponse
    {
        abort_unless($request->user()?->roleIs(['Shipping', 'Manager', 'Admin']), 403);

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $photo = $this->landscapePhotoProcessor->prepare($request->file('photo'), 'photo');

        try {
            $recognition = $this->recognitionService->recognize(
                $photo->getRealPath(),
                (string) $photo->getMimeType(),
            );
        } catch (Throwable $e) {
            Log::warning('Shipping Draft nameplate recognition failed', [
                'user_id' => $request->user()?->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Avi could not read the nameplate. The photo can still be saved with the Draft.',
            ], 503);
        } finally {
            $this->landscapePhotoProcessor->discardTemporaryFile($request->file('photo'), $photo);
        }

        return response()->json([
            'success' => true,
            'recognition' => $recognition,
        ]);
    }
}
