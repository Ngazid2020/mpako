<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'island',
        'city',
        'address',
        'phone',
        'email',
        'currency',
        'is_active',
        'subscription_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
    ];

    /**
     * Les membres (users) de ce commerce.
     * Un commerce peut avoir plusieurs employés.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user')
            ->withTimestamps();
    }

    // ─────────────────────────────────────────────
    // RELATIONS — Module Stock
    // ─────────────────────────────────────────────

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // app/Models/Shop.php
    // Ajouter ces deux relations

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function saleItems(): HasManyThrough
    {
        return $this->hasManyThrough(SaleItem::class, Sale::class);
    }


    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ─────────────────────────────────────────────
    // RELATIONS - Module Portefeuille & Abonnement
    // ─────────────────────────────────────────────

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS - Portefeuille & Abonnement
    // ─────────────────────────────────────────────

    public function getWalletBalanceAttribute(): float
    {
        return $this->wallet?->balance ?? 0;
    }

    public function getWalletBalanceFormattedAttribute(): string
    {
        return number_format($this->wallet_balance, 0, ',', ' ') . ' KMF';
    }

    public function isSubscriptionActive(): bool
    {
        if (!$this->subscription_expires_at) {
            return false;
        }
        return now()->startOfDay() <= $this->subscription_expires_at->endOfDay();
    }

    public function isInGracePeriod(): bool
    {
        if (!$this->subscription_expires_at) {
            return false;
        }
        $graceEnd = $this->subscription_expires_at->copy()->addDays(2)->endOfDay();
        return now()->startOfDay() <= $graceEnd;
    }

    public function canUseSystem(): bool
    {
        return $this->is_active && ($this->isSubscriptionActive() || $this->isInGracePeriod());
    }
}
