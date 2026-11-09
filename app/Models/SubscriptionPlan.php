<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'is_active',
        'is_default',
        'is_trial',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_trial' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ─────────────────────────────────────────────
    // ACCESSEURS
    // ─────────────────────────────────────────────

    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' KMF';
    }

    public function getDurationLabelAttribute(): string
    {
        if ($this->duration_days === 30) {
            return 'Mensuel';
        } elseif ($this->duration_days === 365) {
            return 'Annuel';
        } elseif ($this->duration_days === 7) {
            return 'Hebdomadaire';
        }
        return $this->duration_days . ' jours';
    }
}
