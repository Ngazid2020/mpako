# Termes de Référence (TDR)

## Consolidation, finalisation et mise en production de la plateforme BeeZ — gestion commerciale des commerçants comoriens

| Élément | Valeur |
|---|---|
| Intitulé | Consolidation et mise en production de BeeZ (dépôt `mpako`) |
| Version | 1.0 — 9 novembre 2026 |
| Maître d'ouvrage | Porteur du projet BeeZ (à préciser) |
| Maître d'œuvre | Équipe de développement BeeZ (interne ou prestataire, à préciser) |
| Durée indicative | 14 semaines (7 sprints de 2 semaines) |
| Méthodologie | Agile / Scrum |
| Documents de référence | [PRD](01-PRD.md) · [Architecture](03-ARCHITECTURE.md) · [API](04-API.md) · [Audit 2026-11](05-AUDIT-2026-11.md) · [Product Backlog](scrum/PRODUCT-BACKLOG.md) |

---

## 1. Contexte et justification

### 1.1 Contexte général

Le commerce de détail est l'un des premiers secteurs d'activité et d'emploi de l'Union des Comores. Il est dominé par de petites structures (boutiques, épiceries, quincailleries, commerces de quartier) réparties sur les îles de Grande Comore (Ngazidja), Anjouan (Ndzuani) et Mohéli (Mwali), ainsi que dans la communauté comorienne de Mayotte. Ces commerces gèrent leurs ventes, leurs stocks, les crédits accordés aux clients et leurs dettes envers les fournisseurs principalement sur papier.

Cette gestion manuelle entraîne :

- une méconnaissance du chiffre d'affaires et de la marge réels ;
- des pertes liées aux crédits clients non suivis ou contestés ;
- des litiges avec les fournisseurs faute de rapprochement entre livraisons et paiements ;
- des ruptures de stock et des pertes non détectées ;
- l'impossibilité de présenter des données fiables à un partenaire financier (microfinance, banque).

### 1.2 Le projet BeeZ

BeeZ est une solution numérique de gestion commerciale conçue pour ce contexte :

- **panel web « Commerce »** installable comme application (PWA) : caisse, stock, achats, crédits, fournisseurs, dépenses, rapports ;
- **application mobile Android** (Expo / React Native, `beez-mobile`) fonctionnant **hors-ligne** avec synchronisation ;
- **panel « Admin »** pour l'équipe BeeZ : validation des comptes, gestion des commerces, forfaits d'abonnement et portefeuilles prépayés ;
- devise **KMF**, langue **française**, fuseau horaire des Comores.

La plateforme repose sur Laravel 11, Filament 3.3, MySQL (production), Sanctum (API) et Spatie Permission / Filament Shield (contrôle d'accès). Voir [03-ARCHITECTURE.md](03-ARCHITECTURE.md).

### 1.3 Justification

Un audit technique approfondi conduit en novembre 2026 ([05-AUDIT-2026-11.md](05-AUDIT-2026-11.md)) a mis en évidence environ 75 anomalies, dont plusieurs critiques : fuites de données entre commerces via l'API, absence de contrôle des permissions sur l'API mobile, synchronisation non idempotente (doublons de ventes), incohérences des dettes clients et fournisseurs, possibilité de modifier les prix en caisse depuis le navigateur, plantages du module portefeuille en cours de développement.

Un premier sprint de stabilisation (Sprint 1) a corrigé les anomalies critiques et majeures et introduit une première batterie de tests automatisés (28 tests, 97 assertions). Il reste à :

- sécuriser les rôles par commerce ;
- finaliser le modèle économique (abonnements, portefeuille) ;
- fiabiliser les indicateurs financiers (marge, résultat net) ;
- robustifier la synchronisation mobile ;
- industrialiser (intégration continue, sauvegardes, supervision) et mettre en production.

Les présents TDR encadrent ces travaux.

---

## 2. Objectifs

### 2.1 Objectif général

Mettre à la disposition des commerçants comoriens une plateforme de gestion commerciale **fiable, sécurisée, utilisable hors-ligne et économiquement viable**, prête pour un déploiement à grande échelle.

### 2.2 Objectifs spécifiques

| # | Objectif spécifique |
|---|---|
| OS1 | Garantir l'isolation des données et des droits entre commerces, y compris pour un utilisateur membre de plusieurs commerces |
| OS2 | Garantir la cohérence permanente du stock, des dettes clients et des dettes fournisseurs |
| OS3 | Rendre la synchronisation hors-ligne robuste (aucune perte, aucun doublon) sur réseau instable |
| OS4 | Mettre en œuvre le modèle économique : portefeuille prépayé, abonnements, expiration, limites par forfait |
| OS5 | Fournir des indicateurs financiers exacts (coût des marchandises vendues, marge, résultat net) |
| OS6 | Industrialiser le cycle de développement (tests, intégration continue, environnements) |
| OS7 | Mettre la plateforme en production avec sauvegardes, supervision et documentation d'exploitation |

---

## 3. Résultats attendus

| # | Résultat attendu | Indicateur de vérification |
|---|---|---|
| R1 | Les rôles et permissions sont évalués par commerce | Tests automatisés multi-commerces ; aucun accès hors rôle constaté en recette |
| R2 | Les soldes sont toujours justes | Script de contrôle de cohérence : 0 écart sur la base de recette |
| R3 | La synchronisation est fiable | 0 doublon sur 1 000 opérations rejouées ; pull sans perte au-delà de 200 éléments |
| R4 | L'abonnement fonctionne de bout en bout | Recharge → souscription → expiration → blocage/grâce démontrés en recette |
| R5 | Le résultat net est calculé sur la marge réelle | Rapports concordant avec un jeu d'essai calculé à la main |
| R6 | Chaque modification est testée automatiquement | Pipeline CI vert obligatoire avant fusion |
| R7 | La plateforme est en production | URL publique, HTTPS, sauvegardes quotidiennes, restauration testée, supervision active |
| R8 | La documentation est à jour | PRD, architecture, API, guide d'exploitation, guide utilisateur |

---

## 4. Périmètre des prestations

### 4.1 Prestations incluses

1. **Développement** des fonctionnalités et correctifs du [Product Backlog](scrum/PRODUCT-BACKLOG.md) (épics EPIC-1 à EPIC-6 ci-dessous).
2. **Tests** : tests unitaires et fonctionnels automatisés, tests de non-régression, recette fonctionnelle avec des commerçants pilotes.
3. **Adaptation de l'application mobile** `beez-mobile` aux évolutions de l'API (permissions, idempotence, pagination).
4. **Mise en place de l'intégration continue** et des environnements (développement, recette, production).
5. **Mise en production** : hébergement, nom de domaine, HTTPS, base MySQL, file d'attente, planificateur (`schedule:run`), notifications push (VAPID).
6. **Documentation** technique et utilisateur.
7. **Transfert de compétences** vers l'équipe d'exploitation BeeZ.

### 4.2 Prestations exclues

- Intégration directe des API de paiement mobile (MVola, Huri Money) — étude de faisabilité uniquement si le temps le permet.
- Développement d'une application iOS publiée sur l'App Store.
- Module de comptabilité générale.
- Campagnes marketing et acquisition commerciale.

### 4.3 Épics du projet

Les identifiants d'épics sont ceux du [Product Backlog](scrum/PRODUCT-BACKLOG.md), qui fait foi.

| Épic | Intitulé | Lien PRD |
|---|---|---|
| — | Stabilisation post-audit | Réalisé au Sprint 1 ([SPRINT-01](scrum/SPRINT-01.md)) |
| EPIC-1 | Sécurité multi-tenant et rôles par commerce | FR-EMP-03, FR-MUL-06, FR-EMP-04 |
| EPIC-2 | Intégrité des données (historique, références, survente) | FR-VEN-09, FR-STK-08 |
| EPIC-3 | Rapports fiables (coût historique) | FR-RAP-06 |
| EPIC-4 | Sync et API mobile | FR-API-09, FR-API-10 |
| EPIC-5 | Abonnements et portefeuille | FR-ABO-05 à FR-ABO-10 |
| EPIC-6 | Qualité, DevOps et mise en production | NFR-09, NFR-13, NFR-14 |

---

## 5. Méthodologie

### 5.1 Cadre Agile / Scrum

Le projet est conduit selon **Scrum**, en **sprints de 2 semaines**. Chaque sprint produit un incrément potentiellement livrable, démontré au Product Owner.

### 5.2 Rôles

| Rôle | Responsabilités |
|---|---|
| **Product Owner (PO)** | Porte la vision produit ; tient et priorise le Product Backlog ; rédige et accepte les user stories ; arbitre les décisions métier (ex. grille tarifaire) ; valide les incréments |
| **Scrum Master (SM)** | Garant du cadre Scrum ; anime les cérémonies ; lève les obstacles ; suit la vélocité et le burndown ; protège l'équipe des sollicitations hors sprint |
| **Équipe de développement** | Estime, conçoit, développe, teste et documente ; s'auto-organise pour atteindre l'objectif de sprint ; responsable collectivement de la qualité |
| **Commerçants pilotes** | 3 à 5 commerces de référence (idéalement répartis sur au moins 2 îles) ; participent aux revues de sprint et à la recette |
| **Comité de pilotage** | Maître d'ouvrage, PO, SM ; valide les jalons et les décisions structurantes |

### 5.3 Cérémonies

| Cérémonie | Fréquence | Durée max. | Participants | Résultat |
|---|---|---|---|---|
| Sprint Planning | Début de sprint | 2 h | PO, SM, équipe | Objectif de sprint, Sprint Backlog |
| Daily Scrum | Quotidien | 15 min | Équipe, SM | Synchronisation, obstacles identifiés |
| Backlog Refinement | Hebdomadaire | 1 h | PO, équipe | Stories prêtes (critères d'acceptation, estimation) |
| Sprint Review | Fin de sprint | 1 h | PO, équipe, pilotes | Démonstration, retours, backlog ajusté |
| Sprint Retrospective | Fin de sprint | 45 min | SM, équipe | Plan d'amélioration |

### 5.4 Artefacts

- **Product Backlog** : [scrum/PRODUCT-BACKLOG.md](scrum/PRODUCT-BACKLOG.md) — user stories au format « En tant que… je veux… afin de… », critères d'acceptation, estimation en points (suite de Fibonacci), priorité MoSCoW.
- **Sprint Backlog** : stories engagées et tâches du sprint en cours.
- **Incrément** : version déployée en recette à la fin de chaque sprint.
- **Burndown / vélocité** : suivis par le Scrum Master.

### 5.5 Definition of Ready (DoR)

Une user story peut entrer en sprint si :

- elle est rédigée au format standard et compréhensible par l'équipe ;
- ses critères d'acceptation sont testables ;
- elle est estimée ;
- ses dépendances sont identifiées et levées ;
- les règles métier impactées ([PRD §9](01-PRD.md#9-règles-métier)) sont identifiées.

### 5.6 Definition of Done (DoD)

Une user story est terminée si :

1. le code est relu par un pair (pull request approuvée) ;
2. les **tests automatisés** couvrant les critères d'acceptation sont écrits et passent (`php artisan test`) ;
3. aucune régression : la suite complète est verte en intégration continue ;
4. les écritures multi-tables sont transactionnelles et les données restent **scopées par `shop_id`** ;
5. les permissions sont appliquées de façon identique sur le web et l'API ;
6. les migrations sont réversibles et testées sur une base vierge ;
7. après ajout d'une Resource Filament, les permissions Shield sont régénérées et les rôles mis à jour ;
8. la documentation impactée (API, architecture, PRD) est à jour ;
9. la fonctionnalité est déployée en recette et démontrée au PO.

---

## 6. Livrables

| # | Livrable | Format | Échéance indicative |
|---|---|---|---|
| L1 | Rapport d'audit et correctifs du Sprint 1 | Markdown ([05-AUDIT-2026-11.md](05-AUDIT-2026-11.md)) + code | Livré — S1 |
| L2 | PRD, TDR, architecture, documentation API | Markdown (`docs/`) | Livré — S1, mis à jour à chaque sprint |
| L3 | Product Backlog priorisé et plan de releases | Markdown (`docs/scrum/`) | S1, mis à jour en continu |
| L4 | Incrément « rôles par commerce » | Code + tests + migration | Fin S2 |
| L5 | Incrément « abonnements & portefeuille » | Code + tests | Fin S4 |
| L6 | Incrément « fiabilité financière » | Code + tests + migration | Fin S5 |
| L7 | Incrément « synchronisation robuste » + version mobile compatible | Code serveur + application mobile | Fin S6 |
| L8 | Pipeline CI, environnements, procédures de sauvegarde/restauration | Configuration + guide | Fin S6 |
| L9 | Mise en production, guide d'exploitation, guide utilisateur | Plateforme en ligne + documents | Fin S7 |
| L10 | Procès-verbal de recette | Document signé | Fin S7 |

---

## 7. Organisation et gouvernance

### 7.1 Instances

| Instance | Composition | Fréquence | Rôle |
|---|---|---|---|
| Comité de pilotage | Maître d'ouvrage, PO, SM | Toutes les 2 sprints (≈ mensuel) | Valide jalons, budget, arbitrages majeurs |
| Revue de sprint | PO, équipe, pilotes | Toutes les 2 semaines | Valide les incréments |
| Point technique | Équipe, SM | Selon besoin | Décisions d'architecture (consignées dans [03-ARCHITECTURE.md](03-ARCHITECTURE.md)) |

### 7.2 Outils

- Gestion du code : Git, branche principale `master`, branches de fonctionnalité, pull requests obligatoires.
- Suivi du backlog : fichier `docs/scrum/PRODUCT-BACKLOG.md` (ou outil dédié au choix du PO : Linear, Jira, Trello…).
- Communication : canal de messagerie d'équipe ; groupe WhatsApp avec les commerçants pilotes.
- Environnements : développement local (SQLite/MySQL), recette, production.

### 7.3 Gestion des changements

Toute nouvelle demande est ajoutée au Product Backlog par le PO et priorisée lors du raffinement. Le contenu d'un sprint en cours n'est pas modifié, sauf incident critique de production (traité en priorité, avec rétrospective dédiée).

---

## 8. Calendrier indicatif

Démarrage : semaine du 9 novembre 2026. Les dates sont indicatives et seront ajustées à la vélocité constatée.

| Sprint | Période indicative | Objectif de sprint | Épics | Jalon |
|---|---|---|---|---|
| **S1** | jusqu'au 9 nov. 2026 | Stabilisation post-audit : isolation multi-tenant, permissions API, idempotence sync, cohérence des soldes, sécurisation caisse, module portefeuille sans plantage, 28 tests | — | **Réalisé** |
| **S2** | 9 nov. → 23 nov. 2026 | Rôles par commerce (US-101), rôle `owner` à la création (US-102), app mobile alignée sur l'API (US-406), CI (US-601) | EPIC-1, 4, 6 | J1 : sécurité des rôles |
| **S3** | 23 nov. → 7 déc. 2026 | Portefeuille automatique, souscription par débit, accès conditionné à l'abonnement (US-501 à 503, 507), scope tenant global (US-104) | EPIC-5, 1 | J2 : monétisation |
| **S4** | 7 déc. → 21 déc. 2026 | Coût historique et bénéfice sur marge (US-301, 302), archivage sans perte d'historique (US-201) | EPIC-3, 2 | J3 : fiabilité financière |
| **S5** | 4 janv. → 18 janv. 2027 | Pull paginé (US-401), limites de débit et purge tokens (US-403), login durci (US-404), notifications en file (US-603), renouvellement auto (US-504) | EPIC-4, 5, 6 | J4 : recette pilotes |
| **S6** | 18 janv. → 1er fév. 2027 | Références sans collision, politique de survente, annulation de vente, tests caisse et rapports (US-202 à 204, US-602) | EPIC-2, 6 | — |
| **S7** | 1er fév. → 15 fév. 2027 | Durcissement production (US-605 : sauvegardes, supervision), formation, recette finale ; évolutions « Could » selon la vélocité | EPIC-6 | **J5 : mise en production** |

Une pause est prévue sur la période des fêtes de fin d'année (22 déc. 2026 → 3 janv. 2027).

---

## 9. Profils requis

| Profil | Effectif indicatif | Compétences clés |
|---|---|---|
| Product Owner | 1 (temps partiel) | Connaissance du commerce comorien, priorisation, rédaction de user stories |
| Scrum Master | 1 (temps partiel, cumulable avec un rôle de l'équipe) | Animation Scrum, suivi d'indicateurs, facilitation |
| Développeur·se back-end senior | 1 | PHP 8.3, Laravel 11, Filament 3, Eloquent/observers, MySQL, Sanctum, Spatie Permission, tests PHPUnit |
| Développeur·se mobile | 1 (temps partiel) | React Native / Expo, SQLite, synchronisation hors-ligne |
| Testeur·se / QA | 1 (temps partiel) | Recette fonctionnelle, tests exploratoires sur mobile en réseau dégradé |
| DevOps | Ponctuel (S6–S7) | Hébergement Linux, Nginx/PHP-FPM, MySQL, sauvegardes, CI (GitHub Actions ou équivalent), supervision |

Qualités transverses : maîtrise du français, sensibilité à l'usage mobile en contexte de faible connectivité, rigueur sur la cohérence des données financières.

---

## 10. Critères d'acceptation et recette

### 10.1 Critères généraux

| Domaine | Critère |
|---|---|
| Fonctionnel | Chaque user story satisfait ses critères d'acceptation, démontrés en revue de sprint |
| Isolation | Aucun accès aux données d'un autre commerce (tests automatisés + tests d'intrusion simples sur l'API) |
| Permissions | Un caissier ne peut ni créer d'achat, ni ajuster le stock, ni consulter les rapports financiers (web, API, PDF) |
| Cohérence | Contrôle automatique : stock = dernier `stock_after` ; soldes clients/fournisseurs = sommes des dettes ouvertes |
| Hors-ligne | Une journée de ventes hors-ligne (≥ 100 opérations) se synchronise sans doublon ni perte, y compris avec envois répétés |
| Performance | Encaissement < 3 s (p95) en 3G simulée |
| Qualité | Suite de tests verte en CI ; aucune anomalie critique ou majeure ouverte |
| Exploitation | Sauvegarde quotidienne et restauration testée ; journalisation des erreurs active |

### 10.2 Processus de recette

1. **Recette continue** : chaque incrément est déployé en recette et vérifié en revue de sprint.
2. **Recette pilote** (S6) : 3 à 5 commerçants utilisent la plateforme en conditions réelles pendant 2 semaines ; anomalies tracées dans le backlog.
3. **Recette finale** (S7) : déroulé d'un cahier de recette couvrant les parcours du [PRD §8](01-PRD.md#8-parcours-utilisateurs-clés) ; signature d'un procès-verbal (avec ou sans réserves).

### 10.3 Classement des anomalies

| Niveau | Définition | Exigence |
|---|---|---|
| Critique | Perte/corruption de données, fuite inter-commerces, plateforme indisponible | Bloquant — correction immédiate |
| Majeure | Fonction principale inutilisable ou résultat financier faux | Bloquant pour la mise en production |
| Mineure | Gêne sans impact sur les données | Peut être levée après mise en production |

---

## 11. Risques et mesures d'atténuation

| Risque | Probabilité | Impact | Mesure d'atténuation |
|---|---|---|---|
| Migration des rôles globaux vers des rôles par commerce perturbe les comptes existants | Moyenne | Élevé | Script de migration testé sur copie de production ; déploiement en fenêtre de maintenance ; retour arrière préparé |
| Grille tarifaire non arrêtée | Élevée | Moyen | Décision PO exigée avant le Sprint 4 (jalon) |
| Évolutions API incompatibles avec les applications mobiles déjà installées | Moyenne | Élevé | Versionner l'API ou garantir la rétrocompatibilité ; mise à jour forcée de l'application si nécessaire |
| Connectivité trop faible chez les pilotes pour la recette | Moyenne | Moyen | Tests en réseau dégradé simulé ; mode hors-ligne prioritaire |
| Dépendance à une seule personne (bus factor) | Élevée | Élevé | Revue de code systématique, documentation (`docs/`, `CLAUDE.md`), binômage |
| Coupures d'électricité / Internet chez l'hébergeur local | Moyenne | Élevé | Hébergement chez un fournisseur cloud fiable ; sauvegardes hors site |
| Données personnelles (téléphones clients) | Faible | Moyen | Accès restreint, HTTPS, pas d'export non contrôlé, politique de confidentialité |
| Dérive du périmètre | Moyenne | Moyen | Backlog unique priorisé par le PO ; sprints protégés |

---

## 12. Budget indicatif (structure)

Les montants sont à définir par le maître d'ouvrage selon le mode de réalisation (équipe interne, prestataire, mixte).

| Poste | Unité | Quantité | Coût unitaire | Total |
|---|---|---|---|---|
| Développement back-end | jour·personne | à définir | à définir | à définir |
| Développement mobile | jour·personne | à définir | à définir | à définir |
| Product Owner / Scrum Master | jour·personne | à définir | à définir | à définir |
| Tests et recette (QA) | jour·personne | à définir | à définir | à définir |
| DevOps et mise en production | jour·personne | à définir | à définir | à définir |
| Hébergement (serveur, base, sauvegardes) | mois | à définir | à définir | à définir |
| Nom de domaine, certificats, services push | an | à définir | à définir | à définir |
| Comptes développeur (Google Play) | forfait | 1 | à définir | à définir |
| Accompagnement des commerçants pilotes (déplacements, data) | forfait | à définir | à définir | à définir |
| Formation et transfert de compétences | jour | à définir | à définir | à définir |
| Provision pour imprévus | % | à définir | — | à définir |
| **Total** | | | | **à définir** |

---

## 13. Annexes

| Annexe | Document |
|---|---|
| A | [01-PRD.md](01-PRD.md) — Exigences produit, règles métier, roadmap |
| B | [03-ARCHITECTURE.md](03-ARCHITECTURE.md) — Architecture technique, modèle de données, observers |
| C | [04-API.md](04-API.md) — Documentation de l'API mobile et de la synchronisation |
| D | [05-AUDIT-2026-11.md](05-AUDIT-2026-11.md) — Rapport d'audit et correctifs du Sprint 1 |
| E | [scrum/PRODUCT-BACKLOG.md](scrum/PRODUCT-BACKLOG.md) — Product Backlog et planification des sprints |
| F | `CLAUDE.md` (racine du dépôt) — Contexte projet et conventions de développement |
