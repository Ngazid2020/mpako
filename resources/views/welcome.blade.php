<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KomorShop — Le commerce moderne aux Comores</title>
    <meta name="description" content="Gérez votre boutique facilement : stock, caisse, crédits, ventes. Conçu pour les commerçants des Comores." />

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-gray-900">

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HEADER                                       --}}
    {{-- ═══════════════════════════════════════════ --}}
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">

            {{-- Logo --}}
            <a href="#" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center text-white text-xl font-bold shadow-lg">
                    K
                </div>
                <span class="text-xl font-bold text-gray-900">KomorShop</span>
            </a>

            {{-- Navigation desktop --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#features" class="hover:text-primary-600 transition">Fonctionnalités</a>
                <a href="#pour-qui" class="hover:text-primary-600 transition">Pour qui ?</a>
                <a href="#tarifs" class="hover:text-primary-600 transition">Tarifs</a>
                <a href="#faq" class="hover:text-primary-600 transition">FAQ</a>
            </nav>

            {{-- CTA --}}
            <div class="flex items-center gap-3">
                <a href="/commerce/login" class="hidden sm:inline text-sm font-medium text-gray-700 hover:text-primary-600">
                    Connexion
                </a>
                <a href="/commerce/register" class="bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm hover:shadow-md transition">
                    Commencer
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- HERO                                         --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-primary-50 to-white">
        <div class="max-w-6xl mx-auto grid lg:grid-cols-2 gap-12 items-center">

            {{-- Texte --}}
            <div>
                <div class="inline-flex items-center gap-2 bg-primary-100 text-primary-700 px-4 py-1.5 rounded-full text-sm font-medium mb-6">
                    🇰🇲 Conçu pour les Comores
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 leading-tight mb-6">
                    Gère ton commerce
                    <span class="bg-gradient-to-r from-primary-600 to-blue-500 bg-clip-text text-transparent">
                        en toute simplicité
                    </span>
                </h1>

                <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                    Stock, caisse, ventes, crédits, dépenses, bénéfices.
                    Tout ce dont tu as besoin pour faire grandir ton commerce,
                    accessible depuis ton téléphone ou ton ordinateur.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="/commerce/register" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl transition text-center">
                        Créer mon commerce gratuitement
                    </a>
                    <a href="#features" class="bg-white border-2 border-gray-200 hover:border-primary-300 text-gray-700 font-semibold px-8 py-4 rounded-xl transition text-center">
                        Voir les fonctionnalités
                    </a>
                </div>

                {{-- Trust badges --}}
                <div class="mt-8 flex flex-wrap gap-6 text-sm text-gray-500">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Sans engagement
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Prix en KMF
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Mobile-friendly
                    </span>
                </div>
            </div>

            {{-- Mockup / Image --}}
            <div class="relative">
                <div class="bg-gradient-to-br from-primary-100 to-blue-100 rounded-3xl p-6 shadow-2xl">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        {{-- Mock dashboard --}}
                        <div class="bg-gray-50 px-4 py-3 flex items-center gap-2 border-b">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <span class="ml-2 text-xs text-gray-500">KomorShop — Boutique Ali</span>
                        </div>

                        <div class="p-6 space-y-4">
                            {{-- Stats --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">CA du jour</p>
                                    <p class="text-2xl font-bold text-blue-600">12 500 KMF</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Bénéfice</p>
                                    <p class="text-2xl font-bold text-green-600">+3 200 KMF</p>
                                </div>
                            </div>

                            {{-- Graphique simulé --}}
                            <div class="bg-gray-50 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-2">Ventes des 7 jours</p>
                                <div class="flex items-end gap-1 h-20">
                                    @foreach([40, 60, 35, 80, 55, 90, 75] as $h)
                                        <div class="flex-1 bg-gradient-to-t from-primary-500 to-primary-300 rounded-t" style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Alertes --}}
                            <div class="bg-orange-50 border-l-4 border-orange-400 p-3 rounded-r-lg">
                                <p class="text-sm font-medium text-orange-700">⚠️ 3 produits en stock bas</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Badge flottant --}}
                <div class="absolute -top-4 -right-4 bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full font-bold text-sm shadow-lg transform rotate-12">
                    🚀 100% Made in Comores
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- PROBLÈME / SOLUTION                          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-5xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                Ces problèmes te parlent ?
            </h2>
            <p class="text-lg text-gray-600 mb-12">
                Tu n'es pas seul. La plupart des commerçants vivent ça au quotidien.
            </p>

            <div class="grid sm:grid-cols-3 gap-6">
                @foreach([
                    ['😩', 'Je perds la trace de mon stock', 'Combien de riz me reste-t-il vraiment ?'],
                    ['🤔', 'Qui me doit de l\'argent ?', 'Mes clients à crédit, je ne sais plus combien ils me doivent'],
                    ['😓', 'Est-ce que je gagne vraiment ?', 'Je vends, mais à la fin du mois, il ne reste rien'],
                ] as $problem)
                    <div class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition">
                        <div class="text-5xl mb-4">{{ $problem[0] }}</div>
                        <h3 class="font-bold text-gray-900 mb-2">{{ $problem[1] }}</h3>
                        <p class="text-sm text-gray-500">{{ $problem[2] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 inline-flex items-center gap-2 bg-primary-100 text-primary-700 px-6 py-3 rounded-full font-medium">
                <span class="text-xl">✨</span>
                KomorShop te donne la réponse en quelques clics
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FONCTIONNALITÉS                              --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    Tout ce dont tu as besoin
                </h2>
                <p class="text-lg text-gray-600">
                    Une suite complète d'outils pensés pour les commerces comoriens
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['📦', 'Gestion du stock', 'Produits, catégories, alertes automatiques quand un produit est presque épuisé'],
                    ['💰', 'Caisse rapide', 'Encaisse en quelques secondes, recherche produit en temps réel, calcul automatique de la monnaie'],
                    ['📒', 'Crédits clients', 'Note qui te doit quoi, suis les remboursements, plus jamais d\'oubli'],
                    ['🧾', 'Achats fournisseurs', 'Enregistre tes approvisionnements, paye en plusieurs fois, garde la trace'],
                    ['💳', 'Suivi des dépenses', 'Loyer, électricité, transport... vois où part ton argent'],
                    ['📊', 'Tableau de bord', 'Visualise ton activité : CA, bénéfice, produits qui marchent, alertes'],
                ] as $feat)
                    <div class="group bg-white border border-gray-100 rounded-2xl p-6 hover:border-primary-300 hover:shadow-lg transition">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary-100 to-primary-200 rounded-xl flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                            {{ $feat[0] }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $feat[1] }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $feat[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- POUR QUI ?                                   --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="pour-qui" class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-blue-50 to-white">
        <div class="max-w-5xl mx-auto text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                Pour qui est KomorShop ?
            </h2>
            <p class="text-lg text-gray-600 mb-12">
                Tous les commerces des Comores, peu importe leur taille
            </p>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach([
                    ['🏪', 'Boutiques'],
                    ['🥖', 'Épiceries'],
                    ['☕', 'Restaurants'],
                    ['👕', 'Vendeurs textile'],
                    ['💊', 'Pharmacies'],
                    ['📱', 'Vente téléphones'],
                    ['🛺', 'Vendeurs ambulants'],
                    ['🍞', 'Boulangeries'],
                ] as $type)
                    <div class="bg-white border border-gray-100 rounded-xl p-6 hover:shadow-md transition">
                        <div class="text-4xl mb-2">{{ $type[0] }}</div>
                        <p class="font-medium text-gray-700">{{ $type[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- TARIFICATION                                 --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="tarifs" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    Des prix justes et clairs
                </h2>
                <p class="text-lg text-gray-600">
                    Commence gratuitement, évolue quand tu es prêt
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">

                {{-- Plan Gratuit --}}
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Gratuit</h3>
                    <p class="text-gray-500 mb-6">Pour démarrer</p>

                    <div class="mb-6">
                        <span class="text-5xl font-bold text-gray-900">0</span>
                        <span class="text-gray-500"> KMF/mois</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        @foreach([
                            'Jusqu\'à 50 produits',
                            'Caisse illimitée',
                            'Suivi des crédits',
                            'Tableau de bord basique',
                            '1 utilisateur',
                        ] as $feat)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">{{ $feat }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="/commerce/register" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-900 font-semibold py-3 rounded-xl transition">
                        Commencer gratuitement
                    </a>
                </div>

                {{-- Plan Pro --}}
                <div class="bg-gradient-to-br from-primary-600 to-blue-600 rounded-2xl p-8 text-white shadow-2xl relative">
                    <div class="absolute -top-3 right-6 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-bold">
                        ⭐ RECOMMANDÉ
                    </div>

                    <h3 class="text-2xl font-bold mb-2">Pro</h3>
                    <p class="text-blue-100 mb-6">Pour les commerces qui grandissent</p>

                    <div class="mb-6">
                        <span class="text-5xl font-bold">5 000</span>
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
                            'Support prioritaire WhatsApp',
                        ] as $feat)
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-300 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ $feat }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="/commerce/register" class="block text-center bg-white text-primary-600 hover:bg-gray-50 font-semibold py-3 rounded-xl transition">
                        Essayer 14 jours gratuits
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FAQ                                          --}}
    {{-- ═══════════════════════════════════════════ --}}
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    Questions fréquentes
                </h2>
            </div>

            <div class="space-y-4">
                @foreach([
                    [
                        'Est-ce que ça marche sans internet ?',
                        'KomorShop fonctionne dans ton navigateur. Une connexion internet est nécessaire. Une version hors-ligne est en préparation.',
                    ],
                    [
                        'Mes données sont-elles en sécurité ?',
                        'Oui. Tes données sont stockées de manière sécurisée et ne sont accessibles qu\'à toi et aux personnes que tu autorises.',
                    ],
                    [
                        'Puis-je utiliser KomorShop sur mon téléphone ?',
                        'Absolument. L\'interface s\'adapte à tous les écrans : téléphone, tablette, ordinateur.',
                    ],
                    [
                        'Comment puis-je obtenir de l\'aide ?',
                        'Le plan Pro inclut un support WhatsApp prioritaire. Pour le plan gratuit, contacte-nous par email.',
                    ],
                    [
                        'Y a-t-il un engagement ?',
                        'Aucun engagement. Tu peux arrêter à tout moment.',
                    ],
                ] as $faq)
                    <details class="group bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <summary class="cursor-pointer p-5 flex items-center justify-between font-semibold text-gray-900 hover:bg-gray-50 transition">
                            <span>{{ $faq[0] }}</span>
                            <svg class="w-5 h-5 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="px-5 pb-5 text-gray-600 leading-relaxed">
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
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-primary-600 to-blue-600">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl sm:text-5xl font-bold text-white mb-6">
                Prêt à faire grandir ton commerce ?
            </h2>
            <p class="text-xl text-blue-100 mb-10">
                Rejoins les commerçants qui ont déjà choisi KomorShop
            </p>

            <a href="/commerce/register" class="inline-block bg-white hover:bg-gray-50 text-primary-600 font-bold px-10 py-5 rounded-2xl text-lg shadow-2xl hover:shadow-3xl transition transform hover:-translate-y-1">
                Créer mon commerce gratuitement →
            </a>

            <p class="text-blue-100 text-sm mt-6">
                Sans carte bancaire · Sans engagement · En 2 minutes
            </p>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════ --}}
    {{-- FOOTER                                       --}}
    {{-- ═══════════════════════════════════════════ --}}
    <footer class="bg-gray-900 text-gray-400 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">

                {{-- Logo + intro --}}
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                            K
                        </div>
                        <span class="text-xl font-bold text-white">KomorShop</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-md">
                        Le SaaS de gestion commerciale conçu spécialement
                        pour les petits commerces des Comores.
                    </p>
                </div>

                {{-- Liens --}}
                <div>
                    <h4 class="text-white font-semibold mb-4">Produit</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="#tarifs" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="/commerce/register" class="hover:text-white transition">S'inscrire</a></li>
                        <li><a href="/commerce/login" class="hover:text-white transition">Se connecter</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-semibold mb-4">Contact</h4>
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