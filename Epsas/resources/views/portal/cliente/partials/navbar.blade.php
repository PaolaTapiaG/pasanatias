<header class="sticky top-0 z-50 border-b border-[#d6e8ee] bg-white shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        {{-- LOGO --}}
        <a href="{{ route('portal.index') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-[#02457a] bg-[#f6fbff] p-1 shadow-sm">
                @if ($logoUrl ?? false)
                    <img src="{{ $logoUrl }}" alt="Logo {{ $company['company_name'] ?? 'EPSAS' }}" class="h-11 w-11 object-contain">
                @else
                    <span class="display-font text-xl font-black text-[#02457a]">E</span>
                @endif
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-black text-[#001b48] sm:text-base">{{ $company['company_name'] ?? 'EPSAS' }}</span>
                <span class="block text-[9px] font-bold uppercase tracking-[0.2em] text-[#018abe]">Portal ciudadano</span>
            </span>
        </a>

        {{-- MENU DESKTOP --}}
        <nav class="hidden items-center gap-2 xl:flex">
            @foreach ($navItems as $item)
                @php
                    $params = $item['params'] ?? [];
                    $active = ($item['route'] === 'portal.index' && request()->routeIs('portal.index'))
                        || ($item['route'] === 'portal.page' && ($params[0] ?? null) === $currentPage);
                @endphp
                <a href="{{ route($item['route'], $params) }}" 
                   class="rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-[0.08em] transition-all duration-300
                          {{ $active ? 'bg-[#d6e8ee] text-[#001b48]' : 'text-[#02457a] hover:bg-[#edf8fc] hover:text-[#001b48]' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- ACTIONS --}}
        <div class="flex items-center gap-3">
            @include('portal.cliente.partials.dark-toggle')
            
            <a href="{{ route('portal.page', ['pagos-online']) }}" class="hidden rounded-lg bg-[#02457a] px-5 py-2.5 text-xs font-black uppercase tracking-[0.1em] text-white shadow-md transition hover:bg-[#001b48] hover:shadow-lg sm:inline-flex">
                Pagar
            </a>
            
            <button type="button" data-mobile-menu-toggle class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-[#d6e8ee] bg-[#f6fbff] text-[#02457a] shadow-sm transition hover:bg-white xl:hidden" aria-label="Abrir menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- MOBILE MENU --}}
    <div data-mobile-menu class="mobile-menu border-t border-[#d6e8ee] bg-white/98 px-4 pb-4 xl:hidden">
        <div class="grid gap-2 pt-3 sm:grid-cols-2">
            @foreach ($navItems as $item)
                @php
                    $params = $item['params'] ?? [];
                    $active = ($item['route'] === 'portal.index' && request()->routeIs('portal.index'))
                        || ($item['route'] === 'portal.page' && ($params[0] ?? null) === $currentPage);
                @endphp
                <a href="{{ route($item['route'], $params) }}" 
                   class="rounded-lg px-4 py-2.5 text-sm font-bold uppercase tracking-[0.08em] transition
                          {{ $active ? 'bg-[#d6e8ee] text-[#001b48]' : 'bg-[#f6fbff] text-[#02457a] hover:bg-[#edf8fc]' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>

