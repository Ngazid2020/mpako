<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <title>{{ $shop->name }} — Mode hors ligne · BeeZ</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/images/icons/icon-192x192.png">
    @vite(['resources/css/offline.css', 'resources/js/offline/app.js'])
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/serviceworker.js', { scope: '/' }).catch(() => {});
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
<div
    x-data="offlineApp(@js([
        'shop'        => ['slug' => $shop->slug, 'name' => $shop->name],
        'user'        => ['id' => $user->id, 'name' => $user->name],
        'permissions' => $permissions,
        'apiBase'     => $apiBase,
        'panelUrl'    => $panelUrl,
        'tokenUrl'    => $tokenUrl,
    ]))"
    x-cloak
    class="mx-auto flex min-h-screen max-w-3xl flex-col pb-20"
>
    {{-- ═══════════ En-tête ═══════════ --}}
    <header class="sticky top-0 z-20 bg-primary-600 px-4 py-3 text-white shadow">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-base font-bold" x-text="config.shop.name"></p>
                <p class="text-xs text-primary-100">
                    <span x-show="meta.lastSync">Dernière synchro : <span x-text="fmtDate(meta.lastSync)"></span></span>
                    <span x-show="!meta.lastSync">Jamais synchronisé sur ce poste</span>
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="ol-badge" :class="online ? 'bg-green-500/20 text-white' : 'bg-red-500 text-white'">
                    <span class="mr-1 h-2 w-2 rounded-full" :class="online ? 'bg-green-300' : 'bg-white'"></span>
                    <span x-text="online ? 'En ligne' : 'Hors ligne'"></span>
                </span>
                <button type="button" @click="sync()" :disabled="!online || syncing"
                        class="rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold hover:bg-white/25 disabled:opacity-50">
                    <span x-show="!syncing">⟳ Sync<span x-show="pendingCount > 0" x-text="` (${pendingCount})`"></span></span>
                    <span x-show="syncing">Synchro…</span>
                </button>
            </div>
        </div>
    </header>

    {{-- Alertes --}}
    <div x-show="!online" class="bg-amber-100 px-4 py-2 text-sm text-amber-900">
        Vous travaillez hors ligne. Vos opérations sont enregistrées sur cet appareil et seront envoyées automatiquement au retour de la connexion.
    </div>
    <div x-show="online && wasOffline && !authError" class="flex flex-wrap items-center justify-between gap-2 bg-green-100 px-4 py-2 text-sm text-green-900">
        <span x-show="syncing || pendingCount > 0">Connexion rétablie, synchronisation en cours…</span>
        <span x-show="!syncing && pendingCount === 0">Connexion rétablie, tout est synchronisé ✔</span>
        <span class="flex gap-2">
            <a :href="config.panelUrl" x-show="!syncing && pendingCount === 0" class="font-semibold underline">Retour à la plateforme</a>
            <button type="button" @click="wasOffline = false" class="text-green-700">Masquer</button>
        </span>
    </div>
    <div x-show="authError" class="bg-red-100 px-4 py-2 text-sm text-red-800">
        <span x-text="authError"></span>
        <a :href="config.panelUrl" class="font-semibold underline">Se reconnecter</a>
    </div>
    <div x-show="ready && !snapshot && !online" class="m-4 rounded-xl bg-white p-6 text-center shadow">
        <p class="font-semibold">Aucune donnée disponible sur ce poste.</p>
        <p class="mt-1 text-sm text-gray-500">Ouvrez cette page une première fois avec une connexion pour préparer le mode hors ligne.</p>
    </div>

    <main class="flex-1 space-y-4 p-4">

        {{-- ═══════════ CAISSE ═══════════ --}}
        <section x-show="tab === 'caisse'" class="space-y-4">
            <template x-if="!can('create_sale')">
                <p class="ol-card p-4 text-sm text-gray-500">Votre rôle ne permet pas d'encaisser.</p>
            </template>
            <template x-if="can('create_sale')">
                <div class="space-y-4">
                    <input type="search" x-model="search" placeholder="Filtrer par nom ou code-barres…" class="ol-input">

                    <div class="ol-card max-h-80 divide-y overflow-y-auto">
                        <template x-for="product in saleProducts" :key="keyOf(product)">
                            <button type="button" @click="addToCart(product)" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-primary-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium" x-text="product.name"></span>
                                    <span class="ol-badge mt-1"
                                          :class="Number(product.stock_qty) <= 0 ? 'bg-red-100 text-red-700' : (Number(product.stock_qty) <= Number(product.stock_alert) ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700')"
                                          x-text="`Stock : ${fmt(product.stock_qty)} ${unitOf(product)}`"></span>
                                </span>
                                <span class="shrink-0 font-bold text-primary-600" x-text="`${fmt(product.sell_price)} KMF`"></span>
                            </button>
                        </template>
                        <p x-show="saleProducts.length === 0" class="p-6 text-center text-sm text-gray-500">Aucun produit.</p>
                    </div>

                    <div class="ol-card p-4">
                        <h2 class="mb-3 font-semibold">🛒 Panier</h2>
                        <p x-show="cart.length === 0" class="text-sm text-gray-500">Touchez un produit pour l'ajouter.</p>
                        <div class="space-y-3">
                            <template x-for="(line, index) in cart" :key="line.key">
                                <div class="rounded-lg border border-gray-200 p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-medium" x-text="line.name"></p>
                                        <button type="button" @click="cart.splice(index, 1)" class="text-sm text-red-600">Retirer</button>
                                    </div>
                                    <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                                        <label>Qté <input type="number" min="0.01" step="any" x-model.number="line.quantity" class="ol-input"></label>
                                        <label>Remise
                                            <select x-model="line.discount_type" class="ol-input">
                                                <option value="percent">%</option>
                                                <option value="fixed">KMF</option>
                                            </select>
                                        </label>
                                        <label>Valeur <input type="number" min="0" step="any" x-model.number="line.discount_value" class="ol-input"></label>
                                    </div>
                                    <p class="mt-2 text-right text-sm font-semibold" x-text="`${fmt(lineTotal(line))} KMF`"></p>
                                </div>
                            </template>
                        </div>

                        <div x-show="cart.length > 0" class="mt-4 space-y-3 border-t pt-4">
                            <p class="flex justify-between text-lg font-bold"><span>Total</span><span x-text="`${fmt(cartTotal)} KMF`"></span></p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="paymentType = 'cash'" class="ol-btn" :class="paymentType === 'cash' ? 'bg-primary-600 text-white' : 'bg-gray-100'">💵 Comptant</button>
                                <button type="button" @click="paymentType = 'credit'" x-show="can('create_credit')" class="ol-btn" :class="paymentType === 'credit' ? 'bg-primary-600 text-white' : 'bg-gray-100'">📒 À crédit</button>
                            </div>
                            <template x-if="paymentType === 'cash'">
                                <div>
                                    <label class="ol-label">Montant reçu (vide = montant exact)</label>
                                    <input type="number" min="0" step="any" x-model="paidAmount" class="ol-input">
                                    <p class="mt-1 text-sm text-gray-600" x-show="paidAmount !== ''">Monnaie à rendre : <strong x-text="`${fmt(change)} KMF`"></strong></p>
                                </div>
                            </template>
                            <template x-if="paymentType === 'credit'">
                                <div class="space-y-2">
                                    <label class="ol-label">Client</label>
                                    <select x-model="customerKey" class="ol-input">
                                        <option value="">— Choisir un client —</option>
                                        <template x-for="c in state.customers" :key="keyOf(c)">
                                            <option :value="keyOf(c)" x-text="c.name"></option>
                                        </template>
                                    </select>
                                    <label class="ol-label">Échéance (optionnelle)</label>
                                    <input type="date" x-model="dueDate" class="ol-input">
                                </div>
                            </template>
                            <button type="button" @click="checkout()" class="ol-btn-primary w-full py-3 text-base">Encaisser</button>
                        </div>
                    </div>

                    <div x-show="state.pending_sales.length > 0" class="ol-card p-4">
                        <h3 class="mb-2 text-sm font-semibold text-gray-700">Ventes en attente de synchronisation</h3>
                        <template x-for="sale in state.pending_sales" :key="sale.local_id">
                            <p class="flex justify-between border-b py-1 text-sm last:border-0">
                                <span x-text="`${fmtDate(sale.created_at)} · ${sale.items_count} article(s) · ${sale.payment_type === 'credit' ? 'crédit' : 'comptant'}`"></span>
                                <strong x-text="`${fmt(sale.total_amount)} KMF`"></strong>
                            </p>
                        </template>
                    </div>
                </div>
            </template>
        </section>

        {{-- ═══════════ PRODUITS ═══════════ --}}
        <section x-show="tab === 'produits'" class="space-y-3">
            <div class="flex gap-2">
                <input type="search" x-model="productSearch" placeholder="Rechercher un produit…" class="ol-input">
                <button type="button" x-show="can('create_product')" @click="openProduct()" class="ol-btn-primary shrink-0">+ Produit</button>
            </div>
            <div class="ol-card divide-y">
                <template x-for="product in productList" :key="keyOf(product)">
                    <div class="flex items-center justify-between gap-3 px-4 py-3" :class="!product.is_active && 'opacity-50'">
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                <span x-text="product.name"></span>
                                <span x-show="product._pending" class="ol-badge ml-1 bg-amber-100 text-amber-800">non synchronisé</span>
                            </p>
                            <p class="text-sm text-gray-500" x-text="`${fmt(product.sell_price)} KMF · stock ${fmt(product.stock_qty)} ${unitOf(product)}`"></p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <button type="button" x-show="can('create_stock::movement')" @click="openStock(product)" class="ol-btn-light px-3 py-1.5">Stock</button>
                            <button type="button" x-show="can('update_product')" @click="openProduct(product)" class="ol-btn-light px-3 py-1.5">Modifier</button>
                        </div>
                    </div>
                </template>
                <p x-show="productList.length === 0" class="p-6 text-center text-sm text-gray-500">Aucun produit.</p>
            </div>
        </section>

        {{-- ═══════════ CLIENTS & CRÉDITS ═══════════ --}}
        <section x-show="tab === 'clients'" class="space-y-3">
            <div class="flex gap-2">
                <input type="search" x-model="customerSearch" placeholder="Rechercher un client…" class="ol-input">
                <button type="button" x-show="can('create_customer')" @click="modal = 'customer'" class="ol-btn-primary shrink-0">+ Client</button>
            </div>
            <template x-for="customer in customerList" :key="keyOf(customer)">
                <div class="ol-card p-4" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
                        <span>
                            <span class="font-medium" x-text="customer.name"></span>
                            <span x-show="customer._pending" class="ol-badge ml-1 bg-amber-100 text-amber-800">non synchronisé</span>
                            <span class="block text-sm text-gray-500" x-text="customer.phone ?? ''"></span>
                        </span>
                        <span class="font-semibold" :class="Number(customer.balance) > 0 ? 'text-red-600' : 'text-green-600'"
                              x-text="Number(customer.balance) > 0 ? `Doit ${fmt(customer.balance)} KMF` : 'À jour'"></span>
                    </button>
                    <div x-show="open" class="mt-3 space-y-2 border-t pt-3">
                        <template x-for="credit in creditsOf(customer)" :key="keyOf(credit)">
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span>
                                    <span x-text="credit.reference ?? 'Crédit hors ligne'"></span> ·
                                    <span x-text="`reste ${fmt(credit.remaining_amount)} / ${fmt(credit.total_amount)} KMF`"></span>
                                    <span x-show="credit.is_overdue" class="ol-badge bg-red-100 text-red-700">en retard</span>
                                </span>
                                <button type="button"
                                        x-show="credit.id && Number(credit.remaining_amount) > 0 && can('create_credit', 'update_credit')"
                                        @click="openCreditPayment(credit)" class="ol-btn-light px-3 py-1">Encaisser</button>
                                <span x-show="!credit.id" class="text-xs text-amber-700">à synchroniser avant remboursement</span>
                            </div>
                        </template>
                        <p x-show="creditsOf(customer).length === 0" class="text-sm text-gray-500">Aucun crédit.</p>
                    </div>
                </div>
            </template>
        </section>

        {{-- ═══════════ ACHATS & FOURNISSEURS ═══════════ --}}
        <section x-show="tab === 'achats'" class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" x-show="can('create_purchase')" @click="openPurchase()" class="ol-btn-primary">+ Achat</button>
                <button type="button" x-show="can('create_supplier')" @click="modal = 'supplier'" class="ol-btn-light">+ Fournisseur</button>
            </div>

            <div class="ol-card divide-y">
                <h2 class="px-4 py-3 font-semibold">Fournisseurs</h2>
                <template x-for="supplier in state.suppliers" :key="keyOf(supplier)">
                    <div class="flex items-center justify-between gap-2 px-4 py-3">
                        <span>
                            <span class="font-medium" x-text="supplier.name"></span>
                            <span x-show="supplier._pending" class="ol-badge ml-1 bg-amber-100 text-amber-800">non synchronisé</span>
                            <span class="block text-sm" :class="Number(supplier.balance) > 0 ? 'text-red-600' : 'text-gray-500'"
                                  x-text="Number(supplier.balance) > 0 ? `Dette : ${fmt(supplier.balance)} KMF` : 'Aucune dette'"></span>
                        </span>
                        <button type="button" x-show="Number(supplier.balance) > 0 && can('update_supplier', 'update_purchase')"
                                @click="openSupplierPayment(supplier)" class="ol-btn-light px-3 py-1.5">Payer</button>
                    </div>
                </template>
            </div>

            <div class="ol-card divide-y">
                <h2 class="px-4 py-3 font-semibold">Achats récents</h2>
                <template x-for="purchase in state.purchases" :key="keyOf(purchase)">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="min-w-0">
                                <span class="font-medium" x-text="purchase.reference"></span> ·
                                <span class="text-sm" x-text="supplierName(purchase)"></span>
                                <span class="block text-sm text-gray-500"
                                      x-text="`${fmt(purchase.total_amount)} KMF · dette ${fmt(purchase.debt_amount)} KMF`"></span>
                            </span>
                            <span class="ol-badge shrink-0"
                                  :class="{ 'bg-amber-100 text-amber-800': purchase.status === 'pending', 'bg-green-100 text-green-700': purchase.status === 'completed', 'bg-gray-100 text-gray-600': purchase.status === 'cancelled' }"
                                  x-text="{ pending: 'En attente', completed: 'Validé', cancelled: 'Annulé' }[purchase.status]"></span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button type="button" x-show="purchase.status === 'pending' && can('update_purchase')" @click="completePurchase(purchase)" class="ol-btn-light px-3 py-1.5">Valider</button>
                            <button type="button" x-show="purchase.status === 'completed' && Number(purchase.debt_amount) > 0 && purchase.supplier_id !== null && can('update_purchase', 'update_supplier')"
                                    @click="openPurchasePayment(purchase)" class="ol-btn-light px-3 py-1.5">Payer</button>
                        </div>
                    </div>
                </template>
                <p x-show="state.purchases.length === 0" class="p-6 text-center text-sm text-gray-500">Aucun achat.</p>
            </div>
        </section>

        {{-- ═══════════ DÉPENSES ═══════════ --}}
        <section x-show="tab === 'depenses'" class="space-y-3">
            <button type="button" x-show="can('create_expense')" @click="modal = 'expense'" class="ol-btn-primary">+ Dépense</button>
            <div class="ol-card divide-y">
                <template x-for="expense in state.expenses" :key="keyOf(expense)">
                    <div class="flex items-center justify-between px-4 py-3">
                        <span>
                            <span class="font-medium" x-text="expense.description"></span>
                            <span x-show="expense._pending" class="ol-badge ml-1 bg-amber-100 text-amber-800">non synchronisé</span>
                            <span class="block text-sm text-gray-500" x-text="`${expense.category_name ?? 'Sans catégorie'} · ${expense.spent_at ?? ''}`"></span>
                        </span>
                        <strong x-text="`${fmt(expense.amount)} KMF`"></strong>
                    </div>
                </template>
                <p x-show="state.expenses.length === 0" class="p-6 text-center text-sm text-gray-500">Aucune dépense.</p>
            </div>
        </section>

        {{-- ═══════════ SYNCHRONISATION ═══════════ --}}
        <section x-show="tab === 'sync'" class="space-y-4">
            <div class="ol-card space-y-2 p-4 text-sm">
                <p><strong>Connexion :</strong> <span x-text="online ? 'en ligne' : 'hors ligne'"></span></p>
                <p><strong>Dernière synchronisation :</strong> <span x-text="fmtDate(meta.lastSync)"></span></p>
                <p><strong>Opérations en attente :</strong> <span x-text="pendingCount"></span></p>
                <p><strong>Opérations refusées :</strong> <span x-text="errorCount"></span></p>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="button" @click="sync()" :disabled="!online || syncing" class="ol-btn-primary">Synchroniser maintenant</button>
                    <a :href="config.panelUrl" x-show="online" class="ol-btn-light">Retour à la plateforme</a>
                </div>
            </div>

            <div class="ol-card divide-y">
                <h2 class="px-4 py-3 font-semibold">File d'opérations</h2>
                <template x-for="op in queue" :key="op.local_id">
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span>
                                <strong x-text="opLabel(op)"></strong>
                                <span class="text-gray-500" x-text="` · ${fmtDate(op.created_offline_at)}`"></span>
                            </span>
                            <span class="ol-badge" :class="op.status === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800'"
                                  x-text="op.status === 'error' ? 'Refusée' : 'En attente'"></span>
                        </div>
                        <p x-show="op.error" class="mt-1 text-red-700" x-text="op.error"></p>
                        <div x-show="op.status === 'error'" class="mt-2 flex gap-2">
                            <button type="button" @click="retryOp(op)" class="ol-btn-light px-3 py-1">Réessayer</button>
                            <button type="button" @click="discardOp(op)" class="ol-btn-danger px-3 py-1">Abandonner</button>
                        </div>
                    </div>
                </template>
                <p x-show="queue.length === 0" class="p-6 text-center text-sm text-gray-500">Tout est synchronisé ✔</p>
            </div>

            <div class="ol-card p-4 text-sm">
                <p class="mb-2 text-gray-600">Poste partagé ? Effacez les données de ce commerce stockées sur cet appareil et révoquez son accès.</p>
                <button type="button" @click="forgetDevice()" class="ol-btn-danger">Effacer les données de ce poste</button>
            </div>
        </section>
    </main>

    {{-- ═══════════ Navigation basse ═══════════ --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 border-t bg-white">
        <div class="mx-auto grid max-w-3xl grid-cols-6 text-center text-xs">
            @foreach ([
                ['caisse', '🛒', 'Caisse'],
                ['produits', '📦', 'Produits'],
                ['clients', '👥', 'Clients'],
                ['achats', '🚚', 'Achats'],
                ['depenses', '💸', 'Dépenses'],
                ['sync', '⟳', 'Sync'],
            ] as [$key, $icon, $label])
                <button type="button" @click="tab = '{{ $key }}'" class="relative py-2"
                        :class="tab === '{{ $key }}' ? 'font-semibold text-primary-600' : 'text-gray-500'">
                    <span class="block text-lg leading-none">{{ $icon }}</span>{{ $label }}
                    @if ($key === 'sync')
                        <span x-show="pendingCount + errorCount > 0" x-text="pendingCount + errorCount"
                              class="absolute right-2 top-1 rounded-full px-1.5 text-[10px] text-white"
                              :class="errorCount > 0 ? 'bg-red-600' : 'bg-amber-500'"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </nav>

    {{-- ═══════════ Notification ═══════════ --}}
    <div x-show="toast" x-transition class="fixed inset-x-4 bottom-20 z-40 mx-auto max-w-md rounded-lg px-4 py-3 text-sm text-white shadow-lg"
         :class="toast?.type === 'error' ? 'bg-red-600' : 'bg-gray-900'" x-text="toast?.message"></div>

    {{-- ═══════════ Modales ═══════════ --}}
    <div x-show="modal" class="fixed inset-0 z-30 flex items-end justify-center bg-black/40 sm:items-center" @keydown.escape.window="modal = null">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-2xl bg-white p-5 sm:rounded-2xl" @click.outside="modal = null">

            {{-- Produit --}}
            <form x-show="modal === 'product'" @submit.prevent="saveProduct()" class="space-y-3">
                <h2 class="text-lg font-bold" x-text="forms.product.mode === 'create' ? 'Nouveau produit' : 'Modifier le produit'"></h2>
                <div><label class="ol-label">Nom *</label><input x-model="forms.product.name" class="ol-input" required></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="ol-label">Prix de vente *</label><input type="number" min="0" step="any" x-model="forms.product.sell_price" class="ol-input" required></div>
                    <div><label class="ol-label">Prix d'achat</label><input type="number" min="0" step="any" x-model="forms.product.buy_price" class="ol-input"></div>
                    <div x-show="forms.product.mode === 'create'"><label class="ol-label">Stock initial</label><input type="number" min="0" step="any" x-model="forms.product.stock_qty" class="ol-input"></div>
                    <div><label class="ol-label">Seuil d'alerte</label><input type="number" min="0" step="any" x-model="forms.product.stock_alert" class="ol-input"></div>
                    <div><label class="ol-label">Catégorie</label>
                        <select x-model="forms.product.category_id" class="ol-input">
                            <option value="">—</option>
                            <template x-for="c in state.categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                        </select>
                    </div>
                    <div><label class="ol-label">Unité</label>
                        <select x-model="forms.product.unit_id" class="ol-input">
                            <option value="">—</option>
                            <template x-for="u in state.units" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
                        </select>
                    </div>
                </div>
                <div><label class="ol-label">Code-barres</label><input x-model="forms.product.barcode" class="ol-input"></div>
                <p x-show="forms.product.mode === 'edit'" class="text-xs text-gray-500">Le stock se corrige via le bouton « Stock » (mouvement tracé).</p>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Mouvement de stock --}}
            <form x-show="modal === 'stock'" @submit.prevent="saveStock()" class="space-y-3">
                <h2 class="text-lg font-bold">Stock — <span x-text="forms.stock.name"></span></h2>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="forms.stock.type = 'in'" class="ol-btn" :class="forms.stock.type === 'in' ? 'bg-primary-600 text-white' : 'bg-gray-100'">Entrée</button>
                    <button type="button" @click="forms.stock.type = 'out'" class="ol-btn" :class="forms.stock.type === 'out' ? 'bg-primary-600 text-white' : 'bg-gray-100'">Sortie</button>
                    <button type="button" @click="forms.stock.type = 'adjustment'" class="ol-btn" :class="forms.stock.type === 'adjustment' ? 'bg-primary-600 text-white' : 'bg-gray-100'">Inventaire</button>
                </div>
                <div><label class="ol-label" x-text="forms.stock.type === 'adjustment' ? 'Quantité comptée (nouveau stock)' : 'Quantité'"></label>
                    <input type="number" min="0" step="any" x-model="forms.stock.quantity" class="ol-input" required></div>
                <div><label class="ol-label">Motif</label><input x-model="forms.stock.reason" class="ol-input" placeholder="Casse, inventaire, don…"></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Client --}}
            <form x-show="modal === 'customer'" @submit.prevent="saveCustomer()" class="space-y-3">
                <h2 class="text-lg font-bold">Nouveau client</h2>
                <div><label class="ol-label">Nom *</label><input x-model="forms.customer.name" class="ol-input" required></div>
                <div><label class="ol-label">Téléphone</label><input type="tel" x-model="forms.customer.phone" class="ol-input"></div>
                <div><label class="ol-label">Adresse</label><input x-model="forms.customer.address" class="ol-input"></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Fournisseur --}}
            <form x-show="modal === 'supplier'" @submit.prevent="saveSupplier()" class="space-y-3">
                <h2 class="text-lg font-bold">Nouveau fournisseur</h2>
                <div><label class="ol-label">Nom *</label><input x-model="forms.supplier.name" class="ol-input" required></div>
                <div><label class="ol-label">Téléphone</label><input type="tel" x-model="forms.supplier.phone" class="ol-input"></div>
                <div><label class="ol-label">Adresse</label><input x-model="forms.supplier.address" class="ol-input"></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Remboursement de crédit --}}
            <form x-show="modal === 'creditPayment'" @submit.prevent="saveCreditPayment()" class="space-y-3">
                <h2 class="text-lg font-bold">Remboursement</h2>
                <p class="text-sm text-gray-600" x-text="forms.creditPayment.credit ? `Reste dû : ${fmt(forms.creditPayment.credit.remaining_amount)} KMF` : ''"></p>
                <div><label class="ol-label">Montant reçu</label><input type="number" min="1" step="any" x-model="forms.creditPayment.amount" class="ol-input" required></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Paiement d'achat --}}
            <form x-show="modal === 'purchasePayment'" @submit.prevent="savePurchasePayment()" class="space-y-3">
                <h2 class="text-lg font-bold">Payer l'achat</h2>
                <p class="text-sm text-gray-600" x-text="forms.purchasePayment.purchase ? `Dette : ${fmt(forms.purchasePayment.purchase.debt_amount)} KMF` : ''"></p>
                <div><label class="ol-label">Montant payé</label><input type="number" min="1" step="any" x-model="forms.purchasePayment.amount" class="ol-input" required></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Paiement fournisseur --}}
            <form x-show="modal === 'supplierPayment'" @submit.prevent="saveSupplierPayment()" class="space-y-3">
                <h2 class="text-lg font-bold">Payer <span x-text="forms.supplierPayment.supplier?.name"></span></h2>
                <p class="text-sm text-gray-600">Le paiement est réparti sur les achats validés, du plus ancien au plus récent.</p>
                <div><label class="ol-label">Montant payé</label><input type="number" min="1" step="any" x-model="forms.supplierPayment.amount" class="ol-input" required></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Dépense --}}
            <form x-show="modal === 'expense'" @submit.prevent="saveExpense()" class="space-y-3">
                <h2 class="text-lg font-bold">Nouvelle dépense</h2>
                <div><label class="ol-label">Catégorie</label>
                    <select x-model="forms.expense.expense_category_id" class="ol-input">
                        <option value="">Sans catégorie</option>
                        <template x-for="c in state.expense_categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                    </select>
                </div>
                <div><label class="ol-label">Description</label><input x-model="forms.expense.description" class="ol-input" placeholder="Électricité, transport…"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="ol-label">Montant *</label><input type="number" min="1" step="any" x-model="forms.expense.amount" class="ol-input" required></div>
                    <div><label class="ol-label">Date</label><input type="date" x-model="forms.expense.spent_at" class="ol-input"></div>
                </div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>

            {{-- Achat --}}
            <form x-show="modal === 'purchase'" @submit.prevent="savePurchase()" class="space-y-3">
                <h2 class="text-lg font-bold">Nouvel achat</h2>
                <div><label class="ol-label">Fournisseur</label>
                    <select x-model="forms.purchase.supplierKey" class="ol-input">
                        <option value="">Sans fournisseur</option>
                        <template x-for="s in state.suppliers" :key="keyOf(s)"><option :value="keyOf(s)" x-text="s.name"></option></template>
                    </select>
                </div>
                <template x-for="(line, index) in forms.purchase.lines" :key="index">
                    <div class="grid grid-cols-12 items-end gap-2">
                        <div class="col-span-6"><label class="ol-label">Produit</label>
                            <select x-model="line.productKey" @change="purchaseLineChanged(line)" class="ol-input">
                                <option value="">—</option>
                                <template x-for="p in state.products" :key="keyOf(p)"><option :value="keyOf(p)" x-text="p.name"></option></template>
                            </select>
                        </div>
                        <div class="col-span-2"><label class="ol-label">Qté</label><input type="number" min="0.01" step="any" x-model="line.quantity" class="ol-input"></div>
                        <div class="col-span-3"><label class="ol-label">Coût unit.</label><input type="number" min="0" step="any" x-model="line.unit_cost" class="ol-input"></div>
                        <button type="button" @click="forms.purchase.lines.splice(index, 1)" class="col-span-1 pb-2 text-red-600">✕</button>
                    </div>
                </template>
                <button type="button" @click="forms.purchase.lines.push({ productKey: '', quantity: 1, unit_cost: '' })" class="ol-btn-light">+ Ligne</button>
                <p class="text-right font-semibold" x-text="`Total : ${fmt(purchaseFormTotal)} KMF`"></p>
                <label x-show="can('update_purchase')" class="flex items-center gap-2 text-sm">
                    <input type="checkbox" x-model="forms.purchase.complete"> Marchandise reçue : valider et mettre à jour le stock
                </label>
                <div><label class="ol-label">Note</label><input x-model="forms.purchase.note" class="ol-input"></div>
                <div class="flex justify-end gap-2 pt-2"><button type="button" @click="modal = null" class="ol-btn-light">Annuler</button><button class="ol-btn-primary">Enregistrer</button></div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
