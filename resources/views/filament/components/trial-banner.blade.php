{{-- Bandeau « essai gratuit » affiché en haut des pages du commerce pendant l'essai --}}
@php
    $subscription = $shop->currentSubscription();
    $days = $subscription ? $subscription->daysRemaining() : 0;
@endphp

@if ($subscription?->plan?->is_trial)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl px-4 py-3 text-sm ring-1
                {{ $days <= 5 ? 'bg-amber-50 text-amber-900 ring-amber-200' : 'bg-primary-50 text-primary-900 ring-primary-200' }}">
        <span>
            🎁 <strong>Essai gratuit</strong> :
            @if ($days > 1)
                encore {{ $days }} jours
            @elseif ($days === 1)
                dernier jour
            @else
                se termine aujourd'hui
            @endif
            (jusqu'au {{ $subscription->ends_at->translatedFormat('d F Y') }}).
        </span>
        <span class="text-xs opacity-80">Pensez à recharger votre portefeuille pour continuer après l'essai.</span>
    </div>
@endif
