<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('slug', 'boutique-ali')->first();
        $user = User::where('email', 'ali@test.com')->first();

        if (!$shop || !$user) {
            $this->command->error('Lance d\'abord KomorShopSeeder');
            return;
        }

        // ── Catégories de dépenses ──
        $categories = [
            [
                'name'  => 'Loyer',
                'color' => '#ef4444',
                'icon'  => 'heroicon-o-home',
            ],
            [
                'name'  => 'Électricité',
                'color' => '#f59e0b',
                'icon'  => 'heroicon-o-bolt',
            ],
            [
                'name'  => 'Transport',
                'color' => '#3b82f6',
                'icon'  => 'heroicon-o-truck',
            ],
            [
                'name'  => 'Téléphone',
                'color' => '#8b5cf6',
                'icon'  => 'heroicon-o-device-phone-mobile',
            ],
            [
                'name'  => 'Emballages',
                'color' => '#10b981',
                'icon'  => 'heroicon-o-cube',
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[$cat['name']] = ExpenseCategory::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => $cat['name']],
                [
                    'color' => $cat['color'],
                    'icon'  => $cat['icon'],
                ]
            );
        }

        $this->command->info('✅ Catégories de dépenses créées');

        // ── Dépenses du mois ──
        $expenses = [
            [
                'category' => 'Loyer',
                'amount'   => 50000,
                'description' => 'Loyer du local - Mai 2024',
                'spent_at' => now()->startOfMonth(),
            ],
            [
                'category' => 'Électricité',
                'amount'   => 15000,
                'description' => 'Facture électricité Ma-Mwe',
                'spent_at' => now()->subDays(10),
            ],
            [
                'category' => 'Transport',
                'amount'   => 5000,
                'description' => 'Taxi marchandises depuis le port',
                'spent_at' => now()->subDays(5),
            ],
            [
                'category' => 'Transport',
                'amount'   => 3000,
                'description' => 'Transport retour grossiste',
                'spent_at' => now()->subDays(3),
            ],
            [
                'category' => 'Téléphone',
                'amount'   => 2000,
                'description' => 'Recharge Huri',
                'spent_at' => now()->subDays(2),
            ],
            [
                'category' => 'Emballages',
                'amount'   => 4500,
                'description' => 'Sachets plastiques et sacs',
                'spent_at' => today(),
            ],
        ];

        foreach ($expenses as $exp) {
            Expense::firstOrCreate(
                [
                    'shop_id'     => $shop->id,
                    'description' => $exp['description'],
                ],
                [
                    'expense_category_id' => $createdCategories[$exp['category']]->id,
                    'user_id'             => $user->id,
                    'amount'              => $exp['amount'],
                    'spent_at'            => $exp['spent_at'],
                ]
            );
        }

        $total = collect($expenses)->sum('amount');

        $this->command->info('✅ Dépenses de test créées');
        $this->command->table(
            ['Catégorie', 'Description', 'Montant'],
            collect($expenses)->map(fn ($e) => [
                $e['category'],
                $e['description'],
                number_format($e['amount'], 0, ',', ' ') . ' KMF',
            ])->toArray()
        );
        $this->command->info("Total dépenses : " . number_format($total, 0, ',', ' ') . " KMF");
    }
}