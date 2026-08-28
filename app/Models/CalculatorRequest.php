<?php

namespace App\Models;

use App\Enums\Calculator\RequestStatus;
use App\Enums\Calculator\RequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request raised from the finished estimate — a turnkey order, a saved estimate,
 * a PDF or a WhatsApp share.
 *
 * It carries the snapshot id rather than the session id on purpose: the manager who
 * calls the client back has to see the sum the client saw, not a recalculation made
 * after someone edited a rate.
 */
class CalculatorRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'type' => RequestType::class,
        'status' => RequestStatus::class,
        'total_amount' => 'decimal:2',
        'area' => 'decimal:2',
        'handled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CalculatorSession::class, 'calculator_session_id');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(CalculatorSnapshot::class, 'calculator_snapshot_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where('status', RequestStatus::New);
    }
}
