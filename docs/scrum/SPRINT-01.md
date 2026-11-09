# Sprint 1 — Stabilisation

| | |
|---|---|
| **Période** | 9 novembre 2026 (sprint de stabilisation condensé) |
| **Branche** | `modulepro` |
| **Sprint Goal** | *Aucune donnée ne fuit entre commerces, les stocks et les dettes restent justes, les fonctions mobiles cassées fonctionnent à nouveau, et chaque correctif est protégé par un test.* |
| **Résultat** | ✅ Objectif atteint : 61 constats corrigés sur 78, 28 tests automatisés au vert |

---

## 1. Sprint Planning

**Entrée** : audit complet du code en 4 volets (API/sync, domaine, panel Commerce, panel Admin et module Portefeuille). Voir le [rapport d'audit](../05-AUDIT-2026-11.md).

**Règle de sélection** : tous les constats *Critiques* et *Élevés* dont le correctif est local et sans risque de migration de données. Les chantiers d'architecture vont au [Product Backlog](PRODUCT-BACKLOG.md) :
- rôles par commerce ;
- coût historique des ventes ;
- *soft deletes* ;
- pagination de la sync.

## 2. Sprint Backlog (réalisé)

| # | Élément | Constats | Statut |
|---|---|---|---|
| S1-01 | Isolation multi-tenant de l'API et de la sync (règle `inShop`, produits et clients résolus via le commerce, garde dans l'observer de stock) | A02, A03, D11 | ✅ Done |
| S1-02 | Permissions Shield appliquées à l'API REST et à chaque type d'opération de sync (`InteractsWithShop::authorizeAny`) | A06 | ✅ Done |
| S1-03 | Sync idempotente (`sync_operations`), messages d'erreur assainis, 500 opérations max, pull incrémental sans perte | A01, A13, A15, A16 | ✅ Done |
| S1-04 | Endpoints réparés : paiement de crédit, paiement fournisseur, validation et paiement d'achat, dépenses sans description | A04, A05 | ✅ Done |
| S1-05 | Service `SaleRecorder` : total recalculé côté serveur, stock agrégé, crédit avec client obligatoire, 422 au lieu de 500 | A10 | ✅ Done |
| S1-06 | Cycle de vie des achats : création en « pending » puis validation après enregistrement des lignes, annulation centralisée, suppression d'un achat validé bloquée, anti double-clic | D02, D03, D14, C02, C10, C16 | ✅ Done |
| S1-07 | Paiements : montants plafonnés avant insertion, suppression neutre sur un achat annulé, paiement réservé aux achats validés, répartition FIFO | A08, A09, A11, D04, D05 | ✅ Done |
| S1-08 | Crédit : modification du total répercutée sur la balance client et sur le statut | D06 | ✅ Done |
| S1-09 | Stock : verrou anti-concurrence, alerte de stock faible au franchissement du seuil et après commit | D07, D12 | ✅ Done |
| S1-10 | Caisse : prix reconstruits depuis la base, remise réappliquée, remise plafonnée à 100 % | C01, C07, C08 | ✅ Done |
| S1-11 | Filament Commerce : filtres isolés par commerce, stock verrouillé après création, PDF soumis à permission, autorisation du remboursement, graphique sur 6 mois | C04, C12, C14, C17, C18 | ✅ Done |
| S1-12 | Rôles : `page_Caisse` pour le caissier, permissions super-admin retirées à owner et manager | C05, C06 | ✅ Done (seeder à relancer) |
| S1-13 | Module Portefeuille : crashs (`readOnly`, enum, `modalTitle`, `auth()->can`), lecture seule côté commerçant, journal en ajout seul, crédit/débit atomiques, validation réservée à l'admin | W01–W08, W18, W21, S01, S02 | ✅ Done |
| S1-14 | Admin : action « Retirer » réparée, tri, bouton Recharger, protection des forfaits, ressources masquées en lecture seule | W09–W11, W13, W16 | ✅ Done |
| S1-15 | Schéma : `shops.subscription_expires_at`, unique `wallets.shop_id`, table `sync_operations` | D01, W14 | ✅ Done (migrations à exécuter) |
| S1-16 | Tests : SQLite `:memory:` (protection de la base de dev), 28 tests sur l'API, la sync, les achats, les crédits et un smoke test Filament | S03 | ✅ Done |
| S1-17 | Documentation : PRD, TDR, architecture, API, audit, cadre Scrum, backlog, README, CHANGELOG, CLAUDE.md | D16 | ✅ Done |

## 3. Sprint Review

### Démonstration (scénarios désormais couverts par des tests)

| Scénario | Avant | Après |
|---|---|---|
| Renvoi d'un lot de sync après un timeout | Vente et sortie de stock en double | Une seule vente ; le rejeu renvoie le même identifiant |
| Ajustement de stock vers un produit d'un autre commerce | Stock du commerce B modifié | Échec, stock intact |
| Annulation d'un achat en attente de 2 000 KMF | Fournisseur à −2 000 KMF | Fournisseur à 0 |
| Trop-perçu de 4 500 sur un crédit de 3 500, puis suppression du paiement | Dette client de 4 500 | Dette client de 3 500 |
| Achat créé « validé » depuis Filament ou la sync | Stock inchangé, dette à 0 | Stock et dette fournisseur mis à jour |
| `POST purchases/{id}/complete` (mobile) | Erreur 500 (TypeError) | 200, stock à jour |
| `POST credits/{id}/pay` (mobile) | Erreur 500 (NOT NULL) | 200, dette client réduite |
| Page « Mon portefeuille » | Erreur 500 | S'affiche, en lecture seule |
| Prix modifié dans le panier via la console du navigateur | Vente au prix falsifié | Prix relu en base |

### Non terminé / reporté
- Rôles par commerce (US-101) : changement d'architecture qui demande une migration de données, d'où son report au sprint suivant.
- Fonctionnalités Portefeuille et Abonnements restantes (création automatique, débit, contrôle d'accès) : US-501 à US-504.

### Actions de déploiement
Voir [rapport d'audit § 3](../05-AUDIT-2026-11.md#3-actions-requises-après-déploiement) : migrations, `shield:generate`, `ShopRolesSeeder`, mise à jour de l'app mobile.

## 4. Sprint Retrospective

| 👍 Ce qui a bien marché | 👎 À améliorer | 🎯 Actions (Sprint 2) |
|---|---|---|
| Un audit découpé par domaine a permis de couvrir tout le code rapidement | Le projet n'avait aucun test : chaque bug n'était découvert qu'en production | **US-601** : CI obligatoire sur chaque PR |
| Les smoke tests Filament ont trouvé 3 crashs invisibles à la relecture (dont une régression introduite pendant le sprint et corrigée avant livraison) | `phpunit.xml` pointait sur la base de dev, ce qui était dangereux | Règle de la DoD : toute nouvelle page Filament est ajoutée au smoke test |
| Les règles métier sont centralisées dans les observers et le modèle (`Purchase::saving`, `Credit::deriveStatus`) | Code du module Portefeuille non suivi dans git (`app/Enums`, migration `120005`) | Committer les fichiers non suivis avant de démarrer le Sprint 2 |
| | Rôles « Propriétaire » (base) et `owner` (seeder) en double | Le PO tranche (question ouverte n°3 du backlog) |

## 5. Métriques

| Mesure | Valeur |
|---|---|
| Constats traités | 61 corrigés, 4 partiels, sur 78 |
| Tests automatisés | 2 → 28 (97 assertions) |
| Bugs critiques ouverts | 11 → 0 |
| Fichiers modifiés ou créés | ~55 |
