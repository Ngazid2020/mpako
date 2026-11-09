@php
    $overdue       = $this->getOverdueCredits();
    $expiringSoon  = $this->getExpiringSoonCredits();
    $hasAlerts     = $overdue->isNotEmpty() || $expiringSoon->isNotEmpty();
@endphp

<div>
@if($hasAlerts)
<div class="rounded-xl border overflow-hidden"
     style="border-color: rgb(220 38 38 / 0.3); background: rgb(254 242 242 / 0.6);"
     x-data>

    {{-- En-tête --}}
    <div class="flex items-center justify-between px-5 py-3"
         style="background: rgb(239 68 68 / 0.08); border-bottom: 1px solid rgb(220 38 38 / 0.2);">
        <div class="flex items-center gap-2">
            <x-heroicon-s-bell-alert class="w-5 h-5" style="color: #dc2626;" />
            <span class="text-sm font-bold" style="color: #dc2626;">
                Alertes
                @php $total = $overdue->count() + $expiringSoon->count(); @endphp
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-black text-white"
                      style="background: #dc2626;">{{ $total }}</span>
            </span>
        </div>
        <a href="{{ $this->getCreditsUrl() }}"
           class="text-xs font-semibold flex items-center gap-1 hover:underline"
           style="color: #dc2626;">
            Voir tous les crédits
            <x-heroicon-m-arrow-right class="w-3 h-3" />
        </a>
    </div>

    <div class="divide-y" style="divide-color: rgb(220 38 38 / 0.15);">

        {{-- ── Crédits en retard ── --}}
        @if($overdue->isNotEmpty())
        <div class="px-5 py-4">
            <p class="text-xs font-black uppercase tracking-widest mb-3" style="color: #dc2626; letter-spacing: .08em;">
                🔴 En retard — {{ $overdue->count() }} crédit(s)
                · {{ number_format($overdue->sum('remaining_amount'), 0, ',', ' ') }} KMF
            </p>
            <div class="space-y-2">
                @foreach($overdue as $credit)
                @php
                    $daysLate = now()->startOfDay()->diffInDays($credit->due_date->startOfDay());
                @endphp
                <a href="{{ \App\Filament\Commerce\Resources\CreditResource::getUrl('view', ['record' => $credit, 'tenant' => Filament\Facades\Filament::getTenant()]) }}"
                   class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 transition-colors hover:bg-red-50 dark:hover:bg-red-950/30 group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-bold text-sm text-white"
                             style="background: #dc2626;">
                            {{ mb_strtoupper(mb_substr($credit->customer?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                {{ $credit->customer?->name ?? 'Client inconnu' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $credit->reference }}
                                · <span style="color:#dc2626;font-weight:600;">{{ $daysLate }}j de retard</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="font-bold text-sm" style="color:#dc2626;">
                            {{ number_format($credit->remaining_amount, 0, ',', ' ') }} KMF
                        </span>
                        <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-red-500 transition-colors" />
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── Expirent bientôt ── --}}
        @if($expiringSoon->isNotEmpty())
        <div class="px-5 py-4">
            <p class="text-xs font-black uppercase tracking-widest mb-3" style="color: #d97706; letter-spacing: .08em;">
                🟡 Expirent dans 7 jours — {{ $expiringSoon->count() }} crédit(s)
                · {{ number_format($expiringSoon->sum('remaining_amount'), 0, ',', ' ') }} KMF
            </p>
            <div class="space-y-2">
                @foreach($expiringSoon as $credit)
                @php
                    $daysLeft = now()->startOfDay()->diffInDays($credit->due_date->startOfDay());
                @endphp
                <a href="{{ \App\Filament\Commerce\Resources\CreditResource::getUrl('view', ['record' => $credit, 'tenant' => Filament\Facades\Filament::getTenant()]) }}"
                   class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 transition-colors hover:bg-amber-50 dark:hover:bg-amber-950/30 group">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-bold text-sm text-white"
                             style="background: #d97706;">
                            {{ mb_strtoupper(mb_substr($credit->customer?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                {{ $credit->customer?->name ?? 'Client inconnu' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $credit->reference }}
                                · Échéance {{ $credit->due_date->translatedFormat('d M') }}
                                · <span style="color:#d97706;font-weight:600;">
                                    {{ $daysLeft === 0 ? "aujourd'hui" : "dans {$daysLeft}j" }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="font-bold text-sm" style="color:#d97706;">
                            {{ number_format($credit->remaining_amount, 0, ',', ' ') }} KMF
                        </span>
                        <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" />
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endif
</div>
