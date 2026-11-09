# Product Backlog — BeeZ

> Priorisé par le Product Owner. Dernière mise à jour : 9 novembre 2026 (fin du Sprint 2).
> Les identifiants `Axx/Dxx/Cxx/Wxx` renvoient aux constats du [rapport d'audit](../05-AUDIT-2026-11.md).
> Estimation en points (Fibonacci) ; priorité MoSCoW. Cadre de travail : [SCRUM-GUIDE.md](SCRUM-GUIDE.md).

## Vue d'ensemble des épiques

| Épique | Objectif | Points | Priorité |
|---|---|---|---|
| EPIC-1 Sécurité multi-tenant & RBAC | Les droits d'un utilisateur dépendent du commerce dans lequel il agit | 30 | Must |
| EPIC-2 Intégrité des données | Aucun historique perdu ; aucune référence en doublon | 24 | Must |
| EPIC-3 Rapports fiables | Bénéfice et marges exacts (coût historique) | 13 | Must |
| EPIC-4 Sync & API mobile | Sync robuste sur réseau instable ; app mobile alignée | 23 | Must/Should |
| EPIC-5 Abonnements & portefeuille | Monétisation : abonnement payé par portefeuille, accès conditionné | 39 | Must |
| EPIC-6 Qualité & DevOps | Livraisons sûres et reproductibles | 19 | Must/Should |
| EPIC-7 Mode hors ligne web | Travailler sur la plateforme sans connexion, synchronisation automatique | 23 restants (29 livrés) | Must/Could |
| **Total restant** | | **171** | |

Avec une vélocité de 20 à 25 points par sprint, il faut compter **7 à 9 sprints**, soit environ 3 à 4 mois (voir le [TDR](../02-TDR.md)).

---

## EPIC-1 — Sécurité multi-tenant & RBAC

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-101 | En tant que **propriétaire de plusieurs commerces**, je veux que les rôles soient attribués *par commerce* afin qu'un caissier du commerce B n'hérite pas de mes droits de propriétaire du commerce A. | 13 | Must | — | C03 |
| US-102 | En tant que **nouveau commerçant**, je veux recevoir automatiquement le rôle propriétaire de mon commerce à sa création, afin d'utiliser l'application sans intervention de l'admin. Seuls les propriétaires peuvent créer un commerce, dans la limite fixée par l'abonnement. | 5 | Must | US-101 | C15 |
| US-103 | En tant que **super-admin**, je veux que la gestion des employés en Admin filtre par commerce, rattache l'employé à un commerce et interdise d'attribuer `super_admin`. | 5 | Should | US-101 | W09 |
| US-104 | En tant que **développeur**, je veux un scope tenant global (middleware Filament) afin que tout nouveau `Select` ou filtre soit isolé par défaut. | 5 | Should | — | C04 |
| US-105 | En tant que **super-admin**, je veux que le panel Admin ne découvre plus les pages et widgets du panel Commerce. | 2 | Could | — | W19 |

**Critères d'acceptation — US-101**
- Étant donné un utilisateur *owner* du commerce A et *cashier* du commerce B, quand il ouvre `/commerce/B/purchases`, alors il reçoit un refus (403).
- Étant donné le même utilisateur, quand il appelle `POST /api/shop/B/purchases`, alors il reçoit un 403.
- Les permissions sont résolues avec `setPermissionsTeamId(shop_id)` (Spatie *teams*) dans un middleware du panel Commerce **et** dans `ResolveShop`.
- Une migration de données convertit les rôles globaux existants en rôles par commerce, pour chaque ligne `shop_user`.

## EPIC-2 — Intégrité des données

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-201 | En tant que **commerçant**, je veux pouvoir archiver un produit sans perdre l'historique de ventes et d'achats qui le concerne. Concrètement : *soft deletes* sur produits et ventes, FK `restrictOnDelete` sur `sale_items`, `purchase_items`, `stock_movements` et `credits.sale_id`. | 8 | Must | — | D08, C11 |
| US-202 | En tant que **caissier**, je veux qu'aucune vente ne soit refusée pour cause de référence en doublon, même lorsque plusieurs caisses encaissent en même temps (compteur par commerce et par jour, nouvel essai en cas de conflit). | 5 | Should | — | D09 |
| US-203 | En tant que **PO**, je veux arbitrer la politique de survente : stock négatif autorisé et signalé, ou vente refusée. Décision suivie de son implémentation et d'un rapport des écarts. | 3 | Should | — | D13 |
| US-204 | En tant que **gérant**, je veux annuler une vente, avec retour en stock et annulation du crédit associé, afin de corriger une erreur de caisse sans supprimer de données. | 8 | Should | US-201 | — |

## EPIC-3 — Rapports fiables

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-301 | En tant que **propriétaire**, je veux que chaque ligne de vente enregistre son coût d'achat au moment de la vente (`sale_items.unit_cost`), afin que mes marges passées ne changent pas après un réapprovisionnement. | 5 | Must | — | C13 |
| US-302 | En tant que **propriétaire**, je veux un bénéfice calculé en *coût des ventes* (CA − coût des marchandises vendues − dépenses) dans la page Bénéfice, les rapports et les PDF. | 8 | Must | US-301 | C13 |

## EPIC-4 — Sync & API mobile

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-406 | En tant que **caissier mobile**, je veux que l'app `beez-mobile` gère les nouveaux comportements de l'API (403 selon le rôle, totaux calculés par le serveur, rejeu idempotent, répartition des paiements fournisseur). | 5 | **Must** | — | [04-API.md](../04-API.md) |
| US-401 | En tant qu'**utilisateur mobile** d'un commerce ancien, je veux une première synchronisation paginée (curseur), afin qu'elle aboutisse même en 3G. | 8 | Should | — | A13 |
| ~~US-402~~ ✅ | ~~En tant qu'**utilisateur mobile**, je veux pouvoir valider ou payer, dans un lot de sync ultérieur, un achat créé hors-ligne.~~ **Livré au Sprint 2** : références `*_local_id` génériques (voir US-703). | 5 | — | — | A19 |
| US-403 | En tant qu'**admin**, je veux des limites de débit sur push/pull et une purge planifiée des tokens expirés. | 2 | Should | — | A16, A17 |
| US-404 | En tant qu'**admin sécurité**, je veux que la connexion ne révèle pas les numéros inscrits (temps de réponse constant) et soit limitée par numéro de téléphone. | 3 | Should | — | A18 |
| US-405 | En tant que **développeur mobile**, je veux une API versionnée (`/api/v1`) au format de réponse uniforme. | 5 | Could | US-406 | A20 |

## EPIC-5 — Abonnements & portefeuille

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-501 | En tant que **super-admin**, je veux qu'un portefeuille soit créé automatiquement à la création de chaque commerce. | 3 | Must | — | W17 |
| US-502 | En tant que **commerçant**, je veux souscrire un forfait payé par mon portefeuille. Concrètement : `Wallet::debit()`, création de l'abonnement et mise à jour de `shops.subscription_expires_at` dans une seule transaction. | 8 | Must | US-501 | W12 |
| US-503 | En tant que **BeeZ**, je veux que l'accès au panel et à l'API soit conditionné à un abonnement actif, avec une période de grâce et un message clair. | 5 | Must | US-502 | A14, W17 |
| US-504 | En tant que **commerçant**, je veux être averti avant l'expiration et que le renouvellement automatique débite mon portefeuille (tâche planifiée). | 5 | Should | US-502 | — |
| US-505 | En tant que **commerçant**, je veux resoumettre un portefeuille rejeté et consulter l'historique des échanges avec l'admin. | 3 | Could | — | W15 |
| US-506 | En tant que **commerçant**, je veux recharger mon portefeuille par Mobile Money (MVola, Holo…) sans passer par l'admin. | 13 | Could | US-502 | — |
| US-507 | En tant que **super-admin**, je veux que le formulaire d'abonnement ne propose que le portefeuille du commerce concerné et refuse une date de fin antérieure à la date de début. | 2 | Should | — | W12 |

## EPIC-6 — Qualité & DevOps

| ID | User story | Pts | Prio | Dépend. | Audit |
|---|---|---|---|---|---|
| US-601 | En tant qu'**équipe**, je veux une CI (GitHub Actions : `composer install`, `php artisan test`, `pint --test`) sur chaque pull request. | 3 | Must | — | — |
| US-602 | En tant qu'**équipe**, je veux des tests sur la caisse (Livewire), les rapports, les policies et les PDF. | 8 | Should | US-601 | — |
| US-603 | En tant que **caissier**, je veux que les notifications (stock faible, alertes quotidiennes) partent en file d'attente afin de ne jamais ralentir l'encaissement. Nécessite un worker supervisé. | 3 | Should | — | D12 |
| US-604 | En tant qu'**équipe**, je veux que les assets compilés (`public/build`) ne soient plus versionnés mais construits au déploiement. | 2 | Could | US-601 | — |
| US-605 | En tant qu'**équipe**, je veux un suivi des erreurs en production (Sentry ou équivalent) et des sauvegardes de base automatisées. | 3 | Could | — | — |

---

## EPIC-7 — Mode hors ligne de la plateforme web

| ID | User story | Pts | Prio | Statut |
|---|---|---|---|---|
| US-701 | En tant que **propriétaire**, je veux encaisser, créer ou modifier mes produits et corriger mon stock sur la plateforme même sans connexion. | 13 | Must | ✅ Sprint 2 |
| US-702 | En tant que **propriétaire**, je veux gérer hors ligne mes clients et leurs remboursements, mes achats (création, validation, paiement), mes fournisseurs et mes dépenses. | 8 | Must | ✅ Sprint 2 |
| US-703 | En tant que **propriétaire**, je veux que mes opérations hors ligne se synchronisent automatiquement au retour du réseau, sans doublon, y compris celles qui utilisent un client ou un produit créé hors ligne. | 5 | Must | ✅ Sprint 2 |
| US-704 | En tant qu'**utilisateur du panel**, je veux être basculé vers le mode hors ligne quand la connexion tombe, sans rien avoir préparé (préchargement automatique). | 3 | Must | ✅ Sprint 2 |
| US-705 | En tant qu'**équipe**, je veux des tests navigateur de bout en bout (Playwright) : couper le réseau, vendre, rétablir, vérifier la base. | 5 | Should | À faire |
| US-706 | En tant que **propriétaire**, je veux consulter hors ligne l'historique récent des ventes et un résumé du jour. | 5 | Could | À faire |
| US-707 | En tant que **propriétaire sur un poste partagé**, je veux protéger les données locales par un code PIN (chiffrement). | 5 | Could | À faire |
| US-708 | En tant qu'**utilisateur web**, je veux une resynchronisation incrémentale (`since`) plutôt qu'un snapshot complet à chaque cycle. | 3 | Should | À faire (après US-401) |
| US-709 | En tant que **gérant**, je veux annuler un achat et désactiver un produit hors ligne. | 5 | Could | À faire |

**Critères d'acceptation livrés (US-701 à 704)** : voir [SPRINT-02.md](SPRINT-02.md) et `tests/Feature/OfflineModeTest.php`.

## Proposition de découpage en sprints

| Sprint | Objectif (Sprint Goal) | Stories | Points |
|---|---|---|---|
| **S1** ✅ | Stabiliser : sécurité multi-tenant, cohérence comptable, tests | Voir [SPRINT-01.md](SPRINT-01.md) | — |
| **S2** ✅ | « Le propriétaire travaille sur la plateforme même hors ligne » (priorité PO) | US-701 à US-704, US-402 + liste produits caisse — voir [SPRINT-02.md](SPRINT-02.md) | 34 |
| **S3** | « Chaque utilisateur n'a que les droits de son rôle **dans** ce commerce, et le mobile est aligné » | US-101, US-102, US-406, US-601 | 26 |
| **S4** | « Un commerçant peut payer son abonnement avec son portefeuille » | US-501, US-502, US-503, US-507, US-104 | 23 |
| **S5** | « Les chiffres de bénéfice sont justes et l'historique est préservé » | US-301, US-302, US-201 | 21 |
| **S6** | « La sync tient sur un réseau 3G instable » | US-401, US-708, US-403, US-404, US-603 | 19 |
| **S7** | Consolidation | US-202, US-203, US-204, US-705 | 21 |
| **S8+** | Évolutions | US-504, US-602, US-103, US-105, US-405, US-505, US-506, US-604, US-605, US-706, US-707, US-709 | 61 |

## Questions ouvertes pour le PO

1. **W20 — `CreditPolicy::delete`** : la modification en cours retire la restriction owner/manager. Tout rôle disposant de `delete_credit` pourra donc supprimer un crédit. Est-ce voulu ?
2. **US-203 — Survente** : faut-il autoriser un stock négatif (signalé) ou refuser la vente ? Aujourd'hui le stock est ramené à 0 et l'écart est seulement journalisé.
3. **Rôles existants en base** : le rôle personnalisé « Propriétaire » coexiste avec `owner`, `manager` et `cashier` du seeder. Lequel fait foi ?
4. **Grille tarifaire** : la page d'accueil annonce *Gratuit / Pro à 5 000 KMF par mois*, alors que les forfaits en base sont *1 000 KMF par mois* et *10 000 KMF par an*. Il faut arrêter une grille unique avant US-502.
