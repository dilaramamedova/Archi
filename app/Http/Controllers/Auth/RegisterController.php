<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if (is_numeric($request->input('specialist_specialty_id'))) {
            $request->merge(['specialist_specialty_id' => (int) $request->input('specialist_specialty_id')]);
        }

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
            'specialist_specialty_id' => [
                'nullable',
                'required_if:role,master',
                'integer',
                Rule::exists('specialist_specialties', 'id')->where('is_active', true),
            ],
            'city' => ['nullable', 'required_if:role,master', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'status' => UserStatus::Pending,
                'terms_accepted' => true,
            ]);

            if ($user->role === UserRole::Seller) {
                $user->sellerProfile()->create([
                    'brand_name' => $validated['company_name'],
                ]);
            }

            if ($user->role === UserRole::Master) {
                $specialty = SpecialistSpecialty::query()->where('is_active', true)
                    ->findOrFail($validated['specialist_specialty_id']);
                $user->specialistProfile()->create([
                    'specialist_specialty_id' => $specialty->id,
                    'craft' => $specialty->name,
                    'city' => $validated['city'],
                ]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => t('register.pending_message'),
            ], 201);
        }

        return redirect()->route('login')->with('registered', true);
    }
}
