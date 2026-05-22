<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'customer_id',
        'payment_type',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class);
    }

    // ─────────────────────────────────────────────
    // METHODES STATIQUES
    // ─────────────────────────────────────────────

    /**
     * Générer une référence unique pour ce shop
     * Format : VNT-20260522-0001
     *
     * Utilise la dernière référence du jour pour éviter
     * les race conditions.
     */
    public static function generateReference(int $shopId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "VNT-{$date}";

        // Récupérer la DERNIÈRE référence du shop pour aujourd'hui
        $lastSale = static::where('shop_id', $shopId)
            ->where('reference', 'like', $prefix . '%')
            ->orderBy('reference', 'desc')
            ->first();

        if (!$lastSale) {
            // Pas de vente aujourd'hui → on commence à 1
            return $prefix . '-0001';
        }

        // Extraire le numéro de la dernière référence
        // Ex: VNT-20260522-0042 → 42
        $lastNumber = (int) substr($lastSale->reference, -4);

        // Incrémenter
        $newNumber = $lastNumber + 1;

        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'completed' => '✅ Complétée',
            'cancelled' => '❌ Annulée',
            default     => $this->status,
        };
    }
}
