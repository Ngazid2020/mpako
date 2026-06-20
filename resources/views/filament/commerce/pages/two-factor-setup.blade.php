<x-filament-panels::page>
    <div class="max-w-2xl mx-auto space-y-6">

        {{-- ═══════════════════════════════════════════
             ÉTAT : STATUT (activé / désactivé)
        ═══════════════════════════════════════════ --}}
        @if ($pageState === 'status')
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start gap-5 p-6">

                    @if (auth()->user()->hasEnabledTwoFactor())
                        {{-- 2FA actif --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-50 dark:bg-success-500/10">
                                <x-heroicon-o-shield-check class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                Authentification à deux facteurs activée
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Votre compte est protégé. À chaque connexion, un code à 6 chiffres sera demandé depuis votre application d'authentification.
                            </p>
                            <div class="mt-4">
                                <x-filament::button
                                    wire:click="disable"
                                    wire:confirm="Désactiver le 2FA ? Votre compte sera moins sécurisé."
                                    color="danger"
                                    outlined
                                >
                                    Désactiver le 2FA
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        {{-- 2FA inactif --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-50 dark:bg-warning-500/10">
                                <x-heroicon-o-shield-exclamation class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                Authentification à deux facteurs désactivée
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Activez le 2FA pour protéger votre compte avec un code temporaire (Google Authenticator, Authy, etc.).
                            </p>
                            <div class="mt-4">
                                <x-filament::button wire:click="startSetup" color="success">
                                    Activer le 2FA
                                </x-filament::button>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════
             ÉTAT : CONFIGURATION (scan QR + confirmation)
        ═══════════════════════════════════════════ --}}
        @if ($pageState === 'setup')
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 divide-y divide-gray-100 dark:divide-white/10">

                {{-- En-tête --}}
                <div class="p-6">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Configurer l'authentification 2FA
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Suivez les étapes ci-dessous pour lier votre application d'authentification.
                    </p>
                </div>

                {{-- Étape 1 : Scanner --}}
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">1</span>
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Scannez ce QR code dans votre application</span>
                    </div>

                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                        {{-- QR Code --}}
                        <div class="flex-shrink-0 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10">
                            {!! $qrCodeSvg !!}
                        </div>

                        {{-- Clé manuelle --}}
                        <div class="flex-1 space-y-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Impossible de scanner ? Entrez cette clé manuellement dans votre application :
                            </p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 rounded-lg bg-gray-50 px-3 py-2 font-mono text-sm tracking-widest text-gray-800 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10">
                                    {{ $secretKey }}
                                </code>
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $secretKey }}').then(() => { this.textContent = '✓'; setTimeout(() => this.textContent = 'Copier', 1500) })"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50 dark:text-gray-400 dark:ring-white/10 dark:hover:bg-white/5"
                                >
                                    Copier
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Applications recommandées : Google Authenticator, Authy, 1Password.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Étape 2 : Confirmer --}}
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900/50 dark:text-primary-300">2</span>
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Entrez le code généré par votre application pour confirmer</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <input
                                wire:model.defer="confirmCode"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                placeholder="000000"
                                maxlength="6"
                                class="block w-full rounded-lg border-0 py-2.5 px-3.5 text-center font-mono text-2xl tracking-[0.5em] text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10 sm:max-w-[200px]"
                            >
                            @error('confirmCode')
                                <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <x-filament::button wire:click="confirmSetup" color="success">
                                Confirmer et activer
                            </x-filament::button>
                            <x-filament::button wire:click="cancelSetup" color="gray" outlined>
                                Annuler
                            </x-filament::button>
                        </div>
                    </div>
                </div>

            </div>
        @endif

        {{-- ═══════════════════════════════════════════
             ÉTAT : CODES DE SECOURS (affichage unique)
        ═══════════════════════════════════════════ --}}
        @if ($pageState === 'recovery')
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 divide-y divide-gray-100 dark:divide-white/10">

                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-50 dark:bg-warning-500/10">
                                <x-heroicon-o-key class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                                Codes de secours — sauvegardez-les maintenant !
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Si vous perdez l'accès à votre application, utilisez l'un de ces codes pour vous connecter.
                                Chaque code ne peut être utilisé <strong>qu'une seule fois</strong>.
                                Cette liste ne sera plus affichée après avoir quitté cette page.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($recoveryCodes as $code)
                            <code class="rounded-lg bg-gray-50 px-4 py-2.5 font-mono text-sm text-gray-800 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10">
                                {{ $code }}
                            </code>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            onclick="
                                const codes = @js($recoveryCodes);
                                navigator.clipboard.writeText(codes.join('\n'));
                                this.textContent = '✓ Copié !';
                                setTimeout(() => this.textContent = 'Copier tous les codes', 2000);
                            "
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/5"
                        >
                            Copier tous les codes
                        </button>

                        <x-filament::button wire:click="$set('pageState', 'status')" color="primary">
                            J'ai sauvegardé mes codes
                        </x-filament::button>
                    </div>
                </div>

            </div>
        @endif

    </div>
</x-filament-panels::page>
