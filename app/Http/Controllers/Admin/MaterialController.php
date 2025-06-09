<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {

        $materials = Material::All();

        return view('admin.materials.index', compact('materials'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'material' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Material::create($validated);

        return redirect()->route('materials.index')->with('success', 'Material created successfully.');
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
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'material' => 'required|string|max:255',
            'specification' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $material->update($validated);

        return redirect()->route('materials.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material deleted successfully.');
    }


}
