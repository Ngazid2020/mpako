# BeeZ — Gestion commerciale pour les commerçants comoriens

BeeZ (nom de code du dépôt : **mpako**) est un ERP de gestion commerciale multi-commerce, pensé pour le téléphone. Il gère la caisse, le stock, les achats fournisseurs, les crédits clients, les dépenses et les rapports, en **KMF**. Il est complété par l'application mobile hors-ligne `beez-mobile` (Expo).

- **Stack** : Laravel 11 · Filament 3.3 · Spatie Permission + Filament Shield · Sanctum · MySQL (prod) / SQLite (dev et tests) · PWA
- **Panels** :
  - `/commerce/{slug}` : commerçants, multi-tenant par commerce ;
  - `/admin` : super-admin BeeZ.
- **API mobile** : `/api`, avec authentification par token Sanctum (30 jours) et synchronisation offline `pull`/`push`.
- **Inscription en libre-service** : le bouton « Commencer » ouvre `/commerce/register`. Le compte et la boutique sont créés en une seule fois, avec **1 mois d'essai gratuit**.
- **Mode hors ligne web** : `/commerce/{slug}/hors-ligne`. Caisse, produits, stock, clients, achats, fournisseurs et dépenses restent utilisables sans connexion, puis se synchronisent automatiquement au retour du réseau. Il faut HTTPS en production, et l'avoir ouvert une fois en ligne sur l'appareil.

## Démarrage rapide

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate

# Base de données (SQLite par défaut en dev)
php artisan migrate
php artisan db:seed                                # super-admin : admin@beez.km / password
php artisan shield:generate --all                  # génère les permissions des ressources
php artisan db:seed --class=ShopRolesSeeder        # rôles owner / manager / cashier
php artisan db:seed --class=BeezSeeder             # (optionnel) données de démo : commerçant +2699000001 / password

php artisan serve
npm run dev        # pour tester le mode hors ligne, utiliser plutôt `npm run build` (assets mis en cache)
```

> L'ordre compte : `BeezSeeder` a besoin des rôles, qui ont eux-mêmes besoin des permissions.

Tâche planifiée : `mpako:send-alerts`, chaque jour à 08:00 (stock bas, crédits en retard, dettes fournisseurs). En production, lancer `php artisan schedule:work` ou un cron sur `schedule:run`.

## Tests

```bash
php artisan test
```

Les tests utilisent SQLite en mémoire (`phpunit.xml`) et ne touchent jamais la base de développement. Ils couvrent :
- l'API mobile et la sync ;
- le cycle de vie des achats ;
- les crédits ;
- un smoke test des pages Filament.

## Documentation

L'index complet est dans [`docs/`](docs/README.md) :

- [PRD](docs/01-PRD.md)
- [TDR](docs/02-TDR.md)
- [Architecture](docs/03-ARCHITECTURE.md)
- [API mobile](docs/04-API.md)
- [Rapport d'audit](docs/05-AUDIT-2026-11.md)
- Scrum : [cadre](docs/scrum/SCRUM-GUIDE.md), [backlog](docs/scrum/PRODUCT-BACKLOG.md), sprints [1](docs/scrum/SPRINT-01.md) et [2](docs/scrum/SPRINT-02.md)
- [CHANGELOG](CHANGELOG.md)

## Conventions essentielles

- Toujours filtrer les données par `shop_id` (formulaires, filtres, API : règle `inShop()`).
- Ne jamais contourner les observers : stock, dettes clients et dettes fournisseurs sont maintenus **uniquement** par eux.
- Un achat se crée en `pending` et se valide **après** l'enregistrement de ses lignes.
- Chaque action protégée vérifie une permission Shield.

Les détails sont dans [CLAUDE.md](CLAUDE.md).
