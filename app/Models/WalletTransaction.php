<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'shop_id',
        'user_id',
        'reference',
        'type',
        'payment_method',
        'amount',
        'balance_before',
        'balance_after',
        'notes',
        'is_confirmed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_confirmed' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────
    // METHODES STATIQUES
    // ─────────────────────────────────────────────

    public static function generateReference(int $shopId): string
    {
        return DB::transaction(function () use ($shopId) {
            $date = now()->format('Ymd');
            $prefix = "WAL-{$shopId}-{$date}";

            $lastTransaction = static::where('shop_id', $shopId)
                ->where('reference', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('reference', 'desc')
                ->first();

            $lastNumber = $lastTransaction ? (int) substr($lastTransaction->reference, -4) : 0;

            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'recharge' => 'Recharge',
            'subscription' => 'Abonnement',
            'refund' => 'Remboursement',
            default => $this->type,
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'mvola' => 'MVola',
            'other' => 'Autre',
            'wallet' => 'Portefeuille',
            default => $this->payment_method,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_confirmed ? 'Confirmé' : 'En attente';
    }

    public function getAmountFormattedAttribute(): string
    {
        $prefix = $this->amount > 0 ? '+' : '';
        return $prefix . number_format(abs($this->amount), 0, ',', ' ') . ' KMF';
    }
}
