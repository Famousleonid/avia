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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:155', 'unique:users,email'],
            'password' => ['required', 'string', 'min:3'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $data = $validated;

        $data['password'] = Hash::make($validated['password']);

        $user = User::create($data);

        if (auth()->user()?->roleIs('Admin')) {

            $user->is_admin = $request->has('is_admin') ? 1 : 0;

            if ($request->filled('role_id')) {
                $user->role_id = $request->input('role_id');
            }

            if ($request->filled('team_id')) {
                $user->team_id = $request->input('team_id');
            }

            $user->email_verified_at = $request->has('email_verified_at') ? now() : null;

            $user->save();
        }

        if ($request->hasFile('img')) {

            $request->validate([
                'img' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4048'],
            ]);

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

        // 1. Доступ:
        // Admin может редактировать любого пользователя,
        // остальные — только свой профиль
        if (! $auth->roleIs('Admin') && $auth->id !== $user->id) {
            abort(403, 'You do not have permission to edit this user.');
        }

        $isAdmin = $auth->roleIs('Admin');

        // 2. Базовые правила валидации для всех
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'stamp' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'password' => ['nullable', 'string', 'min:3', 'confirmed'],
            'img' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sign' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ];

        if ($isAdmin) {
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email,' . $user->id];
            $rules['role_id'] = ['required', 'integer', 'exists:roles,id'];
            $rules['team_id'] = ['required', 'integer', 'exists:teams,id'];
        }

        $validated = $request->validate($rules);
        $validated['phone'] = $this->removeSpace($validated['phone'] ?? null);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (! $isAdmin) {
            unset($validated['email'], $validated['role_id'], $validated['team_id'], $validated['img'], $validated['sign']);
        } else {
            $validated['is_admin'] = $request->boolean('is_admin');

            $validated['email_verified_at'] = $request->has('email_verified_at')
                ? now()
                : null;
        }

        unset($validated['img'], $validated['sign']);

        $user->update($validated);

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

        return redirect()
            ->route('users.index')
            ->with('success', 'Changes saved');
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
