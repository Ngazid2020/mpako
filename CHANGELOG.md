# Changelog

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/). Les identifiants (`A01`, `D02`…) renvoient au [rapport d'audit](docs/05-AUDIT-2026-11.md).

## [Non publié] — Sprint 2 « Mode hors ligne web » (2026-11-09)

### ⚠️ À faire au déploiement
- `php artisan migrate` : ajoute `subscription_plans.is_trial`.
- `npm run build`, car de nouveaux assets sont ajoutés (`resources/css/offline.css`, `resources/js/offline/app.js`).
- HTTPS obligatoire en production pour le service worker.
- Le service worker passe en `beez-v2` : l'ancien cache est purgé automatiquement.

### Ajouté
- **Mode hors ligne de la plateforme** (`/commerce/{slug}/hors-ligne`), application autonome Alpine.js + IndexedDB mise en cache par le service worker. Fonctions disponibles sans connexion :
  - caisse : comptant, crédit, remises ;
  - produits : création, modification, entrée, sortie et inventaire de stock ;
  - clients et remboursements de crédit ;
  - achats : création, validation, paiement ;
  - fournisseurs : création, paiement ;
  - dépenses.
- Synchronisation automatique au retour du réseau (et toutes les 30 s), file d'opérations avec effets visibles immédiatement, onglet « Sync » (opérations refusées avec motif, Réessayer / Abandonner, effacement des données du poste).
- Entrée de menu « Mode hors ligne » et **bascule automatique** du panel vers le mode hors ligne quand la connexion tombe : compte à rebours de 5 s, annulable. La coupure est détectée par l'événement navigateur et par une sonde `/up` (Wi-Fi sans Internet). Le cache est préchargé automatiquement. Au retour du réseau, un bandeau « tout est synchronisé » propose de revenir à la plateforme.
- Token API par poste : `POST /commerce/{slug}/hors-ligne/token`.
- Sync push : opérations `create_product`, `update_product`, `create_customer`, `create_supplier`.
- Sync push : références `*_local_id` (client, fournisseur, produit, achat, crédit, vente) résolues via `sync_operations`, dans un même lot ou d'un lot à l'autre (US-402).
- Sync pull : `categories`, `units`, et paramètre `light=1`.
- Tests : `OfflineModeTest`, `CaisseProductListTest`.

- **Inscription en libre-service avec essai gratuit d'un mois** : le bouton « Commencer » mène à un formulaire unique, compte + boutique. Le compte est actif immédiatement et l'utilisateur connecté sur sa boutique. À la création, il reçoit :
  - le rôle `owner` (s'il n'a aucun rôle) ;
  - un portefeuille ;
  - un abonnement « Essai gratuit » (0 KMF, 1 mois, une seule fois par utilisateur).

  Un bandeau « Essai gratuit : encore N jours » s'affiche dans le panel. La logique est centralisée dans `App\Services\ShopOnboarding`, aussi utilisée pour « Créer un autre commerce ».
- Migration `subscription_plans.is_trial`. Le forfait d'essai est créé automatiquement.
- Tests : `ShopSignupTrialTest` (39 tests PHP au total) et **test navigateur de bout en bout** du mode hors ligne : `npm run test:e2e` (`tests/e2e/offline-mode.mjs`, puppeteer-core + Chrome).

### Modifié
- Les nouveaux inscrits ne passent plus par la validation admin (`is_approved = true` d'office). La page « compte en attente » n'est plus utilisée à l'inscription.
- Thème Filament Commerce : les vues `resources/views/filament/components/**` sont incluses dans la compilation Tailwind.
- Caisse (« Boutique ») : la liste des produits actifs est affichée dès l'ouverture (en stock d'abord, 60 au maximum) ; la recherche ne fait que filtrer.
- Service worker : assets `/build/assets/*` en cache-first ; sans réseau, une page du commerce affiche l'application hors ligne au lieu de la page générique `/offline`.

### Corrigé
- **Mode hors ligne inaccessible ou vide** (constaté en test réel) :
  - le service worker copiait la réponse **après** l'avoir rendue au navigateur, donc la page `/hors-ligne` n'était jamais mise en cache ;
  - les données (token, produits, clients…) n'étaient téléchargées qu'à l'ouverture manuelle du mode hors ligne.

  Désormais, le panel prépare tout en arrière-plan (iframe invisible, toutes les 15 min) et le cache passe en `beez-v3`. `manifest.json` est aussi mis en cache.
- Les invités sur une route web protégée hors panel (PDF, mode hors ligne) obtenaient une erreur 500 (`Route [login] not defined`). Ils sont maintenant redirigés vers `/commerce/login`.

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
