<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors ligne — BeeZ</title>
    <link rel="icon" href="/favicon.ico">
    <meta name="theme-color" content="#2563eb">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #eff6ff;
            color: #1e3a5f;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 28px 32px;
            text-align: center;
            max-width: 340px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(37, 99, 235, .12);
        }

        .logo {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            margin: 0 auto 24px;
            display: block;
        }

        .icon-wifi {
            display: block;
            margin: 0 auto 20px;
            opacity: .35;
        }

        h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1e3a5f;
        }

        p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.65;
            margin-bottom: 28px;
        }

        .btn {
            display: block;
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px 20px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .btn:active { background: #1d4ed8; }

        .hint {
            margin-top: 16px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo" src="/images/icons/icon-192x192.png" alt="BeeZ">

        <svg class="icon-wifi" xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" />
        </svg>

        <h1>Pas de connexion</h1>
        <p>Vérifie ta connexion internet et réessaie.<br>Tes données restent sauvegardées sur le serveur.</p>

        <button class="btn" onclick="window.location.reload()">
            Réessayer
        </button>

        <p class="hint">BeeZ nécessite une connexion pour synchroniser les ventes.</p>
    </div>
</body>
</html>
