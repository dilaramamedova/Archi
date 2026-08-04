<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function sessions(Request $request)
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'ip_address' => $s->ip_address,
                'user_agent' => $this->parseUserAgent($s->user_agent),
                'last_activity' => \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                'is_current' => $s->id === $currentSessionId,
            ]);

        return response()->json($sessions);
    }

    public function destroySession(Request $request)
    {
        $request->validate(['session_id' => ['required', 'string']]);

        $currentSessionId = $request->session()->getId();
        $sessionId = $request->input('session_id');

        if ($sessionId === $currentSessionId) {
            return response()->json(['error' => __('security.errors.cannot_logout_current')], 422);
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['success' => true]);
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

        $user->update(['status' => \App\Enums\UserStatus::Blocked]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'redirect' => route('home'),
        ]);
    }

    private function parseUserAgent(?string $ua): string
    {
        if (! $ua) {
            return 'Unknown';
        }

        $browser = 'Browser';
        $os = 'Unknown OS';

        if (str_contains($ua, 'Chrome') && ! str_contains($ua, 'Edg')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Edg')) {
            $browser = 'Edge';
        }

        if (str_contains($ua, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($ua, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $os = 'iOS';
        }

        return "$browser — $os";
    }
}
