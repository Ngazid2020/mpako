<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'shop_id',
        'supplier_id',
        'user_id',
        'reference',
        'status',
        'payment_status',
        'total_amount',
        'paid_amount',
        'debt_amount',
        'purchased_at',
        'note',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'debt_amount'   => 'decimal:2',
        'purchased_at'  => 'date',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // ─────────────────────────────────────────────
    // METHODES STATIQUES
    // ─────────────────────────────────────────────

    /**
     * Générer une référence unique
     * Format : ACH-20240517-0001
     */
    public static function generateReference(int $shopId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "ACH-{$date}";

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
        return match ($this->status) {
            'pending'   => '⏳ En attente',
            'completed' => '✅ Validé',
            'cancelled' => '❌ Annulé',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default     => 'gray',
        };
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    // Ajouter ces deux accesseurs
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid'  => '🔴 Non payé',
            'partial' => '🟠 Partiellement payé',
            'paid'    => '✅ Entièrement payé',
            default   => $this->payment_status,
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'unpaid'  => 'danger',
            'partial' => 'warning',
            'paid'    => 'success',
            default   => 'gray',
        };
    }
}
