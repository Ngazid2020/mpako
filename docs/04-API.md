# BeeZ (Mpako) — Référence de l'API mobile

> Version : Sprint 1 (stabilisation) — novembre 2026
> Public : développeurs de l'app mobile `beez-mobile` (Expo / React Native)
> Source : `routes/api.php`, `app/Http/Controllers/Api/**`

---

## 1. Généralités

| Élément | Valeur |
|---|---|
| URL de base | `{APP_URL}/api` |
| Format | JSON (`Content-Type: application/json`, `Accept: application/json`) |
| Authentification | Laravel Sanctum — `Authorization: Bearer {token}` |
| Durée du token | **30 jours** (un token par `device_name`, remplacé à chaque login) |
| Devise | KMF ; montants renvoyés en `float` |
| Dates | ISO 8601 (`updated_at`, `synced_at`) ; dates simples `YYYY-MM-DD` |
| Routes commerce | `/api/shop/{shop_slug}/...` |

### En-têtes

```http
Authorization: Bearer 12|xxxxxxxxxxxxxxxx
Accept: application/json
Content-Type: application/json
```

### Codes d'erreur

| Code | Cas | Corps |
|---|---|---|
| `401` | Token absent, invalide ou expiré | `{"message": "Unauthenticated."}` |
| `403` | Compte non approuvé / désactivé | `{"message": "Compte en attente de validation ou désactivé."}` |
| `403` | Non membre du commerce | `{"message": "Accès non autorisé à ce commerce."}` |
| `403` | Permission manquante pour l'action | `{"message": "Action non autorisée pour votre rôle."}` |
| `404` | Commerce inexistant ou inactif | `{"message": "Commerce introuvable."}` |
| `404` | Élément hors du commerce (`findOrFail` scopé) | `{"message": "..."}` |
| `422` | Validation / règle métier | `{"message": "...", "errors": {"champ": ["..."]}}` ou `{"message": "..."}` |
| `429` | Trop de tentatives de login (5 / min / IP) | — |

### Contrôle d'accès

Chaque requête `/shop/{slug}/...` passe par le middleware `ResolveShop` :
1. commerce trouvé **et actif** ;
2. utilisateur **approuvé** (re-vérifié à chaque appel) ;
3. utilisateur **membre** du commerce (sauf super-admin).

Puis chaque action vérifie une **permission Shield** (mêmes noms que le back-office). Les super-admins passent toujours.

---

## 2. Authentification

### `POST /api/auth/login` — public, `throttle:5,1`

Requête :

| Champ | Règles |
|---|---|
| `phone` | requis, string, max 30 |
| `password` | requis, string, max 255 |
| `device_name` | optionnel, string, max 100 (défaut `mobile-app`) |

Réponse `200` :

```json
{
  "success": true,
  "token": "12|xxxxxxxx",
  "user": { "id": 3, "name": "Ali", "phone": "+2693000001" },
  "shops": [
    { "id": 1, "name": "Boutique Ali", "slug": "boutique-ali", "island": "Grande Comore", "city": "Moroni", "currency": "KMF" }
  ]
}
```

Erreurs `422` sur `phone` : `Identifiants incorrects.` / `Compte en attente de validation.`
Seuls les commerces **actifs** sont renvoyés.

### `POST /api/auth/logout` — auth

Révoque le token courant. `200 {"success": true, "message": "Déconnexion réussie."}`

### `GET /api/auth/me` — auth

Même structure que le login, sans `token`.

### Notifications push

| Méthode | Chemin | Corps | Réponse |
|---|---|---|---|
| `POST` | `/api/push-subscriptions` | `endpoint` (url, requis), `keys.auth`, `keys.p256dh` (requis) | `{"status":"subscribed"}` |
| `DELETE` | `/api/push-subscriptions` | `endpoint` (url, requis) | `{"status":"unsubscribed"}` |

---

## 3. Récapitulatif des endpoints commerce

Préfixe : `/api/shop/{shop_slug}`

| Méthode | Chemin | Permission(s) requise(s) (au moins une) |
|---|---|---|
| GET | `/pull` | membre du commerce |
| POST | `/push` | selon le type d'opération (§ 6.2) |
| GET | `/products` | `view_any_product` |
| POST | `/products` | `create_product` |
| GET | `/customers` | `view_any_customer` |
| POST | `/customers` | `create_customer` |
| GET | `/customers/{id}/credits` | `view_any_credit` |
| GET | `/credits` | `view_any_credit` |
| POST | `/credits/{id}/pay` | `create_credit` ou `update_credit` |
| GET | `/sales` | `view_any_sale` |
| POST | `/sales` | `create_sale` (+ `create_credit` si `payment_type=credit`) |
| GET | `/suppliers` | `view_any_supplier` |
| POST | `/suppliers/{id}/pay` | `update_supplier` ou `update_purchase` |
| GET | `/purchases` | `view_any_purchase` |
| POST | `/purchases` | `create_purchase` |
| POST | `/purchases/{id}/complete` | `update_purchase` |
| POST | `/purchases/{id}/pay` | `update_purchase` ou `update_supplier` |
| GET | `/expenses` | `view_any_expense` |
| POST | `/expenses` | `create_expense` |
| GET | `/stock-movements` | `view_any_stock::movement` |
| POST | `/stock-adjustments` | `create_stock::movement` |
| GET | `/reports/summary` | `page_SalesReport` ou `page_ProfitReport` |
| GET | `/reports/sales-by-day` | `page_SalesReport` ou `page_ProfitReport` |
| GET | `/reports/top-products` | `page_SalesReport` ou `page_ProfitReport` |
| GET | `/reports/expenses-by-category` | `page_SalesReport` ou `page_ProfitReport` |

Rôle **caissier** (`ShopRolesSeeder`) : produits (lecture), ventes, crédits (création + encaissement), clients. Il reçoit donc `403` sur achats, fournisseurs, dépenses, stock et rapports.

---

## 4. Ressources (lecture)

### Produits — `GET /products`

Produits **actifs** du commerce.

```json
[{
  "id": 12, "name": "Riz parfumé 5kg", "barcode": "6291234567890",
  "sell_price": 4000.0, "buy_price": 3200.0, "stock_qty": 18.0, "stock_alert": 5.0,
  "is_active": true, "category_id": 2, "unit_id": 1,
  "unit": { "id": 1, "name": "Pièce", "abbreviation": "pcs" },
  "updated_at": "2026-11-09T07:12:00.000000Z"
}]
```

### Clients — `GET /customers`

```json
[{ "id": 4, "name": "Fatima", "phone": "+269...", "address": null, "balance": 2500.0, "is_active": true, "updated_at": "..." }]
```

`balance` = dette du client.

### Crédits — `GET /credits`, `GET /customers/{id}/credits`

```json
[{
  "id": 9, "reference": "CRD-20261109-0003", "customer_id": 4, "sale_id": 31,
  "status": "partial", "total_amount": 5000.0, "paid_amount": 1000.0, "remaining_amount": 4000.0,
  "due_date": "2026-11-30", "description": "Vente à crédit VNT-20261109-0012", "note": null,
  "is_overdue": false, "updated_at": "..."
}]
```

### Ventes — `GET /sales` (100 dernières)

```json
[{
  "id": 31, "reference": "VNT-20261109-0012", "status": "completed", "payment_type": "cash",
  "total_amount": 3000.0, "paid_amount": 5000.0, "change_amount": 2000.0, "customer_id": null, "note": null,
  "items": [{ "product_id": 12, "product_name": "Riz", "quantity": 3.0, "unit_price": 1000.0, "subtotal": 3000.0 }],
  "updated_at": "..."
}]
```

### Fournisseurs — `GET /suppliers`

```json
[{ "id": 2, "name": "Grossiste Moroni", "phone": null, "address": null, "balance": 25200.0, "is_active": true, "updated_at": "..." }]
```

`balance` = ce que le commerce doit au fournisseur.

### Achats — `GET /purchases` (100 derniers)

```json
[{
  "id": 7, "reference": "ACH-20261109-0001", "supplier_id": 2, "supplier": { "id": 2, "name": "Grossiste Moroni" },
  "status": "completed", "payment_status": "partial",
  "total_amount": 1000.0, "paid_amount": 600.0, "debt_amount": 400.0,
  "items": [{ "id": 15, "purchase_id": 7, "product_id": 12, "product_name": "Riz", "quantity": 4.0, "unit_cost": 250.0, "subtotal": 1000.0 }],
  "updated_at": "..."
}]
```

### Dépenses — `GET /expenses` (200 dernières)

```json
[{ "id": 5, "expense_category_id": 1, "category_name": "Transport", "description": "Taxi", "amount": 1500.0, "spent_at": "2026-11-09", "updated_at": "..." }]
```

### Mouvements de stock — `GET /stock-movements` (100 derniers)

```json
[{ "id": 88, "product_id": 12, "product_name": "Riz", "type": "out", "quantity": 3.0, "stock_before": 21.0, "stock_after": 18.0, "reason": "Vente VNT-...", "created_at": "..." }]
```

---

## 5. Écritures

### 5.1 `POST /products`

| Champ | Règles |
|---|---|
| `name` | requis, string, max 255 |
| `sell_price` | requis, numeric ≥ 0 |
| `buy_price` | optionnel, numeric ≥ 0 |
| `barcode` | optionnel, string |
| `stock_qty` | optionnel, numeric ≥ 0 — enregistré via un **mouvement `in` « Stock initial »** |
| `stock_alert` | optionnel, numeric ≥ 0 |
| `description` | optionnel |
| `unit_id`, `category_id` | optionnels, doivent appartenir **au commerce** |

Réponse `201` : `ProductResource`.

### 5.2 `POST /customers`

`name` (requis, max 255), `phone` (max 50), `address` (max 500). Réponse `201` : `CustomerResource`.

### 5.3 `POST /sales`

| Champ | Règles |
|---|---|
| `items` | requis, tableau ≥ 1 |
| `items.*.product_id` | requis, produit **du commerce** |
| `items.*.product_name` | optionnel (défaut : nom en base) |
| `items.*.quantity` | requis, numeric ≥ 0.01 |
| `items.*.unit_price` | requis, numeric ≥ 0 |
| `items.*.discount_type` | optionnel, `percent` \| `fixed` |
| `items.*.discount_value` | optionnel, numeric ≥ 0 (un pourcentage est plafonné à 100) |
| `discount_amount` | optionnel, remise globale (plafonnée au total des lignes) |
| `paid_amount` | optionnel (défaut = total) |
| `payment_type` | `cash` (défaut) \| `credit` |
| `customer_id` | **requis si `credit`**, client du commerce |
| `note` | optionnel, max 500 |
| `credit.due_date`, `credit.note` | optionnels |

Comportement (`SaleRecorder`) :
- **le total est recalculé côté serveur** : Σ(quantité × prix − remise ligne) − remise globale ; `total_amount`, `subtotal` et `change_amount` envoyés par le client sont **ignorés** ;
- stock vérifié **par produit, toutes lignes confondues** ; insuffisant → `422` avec le stock disponible ;
- vente à crédit : `paid_amount = 0`, création d'un crédit du montant total, `customer.balance` augmenté.

Réponse `201` : `SaleResource`.

### 5.4 `POST /credits/{id}/pay`

| Champ | Règles |
|---|---|
| `amount` | requis, numeric ≥ 1, **≤ reste dû** (sinon `422 "Montant supérieur au reste dû."`) |
| `paid_at` | optionnel, date (défaut : aujourd'hui) |
| `note` | optionnel, max 255 |

Crédit déjà soldé → `422`. Réponse `200` : `CreditResource` à jour.

### 5.5 `POST /suppliers/{id}/pay`

| Champ | Règles |
|---|---|
| `amount` | requis, numeric ≥ 1 |
| `paid_at` | optionnel, date |
| `note` | optionnel, max 255 |

Le paiement est **réparti en FIFO** sur les achats **validés** encore dus du fournisseur (du plus ancien au plus récent). Montant supérieur à la dette totale réelle → `422`. Aucune dette → `422`. Réponse `201` : `SupplierResource` à jour.

### 5.6 `POST /purchases`

| Champ | Règles |
|---|---|
| `supplier_id` | optionnel, fournisseur du commerce |
| `items` | requis, ≥ 1 |
| `items.*.product_id` | requis, produit du commerce |
| `items.*.product_name` | requis |
| `items.*.quantity` | requis, ≥ 0.01 |
| `items.*.unit_cost` | requis, ≥ 0 |
| `items.*.subtotal` | optionnel (recalculé) |
| `purchased_at` | optionnel, date (défaut : aujourd'hui) |
| `note` | optionnel, max 500 |

L'achat est **toujours créé en `pending`** ; `total_amount = debt_amount = Σ quantité × coût` (calcul serveur). Aucun effet sur le stock tant qu'il n'est pas validé. Réponse `201` : `PurchaseResource`.

### 5.7 `POST /purchases/{id}/complete`

Valide un achat `pending` : entrée en stock, mise à jour du prix d'achat, dette fournisseur. Statut différent de `pending` → `422`.

```json
{ "status": "completed", "message": "Achat validé. Le stock a été mis à jour." }
```

### 5.8 `POST /purchases/{id}/pay`

| Champ | Règles |
|---|---|
| `amount` | requis, ≥ 1, ≤ `debt_amount` |
| `paid_at` | optionnel, date |
| `note` | optionnel, max 255 |

Refusé (`422`) si l'achat n'est pas **validé**, n'a pas de fournisseur ou est déjà payé.

```json
{ "paid": 600.0, "remaining": 400.0, "status": "partial" }
```

### 5.9 `POST /expenses`

| Champ | Règles |
|---|---|
| `amount` | requis, numeric ≥ 1 |
| `expense_category_id` | optionnel, catégorie du commerce |
| `description` | optionnel, max 255 (défaut : « Dépense ») |
| `spent_at` | optionnel, date |
| `note` | optionnel, max 500 |

Réponse `201` : `ExpenseResource`.

### 5.10 `POST /stock-adjustments`

| Champ | Règles |
|---|---|
| `product_id` | requis, produit du commerce |
| `type` | `in` \| `out` \| `adjustment` |
| `quantity` | `in`/`out` : ≥ 0.01 ; `adjustment` : **nouvelle quantité absolue**, ≥ 0 |
| `reason` | optionnel, max 255 |

```json
{ "message": "Mouvement de stock enregistré.", "product": { "id": 12, "name": "Riz", "stock_qty": 20.0 } }
```

Le stock ne descend jamais sous 0.

### 5.11 Rapports

| Endpoint | Paramètres | Réponse |
|---|---|---|
| `GET /reports/summary` | — | `today{sales_count, revenue, expenses}`, `this_month{sales_count, revenue, expenses, credits_collected}`, `pending_credits{count,total_amount}`, `pending_purchases{count,total_amount}`, `supplier_debt`, `customer_debt`, `overdue_credits_count` |
| `GET /reports/sales-by-day` | `days` (7–30, défaut 14) | `[{date, count, revenue}]` (jours vides inclus) |
| `GET /reports/top-products` | `limit` (5–10, défaut 8) | `[{product_name, total_qty, total_revenue}]` sur 30 jours |
| `GET /reports/expenses-by-category` | — | `[{category_name, total}]` du mois en cours |

---

## 6. Synchronisation offline

### 6.1 `GET /pull?since={ISO8601}`

| Mode | Déclencheur | Contenu |
|---|---|---|
| Snapshot | sans `since` (premier démarrage) | tout le référentiel ; achats, dépenses, mouvements de stock, paiements de crédit limités aux **200 plus récents** |
| Incrémental | `since` fourni | **tout** ce qui a changé depuis `since`, **sans limite** |

Réponse :

```json
{
  "products": [ProductResource],
  "customers": [CustomerResource],
  "credits": [CreditResource],
  "sales": [SaleResource avec items],
  "suppliers": [{ "id", "name", "phone", "address", "balance", "updated_at" }],
  "purchases": [{ "id", "reference", "supplier_id", "supplier_name", "status", "payment_status", "total_amount", "paid_amount", "debt_amount", "items": [...], "updated_at" }],
  "expenses": [{ "id", "expense_category_id", "category_name", "description", "amount", "spent_at", "created_at", "updated_at" }],
  "stock_movements": [{ "id", "product_id", "product_name", "type", "quantity", "stock_before", "stock_after", "reason", "created_at" }],
  "credit_payments": [{ "id", "credit_id", "amount", "paid_at", "created_at" }],
  "expense_categories": [{ "id", "name" }],
  "synced_at": "2026-11-09T08:00:00.000000Z"
}
```

- `synced_at` est capturé **avant** les requêtes : le client doit le réutiliser tel quel comme prochain `since` (une écriture concurrente sera renvoyée au pull suivant, au pire en double — dédoublonner par `id`).
- Les fournisseurs inactifs ne sont pas renvoyés.
- Le snapshot `sales` n'est pas paginé (point de backlog).

### 6.2 `POST /push`

Corps :

```json
{
  "operations": [
    {
      "type": "create_sale",
      "local_id": "sale-7f3a9c",
      "created_offline_at": "2026-11-09T07:55:12Z",
      "payload": { "items": [{ "product_id": 12, "quantity": 2, "unit_price": 1000 }] }
    }
  ]
}
```

| Champ | Règles |
|---|---|
| `operations` | requis, tableau, **max 500** |
| `operations.*.type` | requis (voir tableau) |
| `operations.*.local_id` | requis, string ≤ 100, **unique par commerce et stable entre deux envois** |
| `operations.*.payload` | requis, objet |
| `operations.*.created_offline_at` | requis — les opérations sont exécutées dans cet ordre |

Types d'opérations :

| `type` | Permission | Payload | `data` renvoyé |
|---|---|---|---|
| `create_sale` | `create_sale` (+ `create_credit` si crédit) | comme `POST /sales` ; **pas de refus pour stock insuffisant** (la vente a eu lieu) | `{id, reference, total_amount}` |
| `create_credit` | `create_credit` | `customer_id` (requis), `total_amount` > 0, `sale_id?`, `due_date?`, `description?`, `note?` | `{id, reference}` |
| `create_credit_payment` | `create_credit` ou `update_credit` | `credit_id`, `amount` > 0, `paid_at?`, `note?` — plafonné au reste dû | `{id, credit_id, amount}` ou `{credit_id, already_paid: true}` |
| `create_stock_adjustment` | `create_stock::movement` | `product_id`, `type` (`in`/`out`/`adjustment`), `quantity`, `reason?` | `{id}` |
| `create_purchase` | `create_purchase` (+ `update_purchase` si `status=completed`) | `supplier_id?`, `items[{product_id, quantity, unit_cost, product_name?}]`, `status?` (`completed` → validé après insertion des lignes), `purchased_at?`, `note?`, `_local_purchase_rowid?` | `{id, reference, status}` |
| `create_expense` | `create_expense` | `amount`, `expense_category_id?`, `description?`, `spent_at?`, `note?` | `{id}` |
| `pay_supplier` | `update_supplier` ou `update_purchase` | `supplier_id`, `amount`, `paid_at?`, `note?` — FIFO sur achats validés | `{supplier_id, allocations: [{purchase_id, amount}]}` |
| `complete_purchase` | `update_purchase` | `purchase_id` ou `_local_purchase_rowid` | `{id, status}` (+ `already_done: true` si déjà validé) |
| `pay_purchase` | `update_purchase` ou `update_supplier` | `purchase_id` ou `_local_purchase_rowid`, `amount`, `paid_at?` — achat validé uniquement, plafonné à la dette | `{id, paid, remaining, status}` ou `{id, already_paid: true}` |

Réponse (toujours `200`, résultat par opération) :

```json
{
  "results": [
    { "local_id": "sale-7f3a9c", "status": "synced", "data": { "id": 31, "reference": "VNT-20261109-0012", "total_amount": 2000.0 } },
    { "local_id": "adj-01", "status": "failed", "error": "Élément introuvable dans ce commerce." }
  ]
}
```

#### Idempotence

- Chaque opération réussie est journalisée dans `sync_operations` (unique `(shop_id, local_id)`) **dans la même transaction** que ses effets.
- Un renvoi du même `local_id` (réponse perdue, retry réseau) **n'est pas rejoué** : le serveur renvoie `synced` avec le résultat stocké.
- Une opération en échec n'est pas journalisée : elle peut être renvoyée après correction.
- ⚠️ Le client **ne doit jamais régénérer** un `local_id` pour une même opération.

#### `_local_purchase_rowid`

Permet à `complete_purchase` / `pay_purchase` de cibler un achat créé hors-ligne **dans le même lot** `push`. D'un lot à l'autre, utiliser l'`id` serveur (`data.id` de `create_purchase`).

#### Messages d'erreur

| Cause | `error` |
|---|---|
| Validation / règle métier | premier message de validation, ex. « Un client est obligatoire pour une vente à crédit. » |
| Élément d'un autre commerce / inexistant | « Élément introuvable dans ce commerce. » |
| Permission | « Action non autorisée pour votre rôle. » |
| Type inconnu | « Type inconnu : xxx » |
| Erreur technique | « Erreur serveur lors de la synchronisation. » (détail uniquement dans les logs serveur) |

---

## 7. Changements cassants / notes de migration pour `beez-mobile`

Comportements modifiés au Sprint 1 que l'application mobile doit prendre en compte :

| # | Changement | Impact côté mobile |
|---|---|---|
| 1 | `total_amount`, `subtotal`, `change_amount` envoyés pour une vente sont **ignorés** ; le serveur recalcule | Afficher le total renvoyé (`data.total_amount` / `SaleResource`) ; aligner le calcul local des remises (% plafonné à 100) |
| 2 | Vente à crédit sans `customer_id` → `422` (avant : vente « crédit » sans dette) | Rendre le client obligatoire dans l'UI crédit |
| 3 | Vente REST : stock vérifié toutes lignes confondues, insuffisant → `422` (avant `500`) | Gérer l'erreur 422 et afficher le message |
| 4 | `POST /credits/{id}/pay` et `create_credit_payment` **fonctionnent** (avant : erreur SQL `user_id`) | Rejouer les paiements restés en échec dans la file |
| 5 | `POST /purchases/{id}/complete` et `/pay` **fonctionnent** (avant : `500` systématique) | Réactiver les écrans correspondants |
| 6 | Paiement d'un achat **non validé** refusé (`422`) | Proposer la validation avant le paiement |
| 7 | `POST /suppliers/{id}/pay` et `pay_supplier` répartissent en FIFO ; `pay_supplier` renvoie `allocations` | Mettre à jour les achats concernés à partir de `allocations` ou d'un pull |
| 8 | `create_purchase` avec `status: "completed"` met maintenant **réellement** le stock et la dette à jour | Ne plus compenser localement |
| 9 | `POST /purchases` : `total_amount` n'est plus requis (recalculé), `purchased_at` accepté | Facultatif |
| 10 | **Permissions appliquées** : un caissier reçoit `403` sur achats, fournisseurs, dépenses, stock, création de produit et rapports ; en sync, ces opérations passent en `failed` | Masquer ces écrans selon le rôle ; ne pas boucler sur les opérations `failed` pour permission |
| 11 | Compte non approuvé / désactivé → `403` sur toutes les routes commerce, même avec un token valide | Déconnecter et afficher un message |
| 12 | Produit, client, catégorie, fournisseur d'un **autre commerce** → `422`/`failed` | Aucun impact si l'app n'utilise que les données du pull |
| 13 | `POST /expenses` : `description` facultative (défaut « Dépense »), `note` désormais enregistrée | — |
| 14 | `/push` : renvoi d'un `local_id` déjà traité → `synced` avec le **résultat d'origine** (plus de doublon) ; max 500 opérations par appel | Garder des `local_id` stables ; découper les gros lots |
| 15 | `/push` : les messages d'erreur techniques ne contiennent plus de SQL | Ne pas parser les messages |
| 16 | `/pull` incrémental : plus de limite de 200 lignes ; `synced_at` pris avant les requêtes | Utiliser `synced_at` comme prochain `since` ; dédoublonner par `id` |
| 17 | `POST /products` : le stock initial crée un mouvement de stock (visible dans `stock_movements`) | — |
| 18 | Notifications de stock faible envoyées uniquement au **franchissement** du seuil | Moins de notifications push |
