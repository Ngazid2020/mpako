<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class KomorShopSeeder extends Seeder
{
    public function run(): void
    {   
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        // ─────────────────────────────────────
        // 1. Vérifier que le super-admin existe
        // ─────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@komorshop.com'],
            [
                'name' => 'Admin KomorShop',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // S'assurer qu'il a le rôle super_admin
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole($superAdminRole);
        }

        $this->command->info('✅ Super-admin créé : admin@komorshop.com / password');

        // ─────────────────────────────────────
        // 2. Créer des commerçants de test
        // ─────────────────────────────────────
        $ali = User::firstOrCreate(
            ['email' => 'ali@test.com'],
            [
                'name' => 'Ali Mohamed',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );

        $fatima = User::firstOrCreate(
            ['email' => 'fatima@test.com'],
            [
                'name' => 'Fatima Abdou',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );

        $this->command->info('✅ Commerçants créés : ali@test.com, fatima@test.com');

        // ─────────────────────────────────────
        // 3. Créer des commerces
        // ─────────────────────────────────────
        $boutiqueAli = Shop::firstOrCreate(
            ['slug' => 'boutique-ali'],
            [
                'name' => 'Boutique Ali',
                'island' => 'Grande Comore',
                'city' => 'Moroni',
                'address' => 'Volo Volo, près du marché',
                'phone' => '333 00 01',
                'is_active' => true,
            ]
        );

        $epicerieFatima = Shop::firstOrCreate(
            ['slug' => 'epicerie-fatima'],
            [
                'name' => 'Épicerie Fatima',
                'island' => 'Anjouan',
                'city' => 'Mutsamudu',
                'address' => 'Centre-ville',
                'phone' => '333 00 02',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Commerces créés : Boutique Ali, Épicerie Fatima');

        // ─────────────────────────────────────
        // 4. Associer les utilisateurs aux commerces
        // ─────────────────────────────────────
        $boutiqueAli->members()->syncWithoutDetaching([$ali->id]);
        $epicerieFatima->members()->syncWithoutDetaching([$fatima->id]);

        $this->command->info('✅ Associations user-shop effectuées');
        $this->command->newLine();
        $this->command->info('🎉 Données de test prêtes !');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe', 'Commerce'],
            [
                ['Super Admin', 'admin@komorshop.com', 'password', '-'],
                ['Commerçant', 'ali@test.com', 'password', 'Boutique Ali'],
                ['Commerçant', 'fatima@test.com', 'password', 'Épicerie Fatima'],
            ]
        );
    }
}