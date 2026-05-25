@php
    $company = $company ?? [];
    $logo = $company['company_logo'] ?? null;
    $logoUrl = null;

    if ($logo) {
        $logoUrl = \Illuminate\Support\Str::startsWith($logo, ['http://', 'https://'])
            ? $logo
            : asset(\Illuminate\Support\Str::startsWith($logo, ['storage/', 'uploads/']) ? $logo : 'storage/' . $logo);
    }

    $navItems = [
        ['label' => 'Inicio', 'route' => 'portal.index'],
        ['label' => 'Sobre nosotros', 'route' => 'portal.page', 'params' => ['sobre-nosotros']],
        ['label' => 'Atencion', 'route' => 'portal.page', 'params' => ['atencion-al-publico']],
        ['label' => 'Comunicados', 'route' => 'portal.page', 'params' => ['comunicados']],
        ['label' => 'Horarios', 'route' => 'portal.page', 'params' => ['horarios']],
        ['label' => 'Proyectos', 'route' => 'portal.page', 'params' => ['proyectos']],
        ['label' => 'Pagos online', 'route' => 'portal.page', 'params' => ['pagos-online']],
        ['label' => 'Puntos', 'route' => 'portal.page', 'params' => ['puntos']],
        ['label' => 'EPSAS informa', 'route' => 'portal.page', 'params' => ['epsas-informa']],
        ['label' => 'Contactanos', 'route' => 'portal.page', 'params' => ['contactanos']],
    ];

    $currentPage = request()->route('page');
@endphp
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal cliente') - {{ $company['company_name'] ?? 'EPSAS' }}</title>
    
    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css'])
    
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@650;760&family=Manrope:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    
    {{-- Additional head stack --}}
    @stack('head')
</head>
<body class="portal-bg min-h-screen overflow-x-hidden">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[28rem] bg-[linear-gradient(180deg,rgba(0,27,72,.10),transparent)]"></div>

    <header class="sticky top-0 z-40 border-b border-white/55 bg-white/82 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('portal.index') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[1.35rem] border border-[#d6e8ee] bg-white p-1 shadow-sm">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $company['company_name'] ?? 'EPSAS' }}" class="h-11 w-11 object-contain">
                    @else
                        <span class="display-font text-2xl font-black text-[#02457a]">E</span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-base font-black text-[#001b48] sm:text-lg">{{ $company['company_name'] ?? 'EPSAS' }}</span>
                    <span class="block text-[10px] font-black uppercase tracking-[0.24em] text-[#018abe]">Portal ciudadano</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 xl:flex">
                @foreach ($navItems as $item)
                    @php
                        $params = $item['params'] ?? [];
                        $active = ($item['route'] === 'portal.index' && request()->routeIs('portal.index'))
                            || ($item['route'] === 'portal.page' && ($params[0] ?? null) === $currentPage);
                    @endphp
                    <a href="{{ route($item['route'], $params) }}" class="{{ $active ? 'nav-link-active' : 'text-[#02457a] hover:bg-[#edf8fc]' }} rounded-full px-3 py-2 text-xs font-extrabold transition">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('portal.page', ['pagos-online']) }}" class="hidden rounded-full bg-[#001b48] px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-white shadow-[0_14px_28px_rgba(0,27,72,.20)] sm:inline-flex">
                    Pagar
                </a>
                <button type="button" data-mobile-menu-toggle class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[#d6e8ee] bg-white text-[#02457a] shadow-sm xl:hidden" aria-label="Abrir menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div data-mobile-menu class="mobile-menu border-t border-[#d6e8ee]/80 bg-white/96 px-4 pb-4 xl:hidden">
            <div class="grid gap-2 pt-3 sm:grid-cols-2">
                @foreach ($navItems as $item)
                    @php
                        $params = $item['params'] ?? [];
                        $active = ($item['route'] === 'portal.index' && request()->routeIs('portal.index'))
                            || ($item['route'] === 'portal.page' && ($params[0] ?? null) === $currentPage);
                    @endphp
                    <a href="{{ route($item['route'], $params) }}" class="{{ $active ? 'bg-[#d6e8ee] text-[#001b48]' : 'bg-[#f6fbff] text-[#02457a]' }} rounded-2xl px-4 py-3 text-sm font-extrabold">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <footer class="mt-10 overflow-hidden bg-[#001b48] text-white">
        <div class="relative">
            <div class="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(ellipse_at_top,_rgba(151,202,219,.70),transparent_60%)]"></div>
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-[1.2fr_.8fr_.8fr] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-2">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-10 object-contain">
                            @else
                                <span class="display-font text-2xl font-black text-[#02457a]">E</span>
                            @endif
                        </span>
                        <div>
                            <p class="font-black">{{ $company['company_name'] ?? 'EPSAS' }}</p>
                            <p class="text-xs uppercase tracking-[0.22em] text-[#97cadb]">Agua potable y servicio ciudadano</p>
                        </div>
                    </div>
                    <p class="mt-5 max-w-md text-sm leading-7 text-white/72">
                        Gestionamos el acceso al agua con informacion clara, pagos seguros por orden y atencion cercana para cada socio.
                    </p>
                </div>

                <div>
                    <p class="font-black">Enlaces rapidos</p>
                    <div class="mt-4 grid gap-2 text-sm text-white/72">
                        <a href="{{ route('portal.page', ['pagos-online']) }}" class="hover:text-white">Pagos online</a>
                        <a href="{{ route('portal.page', ['puntos']) }}" class="hover:text-white">Puntos de pago</a>
                        <a href="{{ route('portal.page', ['comunicados']) }}" class="hover:text-white">Comunicados</a>
                        <a href="{{ route('portal.page', ['contactanos']) }}" class="hover:text-white">Contactanos</a>
                    </div>
                </div>

                <div>
                    <p class="font-black">Contacto</p>
                    <div class="mt-4 space-y-2 text-sm text-white/72">
                        <p>{{ $company['address'] ?? 'Oficina central EPSAS El Portillo' }}</p>
                        <p>{{ $company['company_phone'] ?? $company['support_phone'] ?? '(591) 678-4664' }}</p>
                        <p>{{ $company['company_email'] ?? $company['support_email'] ?? 'atencion@epsas.bo' }}</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 px-4 py-5 text-center text-xs text-white/55">
                {{ now()->year }} {{ $company['company_name'] ?? 'EPSAS' }}. Portal informativo y de pagos por orden.
            </div>
        </div>
    </footer>

    <script>
        (() => {
            const toggle = document.querySelector('[data-mobile-menu-toggle]');
            const menu = document.querySelector('[data-mobile-menu]');
            toggle?.addEventListener('click', () => menu?.classList.toggle('is-open'));
        })();
    </script>
    
    {{-- Vite JS Bundle --}}
    @vite(['resources/js/app.js'])
    
    {{-- Additional scripts stack --}}
    @stack('scripts')
</body>
</html>
