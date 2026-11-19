<?php

namespace App\Http\Controllers\Auth;

use App\Enums\City;
use App\Enums\SpecialistCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if (is_numeric($request->input('specialist_specialty_id'))) {
            $request->merge(['specialist_specialty_id' => (int) $request->input('specialist_specialty_id')]);
        }

        // The Briqadir / Makler cards are specialists: register.js posts role=master +
        // kind. A client that posts the card name as the role (?role=foreman forms,
        // older JS) is folded into the same shape rather than rejected.
        if (in_array($request->input('role'), SpecialistCategory::ownKindValues(), true)) {
            $request->merge(['kind' => $request->input('role'), 'role' => UserRole::Master->value]);
        }

        $kind = $request->input('role') === UserRole::Master->value && is_string($request->input('kind'))
            ? SpecialistCategory::tryFrom($request->input('kind'))
            : null;
        $kind = $kind?->hasOwnKind() ? $kind : null;

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
            // Master-only. A Briqadir / Makler sends `kind` instead of a specialty.
            'kind' => ['nullable', 'string', Rule::in(SpecialistCategory::ownKindValues())],
            'specialist_specialty_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $request->input('role') === UserRole::Master->value && $kind === null),
                'integer',
                Rule::exists('specialist_specialties', 'id')->where('is_active', true),
            ],
            // Accepts either a canonical slug (register select) or an Azerbaijani label (cabinet select).
            'city' => ['nullable', 'required_if:role,master', 'string', Rule::in(City::acceptedValues())],
        ], [
            // The generic required_if text quoted the raw role value («Hesab növü master
            // olduqda…»), which is meaningless to a Makler who never saw that word.
            'city.required_if' => t('register.form.city_required'),
            'specialist_specialty_id.required' => t('register.form.specialty_required'),
        ]);

        // The kind's specialty is the single active row of that category (seeded by
        // 2026_11_19_120100). If an admin deactivated it, the card is closed — say so
        // instead of silently registering the visitor as something else.
        $kindSpecialty = $kind !== null ? SpecialistSpecialty::forKind($kind) : null;

        if ($kind !== null && $kindSpecialty === null) {
            throw ValidationException::withMessages(['kind' => t('register.form.kind_unavailable')]);
        }

        if ($kindSpecialty !== null) {
            $validated['specialist_specialty_id'] = $kindSpecialty->id;
        }

        $role = UserRole::from($validated['role']);
        // No account-level vetting at sign-up: every role is active immediately and can
        // sign in. Moderation moved to what they publish — a product is created with
        // `is_approved = false` and a portfolio item with `PortfolioStatus::Pending`, so
        // nothing a seller or master posts is public before an admin approves it. An admin
        // can still move an existing account to pending/rejected/blocked from the panel.
        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'status' => UserStatus::Active,
                'approved_at' => now(),
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
                    'craft' => $specialty->getTranslation('name', 'az', false),
                    'city' => City::canonical($validated['city']),
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $this->redirectTo($role);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => t('register.active_message'),
                'redirect' => $redirect,
            ], 201);
        }

        return redirect()->to($redirect)->with('success', t('register.active_message'));
    }

    /**
     * Same landing page the user would get by signing in (see LoginController).
     */
    private function redirectTo(UserRole $role): string
    {
        return match ($role) {
            UserRole::Seller => route('business.profile'),
            UserRole::Master => route('specialist.cabinet'),
            default => route('home'),
        };
    }
}
