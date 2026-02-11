<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:users.viewAny')->only('index');
        $this->middleware('can:users.view')->only('show');
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.update')->only(['edit', 'update']);
        $this->middleware('can:users.delete')->only('destroy');
    }

    public function index()
    {
        $user = Auth::user();
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

        try {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('users.index')
                ->with('success', 'The user has been added. A confirmation email has been sent.');
        } catch (TransportExceptionInterface $e) {

            return redirect()
                ->route('users.index')
                ->with('warning', 'The user was added, but the email was NOT sent (SMTP is not configured locally).');
        } catch (\Throwable $e) {

            return redirect()
                ->route('users.index')
                ->with('warning', 'The user was added, but the letter was NOT sent (sending error).');
        }

        return redirect()->route('users.index')->with('success', 'Technik was added successfully');
    }

    public function edit(User $user)
    {
        $auth = auth()->user();

        if (! $auth->roleIs('Admin') && $auth->id !== $user->id) {
            abort(403, 'You do not have permission to edit this user.');
        }
        $teams = Team::all();
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'teams','roles'));
    }

    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $user = User::findOrFail($id);

        // доступ: Admin -> всех, остальные -> только себя
        if (! $auth->roleIs('Admin') && $auth->id !== $user->id) {
            abort(403, 'You do not have permission to edit this user.');
        }

        // нормализуем phone (лучше сделать $phone отдельно)
        $phone = $this->removeSpace($request->input('phone'));

        // базовые правила (email добавим отдельно для Admin)
        $rules = [
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'stamp'   => 'required|string|max:255',
            'team_id' => 'required|integer|exists:teams,id',
        ];

        // email менять может только Admin
        if ($auth->roleIs('Admin')) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $user->id;
        }

        $validated = $request->validate($rules);
        $validated['phone'] = $phone;

        // is_admin может менять только Admin (и то если поле есть в форме)
        if ($auth->roleIs('Admin')) {
            $validated['is_admin'] = $request->has('is_admin') ? 1 : 0;
        }

        // upload avatar
        if ($request->hasFile('img')) {
            if ($user->getMedia('avatar')->isNotEmpty()) {
                $user->getMedia('avatar')->first()->delete();
            }
            $user->addMedia($request->file('img'))->toMediaCollection('avatar');
        }

        // upload sign
        if ($request->hasFile('sign')) {
            if ($user->getMedia('sign')->isNotEmpty()) {
                $user->getMedia('sign')->first()->delete();
            }
            $user->addMedia($request->file('sign'))->toMediaCollection('sign');
        }

        // обновляем только разрешённые поля
        $user->update($validated);

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
