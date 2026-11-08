<?php

namespace App\Models;

use App\Enums\WalletStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToUser;

class Wallet extends Model
{
    protected $fillable = [
        'shop_id',
        'balance',
        'is_active',
        'notes',
        'status',
        'submitted_by',
        'validated_by',
        'submitted_at',
        'validated_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'status' => WalletStatus::class,
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
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

    public function submittedBy(): BelongsToUser
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function validatedBy(): BelongsToUser
    {
        return $this->belongsTo(User::class, 'validated_by');
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

    /**
     * Crédite le portefeuille (recharge) — atomique :
     * verrou sur la ligne wallet, solde relu en base, écriture du journal + solde dans la même transaction.
     */
    public function credit(float $amount, string $paymentMethod, string $notes = '', ?int $userId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant de la recharge doit être positif.');
        }

        return $this->applyMovement($amount, 'recharge', $paymentMethod, $notes, $userId);
    }

    /**
     * Débite le portefeuille (paiement d'abonnement) — refuse un solde insuffisant.
     */
    public function debit(float $amount, string $notes = '', ?int $userId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant du débit doit être positif.');
        }

        return $this->applyMovement(-$amount, 'subscription', 'wallet', $notes, $userId);
    }

    private function applyMovement(float $signedAmount, string $type, string $paymentMethod, string $notes, ?int $userId): WalletTransaction
    {
        return DB::transaction(function () use ($signedAmount, $type, $paymentMethod, $notes, $userId) {
            /** @var self $locked */
            $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $oldBalance = (float) $locked->balance;
            $newBalance = round($oldBalance + $signedAmount, 2);

            if ($newBalance < 0) {
                throw new \RuntimeException('Solde du portefeuille insuffisant.');
            }

            $transaction = WalletTransaction::create([
                'wallet_id'      => $locked->id,
                'shop_id'        => $locked->shop_id,
                'user_id'        => $userId ?? auth()?->id(),
                'reference'      => WalletTransaction::generateReference($locked->shop_id),
                'type'           => $type,
                'payment_method' => $paymentMethod,
                'amount'         => $signedAmount,
                'balance_before' => $oldBalance,
                'balance_after'  => $newBalance,
                'notes'          => $notes,
                'is_confirmed'   => true,
            ]);

            $locked->update(['balance' => $newBalance]);
            $this->setRawAttributes($locked->getAttributes(), true);

            return $transaction;
        });
    }

    // ─────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', WalletStatus::DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', WalletStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', WalletStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', WalletStatus::REJECTED);
    }

    public function scopeNeedsValidation($query)
    {
        return $query->where('status', WalletStatus::PENDING);
    }

    // ─────────────────────────────────────────────
    // METHODES - Soumission & Validation
    // ─────────────────────────────────────────────

    public function submitForValidation(int $userId, string $notes = ''): bool
    {
        if ($this->status !== WalletStatus::DRAFT) {
            return false;
        }

        return $this->update([
            'status' => WalletStatus::PENDING,
            'submitted_by' => $userId,
            'submitted_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function approve(int $userId, string $notes = ''): bool
    {
        if ($this->status !== WalletStatus::PENDING) {
            return false;
        }

        return $this->update([
            'status' => WalletStatus::APPROVED,
            'is_active' => true,
            'validated_by' => $userId,
            'validated_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function reject(int $userId, string $reason = ''): bool
    {
        if ($this->status !== WalletStatus::PENDING) {
            return false;
        }

        return $this->update([
            'status' => WalletStatus::REJECTED,
            'is_active' => false,
            'validated_by' => $userId,
            'validated_at' => now(),
            'notes' => $reason,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === WalletStatus::DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === WalletStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === WalletStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === WalletStatus::REJECTED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft();
    }

    public function canBeValidated(): bool
    {
        return $this->isPending();
    }
}
