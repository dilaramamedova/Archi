<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\City;
use App\Http\Controllers\Controller;
use App\Support\SpecialistDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpecialistProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        // A master already assigned to a specialty the admin later deactivated
        // must still be able to save the rest of the form without being forced
        // (or silently pushed) onto a different trade.
        $currentSpecialtyId = $request->user()->specialistProfile?->specialist_specialty_id;

        // Briqadir / Makler details are validated against the kind of the specialty being
        // SAVED, not the one on file — switching kind in the same request is allowed.
        $kind = SpecialistDetails::kindOfSpecialtyId($request->input('specialist_specialty_id'));

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'specialist_specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialist_specialties', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                        ->orWhere('id', $currentSpecialtyId),
                ),
            ],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:99'],
            'city' => ['nullable', Rule::in(City::acceptedValues())],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'about' => ['nullable', 'string', 'max:400'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:32'],
            'details' => ['nullable', 'array'],
            ...SpecialistDetails::rules($kind, 'details.', $request->exists('details')),
        ], SpecialistDetails::messages($kind), SpecialistDetails::attributes($kind));

        DB::transaction(function () use ($request, $kind): void {
            $user = $request->user();
            $user->update([
                ...$request->only(['first_name', 'last_name']),
                'name' => trim($request->string('first_name').' '.$request->string('last_name')),
            ]);

            $attributes = [
                ...$request->only([
                    'specialist_specialty_id', 'experience_years', 'phone', 'whatsapp', 'about', 'skills',
                ]),
                // Store the canonical Azerbaijani label whatever form was submitted.
                'city' => City::canonical($request->input('city')),
            ];

            // Only the keys of the saved kind are stored. A request without `details`
            // leaves a foreman's crew alone; a change to a kind-less trade clears it.
            if ($kind === null) {
                $attributes['details'] = null;
            } elseif ($request->exists('details')) {
                $attributes['details'] = SpecialistDetails::normalize($kind, $request->input('details'));
            }

            $user->specialistProfile()->updateOrCreate([], $attributes);
        });

        return response()->json([
            'success' => true,
            'message' => t('specialist-cabinet.save.saved'),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $profile = $request->user()->specialistProfile()->firstOrCreate();
        $this->deleteUploadedAvatar($profile->avatar_path);
        $path = $validated['avatar']->store('specialists/avatars', 'public');
        $profile->update(['avatar_path' => $path]);

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => storage_url($path),
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $profile = $request->user()->specialistProfile;

        if ($profile) {
            $this->deleteUploadedAvatar($profile->avatar_path);
            $profile->update(['avatar_path' => null]);
        }

        return response()->json(['success' => true]);
    }

    private function deleteUploadedAvatar(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'assets/') && ! str_starts_with($path, '/assets/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
