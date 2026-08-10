<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'discount',
        'delivery_fee',
        'total',
        'promo_code',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_city',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * ARCHI-YYYYMMDD-XXXXXX. The suffix is drawn from a CSPRNG (not uniqid())
     * so an order number cannot be guessed from the time it was placed.
     * The alphabet omits look-alike characters (I, O, 0, 1).
     */
    public static function generateOrderNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $suffix = '';

        for ($i = 0; $i < 6; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'ARCHI-' . date('Ymd') . '-' . $suffix;
    }
}
