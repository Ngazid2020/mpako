{{--
    Pont entre le panel Commerce (Livewire, nécessite le réseau) et le mode hors ligne.

    - Prépare le mode hors ligne : met l'application en cache via le service worker
      (au plus toutes les 30 min, ou immédiatement si le cache est absent).
    - Détecte la perte de connexion : événement `offline` du navigateur ET sonde
      réseau (/up) pour les cas « Wi-Fi connecté mais sans Internet ».
    - Bascule AUTOMATIQUEMENT vers le mode hors ligne après un court compte à rebours
      (annulable avec « Rester ici »).
--}}
@php($offlineUrl = route('commerce.offline', ['shop' => $shop->slug]))

<div
    x-data="{
        offlineUrl: @js($offlineUrl),
        warmKey: 'beez-offline-warm:{{ $shop->slug }}',
        online: navigator.onLine,
        ready: false,
        countdown: null,
        stay: false,
        failures: 0,
        timer: null,

        async init() {
            window.addEventListener('offline', () => this.goneOffline());
            window.addEventListener('online', () => this.backOnline());

            await this.prepare();
            if (!navigator.onLine) this.goneOffline();

            // Sonde : le navigateur ne voit pas toujours une coupure (Wi-Fi sans Internet)
            setInterval(() => this.probe(), 20000);
        },

        async isCached() {
            try { return !!(await caches.match(new URL(this.offlineUrl).pathname)); } catch (e) { return false; }
        },

        async prepare() {
            this.ready = await this.isCached();
            if (!('serviceWorker' in navigator) || !navigator.onLine) return;

            let last = 0;
            try { last = Number(localStorage.getItem(this.warmKey) || 0); } catch (e) {}
            if (this.ready && Date.now() - last < 30 * 60 * 1000) return;

            const registration = await navigator.serviceWorker.ready;
            registration.active?.postMessage({ type: 'warm-offline', url: this.offlineUrl });
            try { localStorage.setItem(this.warmKey, String(Date.now())); } catch (e) {}
            // Le service worker met la page en cache en arrière-plan
            setTimeout(async () => { this.ready = await this.isCached(); }, 4000);
        },

        async probe() {
            if (!navigator.onLine) return this.goneOffline();
            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 6000);
                const response = await fetch('/up', { method: 'HEAD', cache: 'no-store', signal: controller.signal });
                clearTimeout(timeout);
                if (!response.ok && response.status !== 405) throw new Error('down');
                this.failures = 0;
                if (!this.online) this.backOnline();
            } catch (e) {
                // Deux échecs consécutifs avant de conclure (évite les fausses alertes)
                if (++this.failures >= 2) this.goneOffline();
            }
        },

        async goneOffline() {
            if (!this.online && this.countdown !== null) return;
            this.online = false;
            this.ready = await this.isCached();
            if (!this.ready || this.stay) return;

            this.countdown = 5;
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (this.stay || this.online) { clearInterval(this.timer); this.countdown = null; return; }
                if (--this.countdown <= 0) {
                    clearInterval(this.timer);
                    window.location.href = this.offlineUrl;
                }
            }, 1000);
        },

        backOnline() {
            this.online = true;
            this.failures = 0;
            this.stay = false;
            this.countdown = null;
            clearInterval(this.timer);
        },

        stayHere() {
            this.stay = true;
            this.countdown = null;
            clearInterval(this.timer);
        },
    }"
    x-show="!online"
    x-cloak
    style="position: fixed; inset-inline: 0; top: 0; z-index: 60;"
    class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white shadow"
>
    <template x-if="ready">
        <span>
            Connexion perdue.
            <span x-show="countdown !== null">Passage en mode hors ligne dans <strong x-text="countdown"></strong> s…</span>
            <a href="{{ $offlineUrl }}" class="ml-1 font-bold underline">Continuer en mode hors ligne →</a>
            <button type="button" x-show="countdown !== null" @click="stayHere()" class="ml-2 rounded bg-white/20 px-2 py-0.5 text-xs">Rester ici</button>
        </span>
    </template>
    <template x-if="!ready">
        <span>
            Connexion perdue. Le mode hors ligne n'a pas encore été préparé sur cet appareil :
            reconnectez-vous une fois à Internet pour l'activer.
        </span>
    </template>
</div>
