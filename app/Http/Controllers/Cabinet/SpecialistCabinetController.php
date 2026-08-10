<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cabinet;

use App\Enums\PortfolioStatus;
use App\Http\Controllers\Controller;
use App\Models\SpecialistPortfolioItem;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class SpecialistCabinetController extends Controller
{
    public function profile(Request $request): View
    {
        $profile = $request->user()->specialistProfile;

        return view('pages.specialist-cabinet', [
            'user' => $request->user(),
            'profile' => $profile,
            // Deactivated specialties are hidden from everyone EXCEPT the master
            // already assigned to one — otherwise the select would silently fall
            // back to its first option and rewrite their trade on the next save.
            'specialties' => SpecialistSpecialty::query()
                ->where(fn ($q) => $q->where('is_active', true)
                    ->orWhere('id', $profile?->specialist_specialty_id))
                ->orderBy('sort_order')
                ->pluck('name', 'id'),
        ]);
    }

    public function portfolio(Request $request): View
    {
        $profile = $request->user()->specialistProfile;

        return view('pages.specialist-cabinet-portfolio', [
            'profile' => $profile,
            'portfolioItems' => $profile?->portfolioItems()->orderBy('sort_order')->get() ?? collect(),
            'maxPortfolio' => 30,
        ]);
    }

    public function services(Request $request): View
    {
        $profile = $request->user()->specialistProfile;

        return view('pages.specialist-cabinet-services', [
            'profile' => $profile,
            'services' => $profile?->services()->orderBy('sort_order')->get() ?? collect(),
        ]);
    }

    public function schedule(Request $request): View
    {
        $profile = $request->user()->specialistProfile;

        return view('pages.specialist-cabinet-schedule', [
            'profile' => $profile,
            'schedules' => $profile?->schedules()->orderBy('day_of_week')->get() ?? collect(),
        ]);
    }

    public function updatePortfolio(Request $request): JsonResponse
    {
        if (is_string($request->input('items'))) {
            $request->merge(['items' => json_decode($request->input('items'), true)]);
        }

        $validated = $request->validate([
            'items' => ['present', 'array', 'max:30'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.title' => ['nullable', 'string', 'max:120'],
            'items.*.file_index' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:30'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $profile = $this->profileFor($request);

        DB::transaction(function () use ($profile, $validated, $request): void {
            $existingIds = collect($validated['items'])->pluck('id')->filter()->map(fn ($id): int => (int) $id);

            $profile->portfolioItems()
                ->whereNotIn('id', $existingIds)
                ->get()
                ->each(function (SpecialistPortfolioItem $item): void {
                    $this->deletePortfolioImage($item->image_path);
                    $item->delete();
                });

            foreach ($validated['items'] as $sortOrder => $itemData) {
                $attributes = [
                    'title' => $itemData['title'] ?? null,
                    'sort_order' => $sortOrder,
                    'is_cover' => $sortOrder === 0,
                ];

                if (! empty($itemData['id'])) {
                    $profile->portfolioItems()->whereKey($itemData['id'])->firstOrFail()->update($attributes);

                    continue;
                }

                $fileIndex = $itemData['file_index'] ?? null;
                $file = $fileIndex !== null ? $request->file("images.{$fileIndex}") : null;
                abort_unless($file, 422, t('specialist-cabinet-portfolio.msg.image_missing'));

                $profile->portfolioItems()->create([
                    ...$attributes,
                    'image_path' => $file->store('specialists/portfolio', 'public'),
                    'status' => PortfolioStatus::Pending,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => t('specialist-cabinet-portfolio.msg.saved')]);
    }

    public function updateServices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'services' => ['present', 'array', 'max:30'],
            'services.*.id' => ['nullable', 'integer'],
            'services.*.name' => ['required', 'string', 'max:150'],
            'services.*.description' => ['nullable', 'string', 'max:255'],
            'services.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'services.*.unit' => ['required', Rule::in(['sqm', 'hour', 'piece', 'linear'])],
            'services.*.is_active' => ['required', 'boolean'],
        ]);

        $profile = $this->profileFor($request);

        DB::transaction(function () use ($profile, $validated): void {
            $ids = collect($validated['services'])->pluck('id')->filter()->map(fn ($id): int => (int) $id);
            $profile->services()->whereNotIn('id', $ids)->delete();

            foreach ($validated['services'] as $sortOrder => $serviceData) {
                $id = Arr::pull($serviceData, 'id');
                $serviceData['sort_order'] = $sortOrder;

                if ($id) {
                    $profile->services()->whereKey($id)->firstOrFail()->update($serviceData);
                } else {
                    $profile->services()->create($serviceData);
                }
            }
        });

        return response()->json(['success' => true, 'message' => t('specialist-cabinet-services.msg.saved')]);
    }

    public function updateSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'days.*.is_day_off' => ['required', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i'],
            'available_slots' => ['required', 'integer', 'between:0,20'],
            'is_on_vacation' => ['required', 'boolean'],
        ]);

        foreach ($validated['days'] as $day) {
            if (! $day['is_day_off'] && ($day['start_time'] === null || $day['end_time'] === null || $day['end_time'] <= $day['start_time'])) {
                return response()->json(['message' => t('specialist-cabinet-schedule.msg.end_after_start')], 422);
            }
        }

        $profile = $this->profileFor($request);

        DB::transaction(function () use ($profile, $validated): void {
            $profile->update(Arr::only($validated, ['available_slots', 'is_on_vacation']));

            foreach ($validated['days'] as $day) {
                $profile->schedules()->updateOrCreate(
                    ['day_of_week' => $day['day_of_week']],
                    [
                        'is_day_off' => $day['is_day_off'],
                        'start_time' => $day['is_day_off'] ? null : $day['start_time'],
                        'end_time' => $day['is_day_off'] ? null : $day['end_time'],
                    ],
                );
            }
        });

        return response()->json(['success' => true, 'message' => t('specialist-cabinet-schedule.msg.saved')]);
    }

    private function profileFor(Request $request): SpecialistProfile
    {
        return $request->user()->specialistProfile()->firstOrCreate();
    }

    private function deletePortfolioImage(string $path): void
    {
        if (! str_starts_with($path, 'assets/') && ! str_starts_with($path, '/assets/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
