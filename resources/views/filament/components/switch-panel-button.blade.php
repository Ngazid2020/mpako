@php
    $user         = auth()->user();
    $isAdminPanel = str_starts_with(request()->path(), 'admin');
@endphp

@if($user && $user->is_admin && $isAdminPanel)
    {{-- On est dans Admin → bouton vers Commerce --}}
    @if($user->shops()->exists())
        <a
            href="{{ url('/commerce') }}"
            style="display:inline-flex;align-items:center;gap:8px;border-radius:8px;padding:6px 12px;font-size:0.875rem;font-weight:500;color:#fff;background:#2563eb;text-decoration:none;"
            title="Aller au panel Commerce"
        >
            <x-heroicon-o-building-storefront style="width:20px;height:20px;flex-shrink:0;" />
            <span>Commerce</span>
        </a>
    @endif

@elseif($user && $user->is_admin && !$isAdminPanel)
    {{-- On est dans Commerce → bouton vers Admin --}}
    <a
        href="{{ url('/admin') }}"
        style="display:inline-flex;align-items:center;gap:8px;border-radius:8px;padding:6px 12px;font-size:0.875rem;font-weight:500;color:#fff;background:#059669;text-decoration:none;"
        title="Retour au panel Admin"
    >
        <x-heroicon-o-shield-check style="width:20px;height:20px;flex-shrink:0;" />
        <span>Admin</span>
    </a>
@endif
