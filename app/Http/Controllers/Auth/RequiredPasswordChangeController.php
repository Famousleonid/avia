<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\Auth\UserPasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequiredPasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        return view('auth.passwords.required', [
            'user' => $request->user(),
        ]);
    }

    public function update(ChangePasswordRequest $request, UserPasswordService $passwords)
    {
        $passwords->changeUsingCurrentPassword(
            $request->user(),
            (string) $request->validated('old_pass'),
            (string) $request->validated('password')
        );

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Password changed. Sign in with your new password.');
    }
}
