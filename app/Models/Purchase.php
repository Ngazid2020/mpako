<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    protected static function booted(): void
    {
        // payment_status est toujours dérivé de paid/debt (source unique de vérité)
        static::saving(function (self $purchase) {
            if ($purchase->isDirty(['paid_amount', 'debt_amount', 'total_amount']) || ! $purchase->payment_status) {
                $purchase->payment_status = static::derivePaymentStatus(
                    (float) $purchase->total_amount,
                    (float) $purchase->paid_amount,
                    (float) $purchase->debt_amount,
                );
            }
        });
    }

    public static function derivePaymentStatus(float $total, float $paid, float $debt): string
    {
        return match (true) {
            $total > 0 && $debt <= 0 => 'paid',
            $paid > 0                => 'partial',
            default                  => 'unpaid',
        };
    }

    /**
     * Recalcule total/dette depuis les lignes enregistrées en base
     * (ne fait jamais confiance aux totaux envoyés par le formulaire).
     */
    public function syncTotalsFromItems(): void
    {
        $total = round((float) $this->items()->sum('subtotal'), 2);
        $paid  = min((float) $this->paid_amount, $total);

        $this->update([
            'total_amount' => $total,
            'paid_amount'  => $paid,
            'debt_amount'  => max(0, round($total - $paid, 2)),
        ]);
    }

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
        return DB::transaction(function () use ($shopId) {
            $date   = now()->format('Ymd');
            $prefix = "ACH-{$date}";

            $lastPurchase = static::where('shop_id', $shopId)
                ->where('reference', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('reference', 'desc')
                ->first();

            $lastNumber = $lastPurchase ? (int) substr($lastPurchase->reference, -4) : 0;

            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
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
