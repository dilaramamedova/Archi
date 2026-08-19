<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpecialistCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

final class SpecialistSpecialty extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected static function booted(): void
    {
        // craft on the profiles is a snapshot of the Azerbaijani name; keep it
        // in sync so a rename in the admin panel propagates immediately.
        self::saved(function (SpecialistSpecialty $specialty): void {
            $az = $specialty->getTranslation('name', 'az', false);

            if ($az === '' || ! $specialty->wasChanged('name')) {
                return;
            }

            SpecialistProfile::query()
                ->where('specialist_specialty_id', $specialty->id)
                ->update(['craft' => $az]);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'category' => SpecialistCategory::class,
        ];
    }

    /**
     * Narrow to one of the header mega panel's four groups. Takes the enum, not a
     * string, so an unrecognised `?type=` cannot reach the query — the caller
     * resolves it with tryFrom() and simply leaves the listing unfiltered, which
     * is how an unknown ?category= slug already behaves on the catalog.
     */
    public function scopeCategory(Builder $query, SpecialistCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function specialists(): HasMany
    {
        return $this->hasMany(SpecialistProfile::class, 'specialist_specialty_id');
    }
}
