<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
