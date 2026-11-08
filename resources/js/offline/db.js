/**
 * Stockage clé/valeur minimal sur IndexedDB (persistant, plusieurs Mo, disponible hors ligne).
 * Une seule base « beez-offline », un seul object store « kv ».
 */
const DB_NAME = 'beez-offline';
const STORE = 'kv';

let dbPromise = null;

function open() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);
        request.onupgradeneeded = () => request.result.createObjectStore(STORE);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

function run(mode, fn) {
    return open().then((db) => new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const req = fn(tx.objectStore(STORE));
        tx.oncomplete = () => resolve(req?.result);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    }));
}

export const kv = {
    get: (key) => run('readonly', (s) => s.get(key)),
    set: (key, value) => run('readwrite', (s) => s.put(value, key)),
    del: (key) => run('readwrite', (s) => s.delete(key)),
};

/** Préfixe de clés propre à un commerce et un utilisateur (postes partagés) */
export function scopedKeys(shopSlug, userId) {
    const prefix = `${shopSlug}:${userId}`;
    return {
        snapshot: `${prefix}:snapshot`,
        queue: `${prefix}:queue`,
        auth: `${prefix}:auth`,
        meta: `${prefix}:meta`,
    };
}
