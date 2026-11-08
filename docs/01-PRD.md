# BeeZ — Product Requirements Document (PRD)

| Élément | Valeur |
|---|---|
| Produit | **BeeZ** — gestion commerciale pour commerçants comoriens (dépôt `mpako`) |
| Version du document | 1.0 — 9 novembre 2026 |
| Statut | Base de référence après le Sprint 1 (stabilisation) |
| Product Owner | À désigner (par défaut : porteur du projet, Ngazid) |
| Documents liés | [Architecture](03-ARCHITECTURE.md) · [API](04-API.md) · [Backlog produit](scrum/PRODUCT-BACKLOG.md) · [Audit 2026-11](05-AUDIT-2026-11.md) · [TDR](02-TDR.md) |

> Statuts utilisés dans ce document :
> **Livré** = disponible et vérifié · **Partiel** = disponible avec limites connues · **À venir** = non implémenté (backlog).
> Priorités **MoSCoW** : Must / Should / Could / Won't (pour cette version).

---

## 1. Contexte et problème

### 1.1 Contexte

Aux Comores (Grande Comore, Anjouan, Mohéli, et la communauté de Mayotte), l'essentiel du commerce de détail repose sur des **boutiques, épiceries et commerces de proximité** gérés par leur propriétaire, souvent avec un ou deux employés. La monnaie est le **franc comorien (KMF)**. La gestion est encore majoritairement faite sur cahier :

- les ventes du jour ne sont pas totalisées de façon fiable ;
- les **crédits clients** (« ardoises ») sont notés à la main et se perdent ou se contestent ;
- les **dettes fournisseurs** ne sont pas suivies, les approvisionnements ne sont pas rapprochés des paiements ;
- le stock réel est inconnu : ruptures non anticipées, pertes non détectées ;
- le commerçant ne connaît ni sa marge ni son résultat net.

La connectivité est **intermittente** (3G/4G instable, coupures électriques) et l'équipement principal est le **smartphone Android**. Les logiciels de caisse existants sont pensés pour d'autres marchés (devise, langue, prix, dépendance au réseau).

### 1.2 Problème à résoudre

> « Un commerçant comorien ne sait pas, à tout moment, ce qu'il a vendu, ce qu'on lui doit, ce qu'il doit, ce qu'il lui reste en stock et ce qu'il gagne réellement. »

### 1.3 Pourquoi maintenant

- Taux d'équipement smartphone élevé et adoption du paiement mobile (MVola, Huri Money…).
- Absence d'offre locale adaptée (KMF, français, usage hors-ligne, prix accessible).
- Base technique existante (BeeZ) fonctionnelle et désormais stabilisée (voir [Audit 2026-11](05-AUDIT-2026-11.md)).

---

## 2. Vision produit

> **BeeZ est le carnet de commerce numérique du commerçant comorien : simple comme un cahier, fiable comme un comptable, utilisable même sans réseau.**

Principes directeurs :

1. **Mobile d'abord** : chaque écran doit être utilisable d'une main sur un téléphone d'entrée de gamme.
2. **Hors-ligne d'abord** : une vente ne doit jamais être bloquée par le réseau (application mobile + synchronisation).
3. **Zéro saisie comptable** : le commerçant saisit des ventes, achats, crédits, dépenses ; BeeZ calcule stock, dettes et résultats automatiquement.
4. **Données sûres et isolées** : chaque commerce ne voit que ses propres données (multi-tenant strict).
5. **Local** : français, KMF, îles des Comores, fuseau `Indian/Comoro`, support WhatsApp.

---

## 3. Objectifs et indicateurs (KPI)

| # | Objectif | KPI | Cible (12 mois après mise en production) |
|---|---|---|---|
| O1 | Adoption | Commerces actifs (≥ 1 vente sur les 7 derniers jours) | 300 |
| O2 | Usage quotidien | % de commerces actifs enregistrant des ventes ≥ 5 jours / 7 | ≥ 60 % |
| O3 | Fiabilité des données | Écart `stock_qty` vs somme des mouvements de stock | 0 écart (contrôle automatique) |
| O4 | Fiabilité des dettes | Écart `customer.balance` vs Σ crédits restants ; `supplier.balance` vs Σ dettes d'achats validés | 0 écart |
| O5 | Robustesse hors-ligne | % d'opérations de sync en échec (hors refus métier) | < 0,5 % |
| O6 | Performance | Temps d'encaissement d'une vente (caisse web, 3G) | < 3 s (p95) |
| O7 | Monétisation | % de commerces actifs avec abonnement payé | ≥ 40 % |
| O8 | Satisfaction | NPS commerçants | ≥ 40 |
| O9 | Qualité logicielle | Couverture de tests sur les règles métier critiques (observers, API, sync) | 100 % des règles listées §9 couvertes |

---

## 4. Personas

### 4.1 Mariama — Propriétaire de boutique (rôle `owner`)

- 38 ans, épicerie à Moroni, 1 employé. Android milieu de gamme, forfait data limité.
- **Besoins** : savoir combien elle a gagné, qui lui doit de l'argent, quoi racheter ; contrôler son employé à distance.
- **Frustrations** : cahier de crédits illisible, fournisseurs qui réclament des montants qu'elle ne peut pas vérifier.
- **Usage BeeZ** : tableau de bord, rapports, crédits, achats, gestion des employés.

### 4.2 Said — Gérant (rôle `manager`)

- Gère une quincaillerie pour le compte du propriétaire (Anjouan).
- **Besoins** : enregistrer achats et dépenses, valider les livraisons, payer les fournisseurs, suivre le stock.
- **Restrictions** : ne gère ni les employés ni les rôles.

### 4.3 Fatima — Caissière (rôle `cashier`)

- 22 ans, utilise surtout la **caisse** (page « Boutique ») et l'application mobile.
- **Besoins** : encaisser vite, trouver un produit par nom ou code-barres, vendre à crédit à un client connu, encaisser un remboursement.
- **Restrictions** : lecture seule sur les produits ; pas d'accès aux achats, dépenses, rapports financiers.

### 4.4 Équipe BeeZ — Super-admin (`is_admin = true`)

- Exploite la plateforme : valide les inscriptions (`is_approved`), gère les commerces, les forfaits, les portefeuilles et les recharges.
- **Besoins** : vue globale, croissance des commerces, validation des portefeuilles, sécurité.

---

## 5. Périmètre

### 5.1 Dans le périmètre (version courante)

- Panel web **Commerce** (`/commerce/{slug}`) multi-commerce, installable en **PWA**.
- Panel web **Admin** (`/admin`) réservé aux super-admins.
- **API REST** Sanctum pour l'application mobile **BeeZ (Expo, `beez-mobile`)** avec **synchronisation hors-ligne** (pull/push).
- Modules : caisse/ventes, stock, achats, fournisseurs, crédits clients, dépenses, rapports (écran + PDF + Excel), employés & rôles, alertes, 2FA, abonnements & portefeuille (en cours).

### 5.2 Hors périmètre (cette version)

| Hors périmètre | Justification |
|---|---|
| Comptabilité générale (plan comptable, bilan, TVA) | Cible = petits commerces informels ; export possible plus tard |
| Paiement en ligne intégré (MVola/Huri API) | Dépend de partenariats opérateurs — backlog |
| Facturation électronique / devis | Non demandé par les utilisateurs pilotes |
| Multi-devises au sein d'un commerce | KMF uniquement (champ `currency` prévu) |
| E-commerce / boutique en ligne | Autre produit |
| Application iOS publiée | Expo le permet ; priorité Android |

---

## 6. Exigences fonctionnelles

### 6.1 Ventes et caisse (VEN)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-VEN-01 | Encaisser une vente depuis la page **Caisse** (« Boutique ») : liste des produits actifs **affichée dès l'ouverture** (en stock d'abord, puis par ordre alphabétique, 60 au maximum), filtrée par la recherche (nom ou code-barres), panier, quantité, montant reçu, monnaie rendue | Must | Livré |
| FR-VEN-02 | Ajout automatique au panier lors d'un scan de code-barres exact (caméra) | Should | Livré |
| FR-VEN-03 | Remise par ligne en **%** (plafonnée à 100 %) ou **montant fixe** (plafonné au sous-total) | Should | Livré |
| FR-VEN-04 | Les prix et totaux sont **recalculés côté serveur** à partir des prix en base avant encaissement (le panier navigateur n'est pas cru) | Must | Livré |
| FR-VEN-05 | Contrôle de stock au moment de l'encaissement, avec verrouillage (pas de survente concurrente) | Must | Livré |
| FR-VEN-06 | Vente **à crédit** : client obligatoire, création automatique d'un crédit, échéance et note optionnelles | Must | Livré |
| FR-VEN-07 | Référence unique `VNT-YYYYMMDD-XXXX` par commerce, remise à zéro quotidienne | Must | Livré |
| FR-VEN-08 | Ticket de caisse affiché après la vente | Should | Livré |
| FR-VEN-09 | Annulation d'une vente avec réintégration du stock et annulation du crédit associé | Should | À venir |
| FR-VEN-10 | Impression d'étiquettes produits (route existante, désactivée) | Could | Partiel |
| FR-VEN-11 | Paiement mixte (espèces + mobile money) | Could | À venir |

### 6.2 Stock et produits (STK)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-STK-01 | Gestion des produits : nom, code-barres, catégorie, unité, prix d'achat, prix de vente, seuil d'alerte | Must | Livré |
| FR-STK-02 | Catégories et unités propres à chaque commerce | Must | Livré |
| FR-STK-03 | Toute variation de stock passe par un **mouvement de stock** (`in` / `out` / `adjustment`) avec `stock_before` / `stock_after`, utilisateur et motif | Must | Livré |
| FR-STK-04 | Le stock initial n'est saisissable qu'à la création ; ensuite seules les corrections par mouvement sont possibles | Must | Livré |
| FR-STK-05 | Le stock ne devient jamais négatif (ramené à 0, écart journalisé) | Must | Livré |
| FR-STK-06 | Alerte de stock faible au **franchissement** du seuil (notification push/base) | Should | Livré |
| FR-STK-07 | Achat par colis : quantité × facteur de conversion (`conversion_qty`) → unités de détail | Should | Livré |
| FR-STK-08 | Désactivation d'un produit plutôt que suppression (préserver l'historique des ventes) | Should | À venir |
| FR-STK-09 | Inventaire physique guidé (comptage et écarts) | Could | À venir |

### 6.3 Achats (ACH)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-ACH-01 | Saisie d'un achat avec lignes (produit, quantité, coût unitaire), fournisseur optionnel, date | Must | Livré |
| FR-ACH-02 | Statuts `pending` → `completed` → `cancelled` ; un achat est **créé en attente** puis **validé après enregistrement des lignes** | Must | Livré |
| FR-ACH-03 | La validation met à jour le stock (entrées), le dernier prix d'achat produit et la dette fournisseur | Must | Livré |
| FR-ACH-04 | Totaux recalculés côté serveur à partir des lignes | Must | Livré |
| FR-ACH-05 | Statut de paiement `unpaid` / `partial` / `paid` dérivé automatiquement des montants | Must | Livré |
| FR-ACH-06 | Annulation : achat validé → sortie de stock compensatoire et retrait de la dette restante ; achat en attente → aucune incidence | Must | Livré |
| FR-ACH-07 | Un achat validé ne peut pas être supprimé (seulement annulé) | Must | Livré |
| FR-ACH-08 | Protection contre la double validation (clics simultanés) | Must | Livré |
| FR-ACH-09 | Référence `ACH-YYYYMMDD-XXXX` | Must | Livré |
| FR-ACH-10 | Saisie d'achat par lot (plusieurs produits rapidement) | Should | Livré |

### 6.4 Fournisseurs (FOU)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-FOU-01 | Fiche fournisseur (nom, téléphone, adresse) et **solde de dette** | Must | Livré |
| FR-FOU-02 | Paiement d'un achat validé, plafonné à la dette restante | Must | Livré |
| FR-FOU-03 | Paiement global d'un fournisseur réparti sur ses achats validés, du plus ancien au plus récent (FIFO) | Should | Livré (API) / À venir (web) |
| FR-FOU-04 | Suppression d'un paiement → annulation exacte de son effet (sauf achat annulé) | Must | Livré |
| FR-FOU-05 | Rapport « Dettes fournisseurs » | Must | Livré |

### 6.5 Crédits clients (CRD)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-CRD-01 | Fiche client et **solde de dette** (`balance`) | Must | Livré |
| FR-CRD-02 | Crédit autonome ou issu d'une vente à crédit, avec échéance | Must | Livré |
| FR-CRD-03 | Remboursements partiels ; statut `pending` / `partial` / `paid` | Must | Livré |
| FR-CRD-04 | Remboursement plafonné au reste dû ; refus sur un crédit soldé | Must | Livré |
| FR-CRD-05 | Modification du montant d'un crédit → recalcul du reste dû, du statut et du solde client | Must | Livré |
| FR-CRD-06 | Crédits en retard (`isOverdue`) et bientôt échus, mis en évidence | Should | Livré |
| FR-CRD-07 | Rappel automatique au client (SMS/WhatsApp) | Could | À venir |
| FR-CRD-08 | Référence `CRD-YYYYMMDD-XXXX` | Must | Livré |

### 6.6 Dépenses (DEP)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-DEP-01 | Saisie d'une dépense (montant, catégorie, description, date, note) | Must | Livré |
| FR-DEP-02 | Catégories de dépenses propres au commerce | Must | Livré |
| FR-DEP-03 | Widget et rapport des dépenses par catégorie | Should | Livré |

### 6.7 Rapports et tableau de bord (RAP)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-RAP-01 | Tableau de bord : statistiques du jour/mois, graphique des ventes, top produits, stock bas, alertes, dépenses | Must | Livré |
| FR-RAP-02 | Rapports : ventes par période, état du stock, crédits en cours, dettes fournisseurs, top produits, résultat net | Must | Livré |
| FR-RAP-03 | Export PDF de chaque rapport, soumis aux mêmes permissions que l'écran | Must | Livré |
| FR-RAP-04 | Export Excel des ventes, achats et crédits | Should | Livré |
| FR-RAP-05 | Page « Bénéfice » avec évolution sur 6 mois | Should | Partiel (voir FR-RAP-06) |
| FR-RAP-06 | Résultat calculé sur le **coût des marchandises vendues** (coût historique figé à la vente), et non sur « ventes − achats de la période » | Must | À venir |

### 6.8 Multi-commerce et comptes (MUL)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-MUL-01 | Inscription utilisateur (téléphone), validation par l'équipe BeeZ (`is_approved`) avant accès | Must | Livré |
| FR-MUL-02 | Création d'un commerce (nom, île, ville…) et profil du commerce modifiable | Must | Livré |
| FR-MUL-03 | Un utilisateur peut appartenir à plusieurs commerces et basculer de l'un à l'autre | Must | Livré |
| FR-MUL-04 | Isolation stricte des données par commerce (formulaires, filtres, API, rapports, PDF) | Must | Livré |
| FR-MUL-05 | Authentification à deux facteurs (2FA TOTP) | Should | Livré |
| FR-MUL-06 | Attribution automatique du rôle `owner` au créateur d'un commerce | Must | À venir (dépend de FR-EMP-03) |

### 6.9 Employés et rôles (EMP)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-EMP-01 | Trois rôles : `owner` (tout le périmètre commerce), `manager` (tout sauf employés/rôles), `cashier` (caisse, ventes, crédits, clients, lecture produits) | Must | Livré |
| FR-EMP-02 | Les permissions réservées à la plateforme (commerces, utilisateurs, rôles, forfaits, portefeuilles) ne sont jamais données aux rôles commerce (lecture de son portefeuille et profil de son commerce exceptés) | Must | Livré |
| FR-EMP-03 | **Rôle par commerce** : un utilisateur caissier dans le commerce B ne doit pas hériter de son rôle propriétaire du commerce A | Must | À venir (priorité Sprint 2) |
| FR-EMP-04 | Le propriétaire invite/retire ses employés depuis le panel Commerce | Should | Partiel |

### 6.10 API mobile et synchronisation hors-ligne (API)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-API-01 | Connexion par téléphone + mot de passe, jeton Sanctum valable 30 jours, limité à 5 tentatives/min | Must | Livré |
| FR-API-02 | Chaque requête re-vérifie : commerce actif, appartenance, compte approuvé | Must | Livré |
| FR-API-03 | Chaque endpoint applique les **mêmes permissions** que le panel web | Must | Livré |
| FR-API-04 | **Pull** : snapshot complet puis incrémental (`since`), sans perte de données entre deux synchronisations | Must | Livré |
| FR-API-05 | **Push** : file d'opérations hors-ligne exécutées dans l'ordre chronologique, chacune dans sa transaction | Must | Livré |
| FR-API-06 | **Idempotence** : une opération (`local_id`) rejouée n'est jamais appliquée deux fois | Must | Livré |
| FR-API-07 | Toute référence (produit, client, fournisseur, catégorie, vente) envoyée par le mobile est vérifiée comme appartenant au commerce | Must | Livré |
| FR-API-08 | Les erreurs renvoyées au mobile sont métier et lisibles, jamais techniques (SQL, pile) | Must | Livré |
| FR-API-09 | Pagination par curseur du snapshot initial (gros historiques) | Should | À venir |
| FR-API-10 | Résolution d'un élément créé hors ligne (client, fournisseur, produit, achat) référencé par son `local_id`, y compris dans un lot de sync ultérieur | Should | Livré |

Détail des endpoints : voir [04-API.md](04-API.md).

### 6.10 bis Mode hors ligne de la plateforme web (OFF)

Le panel web (Filament/Livewire) a besoin du réseau. Pour continuer à travailler sans connexion, la plateforme embarque une **application hors ligne** : `/commerce/{slug}/hors-ligne`. Elle est mise en cache par le service worker, stocke ses données dans le navigateur (IndexedDB) et réutilise la synchronisation de l'API mobile.

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-OFF-01 | Le propriétaire (et tout membre autorisé) peut ouvrir le mode hors ligne depuis le menu « Mode hors ligne » ; quand la connexion tombe sur une page du panel, une bannière propose d'y basculer | Must | Livré |
| FR-OFF-02 | Sans réseau, toute navigation vers une page du commerce affiche l'application hors ligne mise en cache (préchargée automatiquement depuis le panel) | Must | Livré |
| FR-OFF-03 | Hors ligne : **caisse** (comptant et crédit, remises), **produits** (création, modification, entrée/sortie/inventaire de stock), **clients** (création, remboursement de crédit), **achats** (création, validation, paiement), **fournisseurs** (création, paiement), **dépenses** | Must | Livré |
| FR-OFF-04 | Chaque opération est enregistrée localement et son effet est visible immédiatement (stock, dettes) | Must | Livré |
| FR-OFF-05 | **Synchronisation automatique** au retour de la connexion (et toutes les 30 s en ligne), sans doublon (idempotence par `local_id`), suivie d'un rechargement des données serveur | Must | Livré |
| FR-OFF-06 | Les éléments créés hors ligne peuvent être utilisés immédiatement hors ligne (vendre un produit, ou faire crédit à un client, créés sans réseau) | Must | Livré |
| FR-OFF-07 | Les opérations refusées par le serveur (permission, donnée invalide) sont listées avec leur motif, avec les actions « Réessayer » et « Abandonner » | Must | Livré |
| FR-OFF-08 | Les actions proposées respectent le rôle de l'utilisateur ; le serveur revérifie chaque permission | Must | Livré |
| FR-OFF-09 | Poste partagé : effacement des données locales et révocation de l'accès du poste | Should | Livré |
| FR-OFF-10 | Consultation hors ligne de l'historique des ventes et des rapports | Could | À venir |
| FR-OFF-11 | Annulation d'achat, suppression et modifications avancées hors ligne | Could | À venir |

**Pré-requis :**
- ouvrir le mode hors ligne (ou n'importe quelle page du panel) **au moins une fois avec une connexion** sur l'appareil, pour préparer le cache et les données ;
- le service worker exige HTTPS en production (ou `localhost` en développement).

### 6.11 Notifications et alertes (NOT)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-NOT-01 | Alertes quotidiennes à 08:00 : stock bas, crédits en retard, dettes fournisseurs (`mpako:send-alerts`) | Should | Livré |
| FR-NOT-02 | Notifications web push (VAPID) et notifications en base dans le panel | Should | Livré |
| FR-NOT-03 | Une erreur d'envoi de notification ne bloque jamais une vente | Must | Livré |
| FR-NOT-04 | Notifications WhatsApp/SMS | Could | À venir |

### 6.12 Abonnements et portefeuille (ABO)

| ID | Exigence | Priorité | Statut |
|---|---|---|---|
| FR-ABO-01 | Forfaits gérés par l'admin (nom, prix, durée, actif, par défaut). Données initiales : **Mensuel Standard 1 000 KMF / 30 j**, **Annuel Standard 10 000 KMF / 365 j** | Must | Livré |
| FR-ABO-02 | Un **portefeuille unique** par commerce ; solde modifié **uniquement** par des transactions journalisées | Must | Livré |
| FR-ABO-03 | Recharge par l'admin (espèces, MVola, autre), atomique et tracée (solde avant/après) | Must | Livré |
| FR-ABO-04 | Journal des transactions non modifiable et non supprimable (corrections par transaction inverse) | Must | Livré |
| FR-ABO-05 | Workflow du portefeuille : brouillon → soumis → validé / rejeté ; validation réservée au super-admin | Should | Partiel (pas de re-soumission après rejet) |
| FR-ABO-06 | Le commerçant consulte son portefeuille et ses transactions en lecture seule | Must | Livré |
| FR-ABO-07 | Création automatique du portefeuille à la création d'un commerce | Must | À venir |
| FR-ABO-08 | Souscription / renouvellement d'abonnement débitant le portefeuille et mettant à jour `subscription_expires_at` | Must | À venir |
| FR-ABO-09 | Restriction d'accès (lecture seule ou blocage) après expiration + période de grâce | Must | À venir |
| FR-ABO-10 | Limites par forfait (nombre de produits, d'utilisateurs) | Should | À venir |

> **Décision PO attendue** : la page d'accueil publique annonce une offre *Gratuit (0 KMF)* / *Pro (5 000 KMF/mois)*, alors que les forfaits initialisés en base sont *1 000 KMF/mois* et *10 000 KMF/an*. Une grille unique doit être arrêtée avant FR-ABO-08.

---

## 7. Exigences non fonctionnelles

| ID | Catégorie | Exigence |
|---|---|---|
| NFR-01 | Performance | Encaissement caisse < 3 s (p95) en 3G ; pages de liste < 2 s ; requêtes indexées sur `shop_id` + dates |
| NFR-02 | Hors-ligne | L'application mobile permet ventes, crédits, remboursements, dépenses, ajustements et achats sans réseau, puis synchronise automatiquement |
| NFR-03 | Données mobiles | Pull incrémental (seules les données modifiées) pour limiter la consommation de data |
| NFR-04 | Sécurité multi-tenant | Aucune donnée d'un commerce visible ou modifiable depuis un autre (formulaires, filtres, API, PDF, mouvements de stock) — testé automatiquement |
| NFR-05 | RBAC | Contrôle d'accès par permissions Filament Shield / Spatie identique sur web et API |
| NFR-06 | Authentification | Mots de passe hachés (bcrypt), 2FA optionnelle, jetons API expirant à 30 j, limitation des tentatives de connexion |
| NFR-07 | Intégrité | Toute écriture multi-tables est transactionnelle ; verrous sur stock, crédits, achats et portefeuille |
| NFR-08 | Traçabilité | Mouvements de stock, paiements et transactions de portefeuille horodatés et attribués à un utilisateur |
| NFR-09 | Disponibilité | 99,5 % mensuel visé en production ; sauvegarde quotidienne de la base, restauration testée |
| NFR-10 | Localisation | Interface en français ; dates `fr` ; fuseau `Indian/Comoro` ; montants en KMF au format `1 234 KMF` |
| NFR-11 | PWA | Panel commerce installable (manifeste, icônes, splash), utilisable en plein écran mobile |
| NFR-12 | Accessibilité | Boutons tactiles ≥ 44 px, contrastes lisibles en extérieur |
| NFR-13 | Qualité | Tests automatisés sur les règles métier critiques exécutés avant chaque mise en production (voir DoD dans [TDR](02-TDR.md)) |
| NFR-14 | Observabilité | Erreurs serveur journalisées ; erreurs de sync tracées côté serveur sans exposer de détails techniques au client |

---

## 8. Parcours utilisateurs clés

### 8.1 Première utilisation (propriétaire)

1. Mariama s'inscrit avec son numéro de téléphone.
2. Elle voit la page « compte en attente » jusqu'à validation par l'équipe BeeZ.
3. Après validation, elle crée son commerce (nom, île, ville).
4. Elle crée ses catégories et produits (prix de vente, stock initial).
5. Elle encaisse sa première vente depuis la caisse.

### 8.2 Vente à crédit puis remboursement (caissière)

1. Fatima ajoute les produits au panier et choisit « Crédit ».
2. Elle sélectionne le client (ou le crée), fixe une échéance.
3. La vente est enregistrée, le stock baisse, le solde du client augmente.
4. Une semaine plus tard, le client rembourse une partie : Fatima enregistre le remboursement ; le crédit passe en « partiel », le solde client baisse d'autant.

### 8.3 Approvisionnement (gérant)

1. Said saisit l'achat (fournisseur, lignes, éventuellement colis × conversion), statut « en attente ».
2. À la réception, il **valide** : le stock augmente, le prix d'achat est mis à jour, la dette fournisseur augmente du montant non payé.
3. Il paie une partie au fournisseur ; la dette et le statut de paiement se mettent à jour.
4. Si la livraison est retournée, il **annule** l'achat : stock et dette restante sont retirés.

### 8.4 Journée hors-ligne (mobile)

1. Le réseau tombe ; Fatima continue de vendre sur l'application mobile.
2. Les opérations sont stockées localement (SQLite) avec un identifiant local unique.
3. Au retour du réseau, l'application pousse la file ; le serveur applique chaque opération une seule fois, même si l'envoi est répété.
4. L'application tire les données à jour (stock, soldes) depuis la dernière synchronisation.

### 8.4 bis Journée hors ligne sur la plateforme web (propriétaire)

1. Mariama travaille sur la plateforme depuis le navigateur de son téléphone. Le réseau tombe : une bannière « Connexion perdue — Continuer en mode hors ligne » apparaît.
2. Elle passe en mode hors ligne. Elle crée un nouveau produit, encaisse des ventes, fait crédit à un nouveau client, enregistre une livraison fournisseur et une dépense. Le stock et les dettes se mettent à jour à l'écran ; un compteur indique les opérations en attente.
3. Le réseau revient : la file est envoyée automatiquement, puis les données sont rechargées depuis le serveur. Une opération refusée (droits insuffisants, par exemple) reste visible dans l'onglet « Sync » avec son motif.
4. Elle revient à la plateforme habituelle : tout y figure.

### 8.5 Recharge et abonnement (super-admin)

1. Un commerçant paie en espèces ou MVola.
2. L'admin enregistre une recharge sur le portefeuille du commerce (journalisée).
3. *(À venir)* L'abonnement est débité du portefeuille et la date d'expiration du commerce est prolongée.

---

## 9. Règles métier

| ID | Règle |
|---|---|
| RG-01 | Toute donnée métier appartient à un commerce (`shop_id`) ; aucune lecture ou écriture inter-commerces. |
| RG-02 | Références au format `TYPE-YYYYMMDD-XXXX` (`VNT`, `ACH`, `CRD`), uniques par commerce, numérotation remise à zéro chaque jour, générées par le modèle (jamais à la main). |
| RG-03 | Montants en `decimal(10,2)`, devise KMF. |
| RG-04 | `customer.balance` = somme des restes dus des crédits du client. `supplier.balance` = somme des dettes restantes des achats **validés** du fournisseur. Ces soldes sont maintenus **exclusivement** par les observers. |
| RG-05 | Le stock d'un produit ne change que par un mouvement de stock ; il ne descend jamais sous 0. |
| RG-06 | Un mouvement de stock ne peut viser qu'un produit du même commerce. |
| RG-07 | Un achat est créé `pending` ; sa validation (`completed`) a lieu après l'enregistrement de ses lignes et déclenche : entrées de stock, mise à jour du `buy_price`, ajout de la dette restante au fournisseur. |
| RG-08 | Annuler un achat validé : sortie de stock compensatoire, retrait de la dette restante du fournisseur, dette de l'achat ramenée à 0. Annuler un achat en attente : aucune incidence stock/fournisseur. |
| RG-09 | Un achat validé ne se supprime pas. |
| RG-10 | Seul un achat validé, avec fournisseur et dette > 0, peut recevoir un paiement. |
| RG-11 | Tout paiement (fournisseur ou client) est **plafonné** au reste dû au moment de l'enregistrement ; sa suppression annule exactement le montant appliqué. |
| RG-12 | Statut de paiement d'un achat : `paid` si dette = 0 (et total > 0), `partial` si un montant a été payé, sinon `unpaid` — calculé automatiquement. |
| RG-13 | Statut d'un crédit : `paid` si reste = 0, `partial` si un montant a été payé, sinon `pending`. |
| RG-14 | Une vente à crédit exige un client du commerce ; le crédit créé porte sur le total de la vente. |
| RG-15 | Les prix de vente et totaux sont toujours recalculés par le serveur ; une remise en % est bornée à [0 ; 100]. |
| RG-16 | Une opération de sync identifiée par (`shop_id`, `local_id`) est exécutée au plus une fois. |
| RG-17 | Le solde d'un portefeuille ne change que par une transaction journalisée, atomique, jamais négative ; les transactions sont immuables. |
| RG-18 | Un commerce possède au plus un portefeuille. |

---

## 10. Hypothèses

- Les commerçants disposent d'un smartphone Android avec navigateur récent ou de l'application BeeZ.
- La validation manuelle des inscriptions par l'équipe BeeZ reste acceptable à l'échelle visée (≤ quelques dizaines par jour).
- Les recharges de portefeuille sont encaissées hors plateforme (espèces, MVola) et saisies par l'admin.
- Le français est suffisant pour la cible (le shikomori pourra être envisagé plus tard).
- L'hébergement de production utilise MySQL ; SQLite sert au développement et aux tests.

---

## 11. Risques produit

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Rôles globaux : un utilisateur multi-commerces hérite de ses droits les plus élevés partout | Moyenne | Élevé | Rôles par commerce (FR-EMP-03), priorité Sprint 2 |
| Résultat net trompeur (calcul trésorerie, pas marge) | Élevée | Élevé | Coût historique à la vente (FR-RAP-06) |
| Confusion sur l'offre tarifaire (site vs forfaits) | Élevée | Moyen | Décision PO avant FR-ABO-08 |
| Données mobiles trop volumineuses au premier pull | Moyenne | Moyen | Pagination par curseur (FR-API-09) |
| Adoption freinée par la validation manuelle | Moyenne | Moyen | SLA de validation < 24 h, notification à l'approbation |
| Perte de confiance après une incohérence de solde | Faible (après Sprint 1) | Élevé | Tests automatiques, contrôle de cohérence périodique (KPI O3/O4) |
| Fraude interne (caissier) | Moyenne | Moyen | Prix recalculés serveur, traçabilité des mouvements, permissions strictes |

---

## 12. Glossaire

| Terme | Définition |
|---|---|
| Commerce / Shop / Tenant | Entité isolée regroupant les données d'une boutique |
| Panel Commerce | Interface web des commerçants (`/commerce/{slug}`) |
| Panel Admin | Interface web de l'équipe BeeZ (`/admin`) |
| Observer | Composant serveur qui applique automatiquement les effets d'une écriture (stock, soldes) |
| Mouvement de stock | Enregistrement d'une entrée, sortie ou correction de stock |
| Balance client | Dette totale d'un client envers le commerce |
| Balance fournisseur | Dette totale du commerce envers un fournisseur |
| Crédit | Dette d'un client, issue d'une vente à crédit ou saisie directement |
| Achat validé | Achat dont la marchandise a été reçue et intégrée au stock |
| Pull / Push | Synchronisation descendante / montante de l'application mobile |
| `local_id` | Identifiant unique d'une opération créée hors-ligne |
| Portefeuille (wallet) | Compte prépayé d'un commerce servant à payer l'abonnement |
| Forfait (plan) | Offre d'abonnement (prix, durée) |
| PWA | Application web installable sur l'écran d'accueil |
| KMF | Franc comorien |

---

## 13. Roadmap haut niveau

| Release | Contenu | Statut |
|---|---|---|
| **R0 — Socle** | Caisse, stock, achats, crédits, dépenses, rapports, PWA, API & sync offline | Livré |
| **R1 — Stabilisation** (Sprint 1) | Correction de l'audit : isolation multi-tenant, permissions API, idempotence sync, cohérence des soldes, sécurisation caisse, module wallet sans crash, 28 tests automatisés | Livré (9/11/2026) |
| **R1.1 — Mode hors ligne web** (Sprint 2) | Application hors ligne de la plateforme (caisse, produits, stock, clients et crédits, achats, fournisseurs, dépenses), synchronisation automatique, références entre éléments créés hors ligne ; liste des produits dès l'ouverture de la caisse | Livré (9/11/2026) |
| **R2 — Sécurité des rôles** | Rôles par commerce, rôle `owner` à la création, gestion des employés dans le panel commerce | À venir |
| **R3 — Monétisation** | Portefeuille automatique, abonnement débité, expiration et période de grâce, limites par forfait | À venir |
| **R4 — Fiabilité financière** | Coût historique par ligne de vente, résultat net sur marge, annulation de vente, désactivation produit | À venir |
| **R5 — Mobile robuste** | Pagination du pull, contrôles de cohérence | À venir |
| **R6 — Production** | CI, sauvegardes, supervision, durcissement, documentation d'exploitation | À venir |

Le détail (user stories, estimations, sprints) est tenu dans le [Product Backlog](scrum/PRODUCT-BACKLOG.md).
