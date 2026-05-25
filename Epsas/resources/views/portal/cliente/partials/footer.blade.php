<footer class="relative bg-gradient-to-b from-[#001b48] to-[#000d23] text-white dark:from-slate-900 dark:to-slate-950">
    
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        
        <div class="grid gap-12 md:grid-cols-[1.2fr_.8fr_.8fr]">
            
            {{-- BRAND --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/10 p-2 border border-white/20">
                        @if ($logoUrl ?? false)
                            <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-10 object-contain filter brightness-200">
                        @else
                            <span class="display-font text-2xl font-black text-white">E</span>
                        @endif
                    </span>
                    <div>
                        <p class="font-black text-white">{{ $company['company_name'] ?? 'EPSAS' }}</p>
                        <p class="text-xs uppercase tracking-[0.2em] text-[#97cadb]">Agua potable y servicio</p>
                    </div>
                </div>
                <p class="mt-5 max-w-md text-sm leading-7 text-white/75">
                    Gestionamos el acceso al agua con información clara, pagos seguros por orden y atención cercana para cada socio.
                </p>
            </div>

            {{-- QUICK LINKS --}}
            <div>
                <p class="font-black text-white">Enlaces rápidos</p>
                <div class="mt-4 grid gap-2 text-sm text-white/75">
                    <a href="{{ route('portal.page', ['pagos-online']) }}" class="hover:text-[#97cadb] transition">Pagos online</a>
                    <a href="{{ route('portal.page', ['puntos']) }}" class="hover:text-[#97cadb] transition">Puntos de pago</a>
                    <a href="{{ route('portal.page', ['comunicados']) }}" class="hover:text-[#97cadb] transition">Comunicados</a>
                    <a href="{{ route('portal.page', ['contactanos']) }}" class="hover:text-[#97cadb] transition">Contáctanos</a>
                </div>
            </div>

            {{-- CONTACT --}}
            <div>
                <p class="font-black text-white">Contacto</p>
                <div class="mt-4 space-y-2 text-sm text-white/75">
                    <p>{{ $company['address'] ?? 'Oficina central EPSAS El Portillo' }}</p>
                    <p>{{ $company['company_phone'] ?? $company['support_phone'] ?? '(591) 678-4664' }}</p>
                    <p>{{ $company['company_email'] ?? $company['support_email'] ?? 'atencion@epsas.bo' }}</p>
                </div>
            </div>

        </div>

        {{-- DIVIDER --}}
        <div class="my-8 border-t border-white/10"></div>

        {{-- BOTTOM --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between text-xs text-white/55">
            <p>{{ now()->year }} {{ $company['company_name'] ?? 'EPSAS' }}. Portal informativo y de pagos por orden.</p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-white/75 transition">Privacidad</a>
                <a href="#" class="hover:text-white/75 transition">Términos</a>
                <a href="#" class="hover:text-white/75 transition">Accesibilidad</a>
            </div>
        </div>

    </div>

</footer>

