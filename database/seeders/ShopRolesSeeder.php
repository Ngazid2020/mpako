<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShopRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ═══════════════════════════════════════════
        // 1. RÔLE OWNER — Propriétaire du commerce
        // ═══════════════════════════════════════════
        $owner = Role::firstOrCreate([
            'name'       => 'owner',
            'guard_name' => 'web',
        ]);

        // Toutes les permissions du panel commerce
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $owner->syncPermissions($allPermissions);

        $this->command->info('✅ Rôle OWNER : ' . $allPermissions->count() . ' permissions');

        // ═══════════════════════════════════════════
        // 2. RÔLE MANAGER — Gérant
        // ═══════════════════════════════════════════
        $manager = Role::firstOrCreate([
            'name'       => 'manager',
            'guard_name' => 'web',
        ]);

        // Le manager a TOUT sauf gérer les employés
        $managerPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'not like', '%_employee')
            ->where('name', 'not like', '%_any_employee')
            ->where('name', 'not like', '%_role')
            ->where('name', 'not like', '%_any_role')
            ->get();

        $manager->syncPermissions($managerPermissions);

        $this->command->info('✅ Rôle MANAGER : ' . $managerPermissions->count() . ' permissions');

        // ═══════════════════════════════════════════
        // 3. RÔLE CASHIER — Caissier
        // ═══════════════════════════════════════════
        $cashier = Role::firstOrCreate([
            'name'       => 'cashier',
            'guard_name' => 'web',
        ]);

        // Permissions limitées
        $cashierPermissionNames = [
            // Produits : lecture seule
            'view_any_product',
            'view_product',

            // Ventes : voir et créer
            'view_any_sale',
            'view_sale',
            'create_sale',

            // Crédits : voir et créer (vente à crédit)
            'view_any_credit',
            'view_credit',
            'create_credit',

            // Clients : voir et créer
            'view_any_customer',
            'view_customer',
            'create_customer',
        ];

        $cashierPermissions = Permission::whereIn('name', $cashierPermissionNames)
            ->where('guard_name', 'web')
            ->get();

        $cashier->syncPermissions($cashierPermissions);

        $this->command->info('✅ Rôle CASHIER : ' . $cashierPermissions->count() . ' permissions');

        // ═══════════════════════════════════════════
        // RÉSUMÉ
        // ═══════════════════════════════════════════
        $this->command->newLine();
        $this->command->info('🎉 Rôles créés avec succès !');
        $this->command->table(
            ['Rôle', 'Permissions', 'Description'],
            [
                ['👑 owner',   $owner->permissions->count(),   'Propriétaire — Accès total'],
                ['💼 manager', $manager->permissions->count(), 'Gérant — Tout sauf employés'],
                ['🛒 cashier', $cashier->permissions->count(), 'Caissier — Caisse + crédits'],
            ]
        );

        $this->command->newLine();
        $this->command->warn('📝 Le super-admin peut ajuster les permissions depuis :');
        $this->command->line('   /admin/shield/roles');
    }
}