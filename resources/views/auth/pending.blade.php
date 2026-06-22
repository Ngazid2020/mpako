<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Compte en attente — KomorShop</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 font-sans antialiased flex items-center justify-center px-4 py-12">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 via-cyan-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-2xl font-black shadow-lg">
                    K
                </div>
                <span class="text-2xl font-black text-gray-900">KomorShop</span>
            </a>
        </div>

        {{-- Carte principale --}}
        <div class="bg-white rounded-3xl shadow-xl ring-1 ring-gray-950/5 overflow-hidden">

            {{-- Bannière colorée --}}
            <div class="h-2 bg-gradient-to-r from-blue-500 via-cyan-500 to-emerald-500"></div>

            <div class="p-8 text-center">

                {{-- Icône --}}
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-50 ring-8 ring-amber-50 mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-black text-gray-900 mb-3">
                    Compte créé avec succès !
                </h1>

                <p class="text-gray-600 leading-relaxed mb-6">
                    Votre compte est en attente de validation.<br>
                    <strong class="text-gray-900">Un administrateur KomorShop doit activer votre accès</strong>
                    avant que vous puissiez vous connecter.
                </p>

                {{-- Étapes --}}
                <div class="space-y-3 text-left mb-8">
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center mt-0.5">1</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Votre demande est enregistrée</p>
                            <p class="text-xs text-gray-500">Nous avons bien reçu votre inscription.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-200 text-amber-800 text-xs font-bold flex items-center justify-center mt-0.5">2</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Validation en cours</p>
                            <p class="text-xs text-gray-500">Un admin vérifie votre demande sous 24–48h.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 opacity-60">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-200 text-gray-500 text-xs font-bold flex items-center justify-center mt-0.5">3</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-500">Accès activé</p>
                            <p class="text-xs text-gray-400">Vous pourrez vous connecter et démarrer.</p>
                        </div>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="bg-blue-50 rounded-2xl p-4 mb-6 text-sm text-blue-800">
                    <p class="font-semibold mb-1">Besoin d'une activation rapide ?</p>
                    <p>📱 Contactez-nous sur WhatsApp : <strong>+269 333 00 00</strong></p>
                </div>

                <a href="/" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-900 transition">
                    ← Retour à l'accueil
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            © {{ date('Y') }} KomorShop — Fait avec ❤️ aux Comores 🇰🇲
        </p>
    </div>

</body>
</html>
