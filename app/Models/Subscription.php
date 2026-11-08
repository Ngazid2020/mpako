<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'wallet_id',
        'shop_id',
        'subscription_plan_id',
        'starts_at',
        'ends_at',
        'is_active',
        'is_auto_renew',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_auto_renew' => 'boolean',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (now()->isBefore($this->starts_at)) {
            return 'pending';
        }

        if (now()->isAfter($this->ends_at)) {
            return 'expired';
        }

        return 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Actif',
            'pending' => 'En attente',
            'expired' => 'Expiré',
            'inactive' => 'Inactif',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'expired' => 'danger',
            'inactive' => 'gray',
            default => 'gray',
        };
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->ends_at);
    }

    public function isActive(): bool
    {
        return $this->is_active && now()->isBetween($this->starts_at, $this->ends_at);
    }

    public function daysRemaining(): int
    {
        if ($this->ends_at) {
            // Carbon 3 renvoie un float signé : on arrondit au jour inférieur, jamais négatif
            return max(0, (int) floor(now()->diffInDays($this->ends_at, false)));
        }
        return 0;
    }

    /** Accesseur pour les colonnes Filament `days_remaining` */
    public function getDaysRemainingAttribute(): int
    {
        return $this->daysRemaining();
    }

    // ─────────────────────────────────────────────
    // METHODES
    // ─────────────────────────────────────────────

    public function renew(): self
    {
        $newStartsAt = now();
        $newEndsAt = $newStartsAt->copy()->addDays($this->plan?->duration_days ?? 30);

        $this->update([
            'starts_at' => $newStartsAt,
            'ends_at' => $newEndsAt,
            'is_active' => true,
        ]);

        return $this;
    }

    public function activate(): self
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['is_active' => false]);
        return $this;
    }
}
