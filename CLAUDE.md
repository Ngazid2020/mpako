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
| `StockMovementObserver` | Creating | Verrouille le produit (`lockForUpdate`), **refuse un produit d'un autre shop**, calcule `stock_before/after` (plancher 0, écart journalisé), set `shop_id` + `user_id` |
| `StockMovementObserver` | Created | Met à jour `product.stock_qty` ; notification stock faible **au franchissement du seuil**, après commit |
| `PurchaseObserver` | Updated (pending → completed) | Mouvements 'in', `buy_price`, `supplier.balance += debt_amount` |
| `PurchaseObserver` | Updated (→ cancelled) | Si l'achat était validé : mouvements 'out' + `supplier.balance -= debt_amount`. Dans tous les cas `debt_amount = 0` |
| `PurchaseObserver` | Deleting | **Refuse** la suppression d'un achat validé (il faut l'annuler) |
| `Purchase` (modèle) | Saving | `payment_status` dérivé de `paid/debt` (`Purchase::derivePaymentStatus`) |
| `CreditObserver` | Created | Augmente `customer.balance` |
| `CreditObserver` | Updating/Updated (total modifié) | Recalcule `remaining_amount` + statut, répercute l'écart sur `customer.balance` |
| `CreditObserver` | Deleted | Diminue `customer.balance` du restant |
| `CreditPaymentObserver` | Creating | Refuse un crédit soldé, **plafonne `amount` au reste dû**, complète `user_id` |
| `CreditPaymentObserver` | Created / Deleted | Met à jour le crédit (`updateQuietly`) et `customer.balance` du montant exact |
| `SupplierPaymentObserver` | Creating | Refuse un achat non validé ou sans fournisseur, plafonne `amount` à la dette, complète `supplier_id`/`user_id` |
| `SupplierPaymentObserver` | Created / Deleted | Met à jour l'achat et `supplier.balance` (`deleted` ignoré si l'achat n'est plus validé) |

⚠️ **Un achat se crée toujours en `pending`** puis se valide *après* l'enregistrement de ses lignes. Exemples : `CreatePurchase::afterCreate()`, `SyncController::createPurchase`. Créé directement en `completed`, l'observer ne verrait aucune ligne.

### Rôles (ShopRolesSeeder)
- **owner** : toutes les permissions **sauf celles réservées au super-admin** (shops, users, roles, abonnements, portefeuilles en écriture). Garde `view/update_shop` et la lecture de son portefeuille.
- **manager** : comme owner, sans la gestion des employés et des rôles.
- **cashier** : vue produit, création vente/crédit, vue/création client, `page_Caisse`.
- ⚠️ Les rôles Spatie sont **globaux** (`teams=false`) : un rôle s'applique dans tous les commerces de l'utilisateur. Le passage aux rôles par commerce est prévu dans le backlog (US-101).

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
| `/api/shop/{slug}/…` | sanctum + `ResolveShop` | Endpoints commerce + sync `pull`/`push` (voir `docs/04-API.md`) |

Contrôleur : `app/Http/Controllers/Api/AuthController.php`

Règles de l'API commerce (trait `Api\Concerns\InteractsWithShop`) :
- `authorizeAny($request, 'create_purchase', …)` dans chaque action, avec les mêmes noms de permissions Shield que les policies.
- Tout identifiant reçu est validé avec `inShop('table', $shop)`, l'équivalent de `exists` filtré par `shop_id`.
- Les ventes passent par `App\Services\SaleRecorder`, côté REST comme côté sync : totaux recalculés, stock agrégé.
- `push` est idempotent grâce à la table `sync_operations`, avec une contrainte unique sur `(shop_id, local_id)`.
- `ResolveShop` revérifie `is_approved` à chaque requête.

---

## Setup développement

```bash
# Cloner et installer
composer install && npm install

# Environnement
cp .env.example .env
php artisan key:generate

# Base de données — l'ORDRE compte (BeezSeeder a besoin des rôles, les rôles des permissions)
php artisan migrate
php artisan db:seed                              # DatabaseSeeder : admin@beez.km / password
php artisan shield:generate --all                # Permissions Shield (à relancer si nouvelles resources)
php artisan db:seed --class=ShopRolesSeeder      # Crée les 3 rôles
php artisan db:seed --class=BeezSeeder           # Démo : commerçants +2699000001 / +2699000002 (password)
# puis éventuellement StockSeeder, PurchaseSeeder, CreditSeeder, ExpenseSeeder

# Lancer
php artisan serve
npm run dev

# Tests (SQLite :memory: — ne touche jamais la base de dev)
php artisan test
```

Compte admin par défaut : `admin@beez.km` / `password`

⚠️ Ne jamais lancer `migrate:fresh` ou de tests avec `RefreshDatabase` sur la base MySQL de dev (`.env`). Les tests utilisent `phpunit.xml` (SQLite en mémoire).

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
- **Filament multi-tenant** : les `SelectFilter::relationship()` ne sont **pas** filtrés automatiquement par tenant. Il faut toujours passer `fn ($q) => $q->where('shop_id', Filament::getTenant()?->getKey())`.
- **Caisse** : `$cart` est un état Livewire public, donc modifiable par le client. Les prix sont relus en base (`rebuildCartFromDatabase()`) avant encaissement.
- **Portefeuille** : le solde ne change que via `Wallet::credit()` / `debit()` (transaction + verrou). Les `WalletTransaction` forment un journal en ajout seul.
- **Ressources Admin masquées** (`shouldRegisterNavigation = false`) : elles n'existent que pour Shield et utilisent le trait `ShieldOnlyResource`, en lecture seule.
- **Filament 3** : `readOnly()` n'existe que sur `TextInput`/`Textarea`. Pour un `Select`/`Toggle`, utiliser `disabled()`. C'est `modalHeading()` (pas `modalTitle()`) et `auth()->user()?->can()` (pas `auth()->can()`).
- **Mode hors ligne web** (`/commerce/{slug}/hors-ligne`, `resources/js/offline/`) : application Alpine autonome. Toute écriture passe par la file d'opérations de la sync (`push`), jamais par un endpoint ad hoc.
  - Pour une nouvelle action hors ligne, il faut :
    1. ajouter le type d'opération dans `SyncController` (`OPERATION_PERMISSIONS`, `match`, handler) ;
    2. ajouter son effet local dans `operations.js` (`handlers`) ;
    3. l'ajouter à `OPERATION_LABELS` ;
    4. ajouter un test dans `OfflineModeTest`.
  - Un élément créé hors ligne se référence par `<x>_local_id`.
  - Si `public/serviceworker.js` change, incrémenter `CACHE_NAME`. Toujours faire `response.clone()` **avant** de rendre la réponse au navigateur.
  - Toute modification du mode hors ligne se valide avec `npm run build` puis `npm run test:e2e` (Chrome réel). Les tests PHP ne voient ni le service worker ni IndexedDB. Dans les tests puppeteer, couper le réseau avec `setOffline()` (page **et** service worker), jamais `page.setOfflineMode()` seul.
- **Création de boutique** : toujours passer par `App\Services\ShopOnboarding::createShop()`. Il gère le rattachement, le rôle `owner` (seulement sans rôle existant), le portefeuille et l'essai gratuit d'un mois (une fois par utilisateur). Il est utilisé par l'inscription « Commencer » (`Pages/Auth/Register`) et par `RegisterShop`.
- **CI/CD** : `.github/workflows/ci.yml` (tests PHP + E2E bloquants, Pint informatif) et `deploy.yml` (SSH, `scripts/deploy.sh`). Une nouvelle commande de déploiement s'ajoute dans `scripts/deploy.sh`. Les données du test E2E viennent de `E2EOfflineSeeder`. Voir `docs/06-CICD.md`.
- **Méthodologie** : Agile/Scrum. Backlog dans `docs/scrum/PRODUCT-BACKLOG.md`, Definition of Done dans `docs/scrum/SCRUM-GUIDE.md`. Toute nouvelle page Filament s'ajoute à `tests/Feature/FilamentSmokeTest.php`.

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
