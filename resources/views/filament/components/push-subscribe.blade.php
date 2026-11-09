@if(config('webpush.vapid.public_key'))
<script>
(function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

    var VAPID_PUBLIC = @json(config('webpush.vapid.public_key'));
    var STORAGE_KEY  = 'mpako_push_denied';

    // Ne pas re-demander si l'user a refusé dans les 7 jours
    if (localStorage.getItem(STORAGE_KEY)) {
        var denied = parseInt(localStorage.getItem(STORAGE_KEY), 10);
        if (Date.now() - denied < 7 * 24 * 60 * 60 * 1000) return;
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw     = atob(base64);
        var output  = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
        return output;
    }

    navigator.serviceWorker.ready.then(function (registration) {
        registration.pushManager.getSubscription().then(function (existing) {
            if (existing) return; // déjà souscrit

            Notification.requestPermission().then(function (permission) {
                if (permission !== 'granted') {
                    localStorage.setItem(STORAGE_KEY, Date.now().toString());
                    return;
                }

                registration.pushManager.subscribe({
                    userVisibleOnly:      true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC),
                }).then(function (subscription) {
                    var sub = JSON.parse(JSON.stringify(subscription));
                    fetch('/api/push-subscriptions', {
                        method:  'POST',
                        headers: {
                            'Content-Type':  'application/json',
                            'Accept':        'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            endpoint:        sub.endpoint,
                            keys:            sub.keys,
                            contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                        }),
                    });
                }).catch(function () {
                    localStorage.setItem(STORAGE_KEY, Date.now().toString());
                });
            });
        });
    });
})();
</script>
@endif
