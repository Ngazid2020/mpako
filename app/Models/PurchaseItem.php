<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_cost',
        'conversion_qty',
        'subtotal',
    ];

    protected $casts = [
        'quantity'       => 'decimal:2',
        'unit_cost'      => 'decimal:2',
        'conversion_qty' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->subtotal = round((float) $item->quantity * (float) $item->unit_cost, 2);
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}