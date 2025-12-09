<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function index()
    {
        $workorders = Workorder::all();

        return view('mobile.pages.index', compact('workorders'));
    }

    public function show_wo(Request $request)
    {
        $workorder = Workorder::find($request->wo_id);

        $photos = $workorder->getMedia('photos');

        // dd($workorder->id, $photos);

        return view('mobile.pages.mobile_workorder', compact('photos', 'workorder'));
    }

    public function create($wo_id)
    {
        $workorder = $wo_id;

        return view('mobile.pages.create', compact('workorder'));
    }

    public function store(Request $request)
    {
        dd($request);
    }

}
