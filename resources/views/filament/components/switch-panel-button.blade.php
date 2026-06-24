@php
    $user = auth()->user();
    $currentPanel = filament()->getCurrentPanel()->getId();
@endphp

@if($user && $user->is_admin && $currentPanel === 'admin')
    {{-- On est dans Admin → bouton vers Commerce --}}
    @if($user->shops()->exists())
        <a 
            href="{{ url('/commerce') }}"
            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200"
            title="Aller au panel Commerce"
        >
            <x-heroicon-o-building-storefront class="h-5 w-5" />
            <span class="hidden sm:inline">Commerce</span>
        </a>
    @endif
@elseif($user && $user->is_admin && $currentPanel === 'commerce')
    {{-- On est dans Commerce → bouton vers Admin --}}
    <a 
        href="{{ url('/admin') }}"
        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors duration-200"
        title="Retour au panel Admin"
    >
        <x-heroicon-o-shield-check class="h-5 w-5" />
        <span class="hidden sm:inline">Admin</span>
    </a>
@endif