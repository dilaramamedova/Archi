<?php

declare(strict_types=1);

namespace App\Models;

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
        return ['is_active' => 'boolean'];
    }

    public function specialists(): HasMany
    {
        return $this->hasMany(SpecialistProfile::class, 'specialist_specialty_id');
    }
}
