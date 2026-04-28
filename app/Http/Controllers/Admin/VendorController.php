<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VendorController extends Controller
{
    /**
     * Store a newly created vendor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:vendors,name'
            ]);

            $vendor = Vendor::create([
                'name' => $request->input('name')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor created successfully.',
                'vendor' => $vendor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating vendor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Vendor $vendor): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'is_trusted' => (bool) $vendor->is_trusted,
                'description' => (string) ($vendor->description ?? ''),
                'media' => $this->serializeMedia($vendor),
            ],
        ]);
    }

    public function updateMeta(Request $request, Vendor $vendor): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:vendors,name,' . $vendor->id],
            'is_trusted' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $vendor->update(array_filter([
            'name' => array_key_exists('name', $data) ? $data['name'] : null,
            'is_trusted' => (bool) $data['is_trusted'],
            'description' => $data['description'] ?: null,
        ], static fn ($value, $key) => $key !== 'name' || $value !== null, ARRAY_FILTER_USE_BOTH));

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'is_trusted' => (bool) $vendor->is_trusted,
                'description' => (string) ($vendor->description ?? ''),
                'media_count' => $vendor->getMedia('vendor')->count(),
            ],
        ]);
    }

    public function uploadMedia(Request $request, Vendor $vendor): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $request->validate([
            'files' => ['required'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:15360'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $filename = 'vendor_' . $vendor->id . '_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.' . $extension;

            $vendor->addMedia($file)
                ->usingFileName($filename)
                ->toMediaCollection('vendor');
        }

        return response()->json([
            'success' => true,
            'media' => $this->serializeMedia($vendor->fresh()),
        ]);
    }

    public function showMedia(Vendor $vendor, Media $media)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);
        abort_unless(
            $media->model_type === $vendor->getMorphClass()
            && (int) $media->model_id === (int) $vendor->id
            && $media->collection_name === 'vendor',
            404
        );

        $path = $media->getPath();
        abort_unless($path && file_exists($path), 404, 'Media file not found');

        return response()->file($path);
    }

    public function destroyMedia(Vendor $vendor, Media $media): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);
        abort_unless(
            $media->model_type === $vendor->getMorphClass()
            && (int) $media->model_id === (int) $vendor->id
            && $media->collection_name === 'vendor',
            404
        );

        $media->delete();

        return response()->json([
            'success' => true,
            'media' => $this->serializeMedia($vendor->fresh()),
        ]);
    }

    protected function serializeMedia(Vendor $vendor): array
    {
        return $vendor->getMedia('vendor')
            ->map(function (Media $media) use ($vendor) {
                return [
                    'id' => $media->id,
                    'name' => $media->name ?: $media->file_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
                    'thumb_url' => str_starts_with((string) $media->mime_type, 'image/')
                        ? route('image.show.thumb', ['mediaId' => $media->id, 'modelId' => $vendor->id, 'mediaName' => 'vendor'])
                        : null,
                    'view_url' => route('vendors.media.show', [$vendor, $media]),
                    'created_at' => optional($media->created_at)->format('Y-m-d H:i:s'),
                    'size' => (int) $media->size,
                ];
            })
            ->values()
            ->all();
    }
}
