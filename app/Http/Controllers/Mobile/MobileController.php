<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\Material;
use App\Models\Team;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MobileController extends Controller
{
    public function index()
    {
        $workorders = Workorder::with(['unit', 'media'])->get();

        return view('mobile.pages.index',compact('workorders'));
    }

    public function profile()
    {
        $user = Auth::user();
        $teams = Team::all();

        return view('mobile.pages.profile', compact('user','teams'));
    }

    public function update_profile(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'stamp' => 'required',
            'team_id' => 'required|exists:teams,id',
            'file' => 'nullable|image',
        ]);

        $user->update($request->only(['name', 'phone', 'stamp', 'team_id']));

        if ($request->hasFile('file')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('file'))->toMediaCollection('avatar');
        }

        return redirect()->route('mobile.profile')->with('success', 'Changes saved');
    }

    public function materials()
    {
        $user = Auth::user();
        $materials = Material::all();

        return view('mobile.pages.materials', compact('user', 'materials'));
    }

    public function updateMaterialDescription(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $material->description = $request->input('description', '');
        $material->save();

        return response()->json(['success' => true]);
    }

    public function components()
    {
        $components = Component::all();
        $workorders = Workorder::all();

        return view('mobile.pages.components', compact('workorders', 'components'));
    }


        public function componentStore(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'ipl_num' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'photo' => 'image|max:5120',
            'name' => 'required|string|max:255',
        ]);

        $workorder = Workorder::with('unit')->findOrFail($validated['workorder_id']);
        $manualId = optional($workorder->unit)->manual_id;

        if (!$manualId) {
            return redirect()->back()->withErrors(['manual' => 'Manual not found for selected workorder.']);
        }

        $component = new Component();
        $component->manual_id = $manualId;
        $component->ipl_num = $validated['ipl_num'];
        $component->part_number = $validated['part_number'];
        $component->name = $validated['name'];
        $component->save();

        if ($request->hasFile('photo')) {
            $component->addMediaFromRequest('photo')->toMediaCollection('components');
        }

        return redirect()->back()->with('success', 'Component added successfully.');
    }


   public function changePassword(Request $request, $id)
   {
       $request->validate([
           'old_pass' => 'required',
           'password' => 'required|confirmed|min:3',
       ]);

       $user = User::findOrFail($id);

       if (!Hash::check($request->old_pass, $user->password)) {
           return redirect()->back()->with('error', 'The current password is incorrect');
       }

       $user->password = Hash::make($request->password);
       $user->save();

       return redirect()->back()->with('success', 'New password saved');
   }


}
