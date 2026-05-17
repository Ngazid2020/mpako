<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    protected $fillable = [
        'shop_id',
        'customer_id',
        'user_id',
        'reference',
        'status',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'description',
        'note',
    ];

    protected $casts = [
        'total_amount'     => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date'         => 'date',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    // ─────────────────────────────────────────────
    // METHODES STATIQUES
    // ─────────────────────────────────────────────

    public static function generateReference(int $shopId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "CRD-{$date}";

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
        return match($this->status) {
            'pending' => '🔴 Non remboursé',
            'partial' => '🟠 Partiellement remboursé',
            'paid'    => '✅ Soldé',
            default   => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'danger',
            'partial' => 'warning',
            'paid'    => 'success',
            default   => 'gray',
        };
    }

    /**
     * Le crédit est-il en retard ?
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== 'paid';
    }
}