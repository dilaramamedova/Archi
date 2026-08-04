<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = $request->input('identifier');
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $identifier)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => [__('login.errors.invalid_credentials')],
            ]);
        }

        if ($user->status === UserStatus::Pending) {
            throw ValidationException::withMessages([
                'identifier' => [__('login.errors.pending_approval')],
            ]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'identifier' => [__('login.errors.rejected')],
            ]);
        }

        if ($user->status === UserStatus::Blocked) {
            throw ValidationException::withMessages([
                'identifier' => [__('login.errors.blocked')],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $this->redirectTo($user),
            ]);
        }

        return redirect()->intended($this->redirectTo($user));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('home');
    }

    private function redirectTo(User $user): string
    {
        return match ($user->role) {
            \App\Enums\UserRole::Seller => route('business.profile'),
            \App\Enums\UserRole::Master => route('specialist.cabinet'),
            default => route('home'),
        };
    }
}
