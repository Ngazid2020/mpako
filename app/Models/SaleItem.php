<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_price'      => 'decimal:2',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);

            if ($item->discount_type && (float) $item->discount_value > 0) {
                $item->discount_amount = $item->discount_type === 'percent'
                    ? round($base * (float) $item->discount_value / 100, 2)
                    : min(round((float) $item->discount_value, 2), $base);
            } else {
                $item->discount_amount = 0;
            }

            $item->subtotal = round($base - (float) $item->discount_amount, 2);
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}