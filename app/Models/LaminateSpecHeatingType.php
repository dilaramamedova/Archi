<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One approved heating system of a laminate. The table's primary key is the pair
 * (spec, code), so Eloquent has no single key to update by — the set is always
 * rewritten as a whole by LaminateSpecService, never edited row by row.
 */
class LaminateSpecHeatingType extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $guarded = [];

    public function spec(): BelongsTo
    {
        return $this->belongsTo(LaminateSpec::class, 'laminate_spec_id');
    }
}
