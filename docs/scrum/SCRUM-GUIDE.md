# Cadre Agile / Scrum — Projet BeeZ

Ce document décrit **comment l'équipe travaille** sur BeeZ. Il est volontairement court et pragmatique : il s'agit d'une petite équipe, parfois d'un seul développeur.

---

## 1. Rôles

| Rôle | Responsabilités | Sur BeeZ |
|---|---|---|
| **Product Owner (PO)** | Porte la vision produit, priorise le [Product Backlog](PRODUCT-BACKLOG.md), accepte ou refuse les incréments | Fondateur / porteur du projet BeeZ |
| **Scrum Master (SM)** | Garant du cadre : facilite les cérémonies, lève les obstacles, suit les métriques | Peut être cumulé par le développeur principal (à éviter avec le rôle de PO) |
| **Équipe de développement** | S'auto-organise pour livrer un incrément « Done » à chaque sprint | Développeur(s) Laravel/Filament et développeur mobile (Expo) |
| **Parties prenantes** | Donnent du feedback lors des Sprint Reviews | Commerçants pilotes (Moroni, Mutsamudu, Fomboni…), super-admin BeeZ |

> Avec une équipe réduite, une même personne peut tenir plusieurs rôles. Une règle reste non négociable : **le PO décide du *quoi*, l'équipe décide du *comment***.

---

## 2. Rythme (sprints de 2 semaines)

| Cérémonie | Quand | Durée max | Entrée → Sortie |
|---|---|---|---|
| **Sprint Planning** | J1 matin | 2 h | Backlog priorisé → Sprint Goal + Sprint Backlog (stories prêtes, estimées) |
| **Daily Scrum** | Chaque jour | 15 min | Hier / aujourd'hui / blocages (asynchrone accepté : message écrit) |
| **Backlog Refinement** | Milieu de sprint | 1 h | Stories du sprint suivant découpées, estimées et conformes à la DoR |
| **Sprint Review** | Dernier jour | 1 h | Démo de l'incrément aux parties prenantes (idéalement un commerçant pilote) → feedback dans le backlog |
| **Sprint Retrospective** | Dernier jour, après la review | 45 min | Ce qui marche / à améliorer → 1 à 3 actions concrètes pour le sprint suivant |

Chaque sprint donne lieu à un compte rendu `docs/scrum/SPRINT-XX.md` (modèle : [SPRINT-01.md](SPRINT-01.md)).

---

## 3. Artefacts

| Artefact | Emplacement | Engagement associé |
|---|---|---|
| Product Backlog | [PRODUCT-BACKLOG.md](PRODUCT-BACKLOG.md) | Objectif produit (voir [PRD](../01-PRD.md)) |
| Sprint Backlog | Section « Sprint Backlog » de `SPRINT-XX.md` | Sprint Goal |
| Incrément | Branche fusionnée + tag `vX.Y.Z` + entrée dans [CHANGELOG](../../CHANGELOG.md) | Definition of Done |

### Format d'une user story

```
US-XXX — Titre court
En tant que <persona>, je veux <action> afin de <bénéfice>.

Critères d'acceptation (Given/When/Then) :
- Étant donné …, quand …, alors …

Estimation : N points (suite de Fibonacci : 1, 2, 3, 5, 8, 13)
Priorité : Must / Should / Could / Won't (MoSCoW)
Dépendances : US-YYY
```

---

## 4. Definition of Ready (DoR)

Une story n'entre dans un sprint que si :

- [ ] Elle est rédigée au format user story, avec un bénéfice explicite.
- [ ] Ses critères d'acceptation sont testables.
- [ ] Elle est estimée par l'équipe, à 8 points au maximum. Au-delà, il faut la découper.
- [ ] Ses dépendances sont identifiées et levées, ou planifiées dans le même sprint.
- [ ] Son impact sur l'app mobile et sur les données existantes (migration) est identifié.
- [ ] Les maquettes et les règles métier sont disponibles si nécessaire.

## 5. Definition of Done (DoD)

Un élément est « Done » quand :

**Code**
- [ ] Le code est relu (pull request) et respecte les conventions de [CLAUDE.md](../../CLAUDE.md) :
  - scoping `shop_id` ;
  - aucun contournement des observers ;
  - montants en `decimal(10,2)`.
- [ ] Aucune donnée n'est accessible hors du tenant courant :
  - formulaires, filtres, `Select`, API ;
  - pour les identifiants reçus en entrée, règle `inShop()` côté API.
- [ ] Chaque action protégée vérifie une permission Shield (policy, `authorize()` ou `authorizeAny()` côté API).
- [ ] Les écritures multiples passent par `DB::transaction`, avec `lockForUpdate` sur les soldes et les stocks.

**Qualité**
- [ ] Les tests automatisés couvrent les règles métier ajoutées ou modifiées.
- [ ] `php artisan test` est entièrement au vert (SQLite `:memory:`).
- [ ] Le smoke test Filament (`FilamentSmokeTest`) passe pour toute page ajoutée.
- [ ] `vendor/bin/pint --test` ne signale rien (lorsque la CI sera en place : US-601).

**Données & déploiement**
- [ ] Les migrations sont réversibles (`down()`) et testées via `migrate:fresh` sur une base jetable.
- [ ] Les seeders sont à jour si de nouvelles permissions ou de nouveaux rôles sont nécessaires (`shield:generate --all`).

**Documentation**
- [ ] [04-API.md](../04-API.md) est à jour si l'API change, avec une note de migration mobile si le changement est cassant.
- [ ] [CHANGELOG.md](../../CHANGELOG.md) est complété.
- [ ] Le PO a accepté la story en Sprint Review.

---

## 6. Estimation & métriques

- **Unité** : points de complexité relative (Fibonacci). Référence : « ajouter un endpoint API simple, scopé, avec tests » = **3 points**.
- **Vélocité** : moyenne glissante sur les 3 derniers sprints. Hypothèse de départ : **20 à 25 points par sprint** pour un développeur à temps plein.
- **Suivi** :
  - burndown du sprint (points restants par jour) ;
  - taux de stories « Done » par rapport aux stories engagées ;
  - nombre de bugs échappés en production.
- **Bugs** : un bug critique en production interrompt le sprint (hotfix). Les autres bugs entrent dans le backlog et sont priorisés par le PO.

## 7. Gestion des branches

| Branche | Usage |
|---|---|
| `master` | Production. Uniquement par fusion d'une pull request validée. |
| `feature/US-XXX-description` | Une branche par story |
| `hotfix/description` | Correctif urgent, fusionné dans `master` puis rebasé dans le sprint en cours |

Le message de commit référence la story : `feat(US-501): création automatique du portefeuille`.
