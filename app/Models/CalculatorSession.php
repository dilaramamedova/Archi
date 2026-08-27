<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A live calculator session. `inputs` is the complete answer set of steps 0-5; the
 * estimate itself is never stored here because it is recomputed from inputs and the
 * current configuration on every read (master spec 18.1). Only the totals are kept,
 * so a saved session can be listed without running the engine.
 */
class CalculatorSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'inputs' => 'array',
        'totals' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $session): void {
            $session->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(CalculatorSnapshot::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
