<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenderAccessRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * "Ödənişli paketləri seç" — a signed-in user asks to join the tender section.
 * Mailed to the admin inbox (TenderAccessRequestNotification); the admin
 * activates a subscription from the panel.
 */
final class TenderAccessRequest extends Model
{
    protected $fillable = [
        'user_id', 'tender_package_id', 'full_name', 'company_name', 'phone', 'email',
        'message', 'status', 'admin_note', 'processed_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'status' => TenderAccessRequestStatus::class,
        'processed_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TenderPackage::class, 'tender_package_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(TenderSubscription::class);
    }
}
