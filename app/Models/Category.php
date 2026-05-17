<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    /**
     * La catégorie appartient à un commerce (tenant)
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Une catégorie a plusieurs produits
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}