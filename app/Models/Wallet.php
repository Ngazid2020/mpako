<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'shop_id',
        'balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getBalanceFormattedAttribute(): string
    {
        return number_format($this->balance, 0, ',', ' ') . ' KMF';
    }

    public function hasSufficientBalance(float $amount = 1000): bool
    {
        return $this->balance >= $amount;
    }

    public function getMissingAmountAttribute(): float
    {
        return max(0, 1000 - $this->balance);
    }

    // ─────────────────────────────────────────────
    // METHODES
    // ─────────────────────────────────────────────

    public function credit(float $amount, string $paymentMethod, string $notes = '', int $userId = null): WalletTransaction
    {
        $userId = $userId ?? auth()?->id();
        $oldBalance = $this->balance;
        $newBalance = $oldBalance + $amount;

        $transaction = WalletTransaction::create([
            'wallet_id' => $this->id,
            'shop_id' => $this->shop_id,
            'user_id' => $userId,
            'reference' => WalletTransaction::generateReference($this->shop_id),
            'type' => 'recharge',
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'balance_before' => $oldBalance,
            'balance_after' => $newBalance,
            'notes' => $notes,
            'is_confirmed' => true,
        ]);

        $this->update(['balance' => $newBalance]);

        return $transaction;
    }

    public function debit(float $amount, string $notes = '', int $userId = null): WalletTransaction
    {
        $userId = $userId ?? auth()?->id();
        $oldBalance = $this->balance;
        $newBalance = $oldBalance - $amount;

        $transaction = WalletTransaction::create([
            'wallet_id' => $this->id,
            'shop_id' => $this->shop_id,
            'user_id' => $userId,
            'reference' => WalletTransaction::generateReference($this->shop_id),
            'type' => 'subscription',
            'payment_method' => 'wallet',
            'amount' => -$amount,
            'balance_before' => $oldBalance,
            'balance_after' => $newBalance,
            'notes' => $notes,
            'is_confirmed' => true,
        ]);

        $this->update(['balance' => $newBalance]);

        return $transaction;
    }
}
