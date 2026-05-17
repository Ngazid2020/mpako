<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'shop_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after'  => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    /**
     * Label lisible du type de mouvement
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'in'         => '📥 Entrée',
            'out'        => '📤 Sortie',
            'adjustment' => '🔧 Ajustement',
            default      => $this->type,
        };
    }
}