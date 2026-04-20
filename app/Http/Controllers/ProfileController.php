<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $teams = Team::query()->orderBy('name')->get();

        if ($request->routeIs('mobile.*')) {
            return view('mobile.pages.profile', compact('user', 'teams'));
        }

        return view('profile.edit', compact('user', 'teams'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'string'],
            'stamp' => ['required', 'string', 'max:255'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $birthday = $this->parseBirthday($validated['birthday'] ?? null);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'phone' => $this->removeSpaces($validated['phone'] ?? null),
            'birthday' => $birthday,
            'stamp' => $validated['stamp'],
            'team_id' => $validated['team_id'],
        ]);

        if ($request->hasFile('file')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('file'))->toMediaCollection('avatar');
        }

        return redirect()
            ->route($request->routeIs('mobile.*') ? 'mobile.profile' : 'profile.edit')
            ->with('success', 'Changes saved');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_pass' => ['required'],
            'password' => ['required', 'confirmed', 'min:' . config('security.user_password_min')],
        ]);

        $user = $request->user();

        if (! Hash::check($request->old_pass, $user->password)) {
            return redirect()->back()->with('error', 'The current password is incorrect');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->back()->with('success', 'New password saved');
    }

    private function parseBirthday(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                $date = Carbon::createFromFormat('Y-m-d', $raw);
            } elseif (preg_match('/^\d{2}\.[a-z]{3}\.\d{4}$/i', $raw)) {
                $normalized = preg_replace_callback(
                    '/\.(\w{3})\./',
                    static fn (array $m): string => '.' . ucfirst(strtolower((string) $m[1])) . '.',
                    $raw
                );
                $date = Carbon::createFromFormat('d.M.Y', (string) $normalized);
            } else {
                throw ValidationException::withMessages([
                    'birthday' => 'Birthday format must be YYYY-MM-DD or dd.mmm.yyyy.',
                ]);
            }
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'birthday' => 'Invalid birthday date.',
            ]);
        }

        if ($date->startOfDay()->gt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'birthday' => 'Birthday cannot be later than today.',
            ]);
        }

        return $date->format('Y-m-d');
    }

    private function removeSpaces(?string $value): ?string
    {
        return $value === null ? null : str_replace(' ', '', $value);
    }
}
