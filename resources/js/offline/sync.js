/**
 * Moteur de synchronisation du mode hors ligne.
 *
 *  1. push  : envoie la file d'opérations (idempotent côté serveur grâce à local_id)
 *  2. pull  : recharge un snapshot allégé (?light=1) qui devient la nouvelle base locale
 *
 * Authentification : token Sanctum propre au poste, obtenu via la session web
 * (POST /commerce/{slug}/hors-ligne/token) quand on est en ligne, puis conservé en IndexedDB.
 */
export class AuthError extends Error {}

/** Erreurs serveur qui ne sont que temporaires : l'opération reste en file et sera retentée */
const RETRYABLE = [/pas encore synchronisé/i, /Erreur serveur/i];

function readCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

export async function requestToken(config, deviceId) {
    // Jeton CSRF frais (la page peut venir du cache du service worker)
    await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });

    const response = await fetch(config.tokenUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': readCookie('XSRF-TOKEN') ?? '',
        },
        body: JSON.stringify({ device_id: deviceId }),
    });

    if (response.status === 401 || response.status === 419 || response.redirected) {
        throw new AuthError('Session expirée : reconnectez-vous à la plateforme pour réactiver la synchronisation.');
    }
    if (!response.ok) {
        throw new AuthError(`Impossible d'obtenir l'accès hors ligne (${response.status}).`);
    }

    return response.json();
}

async function api(config, token, path, options = {}) {
    const response = await fetch(`${config.apiBase}${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
            ...(options.headers ?? {}),
        },
    });

    if (response.status === 401) throw new AuthError('Accès expiré.');
    if (response.status === 403) {
        const body = await response.json().catch(() => ({}));
        throw new AuthError(body.message ?? 'Accès refusé.');
    }
    if (!response.ok) throw new Error(`Erreur serveur (${response.status})`);

    return response.json();
}

export function pull(config, token) {
    return api(config, token, '/pull?light=1');
}

/**
 * Envoie les opérations en attente. Retourne la file mise à jour :
 * opérations synchronisées retirées, rejets définitifs marqués `error`.
 */
export async function push(config, token, queue) {
    const sendable = queue.filter((op) => op.status !== 'error').slice(0, 500);
    if (sendable.length === 0) return { queue, synced: 0 };

    const body = await api(config, token, '/push', {
        method: 'POST',
        body: JSON.stringify({
            operations: sendable.map(({ type, local_id, payload, created_offline_at }) => ({ type, local_id, payload, created_offline_at })),
        }),
    });

    const results = new Map((body.results ?? []).map((r) => [r.local_id, r]));
    let synced = 0;

    const next = [];
    for (const op of queue) {
        const result = results.get(op.local_id);
        if (!result) {
            next.push(op);
        } else if (result.status === 'synced') {
            synced++;
        } else {
            const retryable = RETRYABLE.some((re) => re.test(result.error ?? ''));
            next.push({
                ...op,
                status: retryable ? 'pending' : 'error',
                error: result.error ?? 'Erreur inconnue',
                attempts: (op.attempts ?? 0) + 1,
            });
        }
    }

    return { queue: next, synced };
}
