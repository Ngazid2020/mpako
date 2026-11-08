{{--
    Pont entre le panel Commerce (Livewire, nécessite le réseau) et le mode hors ligne.
    - précharge l'application hors ligne dans le cache du service worker (au plus toutes les 30 min)
    - affiche une bannière quand la connexion tombe, avec un lien vers le mode hors ligne
--}}
@php($offlineUrl = route('commerce.offline', ['shop' => $shop->slug]))

<div
    x-data="{ online: navigator.onLine }"
    x-init="
        window.addEventListener('online', () => online = true);
        window.addEventListener('offline', () => online = false);

        if ('serviceWorker' in navigator) {
            let last = 0;
            try { last = Number(localStorage.getItem('beez-offline-warm:{{ $shop->slug }}') || 0); } catch (e) {}
            if (Date.now() - last > 30 * 60 * 1000) {
                navigator.serviceWorker.ready.then((registration) => {
                    registration.active?.postMessage({ type: 'warm-offline', url: @js($offlineUrl) });
                    try { localStorage.setItem('beez-offline-warm:{{ $shop->slug }}', String(Date.now())); } catch (e) {}
                });
            }
        }
    "
    x-show="!online"
    x-cloak
    style="position: fixed; inset-inline: 0; top: 0; z-index: 60;"
    class="bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white shadow"
>
    Connexion perdue.
    <a href="{{ $offlineUrl }}" class="underline font-bold">Continuer en mode hors ligne →</a>
</div>
