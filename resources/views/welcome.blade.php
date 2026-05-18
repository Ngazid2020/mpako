<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KomorShop — Le commerce moderne aux Comores 🇰🇲</title>
    <meta name="description" content="Stock, caisse, ventes, crédits, dépenses. L'outil complet pour faire grandir ton commerce." />

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/landing.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900 overflow-x-hidden">

    {{-- ═══════════════════════════════════════════ --}}
    {{-- SCROLL PROGRESS BAR                          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="fixed top-0 left-0 right-0 h-1 z-[100] bg-transparent">
        <div class="scroll-progress h-full bg-gradient-to-r from-primary-500 via-cyan-500 to-emerald-500 transition-all duration-100" style="width: 0%"></div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HEADER                                       --}}
    {{-- ═══════════════════════════════════════════ --}}
    <header class="fixed top-1 left-0 right-0 z-50 bg-white/70 backdrop-blur-xl border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">

            <a href="#" class="flex items-center gap-2 group">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 via-cyan-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                    K
                </div>
                <span class="text-xl font-bold text-gray-900">KomorShop</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#features" class="hover:text-primary-600 transition relative group">
                    Fonctionnalités
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="#stats" class="hover:text-primary-600 transition relative group">
                    Chiffres
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="#tarifs" class="hover:text-primary-600 transition relative group">
                    Tarifs
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="#faq" class="hover:text-primary-600 transition relative group">
                    FAQ
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary-600 group-hover:w-full transition-all duration-300"></span>
                </a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="/commerce/login" class="hidden sm:inline text-sm font-medium text-gray-700 hover:text-primary-600">
                    Connexion
                </a>
                <a href="/commerce/register" class="btn-shine bg-gradient-to-r from-primary-600 to-cyan-600 hover:from-primary-700 hover:to-cyan-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-lg hover:shadow-xl transition-all hover:scale-105">
                    Commencer →
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HERO SECTION                                 --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden min-h-screen flex items-center">

        {{-- Background dots pattern --}}
        <div class="absolute inset-0 dots-pattern opacity-60"></div>

        {{-- Blobs colorés --}}
        <div class="blob absolute top-20 left-10 w-72 h-72 bg-primary-300 rounded-full"></div>
        <div class="blob absolute bottom-20 right-10 w-96 h-96 bg-cyan-300 rounded-full"></div>
        <div class="blob absolute top-1/2 left-1/2 w-80 h-80 bg-emerald-300 rounded-full"></div>

        {{-- Particules flottantes --}}
        @for($i = 0; $i < 15; $i++)
            <div class="particle absolute w-2 h-2 bg-primary-400 rounded-full opacity-50"
                 style="top: {{ rand(10, 90) }}%; left: {{ rand(5, 95) }}%;"></div>
        @endfor

        <div class="relative max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center z-10">

            {{-- TEXTE --}}
            <div>
                <div class="hero-badge inline-flex items-center gap-2 bg-white/80 backdrop-blur border border-primary-200 text-primary-700 px-4 py-2 rounded-full text-sm font-semibold mb-6 shadow-sm">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    🇰🇲 Conçu pour les Comores
                </div>

                <h1 class="hero-title text-4xl sm:text-5xl lg:text-7xl font-black text-gray-900 leading-[1.1] mb-6 tracking-tight">
                    <span class="word inline-block">Gère</span>
                    <span class="word inline-block">ton</span>
                    <span class="word inline-block">commerce</span>
                    <br />
                    <span class="word inline-block gradient-text">comme</span>
                    <span class="word inline-block gradient-text">un</span>
                    <span class="word inline-block gradient-text">pro</span>
                </h1>

                <p class="hero-subtitle text-lg sm:text-xl text-gray-600 mb-8 leading-relaxed max-w-xl">
                    Stock, caisse, ventes, crédits, dépenses, bénéfices.
                    <strong class="text-gray-900">Tout en un</strong>, accessible depuis ton téléphone.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <div class="pulse-container relative inline-block">
                        <a href="/commerce/register" class="hero-cta btn-shine relative inline-block bg-gradient-to-r from-primary-600 to-cyan-600 hover:from-primary-700 hover:to-cyan-700 text-white font-bold px-8 py-4 rounded-2xl shadow-xl hover:shadow-2xl transition-all hover:scale-105 text-center">
                            Créer mon commerce gratuitement
                        </a>
                        <span class="pulse-ring bg-gradient-to-r from-primary-600 to-cyan-600 rounded-2xl"></span>
                    </div>

                    <a href="#features" class="hero-cta bg-white/80 backdrop-blur border-2 border-gray-200 hover:border-primary-400 text-gray-800 font-bold px-8 py-4 rounded-2xl transition-all hover:scale-105 text-center">
                        ▶ Voir la démo
                    </a>
                </div>

                {{-- Trust badges --}}
                <div class="hero-trust flex flex-wrap gap-6 text-sm text-gray-600">
                    @foreach([
                        '⚡ 2 minutes pour démarrer',
                        '🔒 Sans engagement',
                        '🇰🇲 Prix en KMF',
                    ] as $badge)
                        <span class="flex items-center gap-2 font-medium">
                            {{ $badge }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- MOCKUP --}}
            <div class="hero-mockup relative">

                {{-- Forme morphing SVG en arrière-plan --}}
                <svg class="morph-shape absolute -top-10 -right-10 w-96 h-96 opacity-20" viewBox="-100 -100 200 200">
                    <path
                        fill="url(#morphGradient)"
                        d="M60,-40C70,-20,60,20,40,40C20,60,-20,60,-40,40C-60,20,-60,-20,-40,-40C-20,-60,20,-60,40,-40C60,-20,70,-40,60,-40Z"
                    />
                    <defs>
                        <linearGradient id="morphGradient">
                            <stop offset="0%"   stop-color="#3b82f6" />
                            <stop offset="100%" stop-color="#06b6d4" />
                        </linearGradient>
                    </defs>
                </svg>

                {{-- Mockup principal --}}
                <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-3 shadow-2xl glow float">
                    <div class="bg-white rounded-2xl overflow-hidden">

                        {{-- Top bar --}}
                        <div class="bg-gray-50 px-4 py-3 flex items-center gap-2 border-b">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <div class="ml-auto text-xs text-gray-500 font-mono">komorshop.com</div>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500">Bonjour, Ali 👋</p>
                                    <p class="font-bold text-gray-900">Boutique Ali — Moroni</p>
                                </div>
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-cyan-400 rounded-full"></div>
                            </div>

                            {{-- Stats --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">CA du jour</p>
                                    <p class="text-2xl font-bold text-blue-600">12 500</p>
                                    <p class="text-xs text-gray-400">KMF · +15%</p>
                                </div>
                                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">Bénéfice</p>
                                    <p class="text-2xl font-bold text-emerald-600">+3 200</p>
                                    <p class="text-xs text-gray-400">KMF · 25%</p>
                                </div>
                            </div>

                            {{-- Graphique --}}
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-xs text-gray-500 mb-3">Ventes des 7 jours</p>
                                <div class="flex items-end gap-1.5 h-20">
                                    @foreach([40, 65, 35, 80, 55, 90, 75] as $h)
                                        <div class="mock-bar flex-1 bg-gradient-to-t from-primary-500 to-cyan-400 rounded-t origin-bottom"
                                             style="height: {{ $h }}%; transform: scaleY(0);"></div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Alerte --}}
                            <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-l-4 border-orange-400 p-3 rounded-r-xl flex items-center gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-orange-700">3 produits en stock bas</p>
                                    <p class="text-xs text-orange-600">Riz, Huile, Sucre</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Badges flottants --}}
                <div class="absolute -top-6 -left-6 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-2xl font-bold text-sm shadow-xl rotate-[-12deg] float-delayed">
                    🚀 100% local
                </div>

                <div class="absolute -bottom-4 -right-4 bg-white border-2 border-emerald-300 text-emerald-700 px-4 py-3 rounded-2xl font-bold text-sm shadow-xl float">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        En ligne
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- STATS ANIMÉES                                --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="stats" class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-900 via-primary-900 to-cyan-900 relative overflow-hidden">

        {{-- Pattern background --}}
        <div class="absolute inset-0 opacity-10">
            <div class="dots-pattern h-full"></div>
        </div>

        <div class="relative max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal">
                <h2 class="text-4xl sm:text-5xl font-black text-white mb-4">
                    Des résultats concrets
                </h2>
                <p class="text-blue-200 text-lg">Pour les commerçants qui veulent réussir</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 stagger-container">
                @foreach([
                    ['icon' => '⏱️', 'value' => 30, 'suffix' => 's', 'label' => 'Pour faire une vente'],
                    ['icon' => '📦', 'value' => 100, 'suffix' => '%', 'label' => 'De ton stock visible'],
                    ['icon' => '💰', 'value' => 0,   'suffix' => ' KMF', 'label' => 'Pour démarrer'],
                    ['icon' => '🇰🇲', 'value' => 4,   'suffix' => '/4', 'label' => 'Îles couvertes'],
                ] as $stat)
                    <div class="stagger-item text-center">
                        <div class="text-5xl mb-3">{{ $stat['icon'] }}</div>
                        <div class="text-4xl sm:text-5xl font-black text-white mb-2">
                            <span class="counter" data-target="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                        </div>
                        <p class="text-blue-200 font-medium">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PROBLÈMES                                    --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-gray-50">
        <div class="max-w-5xl mx-auto text-center">
            <div class="reveal">
                <span class="inline-block bg-red-100 text-red-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    LE PROBLÈME
                </span>
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 mb-4">
                    Ces situations te parlent ?
                </h2>
                <p class="text-lg text-gray-600 mb-16">
                    Tu n'es pas seul. Tous les commerçants vivent ça.
                </p>
            </div>

            <div class="grid sm:grid-cols-3 gap-6 stagger-container">
                @foreach([
                    ['😩', 'Je perds la trace', 'Combien de riz me reste-t-il vraiment ?'],
                    ['🤔', 'Qui me doit ?', 'Mes clients à crédit, je ne sais plus...'],
                    ['😓', 'Est-ce que je gagne ?', 'Je vends mais à la fin du mois, il ne reste rien'],
                ] as $problem)
                    <div class="stagger-item card-3d bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl border border-gray-100">
                        <div class="text-6xl mb-4">{{ $problem[0] }}</div>
                        <h3 class="font-bold text-xl text-gray-900 mb-2">{{ $problem[1] }}</h3>
                        <p class="text-gray-500">{{ $problem[2] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="reveal mt-16 inline-flex items-center gap-3 bg-gradient-to-r from-primary-600 to-cyan-600 text-white px-8 py-4 rounded-2xl font-bold shadow-xl">
                <span class="text-2xl">✨</span>
                KomorShop répond à tout ça en quelques clics
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FONCTIONNALITÉS                              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="features" class="py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden">

        <div class="blob absolute top-1/4 right-0 w-96 h-96 bg-primary-200 rounded-full"></div>
        <div class="blob absolute bottom-1/4 left-0 w-96 h-96 bg-cyan-200 rounded-full"></div>

        <div class="relative max-w-7xl mx-auto">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-primary-100 text-primary-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    LA SOLUTION
                </span>
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 mb-4">
                    Tout ce dont tu as besoin
                </h2>
                <p class="text-lg text-gray-600">
                    Une suite complète, pensée pour les commerces comoriens
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger-container">
                @foreach([
                    ['📦', 'Gestion du stock', 'Produits, catégories, alertes automatiques quand un produit est presque épuisé', 'from-blue-500 to-cyan-500'],
                    ['💰', 'Caisse rapide', 'Encaisse en quelques secondes, recherche temps réel, calcul auto de la monnaie', 'from-emerald-500 to-teal-500'],
                    ['📒', 'Crédits clients', 'Note qui te doit quoi, suis les remboursements, plus jamais d\'oubli', 'from-orange-500 to-red-500'],
                    ['🧾', 'Achats fournisseurs', 'Enregistre tes achats, paye en plusieurs fois, garde la trace', 'from-purple-500 to-pink-500'],
                    ['💳', 'Dépenses', 'Loyer, électricité, transport... vois où part ton argent', 'from-yellow-500 to-orange-500'],
                    ['📊', 'Tableau de bord', 'Visualise CA, bénéfice, top produits et alertes en un coup d\'œil', 'from-indigo-500 to-purple-500'],
                ] as $feat)
                    <div class="stagger-item card-3d group bg-white border border-gray-100 rounded-3xl p-8 hover:shadow-2xl transition-all overflow-hidden relative">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $feat[3] }} opacity-10 rounded-full blur-2xl group-hover:opacity-20 transition-opacity"></div>

                        <div class="relative">
                            <div class="w-16 h-16 bg-gradient-to-br {{ $feat[3] }} rounded-2xl flex items-center justify-center text-3xl mb-5 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                                {{ $feat[0] }}
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $feat[1] }}</h3>
                            <p class="text-gray-600 leading-relaxed">{{ $feat[2] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- POUR QUI ?                                   --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal">
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 mb-4">
                    Pour tous les commerces
                </h2>
                <p class="text-lg text-gray-600">
                    Peu importe ta taille, KomorShop s'adapte à toi
                </p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 stagger-container">
                @foreach([
                    ['🏪', 'Boutiques'],
                    ['🥖', 'Épiceries'],
                    ['☕', 'Restaurants'],
                    ['👕', 'Textile'],
                    ['💊', 'Pharmacies'],
                    ['📱', 'Téléphones'],
                    ['🛺', 'Ambulants'],
                    ['🍞', 'Boulangeries'],
                ] as $type)
                    <div class="stagger-item card-3d bg-white border border-gray-100 rounded-2xl p-8 text-center hover:shadow-xl hover:border-primary-300 transition-all cursor-pointer group">
                        <div class="text-5xl mb-3 group-hover:scale-125 transition-transform duration-300">{{ $type[0] }}</div>
                        <p class="font-bold text-gray-700">{{ $type[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- TARIFICATION                                 --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="tarifs" class="py-24 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full text-sm font-semibold mb-4">
                    TARIFICATION
                </span>
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 mb-4">
                    Des prix justes et clairs
                </h2>
                <p class="text-lg text-gray-600">
                    Commence gratuit, évolue quand tu veux
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto stagger-container">

                {{-- Plan Gratuit --}}
                <div class="stagger-item card-3d bg-white border-2 border-gray-200 rounded-3xl p-8 hover:border-primary-300 transition-all">
                    <h3 class="text-2xl font-black text-gray-900 mb-2">Gratuit</h3>
                    <p class="text-gray-500 mb-6">Pour démarrer</p>

                    <div class="mb-8">
                        <span class="text-5xl font-black text-gray-900">0</span>
                        <span class="text-gray-500"> KMF/mois</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        @foreach([
                            'Jusqu\'à 50 produits',
                            'Caisse illimitée',
                            'Suivi des crédits',
                            'Tableau de bord basique',
                            '1 utilisateur',
                        ] as $f)
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-500 text-lg flex-shrink-0">✓</span>
                                <span class="text-gray-700">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="/commerce/register" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-900 font-bold py-4 rounded-2xl transition-all">
                        Commencer gratuitement
                    </a>
                </div>

                {{-- Plan Pro --}}
                <div class="stagger-item card-3d relative bg-gradient-to-br from-primary-600 via-cyan-600 to-emerald-600 rounded-3xl p-8 text-white shadow-2xl overflow-hidden">

                    {{-- Effet brillance --}}
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-white/20 rounded-full blur-3xl"></div>

                    <div class="absolute -top-3 right-6 bg-yellow-400 text-yellow-900 px-4 py-1 rounded-full text-xs font-black shadow-lg">
                        ⭐ RECOMMANDÉ
                    </div>

                    <div class="relative">
                        <h3 class="text-2xl font-black mb-2">Pro</h3>
                        <p class="text-blue-100 mb-6">Pour grandir</p>

                        <div class="mb-8">
                            <span class="text-5xl font-black">5 000</span>
                            <span class="text-blue-200"> KMF/mois</span>
                        </div>

                        <ul class="space-y-3 mb-8">
                            @foreach([
                                'Produits illimités',
                                'Tout le pack Gratuit',
                                'Achats fournisseurs',
                                'Gestion des dépenses',
                                'Rapports avancés',
                                'Jusqu\'à 5 utilisateurs',
                                'Support WhatsApp prioritaire',
                            ] as $f)
                                <li class="flex items-start gap-3">
                                    <span class="text-yellow-300 text-lg flex-shrink-0">✓</span>
                                    <span>{{ $f }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a href="/commerce/register" class="block text-center bg-white text-primary-600 hover:bg-gray-50 font-black py-4 rounded-2xl transition-all hover:scale-105">
                            Essayer 14 jours gratuits
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FAQ                                          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="faq" class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-gray-50">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12 reveal">
                <h2 class="text-3xl sm:text-5xl font-black text-gray-900 mb-4">
                    Questions fréquentes
                </h2>
            </div>

            <div class="space-y-4 stagger-container">
                @foreach([
                    ['Est-ce que ça marche sans internet ?', 'KomorShop fonctionne dans ton navigateur, une connexion est nécessaire. Une version hors-ligne est en préparation.'],
                    ['Mes données sont-elles en sécurité ?', 'Oui. Tes données sont stockées de manière sécurisée et accessibles uniquement par toi et tes employés autorisés.'],
                    ['Puis-je l\'utiliser sur téléphone ?', 'Absolument. L\'interface s\'adapte à tous les écrans : téléphone, tablette, ordinateur.'],
                    ['Comment obtenir de l\'aide ?', 'Le plan Pro inclut un support WhatsApp prioritaire. Pour le plan gratuit, contacte-nous par email.'],
                    ['Y a-t-il un engagement ?', 'Aucun engagement. Tu peux arrêter quand tu veux, sans frais cachés.'],
                ] as $faq)
                    <details class="stagger-item faq-item group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <summary class="cursor-pointer p-6 flex items-center justify-between font-bold text-gray-900 hover:bg-gray-50 transition list-none">
                            <span>{{ $faq[0] }}</span>
                            <span class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center group-open:rotate-45 transition-transform text-xl font-bold">+</span>
                        </summary>
                        <div class="faq-content px-6 pb-6 text-gray-600 leading-relaxed">
                            {{ $faq[1] }}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- CTA FINAL                                    --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-gradient-to-br from-primary-600 via-cyan-600 to-emerald-600">

        <div class="absolute inset-0 opacity-20">
            <div class="dots-pattern h-full"></div>
        </div>

        @for($i = 0; $i < 20; $i++)
            <div class="particle absolute w-2 h-2 bg-white rounded-full opacity-50"
                 style="top: {{ rand(10, 90) }}%; left: {{ rand(5, 95) }}%;"></div>
        @endfor

        <div class="relative max-w-4xl mx-auto text-center reveal">
            <h2 class="text-4xl sm:text-6xl font-black text-white mb-6 leading-tight">
                Prêt à faire grandir<br>ton commerce ?
            </h2>
            <p class="text-xl text-blue-100 mb-10 max-w-2xl mx-auto">
                Rejoins les commerçants qui ont choisi KomorShop pour simplifier leur quotidien
            </p>

            <div class="pulse-container relative inline-block">
                <a href="/commerce/register" class="btn-shine relative inline-block bg-white hover:bg-gray-50 text-primary-600 font-black px-12 py-6 rounded-2xl text-xl shadow-2xl hover:shadow-3xl transition-all hover:scale-105">
                    Créer mon commerce gratuitement →
                </a>
                <span class="pulse-ring bg-white rounded-2xl"></span>
            </div>

            <p class="text-blue-100 text-sm mt-6">
                Sans carte bancaire · Sans engagement · En 2 minutes
            </p>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FOOTER                                       --}}
    {{-- ═══════════════════════════════════════════ --}}
    <footer class="bg-gray-900 text-gray-400 py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">

        <div class="absolute inset-0 opacity-5">
            <div class="dots-pattern h-full"></div>
        </div>

        <div class="relative max-w-6xl mx-auto">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">

                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-500 via-cyan-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            K
                        </div>
                        <span class="text-2xl font-bold text-white">KomorShop</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-md mb-4">
                        Le SaaS de gestion commerciale conçu spécialement
                        pour les petits commerces des Comores 🇰🇲
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-4">Produit</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="#tarifs" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="/commerce/register" class="hover:text-white transition">S'inscrire</a></li>
                        <li><a href="/commerce/login" class="hover:text-white transition">Connexion</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm">
                        <li>📧 contact@komorshop.com</li>
                        <li>📱 +269 333 00 00</li>
                        <li>📍 Moroni, Grande Comore</li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
                <p>© {{ date('Y') }} KomorShop. Tous droits réservés.</p>
                <p>Fait avec ❤️ aux Comores 🇰🇲</p>
            </div>
        </div>
    </footer>

</body>
</html>