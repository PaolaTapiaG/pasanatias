@extends('portal.cliente.layout')

@section('title', 'Detalle de factura')

@section('content')
    <section class="mx-auto max-w-3xl rounded-[2.4rem] border border-slate-200 bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,.10)] sm:p-8">
        <a href="{{ route('portal.buscar-deuda', ['numero_socio' => $factura->numero_socio]) }}" class="text-sm font-bold text-orange-600 hover:text-orange-700">
            Volver al estado de cuenta
        </a>

        <div class="mt-6 flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.32em] text-orange-500">Factura</p>
                <h1 class="display-font mt-2 text-4xl font-black text-slate-950">{{ $factura->numero_factura }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $factura->nombre_completo }} · Socio {{ $factura->numero_socio }}</p>
            </div>
            <span class="w-fit rounded-full px-4 py-2 text-sm font-extrabold
                @if($factura->estado_pago === 'Pagada') bg-emerald-100 text-emerald-700
                @elseif($factura->estado_pago === 'Vencida') bg-rose-100 text-rose-700
                @else bg-amber-100 text-amber-700 @endif">
                {{ $factura->estado_pago }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-3xl bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Periodo cobrado</p>
                <p class="mt-2 font-extrabold text-slate-900">
                    {{ $factura->inicio_cobro?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $factura->fin_cobro?->format('d/m/Y') ?? 'Sin fin' }}
                </p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Emision</p>
                <p class="mt-2 font-extrabold text-slate-900">{{ $factura->fecha_emision?->format('d/m/Y') ?? 'Sin fecha' }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Lectura anterior</p>
                <p class="mt-2 font-extrabold text-slate-900">{{ number_format((float) $factura->lectura_anterior, 2) }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Lectura actual</p>
                <p class="mt-2 font-extrabold text-slate-900">{{ number_format((float) $factura->lectura_actual, 2) }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-3xl border border-slate-200">
            <div class="flex justify-between border-b border-slate-100 px-5 py-4 text-sm">
                <span class="font-bold text-slate-600">Consumo facturado</span>
                <span class="font-extrabold text-slate-950">{{ number_format((float) $factura->consumo_m3, 2) }} m3</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 px-5 py-4 text-sm">
                <span class="font-bold text-slate-600">Servicio de agua</span>
                <span class="font-extrabold text-slate-950">Bs {{ number_format((float) $factura->monto_consumo, 2) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 px-5 py-4 text-sm">
                <span class="font-bold text-slate-600">Cargo adicional</span>
                <span class="font-extrabold text-slate-950">Bs {{ number_format((float) $factura->cargo_fijo, 2) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 px-5 py-4 text-sm">
                <span class="font-bold text-slate-600">Mora / multas</span>
                <span class="font-extrabold text-slate-950">Bs {{ number_format((float) $factura->recargo_mora, 2) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 px-5 py-4 text-sm">
                <span class="font-bold text-slate-600">Pagado</span>
                <span class="font-extrabold text-emerald-600">Bs {{ number_format((float) $factura->pagado, 2) }}</span>
            </div>
            <div class="flex justify-between px-5 py-5">
                <span class="font-black text-slate-950">Saldo pendiente</span>
                <span class="text-2xl font-black text-orange-600">Bs {{ number_format((float) $factura->saldo, 2) }}</span>
            </div>
        </div>
    </section>
@endsection
