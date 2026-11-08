<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // Creer le forfait par defaut : Mensuel Standard a 1000 KMF
        $plan = SubscriptionPlan::updateOrCreate(
            ['name' => 'Mensuel Standard'],
            [
                'description' => 'Abonnement mensuel standard de 1000 KMF pour accéder à toutes les fonctionnalités du système.',
                'price' => 1000,
                'duration_days' => 30,
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // Creer un forfait Annuel avec reduction
        SubscriptionPlan::updateOrCreate(
            ['name' => 'Annuel Standard'],
            [
                'description' => 'Abonnement annuel standard avec réduction de 10%.',
                'price' => 10000,
                'duration_days' => 365,
                'is_active' => true,
                'is_default' => false,
            ]
        );

        // Pour chaque commerce, creer un portefeuille et un abonnement
        $shops = Shop::all();

        foreach ($shops as $shop) {
            // Creer le portefeuille avec un solde de 5000 KMF pour les tests
            $wallet = Wallet::updateOrCreate(
                ['shop_id' => $shop->id],
                [
                    'balance' => 5000,
                    'is_active' => true,
                    'notes' => 'Portefeuille initial pour ' . $shop->name,
                ]
            );

            // Creer un abonnement actif pour chaque commerce
            $subscription = Subscription::updateOrCreate(
                ['shop_id' => $shop->id],
                [
                    'wallet_id' => $wallet->id,
                    'subscription_plan_id' => $plan->id,
                    'starts_at' => now()->subDays(15),
                    'ends_at' => now()->addDays(15),
                    'is_active' => true,
                    'is_auto_renew' => false,
                    'notes' => 'Abonnement initial',
                ]
            );

            // Mettre à jour la date d'expiration dans le shop
            $shop->update([
                'subscription_expires_at' => $subscription->ends_at,
            ]);

            // Creer une transaction initiale de recharge
            WalletTransaction::updateOrCreate(
                [
                    'wallet_id' => $wallet->id,
                    'shop_id' => $shop->id,
                    'reference' => 'INIT-' . $shop->id . '-' . now()->format('Ymd'),
                ],
                [
                    'user_id' => 1,
                    'type' => 'recharge',
                    'payment_method' => 'cash',
                    'amount' => 5000,
                    'balance_before' => 0,
                    'balance_after' => 5000,
                    'notes' => 'Recharge initiale du portefeuille',
                    'is_confirmed' => true,
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info('SubscriptionSeeder: Forfaits et portefeuilles créés avec succès !');
    }
}
