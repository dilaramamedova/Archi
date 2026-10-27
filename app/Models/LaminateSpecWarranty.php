<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Laminate\WarrantyScope;
use App\Enums\Laminate\WarrantyTerm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaminateSpecWarranty extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scope' => WarrantyScope::class,
            'term_kind' => WarrantyTerm::class,
            'years' => 'integer',
        ];
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(LaminateSpec::class, 'laminate_spec_id');
    }
}
