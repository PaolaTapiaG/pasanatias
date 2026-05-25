@extends('layouts.app')

@section('title', 'Buscar deudores - EPSAS')

@section('content')
<div class="page-background min-h-screen bg-white">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Secretaria',
            'headerTitle' => 'Registrar pagos',
            'companyName' => $sharedCompanySettings['company_name'] ?? 'EPSAS EL PORTILLO',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-emerald-600">Pagos</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">Socios deudores</h2>
                        <p class="mt-2 text-sm text-slate-500">Selecciona un socio desde la tabla para abrir su ventana de pago.</p>
                    </div>
                    <a href="{{ route('secretaria.facturas.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">
                        Ver facturacion
                    </a>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Usuarios con pagos pendientes</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $resumen['socios_con_pendientes'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Multas pendientes</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">Bs {{ number_format((float) $resumen['multas_pendientes'], 2) }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Monto total por recaudar</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-700">Bs {{ number_format((float) $resumen['monto_total_pendiente'], 2) }}</p>
                </article>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 md:grid-cols-[1fr_auto]">
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $search }}"
                        placeholder="Buscar por nombre, CI o numero de socio"
                        class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                    >
                    <button class="h-12 rounded-2xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Buscar
                    </button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Codigo</th>
                                <th class="px-5 py-4">CI</th>
                                <th class="px-5 py-4">Pendientes</th>
                                <th class="px-5 py-4">Cuotas</th>
                                <th class="px-5 py-4">Multas</th>
                                <th class="px-5 py-4">Total</th>
                                <th class="px-5 py-4 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($socios as $socio)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $socio['nombre_completo'] }}</td>
                                    <td class="px-5 py-4">{{ $socio['codigo_display'] }}</td>
                                    <td class="px-5 py-4">{{ $socio['cedula_identidad'] ?: 'Sin registro' }}</td>
                                    <td class="px-5 py-4">{{ count($socio['facturas_pendientes']) }}</td>
                                    <td class="px-5 py-4">Bs {{ number_format((float) $socio['subtotal_pendiente'], 2) }}</td>
                                    <td class="px-5 py-4 text-rose-600">Bs {{ number_format((float) $socio['recargos_pendientes'], 2) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">Bs {{ number_format((float) $socio['total_pendiente'], 2) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('secretaria.cobros.show', $socio['id_socio']) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            Abrir pago
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron socios con pagos pendientes para este filtro.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
