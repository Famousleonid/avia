<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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

        $model = User::find($modelId);
        $media = $model->getMedia($mediaName)->where('id', $mediaId)->first();

        return response()->file($media->getPath('thumb'));
    }

    public function showBig($mediaId, $modelId, $mediaName)
    {

        $mediaItem = Media::findOrFail($mediaId);

        return response()->file($mediaItem->getPath());
    }
}
