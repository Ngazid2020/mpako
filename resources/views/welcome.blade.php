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
    {{-- SCROLL PROGRESS                              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <div class="fixed top-0 left-0 right-0 h-1 z-[100]">
        <div class="scroll-progress h-full bg-gradient-to-r from-primary-500 via-cyan-500 to-emerald-500" style="width: 0%"></div>
    </div>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HEADER GLASSMORPHISM                         --}}
    {{-- ═══════════════════════════════════════════ --}}
    <header class="fixed top-1 left-0 right-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">

            <a href="#" class="flex items-center gap-2 group magnetic">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 via-cyan-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-xl font-black shadow-lg group-hover:scale-110 group-hover:rotate-12 transition-all duration-500">
                    K
                </div>
                <span class="text-xl font-black text-gray-900">KomorShop</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-gray-700">
                @foreach(['features' => 'Fonctionnalités', 'stats' => 'Chiffres', 'tarifs' => 'Tarifs', 'faq' => 'FAQ'] as $href => $label)
                    <a href="#{{ $href }}" class="hover:text-primary-600 transition relative group">
                        {{ $label }}
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary-500 to-cyan-500 group-hover:w-full transition-all duration-500"></span>
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <a href="/commerce/login" class="hidden sm:inline text-sm font-semibold text-gray-700 hover:text-primary-600">
                    Connexion
                </a>
                <a href="/commerce/register" class="magnetic btn-shine bg-gradient-to-r from-primary-600 via-cyan-600 to-emerald-600 hover:shadow-2xl text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg transition-all">
                    Commencer →
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HERO — Ultra premium                         --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="relative pt-32 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden min-h-screen flex items-center bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 noise">

        {{-- Mesh gradient canvas en background --}}
        <canvas id="mesh-canvas" class="opacity-60"></canvas>

        {{-- Étoiles scintillantes --}}
        <div class="starry-bg absolute inset-0 pointer-events-none"></div>

        {{-- Particles network --}}
        <canvas id="particle-network" class="opacity-30"></canvas>

        <div class="relative max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center z-10">

            {{-- TEXTE --}}
            <div>
                <div class="hero-badge inline-flex items-center gap-2 glass px-4 py-2 rounded-full text-sm font-bold mb-6 shadow-lg">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-gray-800">🇰🇲 Made in Comores</span>
                </div>

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black text-gray-900 leading-[0.95] mb-6 tracking-tight">
                    <span class="split-text block">Gère ton commerce</span>
                    <span class="split-text block gradient-text">comme un pro</span>
                </h1>

                <p class="hero-subtitle text-lg sm:text-xl text-gray-600 mb-10 leading-relaxed max-w-xl">
                    Stock, caisse, ventes, crédits, dépenses.
                    <strong class="text-gray-900">Tout en un endroit</strong>,
                    accessible depuis ton téléphone.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 mb-10">
                    <div class="relative inline-block">
                        <a href="/commerce/register" class="hero-cta magnetic btn-shine relative inline-block bg-gradient-to-r from-primary-600 via-cyan-600 to-emerald-600 text-white font-black px-8 py-5 rounded-2xl shadow-2xl text-center text-lg glow">
                            <span class="relative z-10">Démarrer gratuitement →</span>
                        </a>
                        <span class="pulse-ring bg-gradient-to-r from-primary-600 to-cyan-600 rounded-2xl"></span>
                    </div>

                    <a href="#features" class="hero-cta magnetic glass text-gray-800 font-bold px-8 py-5 rounded-2xl text-center text-lg">
                        ▶ Voir la démo
                    </a>
                </div>

                <div class="hero-trust flex flex-wrap gap-6 text-sm text-gray-600">
                    @foreach([
                        '⚡ 2 minutes',
                        '🔒 Sans engagement',
                        '🇰🇲 Prix en KMF',
                    ] as $badge)
                        <span class="flex items-center gap-2 font-semibold">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>

            {{-- MOCKUP --}}
            <div class="hero-mockup relative">

                {{-- Glow background --}}
                <div class="absolute -inset-10 bg-gradient-to-r from-primary-400 via-cyan-400 to-emerald-400 rounded-3xl blur-3xl opacity-30"></div>

                {{-- Mockup --}}
                <div class="card-tilt relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-3xl p-3 shadow-2xl float">
                    <div class="glare rounded-3xl"></div>
                    <div class="bg-white rounded-2xl overflow-hidden">

                        {{-- Browser bar --}}
                        <div class="bg-gray-50 px-4 py-3 flex items-center gap-2 border-b">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <div class="ml-auto text-xs text-gray-500 font-mono">komorshop.com</div>
                        </div>

                        <div class="p-6 space-y-4 relative">

                            {{-- Notification flottante --}}
                            <div class="mock-notification absolute top-4 right-4 bg-emerald-500 text-white px-3 py-2 rounded-xl text-xs font-bold shadow-xl z-10">
                                ✅ Vente : +2 500 KMF
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-gray-500">Bonjour, Ali 👋</p>
                                    <p class="font-bold text-gray-900">Boutique Ali</p>
                                </div>
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-cyan-400 rounded-full ring-2 ring-white shadow"></div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="mock-stat bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200/50">
                                    <p class="text-xs text-gray-500 mb-1">CA du jour</p>
                                    <p class="text-2xl font-black text-blue-600">12 500</p>
                                    <p class="text-xs text-emerald-600 font-semibold">↑ +15%</p>
                                </div>
                                <div class="mock-stat bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200/50">
                                    <p class="text-xs text-gray-500 mb-1">Bénéfice</p>
                                    <p class="text-2xl font-black text-emerald-600">+3 200</p>
                                    <p class="text-xs text-emerald-600 font-semibold">↑ 25%</p>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-xs text-gray-500 mb-3 font-semibold">Ventes 7 jours</p>
                                <div class="flex items-end gap-1.5 h-20">
                                    @foreach([40, 65, 35, 80, 55, 90, 75] as $h)
                                        <div class="mock-bar flex-1 bg-gradient-to-t from-primary-500 via-cyan-400 to-emerald-400 rounded-t origin-bottom" style="height: {{ $h }}%; transform: scaleY(0);"></div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-l-4 border-orange-400 p-3 rounded-r-xl flex items-center gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-orange-700">3 produits en stock bas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Badges flottants --}}
                <div class="absolute -top-6 -left-6 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-2xl font-black text-sm shadow-2xl -rotate-12 float-delayed">
                    🚀 100% local
                </div>

                <div class="absolute -bottom-4 -right-4 glass border-2 border-emerald-300 text-emerald-700 px-4 py-3 rounded-2xl font-bold text-sm shadow-2xl float">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        En ligne
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="scroll-indicator absolute bottom-8 left-1/2 -translate-x-1/2 text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MARQUEE LOGOS                                --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-12 px-4 bg-gray-50 border-y border-gray-100 overflow-hidden">
        <p class="text-center text-sm text-gray-500 font-semibold mb-6 uppercase tracking-wider">
            Pour tous les commerces des Comores
        </p>
        <div class="marquee">
            <div class="marquee-content">
                @foreach(['🏪 Boutiques', '🥖 Épiceries', '☕ Restaurants', '👕 Textile', '💊 Pharmacies', '📱 Téléphones', '🛺 Ambulants', '🍞 Boulangeries', '🐟 Poissonneries', '🥗 Restaurants', '☕ Cafés', '🎁 Cadeaux'] as $cat)
                    <span class="inline-block mx-8 text-2xl font-bold text-gray-400 hover:text-primary-600 transition-colors">{{ $cat }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- STATS — Dark + particle network              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="stats" class="py-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-900 via-blue-950 to-cyan-950 relative overflow-hidden noise">

        <canvas id="particle-network" class="absolute inset-0 opacity-50"></canvas>

        <div class="relative max-w-6xl mx-auto z-10">
            <div class="text-center mb-16 reveal">
                <h2 class="text-4xl sm:text-6xl font-black text-white mb-4">
                    Des résultats <span class="gradient-text">concrets</span>
                </h2>
                <p class="text-blue-200 text-lg">Pour les commerçants qui veulent réussir</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 stagger-container">
                @foreach([
                    ['icon' => '⏱️', 'value' => 30, 'suffix' => 's', 'label' => 'Pour une vente'],
                    ['icon' => '📦', 'value' => 100, 'suffix' => '%', 'label' => 'Stock visible'],
                    ['icon' => '💰', 'value' => 0,   'suffix' => ' KMF', 'label' => 'Pour démarrer'],
                    ['icon' => '🇰🇲', 'value' => 4,   'suffix' => '/4', 'label' => 'Îles couvertes'],
                ] as $stat)
                    <div class="stagger-item glass-dark rounded-3xl p-6 text-center spotlight">
                        <div class="text-5xl mb-3">{{ $stat['icon'] }}</div>
                        <div class="text-4xl sm:text-5xl font-black text-white mb-2">
                            <span class="counter" data-target="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                        </div>
                        <p class="text-blue-200 font-semibold">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PROBLÈMES                                    --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-24 px-4 sm:px-6 lg:px-8 relative">
        <div class="max-w-5xl mx-auto text-center">
            <div class="reveal">
                <span class="inline-block bg-red-100 text-red-700 px-4 py-1 rounded-full text-sm font-bold mb-4">
                    LE PROBLÈME
                </span>
                <h2 class="text-4xl sm:text-6xl font-black text-gray-900 mb-4">
                    Ces situations te <span class="gradient-text">parlent ?</span>
                </h2>
                <p class="text-lg text-gray-600 mb-16">
                    Tous les commerçants vivent ça
                </p>
            </div>

            <div class="grid sm:grid-cols-3 gap-6 stagger-container">
                @foreach([
                    ['😩', 'Je perds la trace', 'Combien de riz me reste vraiment ?'],
                    ['🤔', 'Qui me doit ?', 'Mes clients à crédit, je ne sais plus...'],
                    ['😓', 'Est-ce que je gagne ?', 'Je vends mais à la fin du mois, rien'],
                ] as $problem)
                    <div class="stagger-item card-tilt spotlight bg-white rounded-3xl p-8 shadow-xl border border-gray-100">
                        <div class="glare rounded-3xl"></div>
                        <div class="text-7xl mb-4">{{ $problem[0] }}</div>
                        <h3 class="font-black text-xl text-gray-900 mb-2">{{ $problem[1] }}</h3>
                        <p class="text-gray-500">{{ $problem[2] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="reveal mt-16 inline-flex items-center gap-3 bg-gradient-to-r from-primary-600 via-cyan-600 to-emerald-600 text-white px-8 py-4 rounded-2xl font-black shadow-2xl glow">
                <span class="text-2xl">✨</span>
                KomorShop répond à tout ça
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FONCTIONNALITÉS                              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="features" class="py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-gradient-to-b from-gray-50 to-white">

        <div class="relative max-w-7xl mx-auto">
            <div class="text-center mb-16 reveal">
                <span class="inline-block bg-primary-100 text-primary-700 px-4 py-1 rounded-full text-sm font-bold mb-4">
                    LA SOLUTION
                </span>
                <h2 class="text-4xl sm:text-6xl font-black text-gray-900 mb-4">
                    Tout ce dont <span class="gradient-text">tu as besoin</span>
                </h2>
                <p class="text-lg text-gray-600">
                    Une suite complète, pensée pour les Comores
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger-container">
                @foreach([
                    ['📦', 'Stock', 'Produits, catégories, alertes auto', 'from-blue-500 to-cyan-500'],
                    ['💰', 'Caisse', 'Encaisse en quelques secondes', 'from-emerald-500 to-teal-500'],
                    ['📒', 'Crédits', 'Suis qui te doit quoi', 'from-orange-500 to-red-500'],
                    ['🧾', 'Achats', 'Enregistre tes approvisionnements', 'from-purple-500 to-pink-500'],
                    ['💳', 'Dépenses', 'Vois où part ton argent', 'from-yellow-500 to-orange-500'],
                    ['📊', 'Tableau de bord', 'Visualise ton activité', 'from-indigo-500 to-purple-500'],
                ] as $feat)
                    <div class="stagger-item card-tilt spotlight border-gradient group bg-white rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all overflow-hidden">
                        <div class="glare rounded-3xl"></div>
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $feat[3] }} opacity-10 rounded-full blur-2xl group-hover:opacity-30 transition-opacity"></div>

                        <div class="relative">
                            <div class="w-16 h-16 bg-gradient-to-br {{ $feat[3] }} rounded-2xl flex items-center justify-center text-3xl mb-5 shadow-xl group-hover:scale-110 group-hover:rotate-12 transition-all duration-500">
                                {{ $feat[0] }}
                            </div>
                            <h3 class="text-xl font-black text-gray-900 mb-3">{{ $feat[1] }}</h3>
                            <p class="text-gray-600 leading-relaxed">{{ $feat[2] }}</p>
                        </div>
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
                <span class="inline-block bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full text-sm font-bold mb-4">
                    TARIFICATION
                </span>
                <h2 class="text-4xl sm:text-6xl font-black text-gray-900 mb-4">
                    Prix <span class="gradient-text">simples</span>
                </h2>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto stagger-container">

                <div class="stagger-item card-tilt spotlight bg-white border-2 border-gray-200 rounded-3xl p-8 hover:border-primary-300 transition-all">
                    <div class="glare rounded-3xl"></div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">Gratuit</h3>
                    <p class="text-gray-500 mb-6">Pour démarrer</p>

                    <div class="mb-8">
                        <span class="text-6xl font-black text-gray-900">0</span>
                        <span class="text-gray-500"> KMF/mois</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        @foreach(['50 produits max', 'Caisse illimitée', 'Crédits clients', 'Dashboard basique', '1 utilisateur'] as $f)
                            <li class="flex items-start gap-3">
                                <span class="text-emerald-500 text-lg flex-shrink-0">✓</span>
                                <span class="text-gray-700">{{ $f }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="/commerce/register" class="magnetic block text-center bg-gray-100 hover:bg-gray-200 text-gray-900 font-black py-4 rounded-2xl transition-all">
                        Commencer
                    </a>
                </div>

                <div class="stagger-item card-tilt relative bg-gradient-to-br from-primary-600 via-cyan-600 to-emerald-600 rounded-3xl p-8 text-white shadow-2xl overflow-hidden glow">
                    <div class="glare rounded-3xl"></div>
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-white/20 rounded-full blur-3xl"></div>

                    <div class="absolute -top-3 right-6 bg-yellow-400 text-yellow-900 px-4 py-1 rounded-full text-xs font-black shadow-lg">
                        ⭐ RECOMMANDÉ
                    </div>

                    <div class="relative">
                        <h3 class="text-2xl font-black mb-2">Pro</h3>
                        <p class="text-blue-100 mb-6">Pour grandir</p>

                        <div class="mb-8">
                            <span class="text-6xl font-black">5 000</span>
                            <span class="text-blue-200"> KMF/mois</span>
                        </div>

                        <ul class="space-y-3 mb-8">
                            @foreach(['Produits illimités', 'Tout le pack Gratuit', 'Achats fournisseurs', 'Dépenses', 'Rapports avancés', '5 utilisateurs', 'Support WhatsApp'] as $f)
                                <li class="flex items-start gap-3">
                                    <span class="text-yellow-300 text-lg flex-shrink-0">✓</span>
                                    <span>{{ $f }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a href="/commerce/register" class="magnetic block text-center bg-white text-primary-600 hover:bg-gray-50 font-black py-4 rounded-2xl transition-all">
                            Essayer 14 jours →
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
                <h2 class="text-4xl sm:text-6xl font-black text-gray-900 mb-4">
                    Questions <span class="gradient-text">fréquentes</span>
                </h2>
            </div>

            <div class="space-y-4 stagger-container">
                @foreach([
                    ['Sans internet ?', 'KomorShop fonctionne dans ton navigateur, une connexion est nécessaire.'],
                    ['Mes données sont sécurisées ?', 'Oui. Stockées de manière sécurisée et accessibles uniquement par toi.'],
                    ['Sur téléphone ?', 'Absolument. L\'interface s\'adapte à tous les écrans.'],
                    ['Comment obtenir de l\'aide ?', 'Plan Pro = support WhatsApp prioritaire. Plan Gratuit = email.'],
                    ['Engagement ?', 'Aucun. Tu arrêtes quand tu veux, sans frais cachés.'],
                ] as $faq)
                    <details class="stagger-item faq-item card-tilt group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                        <summary class="cursor-pointer p-6 flex items-center justify-between font-bold text-gray-900 hover:bg-gray-50 transition list-none">
                            <span>{{ $faq[0] }}</span>
                            <span class="w-8 h-8 bg-gradient-to-br from-primary-500 to-cyan-500 text-white rounded-full flex items-center justify-center group-open:rotate-45 transition-transform text-xl font-bold shadow-md">+</span>
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
    <section class="py-24 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-gradient-to-br from-primary-600 via-cyan-600 to-emerald-600 noise">

        <div class="absolute inset-0 opacity-20 dots-pattern"></div>

        <div class="relative max-w-4xl mx-auto text-center reveal z-10">
            <h2 class="text-5xl sm:text-7xl font-black text-white mb-6 leading-tight">
                Prêt à faire<br>grandir ton commerce ?
            </h2>
            <p class="text-xl text-blue-100 mb-10 max-w-2xl mx-auto">
                Rejoins les commerçants qui ont choisi KomorShop
            </p>

            <div class="relative inline-block">
                <a href="/commerce/register" class="magnetic btn-shine relative inline-block bg-white hover:bg-gray-50 text-primary-600 font-black px-12 py-6 rounded-2xl text-xl shadow-2xl transition-all glow">
                    Démarrer gratuitement →
                </a>
                <span class="pulse-ring bg-white rounded-2xl"></span>
            </div>

            <p class="text-blue-100 text-sm mt-6">
                Sans carte · Sans engagement · 2 minutes
            </p>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FOOTER                                       --}}
    {{-- ═══════════════════════════════════════════ --}}
    <footer class="bg-gray-900 text-gray-400 py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden noise">
        <div class="absolute inset-0 opacity-5 dots-pattern"></div>

        <div class="relative max-w-6xl mx-auto">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">

                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-500 via-cyan-500 to-emerald-500 rounded-xl flex items-center justify-center text-white text-2xl font-black shadow-lg">
                            K
                        </div>
                        <span class="text-2xl font-black text-white">KomorShop</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-md">
                        Le SaaS de gestion commerciale conçu pour les Comores 🇰🇲
                    </p>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-4">Produit</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="#tarifs" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="/commerce/register" class="hover:text-white transition">S'inscrire</a></li>
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