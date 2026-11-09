import Alpine from 'alpinejs';
import { kv, scopedKeys } from './db.js';
import { buildState, clone, keyOf, refPayload, lineTotal, round2, OPERATION_LABELS } from './operations.js';
import { AuthError, requestToken, pull, push } from './sync.js';

const AUTO_SYNC_MS = 30_000;
const TOKEN_RENEW_BEFORE_MS = 5 * 24 * 3600 * 1000;

function uuid() {
    if (globalThis.crypto?.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

const today = () => new Date().toISOString().slice(0, 10);

function emptyForms() {
    return {
        product: { mode: 'create', ref: null, name: '', sell_price: '', buy_price: '', stock_qty: '', stock_alert: '', category_id: '', unit_id: '', barcode: '' },
        stock: { ref: null, name: '', type: 'in', quantity: '', reason: '' },
        customer: { name: '', phone: '', address: '' },
        supplier: { name: '', phone: '', address: '' },
        creditPayment: { credit: null, amount: '' },
        purchasePayment: { purchase: null, amount: '' },
        supplierPayment: { supplier: null, amount: '' },
        expense: { expense_category_id: '', description: '', amount: '', spent_at: today() },
        purchase: { supplierKey: '', complete: true, note: '', lines: [{ productKey: '', quantity: 1, unit_cost: '' }] },
    };
}

window.offlineApp = (config) => ({
    config,
    tab: 'caisse',
    modal: null,
    forms: emptyForms(),

    // État
    online: navigator.onLine,
    wasOffline: !navigator.onLine,
    syncing: false,
    ready: false,
    snapshot: null,
    queue: [],
    state: buildState(null, []),
    auth: null,
    meta: { lastSync: null, deviceId: null },
    authError: null,
    toast: null,

    // Caisse
    search: '',
    cart: [],
    paymentType: 'cash',
    paidAmount: '',
    customerKey: '',
    dueDate: '',

    // Recherches de listes
    productSearch: '',
    customerSearch: '',

    async init() {
        this.keys = scopedKeys(config.shop.slug, config.user.id);

        const [snapshot, queue, auth, meta] = await Promise.all([
            kv.get(this.keys.snapshot), kv.get(this.keys.queue), kv.get(this.keys.auth), kv.get(this.keys.meta),
        ]);
        this.snapshot = snapshot ?? null;
        this.queue = queue ?? [];
        this.auth = auth ?? null;
        this.meta = { lastSync: null, deviceId: null, ...(meta ?? {}) };
        if (!this.meta.deviceId) {
            this.meta.deviceId = uuid();
            await kv.set(this.keys.meta, clone(this.meta));
        }
        this.rebuild();
        this.ready = true;

        window.addEventListener('online', () => { this.online = true; this.sync(); });
        window.addEventListener('offline', () => { this.online = false; this.wasOffline = true; });
        setInterval(() => {
            if (this.online && (this.queue.some((o) => o.status !== 'error') || this.isStale())) this.sync();
        }, AUTO_SYNC_MS);

        if (this.online) this.sync();
    },

    // ───────────── Permissions & helpers ─────────────
    can(...permissions) {
        const granted = this.auth?.permissions ?? config.permissions;
        return granted.includes('*') || permissions.some((p) => granted.includes(p));
    },
    fmt(n) {
        return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(n) || 0);
    },
    fmtDate(iso) {
        if (!iso) return '—';
        return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
    },
    keyOf,
    notify(message, type = 'success') {
        this.toast = { message, type };
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => (this.toast = null), 3500);
    },
    isStale() {
        return !this.meta.lastSync || Date.now() - new Date(this.meta.lastSync).getTime() > 5 * 60_000;
    },
    byKey(list, key) {
        return list.find((e) => keyOf(e) === key);
    },
    unitOf(product) {
        return this.state.units.find((u) => u.id === Number(product.unit_id))?.abbreviation ?? '';
    },
    get pendingCount() {
        return this.queue.filter((o) => o.status !== 'error').length;
    },
    get errorCount() {
        return this.queue.filter((o) => o.status === 'error').length;
    },
    opLabel(op) {
        return OPERATION_LABELS[op.type] ?? op.type;
    },

    // ───────────── File d'opérations ─────────────
    rebuild() {
        this.state = buildState(this.snapshot, this.queue);
    },
    async persistQueue() {
        await kv.set(this.keys.queue, clone(this.queue));
    },
    async enqueue(type, payload, message) {
        const op = { local_id: `web-${uuid()}`, type, payload: clone(payload), created_offline_at: new Date().toISOString(), status: 'pending' };
        this.queue.push(op);
        await this.persistQueue();
        this.rebuild();
        this.notify(message + (this.online ? '' : ' — sera synchronisé au retour de la connexion'));
        if (this.online) this.sync();
        return op;
    },
    async retryOp(op) {
        op.status = 'pending';
        op.error = null;
        await this.persistQueue();
        this.rebuild();
        this.sync();
    },
    async discardOp(op) {
        if (!confirm(`Abandonner « ${this.opLabel(op)} » ? Elle ne sera jamais envoyée au serveur.`)) return;
        this.queue = this.queue.filter((o) => o.local_id !== op.local_id);
        await this.persistQueue();
        this.rebuild();
    },

    // ───────────── Synchronisation ─────────────
    async ensureToken() {
        const valid = this.auth?.token && new Date(this.auth.expires_at).getTime() - Date.now() > TOKEN_RENEW_BEFORE_MS;
        if (valid) return this.auth.token;
        try {
            const auth = await requestToken(config, this.meta.deviceId);
            await kv.set(this.keys.auth, auth);
            this.auth = auth;
            this.authError = null;
        } catch (e) {
            // Token encore valide (même proche de l'expiration) : on continue avec
            if (this.auth?.token && new Date(this.auth.expires_at) > new Date()) return this.auth.token;
            throw e;
        }
        return this.auth.token;
    },

    async sync() {
        if (this.syncing || !navigator.onLine) return;
        this.syncing = true;
        try {
            const token = await this.ensureToken();

            const { queue, synced } = await push(config, token, this.queue);
            this.queue = queue;
            await this.persistQueue();

            const snapshot = await pull(config, token);
            await kv.set(this.keys.snapshot, snapshot);
            this.snapshot = snapshot;

            this.meta.lastSync = new Date().toISOString();
            await kv.set(this.keys.meta, clone(this.meta));
            this.authError = null;
            this.rebuild();

            if (synced > 0) this.notify(`${synced} opération(s) synchronisée(s)`);
            if (this.errorCount > 0) this.notify(`${this.errorCount} opération(s) refusée(s) par le serveur — voir l'onglet Sync`, 'error');
        } catch (e) {
            if (e instanceof AuthError) {
                this.authError = e.message;
                if (/expiré/i.test(e.message)) {
                    this.auth = null;
                    await kv.del(this.keys.auth);
                }
            } else {
                console.warn('Synchronisation interrompue', e);
            }
        } finally {
            this.syncing = false;
        }
    },

    async forgetDevice() {
        if (this.pendingCount > 0 && !confirm(`${this.pendingCount} opération(s) ne sont pas encore synchronisées et seront PERDUES. Continuer ?`)) return;
        if (this.online && this.auth?.token) {
            await fetch('/api/auth/logout', { method: 'POST', headers: { Accept: 'application/json', Authorization: `Bearer ${this.auth.token}` } }).catch(() => {});
        }
        await Promise.all(Object.values(this.keys).map((k) => kv.del(k)));
        this.snapshot = null; this.queue = []; this.auth = null; this.meta.lastSync = null;
        this.rebuild();
        this.notify('Données locales effacées de ce poste');
    },

    // ───────────── Caisse ─────────────
    get saleProducts() {
        const q = this.search.trim().toLowerCase();
        return this.state.products
            .filter((p) => p.is_active)
            .filter((p) => !q || p.name.toLowerCase().includes(q) || (p.barcode ?? '') === this.search.trim())
            .sort((a, b) => (Number(b.stock_qty) > 0) - (Number(a.stock_qty) > 0) || a.name.localeCompare(b.name))
            .slice(0, 80);
    },
    addToCart(product) {
        const key = keyOf(product);
        const line = this.cart.find((l) => l.key === key);
        const wanted = (line?.quantity ?? 0) + 1;
        if (wanted > Number(product.stock_qty) && !confirm(`Stock local de « ${product.name} » : ${this.fmt(product.stock_qty)}. Vendre quand même ?`)) return;
        if (line) line.quantity = wanted;
        else this.cart.push({ key, name: product.name, unit_price: Number(product.sell_price), quantity: 1, discount_type: 'percent', discount_value: 0 });
    },
    lineTotal,
    get cartTotal() {
        return round2(this.cart.reduce((s, l) => s + lineTotal(l), 0));
    },
    get change() {
        return Math.max(0, round2((Number(this.paidAmount) || 0) - this.cartTotal));
    },
    async checkout() {
        if (this.cart.length === 0) return;
        const customer = this.customerKey ? this.byKey(this.state.customers, this.customerKey) : null;
        if (this.paymentType === 'credit' && !customer) return this.notify('Choisissez un client pour une vente à crédit', 'error');
        if (this.paymentType === 'cash' && this.paidAmount !== '' && Number(this.paidAmount) < this.cartTotal) {
            return this.notify('Montant reçu insuffisant', 'error');
        }

        const items = this.cart.map((l) => {
            const product = this.byKey(this.state.products, l.key);
            return {
                ...refPayload('product', product),
                product_name: l.name,
                quantity: Number(l.quantity),
                unit_price: Number(l.unit_price),
                ...(Number(l.discount_value) > 0 ? { discount_type: l.discount_type, discount_value: Number(l.discount_value) } : {}),
            };
        });

        await this.enqueue('create_sale', {
            items,
            payment_type: this.paymentType,
            ...(this.paymentType === 'cash' ? { paid_amount: this.paidAmount === '' ? this.cartTotal : Number(this.paidAmount) } : {}),
            ...refPayload('customer', customer),
            ...(this.paymentType === 'credit' && this.dueDate ? { credit: { due_date: this.dueDate } } : {}),
        }, `Vente de ${this.fmt(this.cartTotal)} KMF enregistrée`);

        this.cart = []; this.paidAmount = ''; this.customerKey = ''; this.dueDate = ''; this.paymentType = 'cash'; this.search = '';
    },

    // ───────────── Produits ─────────────
    get productList() {
        const q = this.productSearch.trim().toLowerCase();
        return this.state.products
            .filter((p) => !q || p.name.toLowerCase().includes(q) || (p.barcode ?? '').includes(q))
            .sort((a, b) => a.name.localeCompare(b.name));
    },
    openProduct(product = null) {
        this.forms.product = product
            ? { mode: 'edit', ref: keyOf(product), name: product.name, sell_price: product.sell_price, buy_price: product.buy_price, stock_qty: '', stock_alert: product.stock_alert, category_id: String(product.category_id ?? ''), unit_id: String(product.unit_id ?? ''), barcode: product.barcode ?? '' }
            : emptyForms().product;
        this.modal = 'product';
    },
    async saveProduct() {
        const f = this.forms.product;
        if (!f.name.trim() || f.sell_price === '') return this.notify('Nom et prix de vente obligatoires', 'error');
        const fields = {
            name: f.name.trim(),
            sell_price: Number(f.sell_price),
            buy_price: f.buy_price === '' ? 0 : Number(f.buy_price),
            stock_alert: f.stock_alert === '' ? 0 : Number(f.stock_alert),
            category_id: f.category_id === '' ? null : Number(f.category_id),
            unit_id: f.unit_id === '' ? null : Number(f.unit_id),
            barcode: f.barcode.trim() || null,
        };
        if (f.mode === 'create') {
            await this.enqueue('create_product', { ...fields, stock_qty: f.stock_qty === '' ? 0 : Number(f.stock_qty) }, `Produit « ${fields.name} » créé`);
        } else {
            const product = this.byKey(this.state.products, f.ref);
            await this.enqueue('update_product', { ...refPayload('product', product), ...fields }, `Produit « ${fields.name} » modifié`);
        }
        this.modal = null;
    },
    openStock(product) {
        this.forms.stock = { ref: keyOf(product), name: product.name, type: 'in', quantity: '', reason: '' };
        this.modal = 'stock';
    },
    async saveStock() {
        const f = this.forms.stock;
        const minimum = f.type === 'adjustment' ? 0 : 0.01;
        if (f.quantity === '' || Number(f.quantity) < minimum) return this.notify('Quantité invalide', 'error');
        const product = this.byKey(this.state.products, f.ref);
        await this.enqueue('create_stock_adjustment', {
            ...refPayload('product', product), type: f.type, quantity: Number(f.quantity), reason: f.reason || 'Ajustement (mode hors ligne)',
        }, `Stock de « ${f.name} » mis à jour`);
        this.modal = null;
    },

    // ───────────── Clients & crédits ─────────────
    get customerList() {
        const q = this.customerSearch.trim().toLowerCase();
        return this.state.customers
            .filter((c) => !q || c.name.toLowerCase().includes(q) || (c.phone ?? '').includes(q))
            .sort((a, b) => Number(b.balance) - Number(a.balance) || a.name.localeCompare(b.name));
    },
    creditsOf(customer) {
        return this.state.credits.filter((c) =>
            (customer.id !== null && Number(c.customer_id) === customer.id) || (customer.local_id && c.customer_local_id === customer.local_id));
    },
    async saveCustomer() {
        const f = this.forms.customer;
        if (!f.name.trim()) return this.notify('Nom obligatoire', 'error');
        await this.enqueue('create_customer', { name: f.name.trim(), phone: f.phone || null, address: f.address || null }, `Client « ${f.name} » ajouté`);
        this.forms.customer = emptyForms().customer;
        this.modal = null;
    },
    openCreditPayment(credit) {
        this.forms.creditPayment = { credit, amount: credit.remaining_amount };
        this.modal = 'creditPayment';
    },
    async saveCreditPayment() {
        const { credit, amount } = this.forms.creditPayment;
        const value = Number(amount);
        if (!(value > 0) || value > Number(credit.remaining_amount)) return this.notify('Montant invalide (au plus le reste dû)', 'error');
        await this.enqueue('create_credit_payment', { credit_id: credit.id, amount: value, paid_at: today() }, `Remboursement de ${this.fmt(value)} KMF enregistré`);
        this.modal = null;
    },

    // ───────────── Achats & fournisseurs ─────────────
    supplierName(purchase) {
        if (purchase.supplier_name) return purchase.supplier_name;
        const supplier = this.state.suppliers.find((s) =>
            (purchase.supplier_id && s.id === Number(purchase.supplier_id)) || (purchase.supplier_local_id && s.local_id === purchase.supplier_local_id));
        return supplier?.name ?? 'Sans fournisseur';
    },
    async saveSupplier() {
        const f = this.forms.supplier;
        if (!f.name.trim()) return this.notify('Nom obligatoire', 'error');
        await this.enqueue('create_supplier', { name: f.name.trim(), phone: f.phone || null, address: f.address || null }, `Fournisseur « ${f.name} » ajouté`);
        this.forms.supplier = emptyForms().supplier;
        this.modal = null;
    },
    openPurchase() {
        this.forms.purchase = emptyForms().purchase;
        this.modal = 'purchase';
    },
    purchaseLineChanged(line) {
        const product = this.byKey(this.state.products, line.productKey);
        if (product && (line.unit_cost === '' || line.unit_cost === null)) line.unit_cost = product.buy_price;
    },
    get purchaseFormTotal() {
        return round2(this.forms.purchase.lines.reduce((s, l) => s + (Number(l.quantity) || 0) * (Number(l.unit_cost) || 0), 0));
    },
    async savePurchase() {
        const f = this.forms.purchase;
        const lines = f.lines.filter((l) => l.productKey && Number(l.quantity) > 0);
        if (lines.length === 0) return this.notify('Ajoutez au moins un produit', 'error');
        const supplier = f.supplierKey ? this.byKey(this.state.suppliers, f.supplierKey) : null;
        const items = lines.map((l) => {
            const product = this.byKey(this.state.products, l.productKey);
            return { ...refPayload('product', product), product_name: product.name, quantity: Number(l.quantity), unit_cost: Number(l.unit_cost) || 0 };
        });
        const complete = f.complete && this.can('update_purchase');
        await this.enqueue('create_purchase', {
            ...refPayload('supplier', supplier), items, status: complete ? 'completed' : 'pending', note: f.note || null, purchased_at: today(),
        }, complete ? 'Achat enregistré et stock mis à jour' : 'Achat enregistré (en attente de validation)');
        this.modal = null;
    },
    async completePurchase(purchase) {
        if (!confirm('Valider cet achat ? Le stock sera augmenté.')) return;
        await this.enqueue('complete_purchase', refPayload('purchase', purchase), 'Achat validé');
    },
    openPurchasePayment(purchase) {
        this.forms.purchasePayment = { purchase, amount: purchase.debt_amount };
        this.modal = 'purchasePayment';
    },
    async savePurchasePayment() {
        const { purchase, amount } = this.forms.purchasePayment;
        const value = Number(amount);
        if (!(value > 0) || value > Number(purchase.debt_amount)) return this.notify('Montant invalide (au plus la dette)', 'error');
        await this.enqueue('pay_purchase', { ...refPayload('purchase', purchase), amount: value, paid_at: today() }, `Paiement de ${this.fmt(value)} KMF enregistré`);
        this.modal = null;
    },
    openSupplierPayment(supplier) {
        this.forms.supplierPayment = { supplier, amount: supplier.balance };
        this.modal = 'supplierPayment';
    },
    async saveSupplierPayment() {
        const { supplier, amount } = this.forms.supplierPayment;
        const value = Number(amount);
        if (!(value > 0) || value > Number(supplier.balance)) return this.notify('Montant invalide (au plus la dette)', 'error');
        await this.enqueue('pay_supplier', { ...refPayload('supplier', supplier), amount: value }, `Paiement de ${this.fmt(value)} KMF à ${supplier.name}`);
        this.modal = null;
    },

    // ───────────── Dépenses ─────────────
    async saveExpense() {
        const f = this.forms.expense;
        if (!(Number(f.amount) > 0)) return this.notify('Montant invalide', 'error');
        await this.enqueue('create_expense', {
            expense_category_id: f.expense_category_id === '' ? null : Number(f.expense_category_id),
            description: f.description || null,
            amount: Number(f.amount),
            spent_at: f.spent_at || today(),
        }, `Dépense de ${this.fmt(f.amount)} KMF enregistrée`);
        this.forms.expense = emptyForms().expense;
        this.modal = null;
    },
});

window.Alpine = Alpine;
Alpine.start();
