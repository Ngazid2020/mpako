<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'change_amount',
        'note',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // ─────────────────────────────────────────────
    // METHODES STATIQUES
    // ─────────────────────────────────────────────

    /**
     * Générer une référence unique
     * Format : VNT-20240517-0001
     */
    public static function generateReference(int $shopId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "VNT-{$date}";

        // Compter les ventes du jour pour ce shop
        $count = static::where('shop_id', $shopId)
            ->whereDate('created_at', today())
            ->count();

        return $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => '✅ Complétée',
            'cancelled' => '❌ Annulée',
            default     => $this->status,
        };
    }
}