<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SpecialistSpecialty extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function specialists(): HasMany
    {
        return $this->hasMany(SpecialistProfile::class, 'specialist_specialty_id');
    }
}
