@extends('portal.cliente.layout')

@section('title', 'Pago no completado')

@section('content')
    <section class="mx-auto max-w-xl water-card rounded-[2.4rem] p-6 text-center sm:p-8">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.8rem] bg-rose-100 text-rose-700">
            <svg class="h-11 w-11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </div>

        <p class="water-kicker mt-6">Pago no completado</p>
        <h1 class="display-font mt-2 text-4xl font-black text-[#001b48]">No pudimos registrar el pago</h1>
        <p class="mt-4 text-sm leading-7 text-slate-600">Puedes volver a consultar tu deuda o comunicarte con atencion al publico.</p>

        <div class="mt-6 rounded-[1.7rem] bg-[#f6fbff] p-5 text-left">
            <div class="flex justify-between gap-4 text-sm">
                <span class="font-bold text-slate-500">Referencia</span>
                <span class="font-black text-[#001b48]">{{ $referencia }}</span>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Revisa conexion, datos ingresados o entidad financiera antes de intentar nuevamente.
            </p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('portal.index') }}" class="water-button">Volver a intentar</a>
            <a href="tel:+59167846664" class="water-button-light">Llamar</a>
        </div>
    </section>
@endsection
