@extends('layouts.app')

@section('title', 'Facturacion - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Facturacion',
            'headerTitle' => 'Vista de facturas',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (($syncResult['created'] ?? 0) > 0)
                <div class="mb-6 rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800 shadow-sm">
                    Se sincronizaron {{ $syncResult['created'] }} factura(s) automatica(s) hasta el ultimo mes cerrado.
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de facturacion</p>
                        <p class="mt-1 text-sm text-slate-500">Descarga reportes de la vista actual sin saturar el encabezado.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ route('secretaria.facturas.export', ['format' => 'excel'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">Exportar Excel</a>
                        <a href="{{ route('secretaria.facturas.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 sm:w-auto">Exportar PDF</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Generacion</p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900">Socios listos para facturar</h2>
                        <p class="mt-2 text-sm text-slate-500">El sistema sincroniza meses pendientes desde la fecha de instalacion o desde la ultima factura generada. Si no hubo lectura en un mes cerrado, crea una lectura automatica de consumo cero para cobrar el cargo fijo.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $candidatos->count() }}</span>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($candidatos->take(6) as $candidato)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $candidato->nombre_completo }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $candidato->codigo_display }} · {{ ucfirst($candidato->tipo_uso) }} · {{ $candidato->tarifa_nombre }}</p>
                                </div>
                                <form method="POST" action="{{ route('secretaria.facturas.store') }}">
                                    @csrf
                                    <input type="hidden" name="id_socio" value="{{ $candidato->id_socio }}">
                                    <button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                        Generar factura
                                    </button>
                                </form>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2 text-xs text-slate-600">
                                <div><span class="font-semibold text-slate-900">Inicio de cobro:</span> {{ $candidato->fecha_inicio ?: 'Sin fecha' }}</div>
                                <div><span class="font-semibold text-slate-900">Instalacion:</span> {{ $candidato->fecha_instalacion ?: 'Sin fecha' }}</div>
                                <div><span class="font-semibold text-slate-900">Ultima lectura:</span> {{ $candidato->fecha_lectura ?: 'Sin fecha' }}</div>
                                <div><span class="font-semibold text-slate-900">Consumo:</span> {{ number_format((float) $candidato->consumo_m3, 2) }} m3</div>
                                <div class="sm:col-span-2"><span class="font-semibold text-slate-900">Saldo pendiente anterior:</span> Bs {{ number_format((float) $candidato->saldo_pendiente, 2) }}</div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-sm text-slate-500 lg:col-span-2">
                            No hay socios con lecturas pendientes de facturacion en este momento.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Pendientes</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['pendientes'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Pagadas</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['pagadas'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Monto total</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">Bs {{ number_format((float) $totales['monto_total'], 2) }}</p>
                </article>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1.3fr_0.8fr_0.8fr_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por factura, socio o CI..." class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <select name="estado" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los estados</option>
                        @foreach (['pendiente', 'parcial', 'pagada', 'vencida', 'anulada'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    <select name="periodo" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los periodos</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id_periodo }}" @selected((string) request('periodo') === (string) $periodo->id_periodo)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filtrar
                    </button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Factura</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Periodo</th>
                                <th class="px-5 py-4">Emision</th>
                                <th class="px-5 py-4">Consumo</th>
                                <th class="px-5 py-4">Total</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($facturas as $factura)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $factura->numero_factura }}</div>
                                        <div class="mt-1 text-xs text-slate-500">ID {{ $factura->id_factura }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $factura->nombre_completo ?: 'Sin nombre' }}</div>
                                        <div class="mt-1 text-xs text-blue-700">{{ $factura->codigo_display }}</div>
                                    </td>
                                    <td class="px-5 py-4">{{ $factura->periodo_nombre ?: 'Sin periodo' }}</td>
                                    <td class="px-5 py-4">{{ optional($factura->fecha_emision)->format('d/m/Y') ?: 'Sin fecha' }}</td>
                                    <td class="px-5 py-4">{{ number_format((float) $factura->consumo_m3, 2) }} m3</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">Bs {{ number_format((float) $factura->total, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                            @if($factura->estado === 'pagada') bg-emerald-100 text-emerald-700
                                            @elseif($factura->estado === 'pendiente') bg-amber-100 text-amber-700
                                            @elseif($factura->estado === 'parcial') bg-blue-100 text-blue-700
                                            @elseif($factura->estado === 'vencida') bg-rose-100 text-rose-700
                                            @else bg-slate-200 text-slate-700 @endif">
                                            {{ ucfirst($factura->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('secretaria.facturas.show', $factura->id_factura) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron facturas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $facturas->links() }}
            </div>
        </main>
    </div>
</div>
@endsection
