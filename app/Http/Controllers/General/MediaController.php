<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{


    protected function store_avatar(Request $request, $id)
    {
        $user = User::find($id);
        $file = $request->File('avatar');

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($file)->toMediaCollection('avatar');
        }

        return redirect()->route('cabinet.profile');
    }

    public function store_photo_workorders(Request $request, $id)
    {
        $workorder = Workorder::findOrFail($id);
        $category = $request->query('category', 'photos');

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                // Формируем уникальное читаемое имя файла
                $filename = 'wo_' . $workorder->number . '_' . now()->format('Ymd_Hi') . '_' . Str::random(3) . '.' . $photo->getClientOriginalExtension();

                $workorder->addMedia($photo)
                    ->usingFileName($filename)
                    ->toMediaCollection($category);
            }
        }

        // Формируем список загруженных файлов для фронта
        $uploadedPhotos = [];
        foreach ($workorder->getMedia($category) as $media) {
            if (!$media->id) continue;

            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category
                ]),
                'alt' => $media->name ?? 'Photo',
            ];
        }

        // Ответ для JS
        return response()->json([
            'success' => true,
            'photos' => $uploadedPhotos,
            'photo_count' => count($uploadedPhotos),
        ]);
    }

    public function delete_photo($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();

        return response()->json(['success' => true]);
    }

    protected function mobile_store_avatar(Request $request, $id)
    {
        $user = User::find($id);
        $file = $request->File('avatar');
        $size = $request->File('avatar')->getSize();

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($file)->toMediaCollection('avatar');
        }

        return redirect()->route('mobile.profile');
    }

    public function showThumb($mediaId, $modelId, $mediaName)
    {

        $media = Media::find($mediaId);
        $thumbPath = $media->getPath('thumb');


        return response()->file($thumbPath);
    }

    public function showBig($mediaId, $modelId, $mediaName)
    {

        $mediaItem = Media::findOrFail($mediaId);

        return response()->file($mediaItem->getPath());
    }

    public function get_photos($id, Request $request)
    {
        $category = $request->query('category', 'photos'); // по умолчанию photos
        $workorder = Workorder::findOrFail($id);

        $uploadedPhotos = [];

        foreach ($workorder->getMedia($category) as $media) {
            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category,
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => $category,
                ]),
                'alt' => $media->name ?? 'Photo',
            ];
        }

        return response()->json([
            'success' => true,
            'photos' => $uploadedPhotos,
            'photo_count' => count($uploadedPhotos),
        ]);
    }
}
