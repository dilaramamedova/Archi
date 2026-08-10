<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = (string) $request->input('email');

        // Admins authenticate through the Filament panel, never through this flow.
        $isResettable = User::query()
            ->where('email', $email)
            ->where('role', '!=', UserRole::Admin)
            ->exists();

        if ($isResettable) {
            Password::sendResetLink(['email' => $email]);
        }

        // Always the same answer, whether or not the address is registered.
        $message = t('login.forgot_neutral');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
