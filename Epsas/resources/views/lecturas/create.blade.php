@extends('layouts.app')

@section('title', 'Nueva lecturacion - EPSAS')

@section('content')
@php
    $isAdmin = auth()->user()?->cachedRoleNames()?->contains('administrador');
@endphp
<div class="page-background min-h-screen">
    @if ($isAdmin)
        @include('slideboard.sidebaradmin')
    @else
        @include('slideboard.sidebartec')
    @endif
    <div data-sidebar-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Lecturaciones',
            'headerTitle' => 'Registrar lectura completa',
        ])
        {{--
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">Lecturaciones</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Registrar lectura completa</h1>
                    <p class="mt-2 text-sm text-slate-500">Formulario técnico detallado con lectura sugerida, fecha y observaciones en un espacio más compacto.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('tecnico.consumo.index') }}" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Registro rápido</a>
                    <a href="{{ route('tecnico.lecturas.index') }}" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Volver al historial</a>
                </div>
            </div>
        </header>

        --}}

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">{{ $errors->first() }}</div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de lectura</p>
                        <p class="mt-1 text-sm text-slate-500">Formulario tecnico detallado con lectura sugerida, fecha y observaciones.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ route('tecnico.consumo.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Registro rapido</a>
                        <a href="{{ route('tecnico.lecturas.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto">Volver al historial</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <form method="POST" action="{{ route('tecnico.lecturas.store') }}" class="grid gap-5">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Medidor activo</label>
                            <select id="medidor-select" name="id_medidor" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                                <option value="">Selecciona un medidor</option>
                                @foreach ($medidoresDisponibles as $medidor)
                                    <option
                                        value="{{ $medidor->id_medidor }}"
                                        data-anterior="{{ $medidor->lectura_sugerida }}"
                                        data-meta="{{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }} · última {{ $medidor->ultima_fecha ?? 'sin lectura' }}"
                                        @selected(old('id_medidor') == $medidor->id_medidor)
                                    >
                                        {{ $medidor->numero_serie }} · {{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="medidor-meta" class="mt-2 text-xs text-slate-500">Selecciona un medidor para ver el contexto de la última lectura.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Fecha de lectura</label>
                                <input type="date" name="fecha_lectura" value="{{ old('fecha_lectura', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Lectura anterior</label>
                                <input id="lectura-anterior" name="lectura_anterior" value="{{ old('lectura_anterior') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Lectura actual</label>
                                <input name="lectura_actual" value="{{ old('lectura_actual') }}" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Observaciones cortas</label>
                                <input name="observaciones" value="{{ old('observaciones') }}" placeholder="Ej. acceso restringido, lectura observada" class="theme-soft h-11 w-full rounded-xl border px-4 text-sm outline-none">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-2xl bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700">Guardar lectura</button>
                            <a href="{{ route('tecnico.anomalias.index') }}" class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reportar anomalía</a>
                        </div>
                    </form>
                </article>

                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Guía rápida</h2>
                    <div class="mt-5 space-y-4 text-sm text-slate-600">
                        <div class="rounded-2xl bg-blue-50 px-4 py-4">
                            El sistema te sugiere la lectura anterior para evitar búsquedas manuales.
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            Si la lectura actual es atípica, registra la observación y luego escala la anomalía.
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-4">
                            Mantén el mismo medidor seleccionado hasta guardar para no perder la referencia sugerida.
                        </div>
                    </div>

                    <div class="mt-6 rounded-[1.8rem] border border-dashed border-slate-300 px-4 py-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Disponibles</p>
                        <p class="mt-3 text-3xl font-bold text-slate-900">{{ count($medidoresDisponibles) }}</p>
                        <p class="mt-1 text-sm text-slate-500">medidor(es) activos listos para lectura</p>
                    </div>
                </article>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const medidorSelect = document.getElementById('medidor-select');
    const lecturaAnterior = document.getElementById('lectura-anterior');
    const medidorMeta = document.getElementById('medidor-meta');

    const syncLecturaAnterior = () => {
        const selected = medidorSelect?.selectedOptions?.[0];
        if (!selected) return;

        if (lecturaAnterior && selected.dataset.anterior && !lecturaAnterior.value) {
            lecturaAnterior.value = selected.dataset.anterior;
        }

        if (medidorMeta) {
            medidorMeta.textContent = selected.dataset.meta || 'Selecciona un medidor para ver el contexto de la última lectura.';
        }
    };

    medidorSelect?.addEventListener('change', () => {
        const selected = medidorSelect.selectedOptions[0];
        if (lecturaAnterior) {
            lecturaAnterior.value = selected?.dataset.anterior || '';
        }
        if (medidorMeta) {
            medidorMeta.textContent = selected?.dataset.meta || 'Selecciona un medidor para ver el contexto de la última lectura.';
        }
    });

    syncLecturaAnterior();
</script>
@endpush
