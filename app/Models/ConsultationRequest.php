<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsultationRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsultationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name', 'phone', 'message', 'status', 'admin_note',
        'contacted_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'status' => ConsultationRequestStatus::class,
        'contacted_at' => 'immutable_datetime',
    ];
}
