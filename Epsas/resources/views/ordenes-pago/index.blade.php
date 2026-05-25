@extends('layouts.app')

@section('title', 'Pagos QR - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('partials.role-sidebar')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Verificacion QR',
            'headerTitle' => 'Ordenes de pago',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success') || session('error'))
                <div class="mb-6 rounded-2xl border px-4 py-3 text-sm font-semibold {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                    {{ session('success') ?: session('error') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Acciones QR</p>
                        <p class="mt-1 text-sm text-slate-500">Aprueba solo comprobantes que coincidan con una orden generada por el sistema.</p>
                    </div>
                    <a href="{{ route('secretaria.cobros.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 sm:w-auto">
                        Volver a cobros
                    </a>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-4">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">En revision</p>
                    <p class="mt-3 text-3xl font-black text-blue-600">{{ $stats['en_revision'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Pendientes</p>
                    <p class="mt-3 text-3xl font-black text-amber-500">{{ $stats['pendientes'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Aprobadas</p>
                    <p class="mt-3 text-3xl font-black text-emerald-600">{{ $stats['aprobadas'] }}</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="theme-muted text-sm text-slate-500">Rechazadas</p>
                    <p class="mt-3 text-3xl font-black text-rose-600">{{ $stats['rechazadas'] }}</p>
                </article>
            </section>

            <section class="theme-card mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1fr_220px_auto]">
                    <input
                        type="text"
                        name="buscar"
                        value="{{ $search }}"
                        placeholder="Buscar por orden, socio, nombre o CI"
                        class="theme-soft h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none transition focus:border-orange-300 focus:bg-white focus:ring-4 focus:ring-orange-100"
                    >
                    <select name="estado" class="theme-soft h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="" @selected($estado === '')>Todos los estados</option>
                        <option value="en_revision" @selected($estado === 'en_revision')>En revision</option>
                        <option value="pendiente" @selected($estado === 'pendiente')>Pendiente</option>
                        <option value="aprobada" @selected($estado === 'aprobada')>Aprobada</option>
                        <option value="rechazada" @selected($estado === 'rechazada')>Rechazada</option>
                        <option value="cancelada" @selected($estado === 'cancelada')>Cancelada</option>
                        <option value="vencida" @selected($estado === 'vencida')>Vencida</option>
                    </select>
                    <button class="h-12 rounded-2xl bg-slate-950 px-6 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filtrar
                    </button>
                </form>
            </section>

            <section class="theme-card mt-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Orden</th>
                                <th class="px-5 py-4">Socio</th>
                                <th class="px-5 py-4">Total</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4">Comprobante</th>
                                <th class="px-5 py-4">Fecha</th>
                                <th class="px-5 py-4 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($orders as $order)
                                @php
                                    $badge = match ($order->estado) {
                                        'aprobada' => 'bg-emerald-100 text-emerald-700',
                                        'en_revision' => 'bg-blue-100 text-blue-700',
                                        'rechazada' => 'bg-rose-100 text-rose-700',
                                        'pendiente' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4">
                                        <p class="font-extrabold text-slate-950">{{ $order->codigo }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $order->detalles->count() }} concepto(s)</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $order->socio?->persona?->nombre_completo ?? 'Sin nombre' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $order->socio?->numero_socio ?? 'Sin codigo' }}</p>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-950">Bs {{ number_format((float) $order->total, 2) }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-extrabold {{ $badge }}">{{ str_replace('_', ' ', ucfirst($order->estado)) }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($order->comprobante_referencia)
                                            <p class="font-semibold text-slate-900">{{ $order->comprobante_referencia }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $order->entidad_financiera ?: 'Entidad no informada' }} - Bs {{ number_format((float) $order->comprobante_monto, 2) }}
                                            </p>
                                        @else
                                            <span class="text-slate-400">Sin comprobante</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('secretaria.ordenes-pago.show', $order) }}" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700 transition hover:bg-orange-100">
                                            Revisar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No hay ordenes de pago con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">{{ $orders->links() }}</div>
        </main>
    </div>
</div>
@endsection
