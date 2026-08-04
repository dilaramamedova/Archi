<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'role' => ['required', 'in:buyer,seller,master'],
            'terms' => ['accepted'],
            // Seller-only
            'company_name' => ['nullable', 'required_if:role,seller', 'string', 'max:255'],
            // Master-only
            'specialization' => ['nullable', 'required_if:role,master', 'string', 'max:255'],
            'city' => ['nullable', 'required_if:role,master', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => UserStatus::Pending,
            'terms_accepted' => true,
        ]);

        if ($user->role === UserRole::Seller && ! empty($validated['company_name'])) {
            $user->sellerProfile()->create([
                'brand_name' => $validated['company_name'],
            ]);
        }

        if ($user->role === UserRole::Master) {
            $user->specialistProfile()->create([
                'craft' => $validated['specialization'] ?? null,
                'city' => $validated['city'] ?? null,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('register.pending_message'),
            ], 201);
        }

        return redirect()->route('login')->with('registered', true);
    }
}
