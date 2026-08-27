<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A frozen estimate version. PDF, WhatsApp, "yadda saxla" and the turnkey order all
 * reference a snapshot id, so two channels can never quote different sums while a
 * rate is being edited (master spec 22). A snapshot is never recalculated; the
 * "update with current prices" action creates the next version instead.
 */
class CalculatorSnapshot extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'inputs' => 'array',
        'derived' => 'array',
        'lines' => 'array',
        'totals' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $snapshot): void {
            $snapshot->uuid ??= (string) Str::uuid();
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CalculatorSession::class, 'calculator_session_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
