<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    protected $fillable = [
        'credit_id',
        'user_id',
        'amount',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'date',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}