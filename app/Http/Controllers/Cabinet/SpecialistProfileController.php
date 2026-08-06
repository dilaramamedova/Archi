<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SpecialistProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'specialist_specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialist_specialties', 'id')->where('is_active', true),
            ],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:99'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'about' => ['nullable', 'string', 'max:400'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:32'],
        ]);

        DB::transaction(function () use ($request): void {
            $user = $request->user();
            $user->update([
                ...$request->only(['first_name', 'last_name']),
                'name' => trim($request->string('first_name').' '.$request->string('last_name')),
            ]);

            $user->specialistProfile()->updateOrCreate([], $request->only([
                'specialist_specialty_id', 'experience_years', 'city', 'phone', 'whatsapp', 'about', 'skills',
            ]));
        });

        return response()->json([
            'success' => true,
            'message' => __('specialist-cabinet.save.saved'),
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
