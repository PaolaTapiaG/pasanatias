@extends('portal.cliente.layout')

@section('title', 'Pago exitoso')

@section('content')
    <section class="mx-auto max-w-xl water-card rounded-[2.4rem] p-6 text-center sm:p-8">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.8rem] bg-emerald-100 text-emerald-700">
            <svg class="h-11 w-11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <p class="water-kicker mt-6">Pago exitoso</p>
        <h1 class="display-font mt-2 text-4xl font-black text-[#001b48]">Comprobante procesado</h1>
        <p class="mt-4 text-sm leading-7 text-slate-600">Guarda tu referencia para cualquier consulta posterior.</p>

        <div class="mt-6 rounded-[1.7rem] bg-[#f6fbff] p-5 text-left">
            <div class="flex justify-between gap-4 text-sm">
                <span class="font-bold text-slate-500">Referencia</span>
                <span class="font-black text-[#001b48]">{{ $referencia }}</span>
            </div>
            <div class="mt-4 flex justify-between gap-4">
                <span class="font-bold text-slate-500">Monto pagado</span>
                <span class="text-xl font-black text-emerald-700">Bs {{ number_format($monto, 2) }}</span>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('portal.index') }}" class="water-button">Nueva consulta</a>
            <button onclick="window.print()" class="water-button-light">Imprimir</button>
        </div>
    </section>
@endsection
