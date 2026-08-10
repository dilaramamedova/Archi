<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\City;
use App\Http\Controllers\Controller;
use App\Models\Showroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShowroomController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $profile = $request->user()->sellerProfile()->firstOrCreate();
        $validated['sort_order'] = ($profile->showrooms()->max('sort_order') ?? 0) + 1;
        $showroom = $profile->showrooms()->create($validated);

        return response()->json(['success' => true, 'showroom' => $showroom]);
    }

    public function update(Request $request, Showroom $showroom): JsonResponse
    {
        $this->authorizeShowroom($request, $showroom);
        $showroom->update($this->validated($request));

        return response()->json(['success' => true, 'showroom' => $showroom->fresh()]);
    }

    public function destroy(Request $request, Showroom $showroom): JsonResponse
    {
        $this->authorizeShowroom($request, $showroom);
        $showroom->delete();

        return response()->json(['success' => true]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', Rule::in(City::acceptedValues())],
            'phone' => ['nullable', 'string', 'max:30'],
            'work_hours' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,hidden'],
        ]);

        if (filled($validated['city'] ?? null)) {
            $validated['city'] = City::canonical($validated['city']);
        }

        return $validated;
    }

    private function authorizeShowroom(Request $request, Showroom $showroom): void
    {
        abort_unless(
            $showroom->sellerProfile?->user_id === $request->user()->id,
            403
        );
    }
}
