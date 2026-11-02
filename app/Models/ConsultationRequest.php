<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsultationRequestSource;
use App\Enums\ConsultationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultationRequest extends Model
{
    use HasFactory;

    /** Plan CTAs on /b2b, keyed "<market>.<plan>". */
    public const B2B_PLANS = [
        'international.free' => 'FREE — beynəlxalq',
        'international.premium' => 'B2B PREMIUM — beynəlxalq',
        'local.free' => 'FREE — daxili bazar',
        'local.basic' => 'BASIC — daxili bazar',
        'local.pro' => 'PRO — daxili bazar',
        'local.premium' => 'PREMIUM — daxili bazar',
    ];

    protected $fillable = [
        'full_name', 'phone', 'message', 'source', 'b2b_plan', 'status', 'admin_note',
        'contacted_at', 'ip_address', 'user_agent',
    ];

    protected $attributes = [
        'source' => 'home',
    ];

    protected $casts = [
        'status' => ConsultationRequestStatus::class,
        'source' => ConsultationRequestSource::class,
        'contacted_at' => 'immutable_datetime',
    ];

    public function b2bPlanLabel(): ?string
    {
        return $this->b2b_plan ? (self::B2B_PLANS[$this->b2b_plan] ?? $this->b2b_plan) : null;
    }
}
