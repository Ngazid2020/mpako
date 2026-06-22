<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_approved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'is_admin'                  => 'boolean',
            'is_approved'               => 'boolean',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at'   => 'datetime',
        ];
    }

    public function hasEnabledTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')
            ->withTimestamps();
    }

    // ─────────────────────────────────────────────
    // FILAMENT : Accès aux panels
    // ─────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_admin === true;
        }

        // Le panel commerce nécessite un compte approuvé par l'admin
        if ($panel->getId() === 'commerce') {
            return $this->is_approved === true;
        }

        return false;
    }

    // ─────────────────────────────────────────────
    // FILAMENT : Multitenancy
    // ─────────────────────────────────────────────

    public function getTenants(Panel $panel): Collection
    {
        return $this->shops;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->shops()->whereKey($tenant)->exists();
    }
}
