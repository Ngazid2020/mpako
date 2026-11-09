{{--
    Pont entre le panel Commerce (Livewire, nécessite le réseau) et le mode hors ligne.

    - PRÉPARE le mode hors ligne en arrière-plan : charge l'application hors ligne dans une
      iframe invisible. Cela met la page et ses assets en cache (service worker) ET récupère
      le token d'accès et les données du commerce (IndexedDB). Rafraîchi toutes les 15 min.
    - DÉTECTE la perte de connexion : événement `offline` du navigateur + sonde réseau (/up)
      pour les cas « Wi-Fi connecté mais sans Internet ».
    - BASCULE automatiquement vers le mode hors ligne après 5 s (annulable : « Rester ici »).
--}}
@php($offlineUrl = route('commerce.offline', ['shop' => $shop->slug]))

<div
    x-data="{
        offlineUrl: @js($offlineUrl),
        readyKey: 'beez-offline-ready:{{ $shop->slug }}:{{ auth()->id() }}',
        online: navigator.onLine,
        ready: false,
        countdown: null,
        stay: false,
        failures: 0,
        timer: null,
        frame: null,

        async init() {
            window.addEventListener('offline', () => this.goneOffline());
            window.addEventListener('online', () => this.backOnline());
            window.addEventListener('message', (event) => this.onFrameMessage(event));

            this.ready = await this.isReady();
            if (!navigator.onLine) return this.goneOffline();

            this.prepare();
            setInterval(() => this.prepare(), 15 * 60 * 1000);
            // Sonde : le navigateur ne voit pas toujours une coupure (Wi-Fi sans Internet)
            setInterval(() => this.probe(), 20000);
        },

        lastPrepared() {
            try { return Number(localStorage.getItem(this.readyKey) || 0); } catch (e) { return 0; }
        },

        async isCached() {
            try { return !!(await caches.match(new URL(this.offlineUrl).pathname)); } catch (e) { return false; }
        },

        // Prêt = page en cache ET données synchronisées au moins une fois sur ce poste
        async isReady() {
            return this.lastPrepared() > 0 && await this.isCached();
        },

        async prepare() {
            if (!('serviceWorker' in navigator) || !('caches' in window) || !navigator.onLine || this.frame) return;
            if (Date.now() - this.lastPrepared() < 15 * 60 * 1000 && await this.isCached()) return;

            await navigator.serviceWorker.ready;

            const frame = document.createElement('iframe');
            frame.src = this.offlineUrl;
            frame.setAttribute('aria-hidden', 'true');
            frame.tabIndex = -1;
            frame.style.cssText = 'position:absolute;width:1px;height:1px;border:0;opacity:0;pointer-events:none;left:-9999px;';
            document.body.appendChild(frame);
            this.frame = frame;

            // Filet de sécurité : on retire l'iframe même sans réponse
            setTimeout(() => this.removeFrame(), 60000);
        },

        async onFrameMessage(event) {
            if (event.origin !== location.origin || event.data?.type !== 'beez-offline-ready') return;
            try { localStorage.setItem(this.readyKey, String(Date.now())); } catch (e) {}
            this.removeFrame();
            this.ready = await this.isReady();
        },

        removeFrame() {
            this.frame?.remove();
            this.frame = null;
        },

        async probe() {
            if (!navigator.onLine) return this.goneOffline();
            try {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 6000);
                const response = await fetch('/up', { method: 'HEAD', cache: 'no-store', signal: controller.signal });
                clearTimeout(timeout);
                if (!response.ok) throw new Error('down');
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
            this.removeFrame();
            this.ready = await this.isReady();
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
            this.prepare();
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
            restez connecté quelques secondes sur la plateforme la prochaine fois pour l'activer.
        </span>
    </template>
</div>
