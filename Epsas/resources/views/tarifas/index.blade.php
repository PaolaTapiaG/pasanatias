@extends('layouts.app')

@section('title', 'Tarifas - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Tarifas',
            'headerTitle' => 'Vista de tarifas',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de tarifas</p>
                        <p class="mt-1 text-sm text-slate-500">Exporta tarifas o crea una nueva configuracion de cobro.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ route('admin.tarifas.export', ['format' => 'excel'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">Exportar Excel</a>
                        <a href="{{ route('admin.tarifas.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Exportar PDF</a>
                        <a href="{{ route('admin.tarifas.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Nueva tarifa</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-[1.2fr_0.8fr_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o estado..." class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <select name="estado" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los estados</option>
                        <option value="activa" @selected(request('estado') === 'activa')>Activas</option>
                        <option value="inactiva" @selected(request('estado') === 'inactiva')>Inactivas</option>
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
                                <th class="px-5 py-4">Tarifa</th>
                                <th class="px-5 py-4">Uso</th>
                                <th class="px-5 py-4">Precio m3</th>
                                <th class="px-5 py-4">Consumo minimo</th>
                                <th class="px-5 py-4">Cargo fijo</th>
                                <th class="px-5 py-4">Vigencia</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4">Socios</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($tarifas as $tarifa)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-slate-900">{{ $tarifa->nombre }}</div>
                                        <div class="mt-1 text-xs text-slate-500">ID {{ $tarifa->id_tarifa }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                            {{ ucfirst($tarifa->tipo_uso ?? 'domestico') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">Bs {{ number_format((float) $tarifa->precio_m3_base, 2) }}</td>
                                    <td class="px-5 py-4">{{ number_format((float) $tarifa->consumo_minimo_m3, 2) }} m3</td>
                                    <td class="px-5 py-4">Bs {{ number_format((float) $tarifa->cargo_fijo, 2) }}</td>
                                    <td class="px-5 py-4">{{ optional($tarifa->fecha_vigencia)->format('d/m/Y') ?: 'Sin fecha' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $tarifa->estado === 'activa' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ ucfirst($tarifa->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">{{ $tarifa->socios_count }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.tarifas.edit', $tarifa->id_tarifa) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron tarifas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $tarifas->links() }}
            </div>
        </main>
    </div>
</div>
@endsection
