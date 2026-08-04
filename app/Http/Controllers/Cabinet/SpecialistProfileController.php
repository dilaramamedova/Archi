<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SpecialistProfileController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'craft' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:99'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'about' => ['nullable', 'string', 'max:400'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['string', 'max:32'],
        ]);

        $user = $request->user();
        $user->update($request->only(['first_name', 'last_name']));

        $profile = $user->specialistProfile;
        if ($profile) {
            $profile->update($request->only([
                'craft', 'experience_years', 'city', 'phone', 'whatsapp', 'about', 'skills',
            ]));
        }

        return response()->json([
            'success' => true,
            'message' => __('specialist-cabinet.save.saved'),
        ]);
    }
}
