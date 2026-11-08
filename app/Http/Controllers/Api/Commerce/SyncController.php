<?php

namespace App\Http\Controllers\Api\Commerce;

use App\Http\Controllers\Api\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CreditResource;
use App\Http\Resources\Api\CustomerResource;
use App\Http\Resources\Api\ProductResource;
use App\Http\Resources\Api\SaleResource;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\SupplierPayment;
use App\Models\SyncOperation;
use App\Services\SaleRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SyncController extends Controller
{
    use InteractsWithShop;

    /**
     * Sync descendante — envoie tout ce qui a changé depuis `?since=` (ISO timestamp).
     * Sans `since` → snapshot complet (premier démarrage).
     */
    public function pull(Request $request): JsonResponse
    {
        $shop  = $this->shop($request);
        $since = $request->query('since');

        // Horodatage capturé AVANT les requêtes : une écriture concurrente pendant le pull
        // sera renvoyée au prochain pull au lieu d'être perdue.
        $syncedAt = now()->toISOString();

        // Snapshot complet : on limite l'historique ; en incrémental on renvoie TOUT ce qui a changé
        // (une limite ferait perdre définitivement les lignes au-delà, le client avançant `since`).
        $historyLimit = $since ? null : 200;

        $query = fn($relation) => $since
            ? $shop->$relation()->where('updated_at', '>', $since)
            : $shop->$relation();

        $suppliers = $shop->suppliers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'phone'      => $s->phone,
                'address'    => $s->address,
                'balance'    => $s->balance,
                'updated_at' => $s->updated_at,
            ]);

        $purchases = $shop->purchases()
            ->with(['items', 'supplier'])
            ->when($since, fn($q) => $q->where('updated_at', '>=', $since))
            ->latest()
            ->when($historyLimit, fn($q) => $q->limit($historyLimit))
            ->get()
            ->map(fn($pur) => [
                'id'             => $pur->id,
                'reference'      => $pur->reference,
                'supplier_id'    => $pur->supplier_id,
                'supplier_name'  => $pur->supplier?->name,
                'status'         => $pur->status,
                'payment_status' => $pur->payment_status,
                'total_amount'   => (float) $pur->total_amount,
                'paid_amount'    => (float) $pur->paid_amount,
                'debt_amount'    => (float) $pur->debt_amount,
                'items'          => $pur->items->map(fn($i) => [
                    'product_id'   => $i->product_id,
                    'product_name' => $i->product_name,
                    'quantity'     => (float) $i->quantity,
                    'unit_cost'    => (float) $i->unit_cost,
                    'subtotal'     => (float) $i->subtotal,
                ]),
                'updated_at'     => $pur->updated_at,
            ]);

        $expenses = $shop->expenses()
            ->with('category')
            ->when($since, fn($q) => $q->where('updated_at', '>=', $since))
            ->latest()
            ->when($historyLimit, fn($q) => $q->limit($historyLimit))
            ->get()
            ->map(fn($e) => [
                'id'                  => $e->id,
                'expense_category_id' => $e->expense_category_id,
                'category_name'       => $e->category?->name,
                'description'         => $e->description,
                'amount'              => $e->amount,
                'spent_at'            => $e->spent_at,
                'created_at'          => $e->created_at?->toISOString(),
                'updated_at'          => $e->updated_at?->toISOString(),
            ]);

        $stockMovements = $shop->stockMovements()
            ->with('product')
            ->when($since, fn($q) => $q->where('created_at', '>=', $since))
            ->latest()
            ->when($historyLimit, fn($q) => $q->limit($historyLimit))
            ->get()
            ->map(fn($m) => [
                'id'           => $m->id,
                'product_id'   => $m->product_id,
                'product_name' => $m->product?->name,
                'type'         => $m->type,
                'quantity'     => $m->quantity,
                'stock_before' => $m->stock_before,
                'stock_after'  => $m->stock_after,
                'reason'       => $m->reason,
                'created_at'   => $m->created_at,
            ]);

        $creditPayments = DB::table('credit_payments')
            ->join('credits', 'credits.id', '=', 'credit_payments.credit_id')
            ->where('credits.shop_id', $shop->id)
            ->when($since, fn($q) => $q->where('credit_payments.created_at', '>=', $since))
            ->select([
                'credit_payments.id',
                'credit_payments.credit_id',
                'credit_payments.amount',
                'credit_payments.paid_at',
                'credit_payments.created_at',
            ])
            ->latest('credit_payments.created_at')
            ->when($historyLimit, fn($q) => $q->limit($historyLimit))
            ->get()
            ->map(fn($cp) => [
                'id'         => $cp->id,
                'credit_id'  => $cp->credit_id,
                'amount'     => (float) $cp->amount,
                'paid_at'    => $cp->paid_at,
                'created_at' => $cp->created_at,
            ]);

        // `?light=1` (mode hors ligne web) : sans l'historique volumineux
        // (ventes, mouvements de stock, paiements de crédit), inutile pour travailler hors ligne.
        $light = $request->boolean('light');

        return response()->json([
            'products'        => ProductResource::collection(
                $query('products')->with('unit')->get()
            ),
            'customers'       => CustomerResource::collection(
                $query('customers')->get()
            ),
            'credits'         => CreditResource::collection(
                $query('credits')->get()
            ),
            'sales'           => $light ? [] : SaleResource::collection(
                $query('sales')->with('items')->get()
            ),
            'suppliers'          => $suppliers,
            'purchases'          => $purchases,
            'expenses'           => $expenses,
            'stock_movements'    => $light ? [] : $stockMovements,
            'credit_payments'    => $light ? [] : $creditPayments,
            'expense_categories' => $shop->expenseCategories()
                ->get()
                ->map(fn($ec) => ['id' => $ec->id, 'name' => $ec->name]),
            'categories'         => $shop->categories()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
            'units'              => $shop->units()
                ->orderBy('name')
                ->get(['id', 'name', 'abbreviation'])
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'abbreviation' => $u->abbreviation]),
            'synced_at'          => $syncedAt,
        ]);
    }

    /**
     * Permissions requises par type d'opération (au moins une).
     * Mêmes noms que les Policies Filament (Shield).
     */
    private const OPERATION_PERMISSIONS = [
        'create_sale'             => ['create_sale'],
        'create_credit'           => ['create_credit'],
        'create_credit_payment'   => ['create_credit', 'update_credit'],
        'create_stock_adjustment' => ['create_stock::movement'],
        'create_purchase'         => ['create_purchase'],
        'create_expense'          => ['create_expense'],
        'pay_supplier'            => ['update_supplier', 'update_purchase'],
        'complete_purchase'       => ['update_purchase'],
        'pay_purchase'            => ['update_purchase', 'update_supplier'],
        'create_product'          => ['create_product'],
        'update_product'          => ['update_product'],
        'create_customer'         => ['create_customer'],
        'create_supplier'         => ['create_supplier'],
    ];

    /**
     * Champs de payload pouvant référencer un élément créé hors ligne via son local_id :
     * `<champ>_local_id` est résolu en `<champ>_id` à partir du journal sync_operations
     * (persistant : fonctionne aussi d'un lot de sync à l'autre).
     */
    private const LOCAL_REFERENCES = ['customer', 'supplier', 'product', 'purchase', 'credit', 'sale'];

    /**
     * Sync montante — reçoit un tableau d'opérations offline et les exécute dans l'ordre.
     * Les observers existants maintiennent la cohérence (stock, balances, etc.).
     *
     * Idempotence : chaque (shop_id, local_id) n'est exécuté qu'une fois ; un renvoi
     * (réponse perdue, retry réseau) retourne le résultat déjà enregistré.
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'operations'                      => 'required|array|max:500',
            'operations.*.type'               => 'required|string',
            'operations.*.local_id'           => 'required|string|max:100',
            'operations.*.payload'            => 'required|array',
            'operations.*.created_offline_at' => 'required|string',
        ]);

        $shop    = $this->shop($request);
        $user    = $request->user();
        $results = [];

        // Mappe _local_purchase_rowid → server purchase id
        // Nécessaire quand complete_purchase / pay_purchase référencent un achat créé offline dans la même session
        $rowIdToServerId = [];

        // Trier par date offline pour respecter l'ordre chronologique
        $operations = collect($request->operations)
            ->sortBy('created_offline_at')
            ->values();

        foreach ($operations as $op) {
            try {
                // ── Déjà traité ? → renvoyer le résultat stocké ──
                $already = SyncOperation::where('shop_id', $shop->id)
                    ->where('local_id', $op['local_id'])
                    ->first();

                if ($already) {
                    $result = $already->result ?? [];
                } else {
                    $permissions = self::OPERATION_PERMISSIONS[$op['type']] ?? null;

                    if ($permissions === null) {
                        throw new \InvalidArgumentException("Type inconnu : {$op['type']}");
                    }

                    if (! static::userCanAny($user, $permissions)) {
                        throw new AuthorizationException('Action non autorisée pour votre rôle.');
                    }

                    // Snapshot du mapping au moment de l'exécution (passé par valeur dans la closure)
                    $idMap = $rowIdToServerId;

                    // Références vers des éléments créés hors ligne (`customer_local_id`…)
                    $op['payload'] = $this->resolveLocalReferences($op['payload'], $shop);

                    $result = DB::transaction(function () use ($op, $shop, $user, $idMap) {
                        $result = match ($op['type']) {
                            'create_sale'             => $this->createSale($op, $shop, $user),
                            'create_credit'           => $this->createCredit($op, $shop, $user),
                            'create_credit_payment'   => $this->createCreditPayment($op, $shop, $user),
                            'create_stock_adjustment' => $this->createStockAdjustment($op, $shop, $user),
                            'create_purchase'         => $this->createPurchase($op, $shop, $user),
                            'create_expense'          => $this->createExpense($op, $shop, $user),
                            'pay_supplier'            => $this->paySupplier($op, $shop, $user),
                            'complete_purchase'       => $this->completePurchase($op, $shop, $idMap),
                            'pay_purchase'            => $this->payPurchase($op, $shop, $user, $idMap),
                            'create_product'          => $this->createProduct($op, $shop, $user),
                            'update_product'          => $this->updateProduct($op, $shop),
                            'create_customer'         => $this->createCustomer($op, $shop),
                            'create_supplier'         => $this->createSupplier($op, $shop),
                        };

                        // Journalisé dans la même transaction : soit tout est appliqué, soit rien
                        SyncOperation::create([
                            'shop_id'  => $shop->id,
                            'user_id'  => $user->id,
                            'local_id' => $op['local_id'],
                            'type'     => $op['type'],
                            'result'   => $result,
                        ]);

                        return $result;
                    });
                }

                // Après create_purchase réussi : enregistrer le mapping rowid → id serveur
                if ($op['type'] === 'create_purchase'
                    && isset($result['id'], $op['payload']['_local_purchase_rowid'])
                ) {
                    $rowIdToServerId[(int) $op['payload']['_local_purchase_rowid']] = (int) $result['id'];
                }

                $results[] = [
                    'local_id' => $op['local_id'],
                    'status'   => 'synced',
                    'data'     => $result,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'local_id' => $op['local_id'],
                    'status'   => 'failed',
                    'error'    => $this->publicErrorMessage($e),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Message renvoyé au mobile : on expose les erreurs métier, jamais le SQL/stack interne.
     */
    private function publicErrorMessage(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            return collect($e->errors())->flatten()->first() ?? 'Données invalides.';
        }

        if ($e instanceof ModelNotFoundException) {
            return 'Élément introuvable dans ce commerce.';
        }

        $isBusinessError = $e instanceof AuthorizationException
            || $e instanceof \InvalidArgumentException
            || ($e instanceof \RuntimeException && ! $e instanceof QueryException);

        if ($isBusinessError) {
            return $e->getMessage();
        }

        Log::error('Sync push failure', ['exception' => $e]);

        return 'Erreur serveur lors de la synchronisation.';
    }

    /**
     * Remplace récursivement `<x>_local_id` par `<x>_id` (lignes d'articles comprises)
     * en lisant le résultat de l'opération de création correspondante.
     * Si la création n'a pas encore été synchronisée, l'opération échoue proprement
     * et sera retentée par le client au prochain cycle.
     */
    private function resolveLocalReferences(array $payload, $shop): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->resolveLocalReferences($value, $shop);
                continue;
            }

            if (! is_string($key) || ! str_ends_with($key, '_local_id') || $value === null || $value === '') {
                continue;
            }

            $base = substr($key, 0, -strlen('_local_id'));

            if (! in_array($base, self::LOCAL_REFERENCES, true) || ! empty($payload["{$base}_id"])) {
                continue;
            }

            $created = SyncOperation::where('shop_id', $shop->id)->where('local_id', (string) $value)->first();

            if (! $created || empty($created->result['id'])) {
                throw new \RuntimeException("Élément créé hors ligne pas encore synchronisé ({$base}).");
            }

            $payload["{$base}_id"] = (int) $created->result['id'];
        }

        return $payload;
    }

    // ─── Handlers individuels ───────────────────────────────────────────

    private function createProduct(array $op, $shop, $user): array
    {
        $data = Validator::make($op['payload'], [
            'name'        => 'required|string|max:255',
            'sell_price'  => 'required|numeric|min:0',
            'buy_price'   => 'nullable|numeric|min:0',
            'barcode'     => 'nullable|string|max:255',
            'stock_qty'   => 'nullable|numeric|min:0',
            'stock_alert' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'unit_id'     => ['nullable', 'integer', $this->inShop('units', $shop)],
            'category_id' => ['nullable', 'integer', $this->inShop('categories', $shop)],
        ])->validate();

        $product = $shop->products()->create([
            'name'        => $data['name'],
            'sell_price'  => $data['sell_price'],
            'buy_price'   => $data['buy_price'] ?? 0,
            'barcode'     => $data['barcode'] ?? null,
            'stock_qty'   => 0,
            'stock_alert' => $data['stock_alert'] ?? 0,
            'description' => $data['description'] ?? null,
            'unit_id'     => $data['unit_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'is_active'   => true,
        ]);

        // Stock initial tracé par un mouvement (l'observer met stock_qty à jour)
        if (($data['stock_qty'] ?? 0) > 0) {
            StockMovement::create([
                'shop_id'    => $shop->id,
                'product_id' => $product->id,
                'user_id'    => $user->id,
                'type'       => 'in',
                'quantity'   => $data['stock_qty'],
                'reason'     => 'Stock initial (hors ligne)',
            ]);
        }

        return ['id' => $product->id];
    }

    /**
     * Modification d'une fiche produit (dernière écriture gagnante).
     * Le stock n'est JAMAIS modifié ici : il passe par create_stock_adjustment (traçabilité).
     */
    private function updateProduct(array $op, $shop): array
    {
        $payload = $op['payload'];
        $product = $shop->products()->findOrFail($payload['product_id'] ?? 0);

        $data = Validator::make($payload, [
            'name'        => 'sometimes|required|string|max:255',
            'sell_price'  => 'sometimes|required|numeric|min:0',
            'buy_price'   => 'sometimes|nullable|numeric|min:0',
            'barcode'     => 'sometimes|nullable|string|max:255',
            'stock_alert' => 'sometimes|nullable|numeric|min:0',
            'description' => 'sometimes|nullable|string',
            'is_active'   => 'sometimes|boolean',
            'unit_id'     => ['sometimes', 'nullable', 'integer', $this->inShop('units', $shop)],
            'category_id' => ['sometimes', 'nullable', 'integer', $this->inShop('categories', $shop)],
        ])->validate();

        $product->update($data);

        return ['id' => $product->id];
    }

    private function createCustomer(array $op, $shop): array
    {
        $data = Validator::make($op['payload'], [
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ])->validate();

        $customer = $shop->customers()->create($data + ['balance' => 0, 'is_active' => true]);

        return ['id' => $customer->id];
    }

    private function createSupplier(array $op, $shop): array
    {
        $data = Validator::make($op['payload'], [
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ])->validate();

        $supplier = $shop->suppliers()->create($data + ['balance' => 0, 'is_active' => true]);

        return ['id' => $supplier->id];
    }

    private function createSale(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        if (($p['payment_type'] ?? 'cash') === 'credit' && ! static::userCanAny($user, ['create_credit'])) {
            throw new AuthorizationException('Vente à crédit non autorisée pour votre rôle.');
        }

        // La vente a déjà eu lieu physiquement hors-ligne : pas de refus pour stock insuffisant
        $sale = app(SaleRecorder::class)->record($shop, $user, $p, strictStock: false, reasonSuffix: '(sync offline)');

        return ['id' => $sale->id, 'reference' => $sale->reference, 'total_amount' => (float) $sale->total_amount];
    }

    private function createCredit(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $customer = $shop->customers()->findOrFail($p['customer_id'] ?? 0);
        $saleId   = ! empty($p['sale_id']) ? $shop->sales()->findOrFail($p['sale_id'])->id : null;
        $total    = (float) ($p['total_amount'] ?? 0);

        if ($total <= 0) {
            throw ValidationException::withMessages(['total_amount' => 'Le montant du crédit doit être positif.']);
        }

        $credit = Credit::create([
            'shop_id'          => $shop->id,
            'sale_id'          => $saleId,
            'customer_id'      => $customer->id,
            'user_id'          => $user->id,
            'reference'        => Credit::generateReference($shop->id),
            'status'           => 'pending',
            'total_amount'     => $total,
            'paid_amount'      => 0,
            'remaining_amount' => $total,
            'due_date'         => $p['due_date'] ?? null,
            'description'      => $p['description'] ?? 'Crédit enregistré hors ligne',
            'note'             => $p['note'] ?? null,
        ]);

        // CreditObserver augmente customer.balance automatiquement

        return ['id' => $credit->id, 'reference' => $credit->reference];
    }

    private function createCreditPayment(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $credit = $shop->credits()->lockForUpdate()->findOrFail($p['credit_id'] ?? 0);
        $amount = (float) ($p['amount'] ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Montant invalide.']);
        }

        if ($credit->status === 'paid' || (float) $credit->remaining_amount <= 0) {
            return ['credit_id' => $credit->id, 'already_paid' => true];
        }

        // Paiement plafonné au reste dû
        $amount = min($amount, (float) $credit->remaining_amount);

        $payment = CreditPayment::create([
            'credit_id' => $credit->id,
            'user_id'   => $user->id,
            'amount'    => $amount,
            'paid_at'   => $p['paid_at'] ?? now()->toDateString(),
            'note'      => $p['note'] ?? null,
        ]);

        // CreditPaymentObserver met à jour credit + customer.balance automatiquement

        return ['id' => $payment->id, 'credit_id' => $credit->id, 'amount' => $amount];
    }

    private function createStockAdjustment(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        if (! in_array($p['type'] ?? null, ['in', 'out', 'adjustment'], true)) {
            throw ValidationException::withMessages(['type' => 'Type de mouvement invalide.']);
        }

        $product = $shop->products()->lockForUpdate()->findOrFail($p['product_id'] ?? 0);

        $movement = StockMovement::create([
            'shop_id'    => $shop->id,
            'product_id' => $product->id,
            'user_id'    => $user->id,
            'type'       => $p['type'], // 'in' | 'out' | 'adjustment'
            'quantity'   => $p['quantity'],
            'reason'     => $p['reason'] ?? 'Ajustement offline',
        ]);

        return ['id' => $movement->id];
    }

    private function createPurchase(array $op, $shop, $user): array
    {
        $p     = $op['payload'];
        $items = collect($p['items'] ?? []);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Achat sans article.']);
        }

        $supplierId = ! empty($p['supplier_id']) ? $shop->suppliers()->findOrFail($p['supplier_id'])->id : null;

        $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
        $products   = $shop->products()->whereIn('id', $productIds)->get()->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Un ou plusieurs produits sont introuvables dans ce commerce.']);
        }

        $total = round($items->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_cost']), 2);

        // Toujours créé en 'pending' : le PurchaseObserver ne peut traiter le stock
        // qu'une fois les lignes enregistrées.
        $purchase = Purchase::create([
            'shop_id'         => $shop->id,
            'user_id'         => $user->id,
            'supplier_id'     => $supplierId,
            'reference'       => Purchase::generateReference($shop->id),
            'status'          => 'pending',
            'payment_status'  => 'unpaid',
            'total_amount'    => $total,
            'paid_amount'     => 0,
            'debt_amount'     => $total,
            'purchased_at'    => $p['purchased_at'] ?? now()->toDateString(),
            'note'            => $p['note'] ?? null,
        ]);

        foreach ($items as $item) {
            PurchaseItem::create([
                'purchase_id'  => $purchase->id,
                'product_id'   => (int) $item['product_id'],
                'product_name' => $item['product_name'] ?? $products[(int) $item['product_id']]->name,
                'quantity'     => $item['quantity'],
                'unit_cost'    => $item['unit_cost'],
            ]);
        }

        if (($p['status'] ?? 'pending') === 'completed') {
            if (! static::userCanAny($user, ['update_purchase'])) {
                throw new AuthorizationException("Validation d'achat non autorisée pour votre rôle.");
            }

            // PurchaseObserver::updated → stock, buy_price, supplier.balance
            $purchase->update(['status' => 'completed']);
        }

        return ['id' => $purchase->id, 'reference' => $purchase->reference, 'status' => $purchase->status];
    }

    private function createExpense(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $categoryId = ! empty($p['expense_category_id'])
            ? $shop->expenseCategories()->findOrFail($p['expense_category_id'])->id
            : null;

        $expense = Expense::create([
            'shop_id'             => $shop->id,
            'user_id'             => $user->id,
            'expense_category_id' => $categoryId,
            'description'         => ($p['description'] ?? null) ?: 'Dépense',
            'amount'              => $p['amount'],
            'spent_at'            => $p['spent_at'] ?? now()->toDateString(),
            'note'                => $p['note'] ?? null,
        ]);

        return ['id' => $expense->id];
    }

    private function paySupplier(array $op, $shop, $user): array
    {
        $p = $op['payload'];

        $supplier = $shop->suppliers()->lockForUpdate()->findOrFail($p['supplier_id'] ?? 0);

        // Répartition FIFO sur les achats validés impayés (même logique que l'API REST)
        $allocations = SupplierController::allocateSupplierPayment(
            $shop,
            $supplier,
            $user,
            (float) ($p['amount'] ?? 0),
            $p['paid_at'] ?? null,
            $p['note'] ?? null,
        );

        return ['supplier_id' => $supplier->id, 'allocations' => $allocations];
    }

    /**
     * Valide un achat (pending → completed) depuis la file offline.
     *
     * Le payload peut contenir :
     *   - purchase_id            : achat déjà synchronisé (id serveur connu)
     *   - _local_purchase_rowid  : achat créé offline dans la même session
     *                              → résolu via $rowIdToServerId
     *
     * Le PurchaseObserver gère automatiquement : stock, buy_price, supplier.balance.
     */
    private function completePurchase(array $op, $shop, array $rowIdToServerId): array
    {
        $p          = $op['payload'];
        $purchaseId = $p['purchase_id'] ?? null;

        if (! $purchaseId && isset($p['_local_purchase_rowid'])) {
            $purchaseId = $rowIdToServerId[(int) $p['_local_purchase_rowid']] ?? null;
        }

        if (! $purchaseId) {
            throw new \RuntimeException('Impossible de résoudre l\'achat à valider (rowid introuvable).');
        }

        $purchase = $shop->purchases()->lockForUpdate()->findOrFail($purchaseId);

        // Idempotent : déjà validé → OK sans erreur
        if ($purchase->status === 'completed') {
            return ['id' => $purchase->id, 'status' => 'completed', 'already_done' => true];
        }

        if ($purchase->status !== 'pending') {
            throw new \RuntimeException("Achat non validable (statut : {$purchase->status}).");
        }

        $purchase->update(['status' => 'completed']);

        return ['id' => $purchase->id, 'status' => 'completed'];
    }

    /**
     * Enregistre un paiement de la dette fournisseur depuis la file offline.
     *
     * Le payload peut contenir :
     *   - purchase_id            : achat déjà synchronisé
     *   - _local_purchase_rowid  : achat créé offline dans la même session
     *
     * Le SupplierPaymentObserver gère : paid_amount, debt_amount, payment_status, supplier.balance.
     */
    private function payPurchase(array $op, $shop, $user, array $rowIdToServerId): array
    {
        $p          = $op['payload'];
        $purchaseId = $p['purchase_id'] ?? null;

        if (! $purchaseId && isset($p['_local_purchase_rowid'])) {
            $purchaseId = $rowIdToServerId[(int) $p['_local_purchase_rowid']] ?? null;
        }

        if (! $purchaseId) {
            throw new \RuntimeException('Impossible de résoudre l\'achat pour le paiement (rowid introuvable).');
        }

        $purchase = $shop->purchases()->lockForUpdate()->findOrFail($purchaseId);

        if ($purchase->status !== 'completed') {
            throw new \RuntimeException("Seul un achat validé peut être payé (statut : {$purchase->status}).");
        }

        if (! $purchase->supplier_id) {
            throw new \RuntimeException('Achat sans fournisseur : aucune dette à régler.');
        }

        if ((float) $purchase->debt_amount <= 0) {
            return ['id' => $purchase->id, 'already_paid' => true];
        }

        $amount = min((float) $p['amount'], (float) $purchase->debt_amount);

        SupplierPayment::create([
            'purchase_id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'paid_at'     => $p['paid_at'] ?? now()->toDateString(),
        ]);

        $purchase->refresh();

        return [
            'id'        => $purchase->id,
            'paid'      => $amount,
            'remaining' => (float) $purchase->debt_amount,
            'status'    => $purchase->payment_status,
        ];
    }
}
