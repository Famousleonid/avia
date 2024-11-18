<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileController extends Controller
{
    public function index()
    {
        // $workorders = Workorder::all();

        return view('mobile.pages.index');
    }

    public function profile()
    {
        $user = Auth::user();
        $avatar = $user->getMedia('avatar')->first();

        return view('mobile.pages.profile', compact('user', 'avatar'));
    }

    public function materials()
    {
        $user = Auth::user();
        $materials = Material::all();

        return view('mobile.pages.materials', compact('user', 'materials'));
    }

    public function show_wo(Request $request)
    {
        $workorder = Workorder::find($request->wo_id);
        $photos = $workorder->getMedia('photos');

        return view('mobile.pages.photos', compact('photos', 'workorder'));
    }

    public function create($wo_id)
    {
        $workorder = $wo_id;

        return view('mobile.pages.create', compact('workorder'));
    }

    public function store(Request $request)
    {

        $imageData = $request->input('image');
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);

        $workorder = Workorder::find($request->workorder);

        $workorder->addMediaFromBase64($imageData)
            ->usingFileName(str($workorder->number) . time() . '.jpg')
            ->toMediaCollection('photos');

        $photos = $workorder->getMedia('photos');

        return view('mobile.pages.photos', compact('photos', 'workorder'));

    }

    public function photoShowThumb($mediaId, $modelId, $mediaName)
    {
        $model = Workorder::find($modelId);
        $media = $model->getMedia($mediaName)->where('id', $mediaId)->first();

        return response()->file($media->getPath('thumb'));
    }
}
