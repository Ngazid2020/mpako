<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShopRolesSeeder extends Seeder
{
    /** Ressources réservées au super-admin (suffixe de permission Shield) */
    private const ADMIN_ONLY_SUFFIXES = [
        'shop', 'user', 'role',
        'subscription', 'subscription::plan',
        'wallet', 'wallet::transaction',
    ];

    private const ADMIN_ONLY_PERMISSIONS = [
        'widget_AdminStatsOverview',
        'widget_ShopGrowthChart',
        'approve_wallet',
        'reject_wallet',
    ];

    private const OWNER_EXCEPTIONS = [
        'view_shop', 'update_shop',
        'view_any_wallet', 'view_wallet',
        'view_any_wallet::transaction', 'view_wallet::transaction',
    ];

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

        // Toutes les permissions du panel commerce — SAUF celles réservées au super-admin
        // (gestion des commerces, utilisateurs, rôles, abonnements, portefeuilles, widgets admin)
        $allPermissions = Permission::where('guard_name', 'web')
            ->where(function ($q) {
                foreach (self::ADMIN_ONLY_SUFFIXES as $suffix) {
                    $q->where('name', 'not like', '%_' . $suffix);
                }
            })
            ->whereNotIn('name', self::ADMIN_ONLY_PERMISSIONS)
            ->get()
            // …mais le commerçant garde la gestion de SON profil et la lecture de SON portefeuille
            ->merge(Permission::where('guard_name', 'web')->whereIn('name', self::OWNER_EXCEPTIONS)->get())
            ->unique('id')
            ->values();
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
        $managerPermissions = $allPermissions
            ->reject(fn ($p) => str_ends_with($p->name, '_employee') || str_ends_with($p->name, '_role'))
            ->values();

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

            // Accès à la caisse (page protégée par HasShieldPermissionPages)
            'page_Caisse',
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