<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────

    /**
     * Les commerces auxquels cet utilisateur a accès.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')
            ->withTimestamps();
    }

    // ─────────────────────────────────────────────
    // FILAMENT : Accès aux panels
    // ─────────────────────────────────────────────

    /**
     * Qui peut accéder à quel panel ?
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Le panel admin n'est accessible qu'aux super-admins
        if ($panel->getId() === 'admin') {
            return $this->is_admin === true;
        }

        // Le panel commerce est accessible à tout utilisateur actif
        if ($panel->getId() === 'commerce') {
            return true;
        }

        return false;
    }

    // ─────────────────────────────────────────────
    // FILAMENT : Multitenancy
    // ─────────────────────────────────────────────

    /**
     * Retourne les tenants (commerces) auxquels l'utilisateur a accès.
     * Filament affichera un sélecteur si l'user a plusieurs commerces.
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->shops;
    }

    /**
     * Vérifie si l'utilisateur peut accéder à un tenant donné.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->shops()->whereKey($tenant)->exists();
    }
}
