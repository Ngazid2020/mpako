<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class CreditSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('slug', 'boutique-ali')->first();
        $user = User::where('email', 'ali@test.com')->first();

        if (!$shop || !$user) {
            $this->command->error('Lance d\'abord KomorShopSeeder');
            return;
        }

        // ── Clients ──
        $ahmed = Customer::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Ahmed Ali'],
            ['phone' => '333 11 11', 'address' => 'Bandamadji']
        );

        $mariama = Customer::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Mariama Said'],
            ['phone' => '333 22 22', 'address' => 'Itsandra']
        );

        $ibrahim = Customer::firstOrCreate(
            ['shop_id' => $shop->id, 'name' => 'Ibrahim Mohamed'],
            ['phone' => '333 33 33', 'address' => 'Moroni Centre']
        );

        $this->command->info('✅ Clients créés');

        // ── Crédit 1 : Ahmed — Non remboursé ──
        $credit1 = Credit::create([
            'shop_id'          => $shop->id,
            'customer_id'      => $ahmed->id,
            'user_id'          => $user->id,
            'reference'        => 'CRD-' . now()->format('Ymd') . '-0001',
            'status'           => 'pending',
            'total_amount'     => 3500,
            'paid_amount'      => 0,
            'remaining_amount' => 3500,
            'due_date'         => today()->addDays(7),
            'description'      => '5kg de riz + 1 bouteille d\'huile',
        ]);

        // ── Crédit 2 : Mariama — Partiellement remboursé ──
        $credit2 = Credit::create([
            'shop_id'          => $shop->id,
            'customer_id'      => $mariama->id,
            'user_id'          => $user->id,
            'reference'        => 'CRD-' . now()->format('Ymd') . '-0002',
            'status'           => 'pending',
            'total_amount'     => 5000,
            'paid_amount'      => 0,
            'remaining_amount' => 5000,
            'due_date'         => today()->addDays(14),
            'description'      => 'Courses diverses',
        ]);

        // Remboursement partiel de Mariama
        CreditPayment::create([
            'credit_id' => $credit2->id,
            'user_id'   => $user->id,
            'amount'    => 2000,
            'paid_at'   => today(),
            'note'      => 'Premier versement',
        ]);

        // ── Crédit 3 : Ibrahim — Entièrement soldé ──
        $credit3 = Credit::create([
            'shop_id'          => $shop->id,
            'customer_id'      => $ibrahim->id,
            'user_id'          => $user->id,
            'reference'        => 'CRD-' . now()->format('Ymd') . '-0003',
            'status'           => 'pending',
            'total_amount'     => 1500,
            'paid_amount'      => 0,
            'remaining_amount' => 1500,
            'description'      => 'Boissons',
        ]);

        // Remboursement total d'Ibrahim
        CreditPayment::create([
            'credit_id' => $credit3->id,
            'user_id'   => $user->id,
            'amount'    => 1500,
            'paid_at'   => today(),
            'note'      => 'Remboursement complet',
        ]);

        $this->command->info('✅ Crédits de test créés');
        $this->command->table(
            ['Client', 'Montant', 'Statut'],
            [
                [$ahmed->name,   '3 500 KMF', '🔴 Non remboursé'],
                [$mariama->name, '5 000 KMF', '🟠 Partiel (2 000 KMF remboursés)'],
                [$ibrahim->name, '1 500 KMF', '✅ Soldé'],
            ]
        );
    }
}