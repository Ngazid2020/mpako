@php
    use Filament\Facades\Filament;

    $shop  = Filament::getTenant();
    $slug  = $shop?->slug ?? '';
    $base  = "/commerce/{$slug}";
    $path  = '/' . trim(request()->path(), '/');

    $isDash      = $path === $base || $path === "{$base}/";
    $isCaisse    = str_contains($path, '/caisse');
    $isProducts  = str_contains($path, '/products');
    $isCustomers = str_contains($path, '/customers');
    $isReports   = str_contains($path, '-report') || str_contains($path, '/sales-report');

    $tab = 'flex-1 flex flex-col items-center justify-center gap-[3px] no-underline';
@endphp

<nav class="beez-mobile-nav lg:hidden fixed bottom-0 inset-x-0 z-[9998]"
     style="padding-bottom:env(safe-area-inset-bottom,0px)">

    <div class="flex h-[68px]">

        {{-- Accueil --}}
        <a href="{{ url($base) }}" class="{{ $tab }} {{ $isDash ? 'active-tab' : '' }}"
           style="-webkit-tap-highlight-color:transparent">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"/>
                <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"/>
            </svg>
            <span class="nav-label text-[10px] leading-none">Accueil</span>
        </a>

        {{-- Produits --}}
        <a href="{{ url("{$base}/products") }}" class="{{ $tab }} {{ $isProducts ? 'active-tab' : '' }}"
           style="-webkit-tap-highlight-color:transparent">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03Z"/>
                <path d="M21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 0 0 .372-.648V7.93ZM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 0 0 .372.648l8.628 5.033Z"/>
            </svg>
            <span class="nav-label text-[10px] leading-none">Produits</span>
        </a>

        {{-- Boutique — cercle élevé au centre --}}
        <a href="{{ url("{$base}/caisse") }}"
           class="flex-1 flex flex-col items-center justify-end pb-[8px] no-underline {{ $isCaisse ? 'active-tab' : '' }}"
           style="-webkit-tap-highlight-color:transparent">
            <span class="boutique-circle w-[48px] h-[48px] rounded-full flex items-center justify-center flex-shrink-0"
                  style="margin-top:-16px">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path fill="currentColor" d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"/>
                </svg>
            </span>
            <span class="nav-label text-[10px] leading-none mt-[3px]">Boutique</span>
        </a>

        {{-- Clients --}}
        <a href="{{ url("{$base}/customers") }}" class="{{ $tab }} {{ $isCustomers ? 'active-tab' : '' }}"
           style="-webkit-tap-highlight-color:transparent">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" fill="currentColor" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd"/>
                <path fill="currentColor" d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.487l-.115.04c-.567.2-1.156.349-1.764.44Z"/>
            </svg>
            <span class="nav-label text-[10px] leading-none">Clients</span>
        </a>

        {{-- Rapports --}}
        <a href="{{ url("{$base}/profit-report") }}" class="{{ $tab }} {{ $isReports ? 'active-tab' : '' }}"
           style="-webkit-tap-highlight-color:transparent">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path fill="currentColor" d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.035-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.035.84-1.875 1.875-1.875h.75c1.035 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.035.84-1.875 1.875-1.875h.75c1.035 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z"/>
            </svg>
            <span class="nav-label text-[10px] leading-none">Rapports</span>
        </a>

    </div>
</nav>
