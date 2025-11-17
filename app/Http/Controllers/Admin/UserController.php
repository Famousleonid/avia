<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {

        $users = User::all();

        return View('admin.users.index', compact('users', ));

    }

    public function create()
    {
        $roles = Role::all();
        $teams = Team::all();

        return View('admin.users.create', compact('roles', 'teams'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:155', 'unique:users'],
            'password' => ['required', 'string', 'min:3'],
        ]);

        $data = $request->all();
        $data['is_admin'] = isset($request->is_admin) ? 1 : 0;
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

        if ($request->hasFile('img')) {
            $user->addMedia($request->file('img'))->toMediaCollection('avatar');
        }

        $user->sendEmailVerificationNotification();

        return redirect()->route('users.index')->with('success', 'Пользователь добавлен');
    }

    public function edit(User $user)
    {

        $teams = Team::all();
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'teams','roles'));
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

        if ($request->hasFile('img')) {
            if ($user->getMedia('avatar')->isNotEmpty()) {
                $user->getMedia('avatar')->first()->delete();
            }
            $user->addMedia($request->file('img'))->toMediaCollection('avatar');
        }
        if ($request->hasFile('sign')) {
            if ($user->getMedia('sign')->isNotEmpty()) {
                $user->getMedia('sign')->first()->delete();
            }
            $user->addMedia($request->file('sign'))->toMediaCollection('sign');
        }

        $user->update($request->all());

        return redirect()->route('users.index')->with('success', 'Changes saved');

    }

    public function destroy($id)
    {
        try {
            $answer = User::destroy($id);
        } catch (\Exception $e) {
            return back()->withErrors('The databases are linked. First remove...');
        }

        return redirect()->route('users.index')->with('success', 'Technik deleted');
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
