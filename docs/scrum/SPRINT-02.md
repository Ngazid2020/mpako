# Sprint 2 — Mode hors ligne de la plateforme web

| | |
|---|---|
| **Période** | 9 novembre 2026 |
| **Branche** | `modulepro` |
| **Sprint Goal** | *Le propriétaire peut travailler sur la plateforme sans connexion (ventes, produits, stock, clients, achats, fournisseurs, dépenses) ; tout se synchronise automatiquement au retour du réseau.* |
| **Résultat** | ✅ Objectif atteint : 34 points livrés, 34 tests automatisés au vert |

---

## 1. Sprint Planning

**Changement de priorité du PO.** Le mode hors ligne web passe devant les rôles par commerce, prévus initialement au Sprint 2 et décalés au Sprint 3. Le [Product Backlog](PRODUCT-BACKLOG.md) est mis à jour.

**Décision d'architecture.** Le panel Filament (Livewire) exige le réseau à chaque interaction et ne peut pas fonctionner hors ligne. Le choix retenu est une **application hors ligne autonome**, servie par la plateforme (`/commerce/{slug}/hors-ligne`), mise en cache par le service worker et branchée sur la **synchronisation déjà utilisée par l'app mobile**. Il n'y a qu'un protocole, qu'un jeu de règles serveur et qu'une idempotence. Voir [03-ARCHITECTURE.md § 7.5](../03-ARCHITECTURE.md).

## 2. Sprint Backlog (réalisé)

| Story | Contenu | Pts | Statut |
|---|---|---|---|
| — | Caisse en ligne : liste des produits affichée dès l'ouverture, la recherche ne fait que filtrer | — | ✅ Done |
| US-701 | Application hors ligne : caisse (comptant, crédit, remises), produits (création, modification), stock (entrée, sortie, inventaire) | 13 | ✅ Done |
| US-702 | Clients et remboursements de crédit, achats (création, validation, paiement), fournisseurs (création, paiement FIFO), dépenses | 8 | ✅ Done |
| US-703 | Synchronisation automatique (retour réseau, toutes les 30 s), nouvelles opérations `create_product`, `update_product`, `create_customer`, `create_supplier`, références `*_local_id` | 5 | ✅ Done |
| US-402 | Références locales résolues d'un lot de sync à l'autre (couvert par US-703) | 5 | ✅ Done |
| US-704 | Service worker (`beez-v2`) : cache de l'application et de ses assets, repli automatique hors ligne ; menu « Mode hors ligne » ; bannière « Connexion perdue » ; préchargement | 3 | ✅ Done |

### Critères d'acceptation vérifiés

| Critère | Vérification |
|---|---|
| Seul un membre approuvé du commerce accède au mode hors ligne ; un invité est redirigé vers la connexion | `OfflineModeTest::test_offline_page_requires_membership` |
| Un token par poste ; le renouvellement révoque l'ancien | `test_token_is_issued_per_device_and_works_on_the_api` |
| Un client et un produit créés hors ligne sont utilisables dans une vente à crédit du même lot | `test_customer_and_product_created_offline_can_be_used_in_the_same_batch` |
| Un achat créé hors ligne peut être validé et payé dans un lot ultérieur ; une référence inconnue échoue proprement (temporaire) | `test_local_reference_resolves_across_batches_and_fails_cleanly_when_missing` |
| La modification d'une fiche produit ne change jamais le stock et exige `update_product` | `test_update_product_never_changes_stock_and_requires_permission` |
| Les effets locaux (stock, remises, crédits, paiements plafonnés, FIFO) correspondent aux règles serveur, et une opération refusée n'est pas rejouée | Script Node de contrôle de `operations.js` (exécuté pendant le sprint) |
| La caisse en ligne affiche les produits sans saisie | `CaisseProductListTest` |

## 3. Sprint Review

**Démonstration (scénario cible)**
1. Ouvrir la plateforme avec une connexion : l'application hors ligne est préchargée.
2. Couper le réseau, par exemple avec le mode avion. Une bannière propose « Continuer en mode hors ligne ».
3. Créer un produit, le vendre à crédit à un nouveau client, enregistrer un achat validé et une dépense. Les stocks et les dettes évoluent à l'écran ; le compteur « Sync » augmente.
4. Rétablir le réseau. La file part automatiquement, les données sont rechargées et tout apparaît dans le panel habituel.

**Limites connues** (reportées au backlog)
- Aucun test navigateur de bout en bout : US-705.
- Pas d'historique des ventes consultable hors ligne : US-706.
- Données locales non chiffrées : US-707.
- Chaque synchronisation recharge le snapshot complet : US-708.
- Pré-requis d'exploitation : HTTPS en production, et une première ouverture en ligne par appareil.

## 4. Sprint Retrospective

| 👍 Ce qui a bien marché | 👎 À améliorer | 🎯 Actions |
|---|---|---|
| La réutilisation du protocole de sync mobile a évité de dupliquer les règles métier ; le serveur reste la seule source de vérité | La logique JavaScript n'a pas de suite de tests versionnée (seulement un contrôle ponctuel) | Ajouter Vitest pour `operations.js` (avec US-705) |
| Le mécanisme générique `*_local_id` a aussi livré US-402 | Le test manuel du service worker demande `npm run build`, car `npm run dev` ne met pas les assets en cache | Documenté dans l'architecture ; prévoir un environnement de recette en HTTPS |

## 5. Métriques

| Mesure | Valeur |
|---|---|
| Points livrés | 34 |
| Tests automatisés | 28 → 34 (139 assertions) |
| Nouvelles opérations de sync | 4 (+ références locales génériques) |
