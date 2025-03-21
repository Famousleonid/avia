<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index()
    {
        $users = User::all();
        $id = Auth::user()->getAuthIdentifier();

        if (!Auth::check())
            return redirect()->back()->with('status', 'Not authenticated');

        $user = User::find($id);

        return View('cabinet.users.index', compact('users', ));

    }

    public function edit($id)
    {
        $user = User::find($id);
        $avatar = $user->getMedia('avatar')->first(); // ->getUrl('thumb');
        $teams = Team::all();

        if (!$avatar) {
            $avatar = 0;
        }

        return view('admin.users.edit', compact('user', 'avatar', 'teams'));
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        $request->phone = $this->removeSpace($request->phone);
        $request->validate([
            'name' => 'required',
            'phone' => '',      // |size:10
            'stamp' => 'required',
            'team_id' => 'required',
        ]);

        ($request->has('is_admin')) ? $request->request->add(['is_admin' => 1]) : $request->request->add(['is_admin' => 0]);

        $user->update($request->all());

        return redirect()->back()->with('success', 'Changes saved');

    }

    public function destroy($id)
    {
        try {
            $answer = User::destroy($id);
        } catch (\Exception $e) {
            return back()->withErrors('The databases are linked. First remove...');
        }

        return redirect()->route('users.index')->with('success', 'User deleted');
    }

    public function removeSpace($var)
    {
        return str_replace(' ', '', $var);
    }

    public function changePassword(Request $request, $id)
    {
        $this->validate($request, [
            'password' => 'required|confirmed|min:3',
        ]);

        $user = User::findOrFail($id);
        if (Hash::check($request->old_pass, $user->password)) {
            $user->fill(['password' => Hash::make($request->password)])->save();
            return redirect()->back()->with('success', 'New password saved');
        } else {
            return redirect()->back()->with('error', 'The current password is incorrect');
        }


    }

}
