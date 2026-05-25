@extends('portal.cliente.layout')

@section('title', 'Orden de pago')

@section('content')
    @php
        $estadoStyles = [
            'pendiente' => 'bg-amber-100 text-amber-800',
            'en_revision' => 'bg-blue-100 text-blue-800',
            'aprobada' => 'bg-emerald-100 text-emerald-800',
            'rechazada' => 'bg-rose-100 text-rose-800',
            'cancelada' => 'bg-slate-100 text-slate-700',
            'vencida' => 'bg-slate-100 text-slate-700',
        ];
        $estadoLabels = [
            'pendiente' => 'Pendiente de comprobante',
            'en_revision' => 'En revision',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            'vencida' => 'Vencida',
        ];
        $canUpload = in_array($orden->estado, ['pendiente', 'rechazada'], true);
    @endphp

    <section class="rounded-[2.4rem] border border-orange-100 bg-white/90 p-6 shadow-[0_24px_70px_rgba(15,23,42,.10)] sm:p-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('portal.buscar-deuda', ['numero_socio' => $orden->socio?->numero_socio]) }}" class="text-sm font-bold text-orange-600 hover:text-orange-700">Volver al estado de cuenta</a>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.32em] text-orange-500">Pago QR verificado</p>
                <h1 class="display-font mt-2 text-4xl font-black text-slate-950 sm:text-5xl">{{ $orden->codigo }}</h1>
                <p class="mt-3 text-slate-600">
                    Socio {{ $orden->socio?->numero_socio }} - {{ $orden->socio?->persona?->nombre_completo ?? 'Cliente EPSAS' }}
                </p>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Estado</p>
                <span class="mt-2 inline-flex rounded-full px-4 py-2 text-sm font-extrabold {{ $estadoStyles[$orden->estado] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ $estadoLabels[$orden->estado] ?? ucfirst($orden->estado) }}
                </span>
            </div>
        </div>
    </section>

    @if (session('error') || session('success') || $errors->any())
        <div class="mt-6 rounded-3xl border px-5 py-4 text-sm font-semibold
            {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
            {{ session('success') ?: session('error') ?: $errors->first() }}
        </div>
    @endif

    <section class="mt-6 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
        <article class="rounded-[2.2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-500">Total exacto a pagar</p>
                    <p class="mt-3 text-4xl font-black text-slate-950">Bs {{ number_format((float) $orden->total, 2) }}</p>
                    <p class="mt-2 text-sm text-slate-500">El comprobante debe coincidir con este monto.</p>
                </div>
                <span class="rounded-full bg-orange-100 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-orange-700">QR estatico</span>
            </div>

            <div class="mt-6 rounded-[2rem] border border-orange-100 bg-orange-50 p-4">
                <p class="text-sm font-extrabold text-orange-900">Antes de pagar</p>
                <div class="mt-3 space-y-2 text-sm leading-6 text-orange-950/80">
                    <p>1. Paga exactamente Bs {{ number_format((float) $orden->total, 2) }}.</p>
                    <p>2. Si tu app bancaria permite descripcion, escribe {{ $orden->codigo }}.</p>
                    <p>3. Sube una captura o PDF del comprobante para que administracion lo verifique.</p>
                </div>
            </div>

            <div class="mt-6 rounded-[2rem] border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-lg font-extrabold text-slate-950">Conceptos incluidos</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($orden->detalles as $detalle)
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-white p-4">
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">{{ $detalle->descripcion }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $detalle->tipo }}</p>
                            </div>
                            <p class="text-sm font-black text-slate-950">Bs {{ number_format((float) $detalle->monto, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="rounded-[2.2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="rounded-[2rem] bg-[#02457a] p-5 text-center text-white shadow-[0_24px_70px_rgba(0,27,72,.24)]">
                <p class="text-xs font-black uppercase tracking-[0.32em] text-white/75">QR de prueba empresa</p>
                <div class="mt-5 rounded-[1.7rem] bg-white p-5 text-slate-950">
                    <div class="mx-auto flex max-w-[320px] items-center justify-center [&_svg]:h-auto [&_svg]:w-full">
                        {!! $qrSvg !!}
                    </div>
                </div>
                <p class="mt-5 text-lg font-black">{{ $company['company_name'] ?? 'EPSAS' }}</p>
                <p class="mt-1 text-sm text-white/75">La orden identifica que se esta pagando, no el QR.</p>
            </div>

            @if ($orden->estado === 'en_revision')
                <div class="mt-6 rounded-[2rem] border border-blue-200 bg-blue-50 p-5 text-blue-900">
                    <p class="font-extrabold">Comprobante recibido</p>
                    <p class="mt-2 text-sm leading-6">Administracion verificara la entidad, referencia, monto exacto de la orden y archivo antes de marcar tus facturas como pagadas.</p>
                </div>
            @elseif ($orden->estado === 'aprobada')
                <div class="mt-6 rounded-[2rem] border border-emerald-200 bg-emerald-50 p-5 text-emerald-900">
                    <p class="font-extrabold">Pago aprobado</p>
                    <p class="mt-2 text-sm leading-6">Los conceptos de esta orden ya fueron marcados como pagados en el sistema.</p>
                </div>
            @else
                @if ($orden->estado === 'rechazada')
                    <div class="mt-6 rounded-[2rem] border border-rose-200 bg-rose-50 p-5 text-rose-900">
                        <p class="font-extrabold">Comprobante rechazado</p>
                        <p class="mt-2 text-sm leading-6">{{ $orden->notas_revision ?: 'Revisa los datos y vuelve a subir un comprobante correcto.' }}</p>
                    </div>
                @endif

                @if ($canUpload)
                    <form method="POST" action="{{ route('portal.ordenes.comprobante', [$orden, $orden->access_token]) }}" enctype="multipart/form-data" class="mt-6 rounded-[2rem] border border-slate-200 bg-slate-50 p-5">
                        @csrf
                        <h2 class="text-lg font-extrabold text-slate-950">Subir comprobante</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-orange-100 bg-white p-4">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-orange-500">Monto protegido</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">Bs {{ number_format((float) $orden->total, 2) }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">No se puede editar para evitar pagos alterados.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Fecha registrada</p>
                                <p class="mt-2 text-2xl font-black text-slate-950">{{ now()->format('d/m/Y') }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">El sistema toma la fecha de envio del comprobante.</p>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">Entidad financiera</label>
                                <select name="entidad_financiera" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100" required>
                                    <option value="">Seleccionar banco o billetera</option>
                                    @foreach ($financialEntities as $entity)
                                        <option value="{{ $entity }}" @selected(old('entidad_financiera', $orden->entidad_financiera) === $entity)>{{ $entity }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">Referencia del banco</label>
                                <input name="comprobante_referencia" value="{{ old('comprobante_referencia', $orden->comprobante_referencia) }}" class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100" required>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">Archivo</label>
                                <input type="file" name="comprobante" accept="image/*,.pdf" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-orange-100 file:px-4 file:py-2 file:font-bold file:text-orange-700" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-bold text-slate-700">Observaciones</label>
                            <textarea name="observaciones_cliente" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-orange-300 focus:ring-4 focus:ring-orange-100">{{ old('observaciones_cliente', $orden->observaciones_cliente) }}</textarea>
                        </div>
                        <button class="mt-5 w-full rounded-2xl bg-orange-500 px-6 py-4 text-sm font-extrabold uppercase tracking-[0.12em] text-white shadow-[0_14px_30px_rgba(249,115,22,.30)] transition hover:bg-orange-600">
                            Enviar comprobante
                        </button>
                    </form>
                @endif
            @endif
        </article>
    </section>
@endsection
