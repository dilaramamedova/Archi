<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SecurityController extends Controller
{
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'errors' => ['current_password' => [__('security.errors.wrong_password')]],
            ], 422);
        }

        $user->update(['password' => $request->input('password')]);

        return response()->json([
            'success' => true,
            'message' => __('security.password_changed'),
        ]);
    }

    public function deactivateAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'errors' => ['password' => [__('security.errors.wrong_password')]],
            ], 422);
        }

        $user->update(['status' => UserStatus::Blocked]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'redirect' => route('home'),
        ]);
    }
}
