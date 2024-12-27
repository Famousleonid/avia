<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::all();

        return View('cabinet.materials.index', compact('materials'));

    }

    public function create()
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, Material $material)
    {
//        Log::channel('avia')->info('Material ID:', ['id' => $material->id]);
//        Log::channel('avia')->info('Update Request:', ['data' => $request->all()]);

        $data = $request->validate([
            'description' => 'nullable|max:250',
        ]);

        $material->update($data);

        return response()->json(['success' => true, 'message' => 'Description updated successfully!']);
    }

    public function destroy($id)
    {
        //
    }
}
