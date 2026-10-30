<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One paid access period. Access is open while any period covers "now". */
final class TenderSubscription extends Model
{
    protected $fillable = ['user_id', 'tender_package_id', 'tender_access_request_id', 'starts_at', 'ends_at', 'note'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TenderPackage::class, 'tender_package_id');
    }

    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(TenderAccessRequest::class, 'tender_access_request_id');
    }

    public function isActive(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }
}
