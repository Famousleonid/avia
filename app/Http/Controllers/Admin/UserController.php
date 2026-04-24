<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:users.viewAny')->only('index');
        $this->middleware('can:users.view')->only('show');
    }

    public function index()
    {
        $users = User::all();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $roles = Role::all();
        $teams = Team::all();

        return view('admin.users.create', compact('roles', 'teams'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:155', 'unique:users,email'],
            'password' => ['required', 'string', 'min:' . config('security.user_password_min')],
            'birthday' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'stamp' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $data = $validated;
        $data['phone'] = $this->removeSpace($validated['phone'] ?? null);
        $data['password'] = Hash::make($validated['password']);

        $user = User::create($data);
        $user->is_admin = $request->boolean('is_admin');
        $user->email_verified_at = $request->has('email_verified_at') ? now() : null;
        $user->save();

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
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $teams = Team::all();
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'teams', 'roles'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'stamp' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'password' => ['nullable', 'string', 'min:' . config('security.user_password_min'), 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'img' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sign' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $validated['phone'] = $this->removeSpace($validated['phone'] ?? null);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_admin'] = $request->boolean('is_admin');
        $validated['email_verified_at'] = $request->has('email_verified_at') ? now() : null;

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
        abort_unless(auth()->user()?->isSystemAdmin(), 403);
        abort_if((int) auth()->id() === (int) $id, 403, 'You cannot delete yourself.');

        try {
            User::destroy($id);
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
        abort_unless(auth()->user()?->isSystemAdmin(), 403);

        $this->validate($request, [
            'password' => 'required|confirmed|min:' . config('security.user_password_min'),
        ]);

        $user = User::findOrFail($id);

        if (Hash::check($request->old_pass, $user->password)) {
            $user->fill(['password' => Hash::make($request->password)])->save();

            return redirect()->back()->with('success', 'New password saved');
        }

        return redirect()->back()->with('error', 'The current password is incorrect');
    }
}
