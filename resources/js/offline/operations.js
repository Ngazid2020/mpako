/**
 * Effets locaux (optimistes) des opérations hors ligne.
 *
 * Principe : l'état affiché = dernier snapshot serveur + rejeu des opérations en attente.
 * Ces fonctions ne font que refléter localement ce que le serveur fera à la synchronisation
 * (le serveur reste la source de vérité : observers, plafonds, permissions).
 */

/** Copie profonde « sûre » (les objets réactifs d'Alpine sont des Proxy non clonables) */
export const clone = (value) => (value === undefined ? undefined : JSON.parse(JSON.stringify(value)));

export const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

/** Clé d'identification d'une entité : id serveur, ou local_id si créée hors ligne */
export const keyOf = (entity) => (entity?.id ?? null) !== null ? `id:${entity.id}` : `local:${entity?.local_id}`;

/** Référence à envoyer au serveur : { x_id } ou { x_local_id } */
export function refPayload(field, entity) {
    if (!entity) return {};
    return entity.id !== null && entity.id !== undefined
        ? { [`${field}_id`]: entity.id }
        : { [`${field}_local_id`]: entity.local_id };
}

/** Retrouve une entité d'après un payload contenant x_id ou x_local_id */
export function findRef(list, payload, field) {
    const id = payload[`${field}_id`];
    const localId = payload[`${field}_local_id`];
    if (id !== undefined && id !== null) return list.find((e) => e.id === Number(id));
    if (localId) return list.find((e) => e.local_id === localId);
    return undefined;
}

export function lineTotal(item) {
    const base = round2(Number(item.quantity) * Number(item.unit_price));
    const value = Math.max(0, Number(item.discount_value) || 0);
    if (!item.discount_type || value <= 0) return base;
    const discount = item.discount_type === 'percent'
        ? round2(base * Math.min(100, value) / 100)
        : Math.min(round2(value), base);
    return round2(base - discount);
}

function adjustStock(product, type, quantity) {
    if (!product) return;
    const q = Number(quantity) || 0;
    const current = Number(product.stock_qty) || 0;
    const next = type === 'in' ? current + q : type === 'out' ? current - q : q;
    product.stock_qty = Math.max(0, round2(next));
}

function creditStatus(paid, remaining) {
    if (remaining <= 0) return 'paid';
    return paid > 0 ? 'partial' : 'pending';
}

const handlers = {
    create_product(state, p, op) {
        state.products.push({
            id: null,
            local_id: op.local_id,
            name: p.name,
            barcode: p.barcode ?? null,
            sell_price: Number(p.sell_price) || 0,
            buy_price: Number(p.buy_price) || 0,
            stock_qty: Number(p.stock_qty) || 0,
            stock_alert: Number(p.stock_alert) || 0,
            category_id: p.category_id ?? null,
            unit_id: p.unit_id ?? null,
            is_active: true,
            _pending: true,
        });
    },

    update_product(state, p) {
        const product = findRef(state.products, p, 'product');
        if (!product) return;
        for (const field of ['name', 'sell_price', 'buy_price', 'barcode', 'stock_alert', 'category_id', 'unit_id', 'is_active']) {
            if (field in p) product[field] = p[field];
        }
        product._pending = true;
    },

    create_stock_adjustment(state, p) {
        const product = findRef(state.products, p, 'product');
        adjustStock(product, p.type, p.quantity);
        if (product) product._pending = true;
    },

    create_customer(state, p, op) {
        state.customers.push({ id: null, local_id: op.local_id, name: p.name, phone: p.phone ?? null, address: p.address ?? null, balance: 0, is_active: true, _pending: true });
    },

    create_supplier(state, p, op) {
        state.suppliers.push({ id: null, local_id: op.local_id, name: p.name, phone: p.phone ?? null, address: p.address ?? null, balance: 0, _pending: true });
    },

    create_sale(state, p, op) {
        let total = 0;
        for (const item of p.items) {
            adjustStock(findRef(state.products, item, 'product'), 'out', item.quantity);
            total += lineTotal(item);
        }
        total = round2(total);

        state.pending_sales.unshift({
            local_id: op.local_id,
            created_at: op.created_offline_at,
            payment_type: p.payment_type,
            total_amount: total,
            items_count: p.items.length,
        });

        if (p.payment_type === 'credit') {
            const customer = findRef(state.customers, p, 'customer');
            if (customer) customer.balance = round2(Number(customer.balance) + total);
            state.credits.unshift({
                id: null,
                local_id: `${op.local_id}:credit`,
                customer_id: p.customer_id ?? null,
                customer_local_id: p.customer_local_id ?? null,
                status: 'pending',
                total_amount: total,
                paid_amount: 0,
                remaining_amount: total,
                due_date: p.credit?.due_date ?? null,
                description: 'Vente à crédit (hors ligne)',
                _pending: true,
            });
        }
    },

    create_credit_payment(state, p) {
        const credit = state.credits.find((c) => c.id === Number(p.credit_id));
        if (!credit) return;
        const amount = Math.min(Number(p.amount) || 0, Number(credit.remaining_amount));
        credit.paid_amount = round2(Number(credit.paid_amount) + amount);
        credit.remaining_amount = round2(Number(credit.remaining_amount) - amount);
        credit.status = creditStatus(credit.paid_amount, credit.remaining_amount);
        credit._pending = true;
        const customer = state.customers.find((c) => c.id === Number(credit.customer_id));
        if (customer) customer.balance = round2(Number(customer.balance) - amount);
    },

    create_expense(state, p, op) {
        const category = state.expense_categories.find((c) => c.id === Number(p.expense_category_id));
        state.expenses.unshift({
            id: null,
            local_id: op.local_id,
            expense_category_id: p.expense_category_id ?? null,
            category_name: category?.name ?? null,
            description: p.description || 'Dépense',
            amount: Number(p.amount) || 0,
            spent_at: p.spent_at,
            _pending: true,
        });
    },

    create_purchase(state, p, op) {
        const supplier = findRef(state.suppliers, p, 'supplier');
        const items = p.items.map((i) => ({
            ...i,
            subtotal: round2(Number(i.quantity) * Number(i.unit_cost)),
        }));
        const total = round2(items.reduce((s, i) => s + i.subtotal, 0));
        const purchase = {
            id: null,
            local_id: op.local_id,
            reference: 'Hors ligne',
            supplier_id: p.supplier_id ?? null,
            supplier_local_id: p.supplier_local_id ?? null,
            supplier_name: supplier?.name ?? null,
            status: 'pending',
            payment_status: 'unpaid',
            total_amount: total,
            paid_amount: 0,
            debt_amount: total,
            items,
            _pending: true,
        };
        state.purchases.unshift(purchase);
        if (p.status === 'completed') completePurchase(state, purchase);
    },

    complete_purchase(state, p) {
        const purchase = findRef(state.purchases, p, 'purchase');
        if (purchase && purchase.status === 'pending') completePurchase(state, purchase);
    },

    pay_purchase(state, p) {
        const purchase = findRef(state.purchases, p, 'purchase');
        if (!purchase || purchase.status !== 'completed') return;
        const amount = Math.min(Number(p.amount) || 0, Number(purchase.debt_amount));
        applyPurchasePayment(state, purchase, amount);
    },

    pay_supplier(state, p) {
        const supplier = findRef(state.suppliers, p, 'supplier');
        if (!supplier) return;
        let remaining = Number(p.amount) || 0;
        // Même répartition FIFO que le serveur : achats validés, du plus ancien au plus récent
        const purchases = state.purchases
            .filter((pu) => pu.status === 'completed' && Number(pu.debt_amount) > 0 && keyOf(supplierOf(state, pu) ?? {}) === keyOf(supplier))
            .reverse();
        for (const purchase of purchases) {
            if (remaining <= 0) break;
            const part = Math.min(remaining, Number(purchase.debt_amount));
            applyPurchasePayment(state, purchase, part);
            remaining = round2(remaining - part);
        }
    },
};

function supplierOf(state, purchase) {
    return findRef(state.suppliers, purchase, 'supplier');
}

function completePurchase(state, purchase) {
    purchase.status = 'completed';
    purchase._pending = true;
    for (const item of purchase.items ?? []) {
        const product = findRef(state.products, item, 'product');
        adjustStock(product, 'in', Number(item.quantity) * Math.max(1, Number(item.conversion_qty) || 1));
    }
    const supplier = supplierOf(state, purchase);
    if (supplier) supplier.balance = round2(Number(supplier.balance) + Number(purchase.debt_amount));
}

function applyPurchasePayment(state, purchase, amount) {
    purchase.paid_amount = round2(Number(purchase.paid_amount) + amount);
    purchase.debt_amount = round2(Math.max(0, Number(purchase.debt_amount) - amount));
    purchase.payment_status = purchase.debt_amount <= 0 ? 'paid' : 'partial';
    purchase._pending = true;
    const supplier = supplierOf(state, purchase);
    if (supplier) supplier.balance = round2(Number(supplier.balance) - amount);
}

/** Construit l'état affiché à partir du snapshot serveur et de la file d'opérations */
export function buildState(snapshot, queue) {
    const base = snapshot ? clone(snapshot) : {};
    const state = {
        products: base.products ?? [],
        customers: base.customers ?? [],
        credits: base.credits ?? [],
        suppliers: base.suppliers ?? [],
        purchases: base.purchases ?? [],
        expenses: base.expenses ?? [],
        expense_categories: base.expense_categories ?? [],
        categories: base.categories ?? [],
        units: base.units ?? [],
        pending_sales: [],
        synced_at: base.synced_at ?? null,
    };

    for (const op of queue) {
        // Une opération rejetée définitivement n'est pas rejouée (son effet serait faux)
        if (op.status === 'error') continue;
        try {
            handlers[op.type]?.(state, op.payload, op);
        } catch (e) {
            console.warn('Rejeu local impossible', op, e);
        }
    }

    return state;
}

export const OPERATION_LABELS = {
    create_sale: 'Vente',
    create_credit_payment: 'Remboursement de crédit',
    create_stock_adjustment: 'Mouvement de stock',
    create_purchase: 'Achat',
    complete_purchase: "Validation d'achat",
    pay_purchase: "Paiement d'achat",
    pay_supplier: 'Paiement fournisseur',
    create_expense: 'Dépense',
    create_product: 'Nouveau produit',
    update_product: 'Modification produit',
    create_customer: 'Nouveau client',
    create_supplier: 'Nouveau fournisseur',
};
