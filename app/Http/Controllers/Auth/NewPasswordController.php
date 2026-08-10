<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request): void {
                // Runs only once the token has been validated, so the status
                // checks below cannot be used to probe arbitrary accounts.
                $this->assertCanSignIn($user);

                $user->forceFill([
                    'password' => $request->string('password')->value(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Auth::login($user);
                $request->session()->regenerate();
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [t('auth.reset_invalid_token')],
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => t('auth.reset_success'),
                'redirect' => $this->redirectTo(Auth::user()),
            ]);
        }

        return redirect()->to($this->redirectTo(Auth::user()));
    }

    /**
     * Mirrors the LoginController status gate — a non-active account must never
     * end up with a session, even with a valid reset token.
     */
    private function assertCanSignIn(User $user): void
    {
        $message = match (true) {
            $user->role === UserRole::Admin => t('login.errors.invalid_credentials'),
            $user->status === UserStatus::Pending => t('login.errors.pending_approval'),
            $user->status === UserStatus::Rejected => t('login.errors.rejected'),
            $user->status === UserStatus::Blocked => t('login.errors.blocked'),
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['email' => [$message]]);
        }
    }

    private function redirectTo(?User $user): string
    {
        return match ($user?->role) {
            UserRole::Seller => route('business.profile'),
            UserRole::Master => route('specialist.cabinet'),
            default => route('home'),
        };
    }
}
