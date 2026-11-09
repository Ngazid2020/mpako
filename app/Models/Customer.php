<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'address',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    /**
     * Le client a-t-il une dette en cours ?
     */
    public function hasDebt(): bool
    {
        return $this->balance > 0;
    }

    /**
     * Nombre de crédits non soldés
     */
    public function getPendingCreditsCountAttribute(): int
    {
        return $this->credits()
            ->whereIn('status', ['pending', 'partial'])
            ->count();
    }
}
