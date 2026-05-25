@extends('portal.cliente.layout')

@section('title', 'Estado de cuenta')

@section('content')
    @php
        $paymentFacturas = ($paymentFacturas ?? $facturas->filter(fn ($factura) => (float) $factura->saldo > 0))->values();
        $initialTotal = (float) ($paymentFacturas->first()?->saldo ?? 0);
        $allPendingTotal = $paymentFacturas->sum(fn ($factura) => (float) $factura->saldo);
        $runningTotal = 0;
    @endphp

    <section class="rounded-[2.4rem] border border-orange-100 bg-white/86 p-6 shadow-[0_24px_70px_rgba(15,23,42,.10)] sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('portal.index') }}" class="text-sm font-bold text-orange-600 hover:text-orange-700">Volver a consulta</a>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.32em] text-orange-500">Estado de cuenta</p>
                <h1 class="display-font mt-2 text-4xl font-black text-slate-950 sm:text-5xl">
                    {{ $socio->persona?->nombre_completo ?? 'Cliente EPSAS' }}
                </h1>
                <p class="mt-3 text-slate-600">
                    Socio {{ $socio->numero_socio }} - Medidor {{ $socio->medidorActivo?->numero_serie ?? 'sin medidor activo' }}
                </p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Direccion</p>
                <p class="mt-1 max-w-sm text-sm font-bold text-slate-800">{{ $socio->direccion ?: 'Sin direccion registrada' }}</p>
            </div>
        </div>
    </section>

    @if (session('error') || session('success'))
        <div class="mt-6 rounded-3xl border px-5 py-4 text-sm font-semibold
            {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
            {{ session('success') ?: session('error') }}
        </div>
    @endif

    <section class="mt-6 grid gap-4 md:grid-cols-3">
        <article class="rounded-[2rem] bg-slate-900 p-6 text-white shadow-lg">
            <p class="text-sm font-bold text-slate-300">Saldo pendiente</p>
            <p class="mt-4 text-4xl font-black">Bs {{ number_format($deuda['total'], 2) }}</p>
            <p class="mt-2 text-sm text-slate-300">{{ $deuda['pendientes'] }} factura(s) abierta(s)</p>
        </article>
        <article class="rounded-[2rem] border border-orange-100 bg-orange-50 p-6">
            <p class="text-sm font-bold text-orange-700">Facturas vencidas</p>
            <p class="mt-4 text-4xl font-black text-orange-500">{{ $deuda['vencidas'] }}</p>
            <p class="mt-2 text-sm text-orange-900/70">Se consideran vencidas despues de 30 dias.</p>
        </article>
        <article class="rounded-[2rem] border border-slate-200 bg-white p-6">
            <p class="text-sm font-bold text-slate-500">Pago seguro por orden</p>
            @if ($paymentFacturas->isNotEmpty())
                <a href="#orden-pago" class="mt-5 flex w-full items-center justify-center rounded-2xl bg-orange-500 px-5 py-4 text-center text-sm font-extrabold uppercase tracking-[0.12em] text-white shadow-[0_14px_30px_rgba(249,115,22,.30)] transition hover:bg-orange-600">
                    Generar orden QR
                </a>
                <p class="mt-3 text-xs leading-5 text-slate-500">El pago se arma desde la deuda mas antigua. No se permiten saltos de meses.</p>
            @else
                <p class="mt-5 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">Tu cuenta esta al dia.</p>
            @endif
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
            <section id="orden-pago" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">Opciones de pago</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Selecciona hasta que mes quieres pagar. El sistema incluira automaticamente todas las facturas anteriores.</p>
                    </div>
                    <span class="w-fit rounded-full bg-orange-100 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-orange-700">
                        Secuencial
                    </span>
                </div>

                @if ($paymentFacturas->isNotEmpty())
                    <div class="mt-5 rounded-[1.7rem] border border-orange-100 bg-orange-50 p-4 text-sm leading-6 text-orange-950/80">
                        <strong class="text-orange-800">Regla de cobranza:</strong>
                        no puedes pagar meses recientes dejando meses antiguos pendientes. Si eliges abril, tambien se incluiran enero, febrero y marzo si siguen abiertos.
                    </div>

                    <form method="POST" action="{{ route('portal.ordenes.store') }}" class="mt-5 space-y-4" data-sequential-payment-form>
                        @csrf
                        <input type="hidden" name="numero_socio" value="{{ $socio->numero_socio }}">
                        <input type="hidden" name="hasta_factura_id" value="{{ $paymentFacturas->first()?->id_factura }}" data-payment-target>

                        <div class="grid gap-3">
                            @foreach ($paymentFacturas as $factura)
                                @php
                                    $runningTotal += (float) $factura->saldo;
                                        $periodLabel = $factura->periodo_nombre
                                            ?: (($factura->inicio_cobro && $factura->fin_cobro)
                                                ? $factura->inicio_cobro->format('d/m/Y') . ' - ' . $factura->fin_cobro->format('d/m/Y')
                                                : $factura->numero_factura);
                                @endphp
                                <label class="group block cursor-pointer rounded-[1.6rem] border border-slate-200 bg-slate-50 p-4 transition hover:border-orange-200 hover:bg-orange-50/60" role="button" tabindex="0" data-payment-row>
                                    <input
                                        type="checkbox"
                                        value="{{ $factura->id_factura }}"
                                        data-payment-check
                                        data-total="{{ number_format($runningTotal, 2, '.', '') }}"
                                        data-count="{{ $loop->iteration }}"
                                        class="sr-only"
                                        @checked($loop->first)
                                    >
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex gap-4">
                                            <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border-2 border-slate-300 bg-white text-white transition group-hover:border-orange-300" data-payment-box>
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                    <path d="M5 10.5 8.2 14 15.5 6" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </span>
                                            <div>
                                                <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Factura pendiente</p>
                                                <p class="mt-1 text-lg font-black text-slate-950">{{ $periodLabel }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $factura->numero_factura }} - saldo del mes Bs {{ number_format((float) $factura->saldo, 2) }}</p>
                                                <p class="mt-2 text-xs font-bold text-slate-400" data-payment-status>Selecciona este mes para incluirlo.</p>
                                            </div>
                                        </div>
                                        <div class="rounded-2xl bg-white px-4 py-3 text-right shadow-sm">
                                            <p class="text-xs font-bold text-slate-400">Total acumulado</p>
                                            <p class="mt-1 text-xl font-black text-orange-600">Bs {{ number_format($runningTotal, 2) }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="rounded-[1.75rem] border border-orange-200 bg-white/95 p-4 shadow-[0_18px_45px_rgba(249,115,22,.18)] backdrop-blur">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-orange-500">Orden de pago</p>
                                    <p class="mt-1 text-3xl font-black text-slate-950">Bs <span data-sequential-total>{{ number_format($initialTotal, 2) }}</span></p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                        Incluye <span data-sequential-count>1</span> factura(s), siempre desde la mas antigua.
                                    </p>
                                </div>
                                <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-4 text-sm font-extrabold uppercase tracking-[0.12em] text-white transition hover:bg-slate-800">
                                    Generar orden QR
                                </button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="mt-5 rounded-3xl border border-dashed border-emerald-200 bg-emerald-50 px-5 py-12 text-center text-sm font-semibold text-emerald-700">
                        No tienes facturas pendientes para pagar.
                    </div>
                @endif
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-950">Facturas recientes</h2>
                        <p class="mt-1 text-sm text-slate-500">Importes y periodos reales usados por el sistema interno.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $facturas->count() }}</span>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($facturas as $factura)
                        @php
                            $isPending = (float) $factura->saldo > 0;
                        @endphp
                        <article class="rounded-3xl border {{ $isPending ? 'border-orange-200 bg-orange-50/50' : 'border-slate-200 bg-slate-50' }} p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-extrabold text-slate-950">{{ $factura->numero_factura }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $factura->inicio_cobro?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $factura->fin_cobro?->format('d/m/Y') ?? 'Sin fin' }}
                                    </p>
                                </div>
                                <span class="w-fit rounded-full px-3 py-1 text-xs font-extrabold
                                    @if($factura->estado_pago === 'Pagada') bg-emerald-100 text-emerald-700
                                    @elseif($factura->estado_pago === 'Vencida') bg-rose-100 text-rose-700
                                    @else bg-amber-100 text-amber-700 @endif">
                                    {{ $factura->estado_pago }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                                <div class="rounded-2xl bg-white p-3">
                                    <p class="text-xs font-bold text-slate-400">Consumo</p>
                                    <p class="mt-1 font-extrabold text-slate-800">{{ number_format((float) $factura->consumo_m3, 2) }} m3</p>
                                </div>
                                <div class="rounded-2xl bg-white p-3">
                                    <p class="text-xs font-bold text-slate-400">Agua</p>
                                    <p class="mt-1 font-extrabold text-slate-800">Bs {{ number_format((float) $factura->monto_consumo, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white p-3">
                                    <p class="text-xs font-bold text-slate-400">Mora</p>
                                    <p class="mt-1 font-extrabold text-slate-800">Bs {{ number_format((float) $factura->recargo_mora, 2) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white p-3">
                                    <p class="text-xs font-bold text-slate-400">Saldo</p>
                                    <p class="mt-1 font-extrabold {{ $isPending ? 'text-orange-600' : 'text-emerald-600' }}">Bs {{ number_format((float) $factura->saldo, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                <span>Total Bs {{ number_format((float) $factura->total, 2) }} - Cargo fijo Bs {{ number_format((float) $factura->cargo_fijo, 2) }}</span>
                                <a href="{{ route('portal.ver-factura', ['id' => $factura->id_factura, 'numero_socio' => $socio->numero_socio]) }}" class="font-extrabold text-orange-600 hover:text-orange-700">Ver detalle</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center text-sm font-semibold text-slate-500">
                            Aun no hay facturas registradas para este socio.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="rounded-[1.7rem] bg-slate-950 p-5 text-white">
                <p class="text-xs font-black uppercase tracking-[0.22em] text-orange-300">Resumen de deuda</p>
                <p class="mt-3 text-4xl font-black">Bs {{ number_format($allPendingTotal, 2) }}</p>
                <p class="mt-2 text-sm text-slate-300">Total si decides cancelar todo lo pendiente en una sola orden.</p>
            </div>

            <h2 class="mt-6 text-xl font-extrabold text-slate-950">Ultimos pagos</h2>
            <p class="mt-1 text-sm text-slate-500">Pagos registrados por caja, administracion o QR.</p>

            <div class="mt-5 space-y-3">
                @forelse ($pagos as $pago)
                    <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-sm font-extrabold text-slate-900">{{ $pago->numero_factura }}</p>
                        <p class="mt-1 text-xs text-emerald-700">{{ $pago->metodo_pago ?? 'Metodo registrado' }} - {{ \Carbon\Carbon::parse($pago->fecha_cobro)->format('d/m/Y') }}</p>
                        <p class="mt-3 text-2xl font-black text-emerald-700">Bs {{ number_format((float) $pago->monto_pagado, 2) }}</p>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm font-semibold text-slate-500">
                        No hay pagos recientes para mostrar.
                    </div>
                @endforelse
            </div>
        </aside>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-sequential-payment-form]');
            if (!form) {
                return;
            }

            const target = form.querySelector('[data-payment-target]');
            const totalNode = form.querySelector('[data-sequential-total]');
            const countNode = form.querySelector('[data-sequential-count]');
            const rows = [...form.querySelectorAll('[data-payment-row]')];
            const checks = [...form.querySelectorAll('[data-payment-check]')];
            const formatter = new Intl.NumberFormat('es-BO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            let selectedIndex = 0;

            const refresh = (nextIndex = selectedIndex) => {
                selectedIndex = Math.max(0, Math.min(nextIndex, checks.length - 1));
                const selected = checks[selectedIndex];

                if (totalNode) {
                    totalNode.textContent = formatter.format(Number(selected?.dataset.total || 0));
                }

                if (countNode) {
                    countNode.textContent = selected?.dataset.count || '0';
                }

                if (target && selected) {
                    target.value = selected.value;
                }

                rows.forEach((row, index) => {
                    const included = index <= selectedIndex;
                    const box = row.querySelector('[data-payment-box]');
                    const status = row.querySelector('[data-payment-status]');

                    row.setAttribute('aria-pressed', included ? 'true' : 'false');

                    if (checks[index]) {
                        checks[index].checked = included;
                    }

                    row.classList.toggle('border-orange-300', included);
                    row.classList.toggle('bg-orange-50', included);
                    row.classList.toggle('shadow-[0_16px_35px_rgba(249,115,22,.14)]', index === selectedIndex);

                    if (box) {
                        box.classList.toggle('border-orange-500', included);
                        box.classList.toggle('bg-orange-500', included);
                        box.classList.toggle('text-white', included);
                        box.classList.toggle('border-slate-300', !included);
                        box.classList.toggle('bg-white', !included);
                    }

                    if (status) {
                        status.textContent = included
                            ? (index === selectedIndex ? 'Limite elegido: se pagara hasta esta factura.' : 'Incluida automaticamente por deuda anterior.')
                            : 'Selecciona este mes para incluirlo junto con los anteriores.';
                        status.classList.toggle('text-orange-600', included);
                        status.classList.toggle('text-slate-400', !included);
                    }
                });
            };

            checks.forEach((check, index) => {
                const row = check.closest('[data-payment-row]');

                row?.addEventListener('click', (event) => {
                    event.preventDefault();
                    refresh(index);
                });

                row?.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    refresh(index);
                });
            });

            refresh(0);
        })();
    </script>
@endpush
