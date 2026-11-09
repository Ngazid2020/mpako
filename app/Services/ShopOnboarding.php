<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Création d'une boutique en libre-service :
 *  - commerce + rattachement du créateur
 *  - rôle « owner » si l'utilisateur n'a encore aucun rôle
 *  - portefeuille (vide, brouillon)
 *  - essai gratuit d'un mois (une seule fois par utilisateur)
 */
class ShopOnboarding
{
    /** Rôle attribué au créateur d'une boutique (ShopRolesSeeder) */
    public const OWNER_ROLE = 'owner';

    public function createShop(User $user, array $data): Shop
    {
        return DB::transaction(function () use ($user, $data) {
            $shop = Shop::create([
                'name'      => $data['name'],
                'slug'      => $this->uniqueSlug($data['slug'] ?? $data['name']),
                'island'    => $data['island'],
                'city'      => $data['city'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'address'   => $data['address'] ?? null,
                'currency'  => 'KMF',
                'is_active' => true,
            ]);

            $shop->members()->attach($user->id);

            $this->grantOwnerRole($user);

            $wallet = Wallet::firstOrCreate(
                ['shop_id' => $shop->id],
                ['balance' => 0, 'is_active' => true, 'status' => 'draft', 'notes' => 'Portefeuille créé automatiquement'],
            );

            if ($this->isEligibleForTrial($user, $shop)) {
                $this->startTrial($shop, $wallet);
            }

            return $shop->fresh();
        });
    }

    /**
     * Un utilisateur ne bénéficie de l'essai gratuit qu'une seule fois :
     * créer une deuxième boutique ne relance pas un nouvel essai.
     */
    public function isEligibleForTrial(User $user, ?Shop $except = null): bool
    {
        $shopIds = $user->shops()
            ->when($except, fn ($q) => $q->where('shops.id', '!=', $except->id))
            ->pluck('shops.id');

        return ! Subscription::whereIn('shop_id', $shopIds)
            ->whereHas('plan', fn ($q) => $q->where('is_trial', true))
            ->exists();
    }

    public function trialPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::firstOrCreate(
            ['is_trial' => true],
            [
                'name'          => 'Essai gratuit',
                'description'   => "Un mois d'essai gratuit de toutes les fonctionnalités, offert à la création de la boutique.",
                'price'         => 0,
                'duration_days' => 30,
                'is_active'     => true,
                'is_default'    => false,
            ],
        );
    }

    private function startTrial(Shop $shop, Wallet $wallet): Subscription
    {
        $start = now();
        $end   = $start->copy()->addMonthNoOverflow();

        $subscription = Subscription::create([
            'wallet_id'            => $wallet->id,
            'shop_id'              => $shop->id,
            'subscription_plan_id' => $this->trialPlan()->id,
            'starts_at'            => $start,
            'ends_at'              => $end,
            'is_active'            => true,
            'is_auto_renew'        => false,
            'notes'                => "Essai gratuit d'un mois",
        ]);

        $shop->update(['subscription_expires_at' => $end]);

        return $subscription;
    }

    /**
     * Les rôles Spatie sont globaux (teams=false) : on ne donne « owner » qu'à un utilisateur
     * sans aucun rôle, pour ne pas élever les droits d'un employé existant dans un autre
     * commerce (voir US-101 : rôles par commerce).
     */
    private function grantOwnerRole(User $user): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        if (! Role::where('name', self::OWNER_ROLE)->where('guard_name', 'web')->exists()) {
            Log::warning("Rôle « " . self::OWNER_ROLE . " » introuvable : lancer ShopRolesSeeder. Utilisateur {$user->id} créé sans rôle.");

            return;
        }

        $user->assignRole(self::OWNER_ROLE);
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'boutique';
        $slug = $base;
        $i    = 2;

        while (Shop::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
