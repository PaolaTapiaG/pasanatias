@extends('portal.cliente.layout')

@section('title', $pageData['kicker'] ?? 'Portal cliente')

@section('content')
    <section class="relative overflow-hidden rounded-[2.5rem] water-wave p-6 text-white shadow-[0_30px_80px_rgba(0,27,72,.18)] sm:p-10">
        <div class="absolute inset-0 water-ripple opacity-25"></div>
        <div class="absolute -right-16 -top-16 h-60 w-60 rounded-full bg-white/20 blur-3xl"></div>
        <div class="relative grid gap-8 lg:grid-cols-[1fr_.72fr] lg:items-end">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.34em] text-[#d6e8ee]">{{ $pageData['kicker'] }}</p>
                <h1 class="display-font mt-4 max-w-4xl text-4xl font-black leading-tight sm:text-6xl">
                    {{ $pageData['title'] }}
                </h1>
                <p class="mt-6 max-w-3xl text-base leading-8 text-white/80">
                    {{ $pageData['intro'] }}
                </p>
            </div>
            <div class="rounded-[2rem] border border-white/25 bg-white/18 p-5 backdrop-blur">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-[#d6e8ee]">Modulo</p>
                <p class="display-font mt-3 text-4xl font-black">{{ $pageData['hero'] }}</p>
                <p class="mt-4 text-sm leading-7 text-white/75">
                    Informacion publica separada del acceso interno para proteger los procesos administrativos.
                </p>
            </div>
        </div>
    </section>

    @if ($pageKey === 'pagos-online')
        <section id="consulta-pago" class="mt-8 grid gap-6 lg:grid-cols-[1.08fr_.92fr] lg:items-start">
            <article class="water-card rounded-[2.2rem] p-6 sm:p-8">
                <p class="water-kicker">Consulta y orden QR</p>
                <h2 class="display-font mt-3 text-4xl font-black text-[#001b48]">Inicia tu pago desde aqui.</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600">
                    Ingresa tu numero de socio, cedula o codigo de medidor. El sistema mostrara tus facturas y solo permitira pagar en orden cronologico.
                </p>

                @if ($errors->any() || session('error') || session('success'))
                    <div class="mt-5 rounded-3xl border px-4 py-3 text-sm font-semibold
                        {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                        {{ session('success') ?: session('error') ?: $errors->first() }}
                    </div>
                @endif

                <form method="GET" action="{{ route('portal.buscar-deuda') }}" class="mt-6 rounded-[1.75rem] border border-[#d6e8ee] bg-[#f6fbff] p-3">
                    <label for="numero_socio" class="sr-only">Numero de socio o medidor</label>
                    <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <input
                            id="numero_socio"
                            name="numero_socio"
                            value="{{ old('numero_socio') }}"
                            placeholder="SOC-0001, S-001, CI o MED-2020-001"
                            class="water-input h-14 bg-white px-5 text-base text-[#001b48] placeholder:text-slate-400"
                            required
                        >
                        <button class="water-button h-14 px-7">
                            Consultar deuda
                        </button>
                    </div>
                </form>
            </article>

            <article class="overflow-hidden rounded-[2.2rem] border border-[#d6e8ee] bg-white shadow-[0_24px_60px_rgba(0,27,72,.10)]">
                <img src="{{ asset('portal/qr-payment.svg') }}" alt="Pago QR seguro" class="h-64 w-full object-cover">
                <div class="p-6">
                    <p class="water-kicker">Regla clave</p>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        Si debes enero, febrero y marzo, no podras pagar marzo dejando enero pendiente. El portal arma la orden desde la deuda mas antigua.
                    </p>
                </div>
            </article>
        </section>
    @endif

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ($pageData['cards'] as $card)
            <article class="water-card rounded-[2rem] p-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#d6e8ee] text-[#02457a]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c3 3.2 5 6.2 5 9.2A5 5 0 0 1 7 12.2C7 9.2 9 6.2 12 3z"/>
                    </svg>
                </span>
                <h2 class="mt-5 text-xl font-black text-[#001b48]">{{ $card['title'] }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $card['text'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
        <article class="water-card rounded-[2rem] p-6">
            <p class="water-kicker">Resumen</p>
            <h2 class="display-font mt-3 text-3xl font-black text-[#001b48]">Informacion practica para socios.</h2>
            <p class="mt-4 text-sm leading-7 text-slate-600">
                Esta pagina mantiene informacion institucional de consulta publica. Para pagos, el portal siempre exige codigo de socio, factura pendiente y orden de pago verificable.
            </p>
            @if ($pageKey === 'pagos-online')
                <a href="#consulta-pago" class="water-button mt-6">Iniciar pago QR</a>
            @else
                <a href="{{ route('portal.page', ['pagos-online']) }}" class="water-button mt-6">Ir a pagos online</a>
            @endif
        </article>

        <article class="rounded-[2rem] border border-[#d6e8ee] bg-[#f6fbff] p-6">
            <p class="water-kicker">Puntos clave</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($pageData['bullets'] as $bullet)
                    <div class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#018abe]"></span>
                        <span class="text-sm font-extrabold text-[#02457a]">{{ $bullet }}</span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
@endsection
