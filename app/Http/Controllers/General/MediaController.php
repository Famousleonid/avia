<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $media = $workorder->addMedia($photo)->toMediaCollection('photos');
                Log::channel('avia')->info("Added media ID: {$media->id} for workorder {$id}");
            }
        }

        $uploadedPhotos = [];
        foreach ($workorder->getMedia('photos') as $media) {
            if (!$media->id) {
                Log::channel('avia')->error("Media ID is missing for workorder {$id}", ['media' => $media]);
                continue;
            }
            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => 'photos'
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => 'photos'
                ]),
                'alt' => $media->name ?? 'Photo',
            ];
        }

        Log::channel('avia')->info("Photos in store_photo_workorders:", ['photos' => $uploadedPhotos]);
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

    public function get_photos($id)
    {
        $workorder = Workorder::findOrFail($id);
        $uploadedPhotos = [];

        foreach ($workorder->getMedia('photos') as $media) {
            Log::channel('avia')->info("Media ID for workorder {$workorder->id}: {$media->id}");
            $uploadedPhotos[] = [
                'id' => $media->id,
                'big_url' => route('image.show.big', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => 'photos'
                ]),
                'thumb_url' => route('image.show.thumb', [
                    'mediaId' => $media->id,
                    'modelId' => $workorder->id,
                    'mediaName' => 'photos'
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
