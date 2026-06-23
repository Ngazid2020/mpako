{{-- Bannière d'installation PWA — affichée uniquement quand l'app est installable --}}
<div id="pwa-banner" aria-live="polite" style="
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 99999;
    background: #1d4ed8;
    color: #fff;
    padding: 14px 16px;
    box-shadow: 0 -2px 16px rgba(0,0,0,.25);
    align-items: center;
    justify-content: space-between;
    gap: 12px;
">
    <div style="display:flex; align-items:center; gap:12px; min-width:0;">
        <img src="/images/icons/icon-72x72.png"
             alt="BeeZ"
             width="40" height="40"
             style="border-radius:8px; flex-shrink:0;">
        <div style="min-width:0;">
            <div style="font-weight:700; font-size:14px; line-height:1.3;">Installer BeeZ</div>
            <div style="font-size:12px; opacity:.8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                Accède à ton commerce depuis l'écran d'accueil
            </div>
        </div>
    </div>
    <div style="display:flex; gap:8px; flex-shrink:0;">
        <button onclick="pwaDismiss()"
                style="background:transparent; border:1px solid rgba(255,255,255,.45);
                       color:#fff; padding:7px 12px; border-radius:6px;
                       cursor:pointer; font-size:13px; white-space:nowrap;">
            Plus tard
        </button>
        <button onclick="pwaInstall()"
                style="background:#fff; color:#1d4ed8; border:none;
                       padding:7px 14px; border-radius:6px;
                       cursor:pointer; font-size:13px; font-weight:700; white-space:nowrap;">
            Installer
        </button>
    </div>
</div>

<script>
(function () {
    var banner = document.getElementById('pwa-banner');
    var deferred = null;

    // Déjà installé en mode standalone → rien à faire
    if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone) return;

    // Rejeté récemment (7 jours) → on attend
    var dismissedAt = localStorage.getItem('pwa-dismissed');
    if (dismissedAt && Date.now() - parseInt(dismissedAt, 10) < 7 * 86400000) return;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        banner.style.display = 'flex';
    });

    window.addEventListener('appinstalled', function () {
        banner.style.display = 'none';
        deferred = null;
    });

    window.pwaInstall = function () {
        if (!deferred) return;
        deferred.prompt();
        deferred.userChoice.then(function (result) {
            if (result.outcome === 'accepted') {
                banner.style.display = 'none';
            }
            deferred = null;
        });
    };

    window.pwaDismiss = function () {
        localStorage.setItem('pwa-dismissed', String(Date.now()));
        banner.style.display = 'none';
    };
}());
</script>
