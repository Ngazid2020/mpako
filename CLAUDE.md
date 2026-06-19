# MPAKO — Contexte Projet (ERP Commerce Multi-Tenant)

## Vue d'ensemble

**Mpako** est un ERP de gestion commerciale multi-tenant pour commerçants comoriens (KMF).  
Stack : Laravel 11 + Filament 3.3 + SQLite (dev) + Sanctum (API mobile) + Spatie Permission (RBAC).

**Deux panels Filament distincts :**
- `/admin` — Super-admin uniquement (`is_admin = true`), couleur Emerald
- `/commerce` — Commerçants, multi-tenant via `Shop::class`, couleur Blue

---

## Architecture

### Multitenancy
Chaque donnée métier est isolée par `shop_id`. Le tenant est le modèle `Shop`. L'URL de commerce inclut le slug du shop : `/commerce/{shop_slug}/...`.  
Un user peut appartenir à plusieurs shops (pivot `shop_user`).

### Observers — automatisation critique
Enregistrés dans `AppServiceProvider`. **Ne jamais bypasser les observers**, ils maintiennent la cohérence des données :

| Observer | Déclencheur | Effet |
|----------|------------|-------|
| `StockMovementObserver` | Creating | Calcule `stock_before/after`, set `shop_id` + `user_id` |
| `StockMovementObserver` | Created | Met à jour `product.stock_qty` |
| `PurchaseObserver` | Updated (→ completed) | Crée mouvements stock 'in', update prix achat, augmente `supplier.balance` |
| `PurchaseObserver` | Updating (→ cancelled) | Crée mouvements stock 'out' inversés |
| `CreditObserver` | Created | Augmente `customer.balance` |
| `CreditObserver` | Deleted | Diminue `customer.balance` du restant |
| `CreditPaymentObserver` | Created | Update `credit.paid_amount/remaining/status`, diminue `customer.balance` |
| `CreditPaymentObserver` | Deleted | Annule les effets |
| `SupplierPaymentObserver` | Created | Update `purchase.paid_amount/debt/payment_status`, diminue `supplier.balance` |
| `SupplierPaymentObserver` | Deleted | Annule les effets |

### Rôles (ShopRolesSeeder)
- **owner** : Toutes les permissions
- **manager** : Tout sauf gestion employés/rôles
- **cashier** : Vue produit, création vente/crédit, vue client

---

## Modèles clés

```
User           is_admin (bool) | BelongsToMany(Shop) | HasRoles
Shop           name, slug, island, city, currency, is_active
Product        shop_id, category_id, unit_id | buy_price, sell_price, stock_qty, stock_alert
Sale           reference VNT-YYYYMMDD-0001 | status (completed/cancelled)
SaleItem       sale_id, product_id, quantity, unit_price, subtotal
Purchase       reference ACH-YYYYMMDD-0001 | status (pending/completed/cancelled)
               payment_status (unpaid/partial/paid)
PurchaseItem   purchase_id, product_id, quantity, unit_cost, subtotal
Customer       balance (dette cumulée), hasDebt()
Supplier       balance (dette cumulée), hasDebt()
Credit         reference CRD-YYYYMMDD-0001 | status (pending/partial/paid) | isOverdue()
CreditPayment  credit_id, amount, paid_at
SupplierPayment purchase_id, amount, paid_at
StockMovement  type (in/out/adjustment), stock_before, stock_after
Expense        shop_id, expense_category_id, amount, spent_at
```

**Références** : Format `TYPE-YYYYMMDD-XXXX`, scope unique par `(shop_id, reference)`, reset quotidien. Générées via `Model::generateReference($shopId)`.

**Décimales** : `decimal(10, 2)` pour tous les montants. Carbon en locale `fr`.

---

## Filament — Panels & Resources

### Admin Panel (`app/Filament/Admin/`)
Resources : ShopResource, UserResource, EmployeeResource, CategoryResource, ProductResource, SaleResource, PurchaseResource, SupplierResource, CustomerResource, CreditResource, ExpenseCategoryResource, ExpenseResource, StockMovementResource, UnitResource

### Commerce Panel (`app/Filament/Commerce/`)
Resources : ProductResource, SaleResource, PurchaseResource, CreditResource, CustomerResource, SupplierResource, StockMovementResource, ExpenseResource, ExpenseCategoryResource, CategoryResource, UnitResource

Pages spéciales :
- `RegisterShop` — enregistrement nouveau commerce (page registration custom)
- `EditShop` — profil tenant (accessible dans Tenant profile)
- `Dashboard` — tableau de bord commerce

**PurchaseResource** est la resource la plus complexe : repeater pour les items, recalcul dynamique des totaux (`recalculateTotals()`), actions `complete` / `cancel` / `pay_debt`.

---

## API (Sanctum)

Base : `/api`  
Token durée : 30 jours

| Endpoint | Auth | Description |
|----------|------|-------------|
| POST `/api/auth/login` | — | Login, retourne user + shops + token |
| POST `/api/auth/logout` | sanctum | Supprime le token courant |
| GET `/api/auth/me` | sanctum | User + shops |

Contrôleur : `app/Http/Controllers/Api/AuthController.php`

---

## Setup développement

```bash
# Cloner et installer
composer install && npm install

# Environnement
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate
php artisan db:seed --class=DatabaseSeeder       # admin@komorshop.com / password
php artisan db:seed --class=ShopRolesSeeder      # Crée les 3 rôles
php artisan db:seed --class=KomorShopSeeder      # Données de démo

# Permissions Filament-Shield (à relancer si nouvelles resources)
php artisan shield:generate --all

# Lancer
php artisan serve
npm run dev
```

Compte admin par défaut : `admin@komorshop.com` / `password`

---

## Fichiers de référence rapide

| Sujet | Fichier |
|-------|---------|
| Panels Filament | `app/Providers/Filament/AdminPanelProvider.php` |
| | `app/Providers/Filament/CommercePanelProvider.php` |
| Observers | `app/Observers/` |
| Policies (RBAC) | `app/Policies/` |
| Rôles shop | `database/seeders/ShopRolesSeeder.php` |
| API auth | `app/Http/Controllers/Api/AuthController.php` |
| Routes API | `routes/api.php` |
| Modèle tenant | `app/Models/Shop.php` |
| Vente (caisse) | `app/Filament/Commerce/Resources/SaleResource.php` |
| Achat complexe | `app/Filament/Commerce/Resources/PurchaseResource.php` |

---

## Conventions & points d'attention

- **Jamais de delete cascade manuel** sur les données liées à un shop — les observers gèrent la cohérence.
- **Toujours scoper par `shop_id`** dans les queries et forms pour éviter les fuites de données cross-tenant.
- **Filament-Shield** : après ajout d'une nouvelle Resource, relancer `php artisan shield:generate --all` pour générer les permissions.
- **Les prix d'achat** (`buy_price`) des produits sont mis à jour automatiquement par `PurchaseObserver` lors de la validation d'un achat.
- **`customer.balance` et `supplier.balance`** sont des dettes (positif = doit de l'argent) — maintenu exclusivement par les observers.
- **Références de vente** : créées dans `Sale::generateReference()`, ne jamais les générer manuellement.
- Le projet cible les **Comores** — noms d'îles (Grande Comore, Anjouan, Mohéli, Mayotte), devise **KMF**.

---

## Dépendances majeures

| Package | Version | Rôle |
|---------|---------|------|
| `laravel/framework` | ^11.31 | Framework |
| `filament/filament` | 3.3 | Admin UI |
| `bezhansalleh/filament-shield` | ^3.9 | RBAC graphique |
| `spatie/laravel-permission` | (via shield) | Rôles/Permissions |
| `laravel/sanctum` | ^4.0 | Auth API token |
| `silviolleite/laravelpwa` | ^2.0 | Support PWA |
| Tailwind CSS | ^3.4.19 | Styling |
| Vite | ^6.0.11 | Bundler |
