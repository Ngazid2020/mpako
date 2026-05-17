<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'category_id',
        'unit_id',
        'name',
        'barcode',
        'description',
        'buy_price',
        'sell_price',
        'stock_qty',
        'stock_alert',
        'is_active',
    ];

    protected $casts = [
        'buy_price'   => 'decimal:2',
        'sell_price'  => 'decimal:2',
        'stock_qty'   => 'decimal:2',
        'stock_alert' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    /**
     * Le stock est-il en dessous du seuil d'alerte ?
     */
    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->stock_alert;
    }

    /**
     * Marge bénéficiaire en KMF
     */
    public function getMarginAttribute(): float
    {
        return (float) ($this->sell_price - $this->buy_price);
    }

    /**
     * Marge en pourcentage
     */
    public function getMarginPercentAttribute(): float
    {
        if ($this->buy_price == 0) return 0;
        return round((($this->sell_price - $this->buy_price) / $this->buy_price) * 100, 2);
    }
}