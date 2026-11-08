# Changelog

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Les identifiants (`A01`, `D02`…) renvoient au [rapport d'audit](docs/05-AUDIT-2026-11.md).

## [Non publié] — Sprint 1 « Stabilisation » (2026-11-09)

### ⚠️ À faire au déploiement
- `php artisan migrate` : ajoute `sync_operations`, `shops.subscription_expires_at`, l'unique `wallets.shop_id`, et la migration `wallets.status` qui était en attente.
- `php artisan shield:generate --all && php artisan db:seed --class=ShopRolesSeeder`.
- Mettre à jour l'app mobile `beez-mobile`. Voir [docs/04-API.md](docs/04-API.md), section des notes de migration.

### Sécurité
- API : chaque endpoint et chaque opération de sync vérifie une permission Shield. Un caissier ne peut plus acheter, ajuster le stock ou payer les fournisseurs (A06).
- API et sync : tous les identifiants reçus (produit, client, fournisseur, catégorie, unité) sont vérifiés comme appartenant au commerce (A02, A03).
- `StockMovementObserver` refuse un mouvement sur le produit d'un autre commerce (D11).
- `ResolveShop` revérifie `is_approved` à chaque requête API (A14).
- Caisse : prix et remises reconstruits côté serveur avant l'encaissement (C01).
- Filtres Filament (clients, fournisseurs, catégories) limités au commerce courant (C04).
- PDF des rapports soumis à la permission `page_*Report` (C14).
- Rôle `owner` sans permissions super-admin ; `cashier` avec `page_Caisse` (C05, C06).
- Portefeuille : en lecture seule pour le commerçant ; journal des transactions en ajout seul ; validation réservée au super-admin (W03–W08).
- Ressources Admin masquées passées en lecture seule (trait `ShieldOnlyResource`) (W16).
- Sync : les erreurs internes (SQL) ne sont plus renvoyées au client (A15).

### Corrigé
- Sync `push` idempotente via `sync_operations` (A01).
- `purchases/{id}/complete` et `/pay` : mauvaise signature qui provoquait une TypeError (A05).
- `credits/{id}/pay`, `suppliers/{id}/pay`, `POST purchases`, `POST expenses` : violations NOT NULL (A04).
- Paiement fournisseur global réparti en FIFO sur les achats validés (A09).
- Un achat créé directement « validé » (Filament, sync) met bien à jour le stock et la dette (D02, A07).
- Annulation d'un achat en attente : la balance fournisseur n'est plus faussée (D03).
- Paiements plafonnés avant insertion, donc une suppression annule exactement l'effet (D04).
- Supprimer un paiement d'un achat annulé ne fait plus « ressusciter » sa dette (D05).
- Modifier le total d'un crédit met à jour la balance client et le statut (D06).
- Verrou sur la ligne produit lors des mouvements de stock (D07).
- Un achat validé ne peut plus être supprimé, individuellement ou en lot (D08, C10).
- Double validation d'un achat (double clic) (C16).
- Validation depuis l'édition d'un achat : stock calculé sur les nouvelles lignes (C02).
- `payment_status` toujours dérivé de `paid_amount` et `debt_amount` (D10).
- Caisse : remise conservée au ré-ajout d'un produit ; remise en % plafonnée à 100 (C07, C08).
- Pull incrémental : plus de limite à 200 lignes ; `synced_at` capturé avant les requêtes (A13).
- Module Portefeuille : crashs `readOnly()`, `WalletStatus::from()`, `modalTitle()`, `auth()->can()` (W01, W02, S01, S02).
- Admin : action « Retirer » un employé, tri sur le solde, bouton « Recharger », suppression d'un forfait utilisé (W09–W13).
- Notification de stock faible : envoyée au franchissement du seuil seulement, après commit (D12).

### Ajouté
- `App\Services\SaleRecorder` : enregistrement de vente unique pour l'API REST et la sync.
- `App\Http\Controllers\Api\Concerns\InteractsWithShop` : `shop()`, `authorizeAny()`, `inShop()`.
- Modèle `SyncOperation` et migration.
- `Purchase::syncTotalsFromItems()`, `Purchase::derivePaymentStatus()`, `Credit::deriveStatus()`.
- Tests : `CommerceApiTest`, `SyncPushTest`, `PurchaseLifecycleTest`, `CreditConsistencyTest`, `FilamentSmokeTest` (28 tests).
- Documentation `docs/` : PRD, TDR, architecture, API, audit, cadre Scrum, backlog, compte rendu du Sprint 1.

### Modifié
- `phpunit.xml` : tests sur SQLite `:memory:` (ils visaient auparavant la base MySQL de développement).
- Fiche produit : le stock n'est plus modifiable après création (passer par un mouvement de stock).
- Annulation d'un achat : `paid_amount` est conservé (historique des paiements). Seule la dette est soldée.
