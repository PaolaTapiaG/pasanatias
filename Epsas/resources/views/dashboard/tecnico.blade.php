@extends('layouts.app')

@section('title', 'Panel Tecnico - EPSAS')

@section('content')
@php
    $profilePhoto = ($sharedAuthUser ?? auth()->user()?->loadMissing('persona'))?->persona?->foto_url;
    $cards = [
        ['title' => 'Lecturacion', 'description' => 'Registra consumos por usuario, controla lecturas pendientes y filtra inconsistencias.', 'route' => route('tecnico.lecturas.index'), 'cta' => 'Abrir lecturas'],
        ['title' => 'Servicio y cortes', 'description' => 'Administra suspensiones por mora, reconexiones y autorizaciones de restablecimiento.', 'route' => route('tecnico.cortes.index'), 'cta' => 'Ver servicio'],
        ['title' => 'Campo e instalaciones', 'description' => 'Planifica instalaciones nuevas, cambios de medidor y mantenimiento de red.', 'route' => route('tecnico.instalaciones.index'), 'cta' => 'Ir a campo'],
        ['title' => 'Anomalias', 'description' => 'Centraliza medidores dañados, manipulaciones, fugas y observaciones técnicas.', 'route' => route('tecnico.anomalias.index'), 'cta' => 'Registrar anomalia'],
        ['title' => 'Operacion del agua', 'description' => 'Haz seguimiento a bombas, distribución por zonas y horarios operativos.', 'route' => route('tecnico.operacion.index'), 'cta' => 'Monitorear red'],
        ['title' => 'Incidencias y reportes', 'description' => 'Documenta emergencias, baja presión y problemas en bombas con evidencia.', 'route' => route('tecnico.incidencias.index'), 'cta' => 'Abrir incidencias'],
    ];
@endphp
<div class="page-background min-h-screen">
    @include('slideboard.sidebartec')

    <div data-tech-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        <!-- Header integrado con notificaciones y modo oscuro -->
        @include('partials.header-with-notifications', [
            'headerRole' => 'Tecnico',
            'headerTitle' => 'Centro operativo',
            'companyName' => 'Gestiona lecturas, incidencias, cortes y reconexiones',
            'userName' => Auth::user()->name ?? '',
            'userEmail' => Auth::user()->email ?? '',
            'profilePhoto' => $profilePhoto,
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="sm:hidden">
                <section class="grid gap-4">
                    <article class="rounded-[1.8rem] bg-[linear-gradient(135deg,#f97316_0%,#fb923c_55%,#fed7aa_100%)] px-5 py-5 text-white shadow-[0_22px_44px_rgba(249,115,22,0.22)]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-orange-50/90">Resumen operativo</p>
                                <p class="mt-3 text-4xl font-bold">{{ \Illuminate\Support\Facades\Cache::remember('dashboard:tecnico:medidores-total', now()->addMinutes(10), fn () => \App\Models\Medidor::count()) }}</p>
                                <p class="mt-2 text-sm text-orange-50/90">medidores registrados</p>
                            </div>
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.75 18.25h12.5V9.5a6.25 6.25 0 10-12.5 0v8.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l2.5-2.5" />
                                </svg>
                            </span>
                        </div>
                    </article>

                    <div class="grid grid-cols-2 gap-3">
                        <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <p class="text-sm text-slate-500">Lecturas</p>
                            <p class="mt-3 text-3xl font-bold text-slate-950" data-tecnico-metric="lecturas_cargadas">--</p>
                        </article>
                        <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <p class="text-sm text-slate-500">Pendientes</p>
                            <p class="mt-3 text-3xl font-bold text-slate-950" data-tecnico-metric="pendientes_tecnicos">--</p>
                        </article>
                    </div>
                </section>

                <section class="mt-5 mobile-finance-card rounded-[1.7rem] p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">Proximas lecturaciones</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $readingCalendar['month_label'] }}</p>
                        </div>
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">{{ $upcomingReadings->count() }} programadas</span>
                    </div>

                    <div class="mt-4 rounded-[1.45rem] border border-orange-100 bg-white p-4 shadow-[0_18px_36px_rgba(249,115,22,0.10)]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.18em] text-orange-500">Calendario</p>
                                <p class="mt-1 text-lg font-semibold text-slate-950">{{ $readingCalendar['month_label'] }}</p>
                            </div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-50 text-orange-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 2.75v2.5M16 2.75v2.5M4.75 9.25h14.5M6.75 5.25h10.5A2.25 2.25 0 0119.5 7.5v10.75a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18.25V7.5a2.25 2.25 0 012.25-2.25z" />
                                </svg>
                            </span>
                        </div>

                        <div class="mt-4 text-center" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.35rem;">
                            @foreach ($readingCalendar['weekdays'] as $weekday)
                                <span class="text-[0.68rem] font-semibold uppercase {{ $weekday === 'D' ? 'text-orange-500' : 'text-slate-400' }}">{{ $weekday }}</span>
                            @endforeach
                        </div>

                        <div class="mt-3 space-y-1.5">
                            @foreach ($readingCalendar['weeks'] as $week)
                                <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.35rem;">
                                    @foreach ($week as $day)
                                        <div class="
                                            {{ !$day['in_month'] ? 'bg-slate-50 text-slate-300' : '' }}
                                            {{ $day['date']->isSunday() && $day['in_month'] && !$day['is_busy'] ? 'border border-dashed border-orange-200 bg-orange-50 text-orange-500' : '' }}
                                            {{ $day['is_busy'] ? 'bg-orange-500 text-white shadow-sm' : '' }}
                                            {{ $day['is_today'] ? 'ring-2 ring-orange-300' : '' }}
                                            {{ $day['in_month'] && !$day['is_busy'] && !$day['date']->isSunday() ? 'bg-slate-100 text-slate-700' : '' }}
                                            flex h-10 w-full items-center justify-center rounded-2xl text-xs font-semibold
                                        ">
                                            {{ $day['day'] }}
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 text-[0.68rem] font-medium">
                            <span class="rounded-full bg-orange-500 px-2.5 py-1 text-white">Lectura programada</span>
                            <span class="rounded-full border border-dashed border-orange-200 bg-orange-50 px-2.5 py-1 text-orange-600">Domingo libre</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($upcomingReadings->take(3) as $reading)
                            <article class="rounded-[1.35rem] border border-slate-100 bg-slate-50 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-950">{{ $reading->socio }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $reading->numero_serie }} · {{ $reading->codigo }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                        {{ $reading->due_day }}/{{ $reading->due_date->format('m') }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
                                No hay lecturaciones proximas registradas.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="mt-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-950">Acciones rapidas</h2>
                        <span class="text-sm font-medium text-orange-500">Hoy</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('tecnico.consumo.index') }}" class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75h5.38l3.12 3.12v9.38A1.75 1.75 0 0115 18H8.25A1.75 1.75 0 016.5 16.25V5.5A1.75 1.75 0 018.25 3.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75v3.75h3.75M9 10.5h4.5M9 13.5h4.5" />
                                </svg>
                            </div>
                            <p class="mt-4 text-base font-semibold text-slate-950">Registrar consumo</p>
                            <p class="mt-1 text-sm text-slate-500">Captura consumo en campo</p>
                        </a>
                        <a href="{{ route('tecnico.anomalias.index') }}" class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.75l7 12.5H5l7-12.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.25v3.5M12 15.5h.01" />
                                </svg>
                            </div>
                            <p class="mt-4 text-base font-semibold text-slate-950">Anomalias</p>
                            <p class="mt-1 text-sm text-slate-500">Reporta alertas tecnicas</p>
                        </a>
                        <a href="{{ route('tecnico.cortes.index') }}" class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5l14 14" /><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.5V7A2.25 2.25 0 0015 4.75H9A2.25 2.25 0 006.75 7v10A2.25 2.25 0 009 19.25h6A2.25 2.25 0 0017.25 17v-2.5" />
                                </svg>
                            </div>
                            <p class="mt-4 text-base font-semibold text-slate-950">Cortes</p>
                            <p class="mt-1 text-sm text-slate-500">Programa servicio</p>
                        </a>
                        <a href="{{ route('tecnico.incidencias.index') }}" class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h10.5v10.5H6.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9.25 9.25h5.5M9.25 12h5.5M9.25 14.75h3.5" />
                                </svg>
                            </div>
                            <p class="mt-4 text-base font-semibold text-slate-950">Alertas</p>
                            <p class="mt-1 text-sm text-slate-500">Incidencias de red</p>
                        </a>
                    </div>
                </section>

                <section class="mt-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-950">Estado reciente</h2>
                        <span class="text-sm font-medium text-orange-500">Monitoreo</span>
                    </div>
                    <div class="grid gap-4">
                        <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-950">Medidores activos</p>
                                    <p class="mt-1 text-sm text-slate-500">Equipos listos para lectura</p>
                                </div>
                                <p class="text-2xl font-bold text-orange-500" data-tecnico-metric="medidores_activos">--</p>
                            </div>
                        </article>
                        <article class="mobile-finance-card rounded-[1.6rem] p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-950">Plan del dia</p>
                                    <p class="mt-1 text-sm text-slate-500">Lecturas, incidencias y servicio</p>
                                </div>
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Listo</span>
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div class="hidden sm:block">
            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="overflow-hidden rounded-[2rem] bg-[linear-gradient(135deg,#1f2937_0%,#f97316_48%,#c2410c_100%)] px-6 py-8 text-white shadow-[0_24px_50px_rgba(194,65,12,0.24)] sm:px-8">
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-orange-100/80">EPSAS</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Bienvenido, {{ Auth::user()->name }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-orange-50/90 sm:text-base">
                        Prioriza lecturacion, atiende emergencias y mantén trazabilidad de cada intervención técnica con una interfaz preparada para trabajo de campo.
                    </p>
                </div>

                <div class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <h3 class="theme-text text-lg font-semibold text-slate-900 dark:text-slate-100">Acciones rapidas</h3>
                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('tecnico.consumo.index') }}" class="rounded-2xl bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-100 dark:bg-orange-500/10 dark:text-orange-200 dark:hover:bg-orange-500/20">
                            Registrar consumo
                        </a>
                        <a href="{{ route('tecnico.configuracion.index') }}" class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                            Editar perfil
                        </a>
                        <a href="{{ route('tecnico.anomalias.index') }}" class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/20">
                            Reportar anomalia
                        </a>
                        <a href="{{ route('tecnico.incidencias.index') }}" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/20">
                            Gestionar incidencias
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="theme-muted text-sm text-slate-500 dark:text-slate-400">Medidores registrados</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100" data-tecnico-metric="medidores_registrados">--</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="theme-muted text-sm text-slate-500 dark:text-slate-400">Activos</p>
                    <p class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-300" data-tecnico-metric="medidores_activos">--</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="theme-muted text-sm text-slate-500 dark:text-slate-400">Lecturas cargadas</p>
                    <p class="mt-3 text-3xl font-bold text-orange-600 dark:text-orange-300" data-tecnico-metric="lecturas_cargadas">--</p>
                </article>
                <article class="theme-card rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="theme-muted text-sm text-slate-500 dark:text-slate-400">Pendientes tecnicos</p>
                    <p class="mt-3 text-3xl font-bold text-rose-600 dark:text-rose-300" data-tecnico-metric="pendientes_tecnicos">--</p>
                </article>
            </section>

            <section class="mt-8 grid gap-6 xl:grid-cols-[1fr_0.95fr]">
                <article class="overflow-hidden rounded-[2rem] border border-orange-100 bg-white p-6 shadow-[0_24px_46px_rgba(249,115,22,0.10)]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-orange-500">Calendario tecnico</p>
                            <h3 class="mt-3 text-2xl font-bold text-slate-950">{{ $readingCalendar['month_label'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Visualiza las proximas lecturas sugeridas a partir de la ultima visita registrada por medidor.</p>
                        </div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2.75v2.5M16 2.75v2.5M4.75 9.25h14.5M6.75 5.25h10.5A2.25 2.25 0 0119.5 7.5v10.75a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18.25V7.5a2.25 2.25 0 012.25-2.25z" />
                            </svg>
                        </span>
                    </div>

                    <div class="mt-6 text-center" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.5rem;">
                        @foreach ($readingCalendar['weekdays'] as $weekday)
                            <span class="text-xs font-semibold uppercase {{ $weekday === 'D' ? 'text-orange-500' : 'text-slate-400' }}">{{ $weekday }}</span>
                        @endforeach
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach ($readingCalendar['weeks'] as $week)
                            <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.5rem;">
                                @foreach ($week as $day)
                                    <div class="
                                        {{ !$day['in_month'] ? 'bg-slate-50 text-slate-300' : '' }}
                                        {{ $day['date']->isSunday() && $day['in_month'] && !$day['is_busy'] ? 'border border-dashed border-orange-200 bg-orange-50 text-orange-500' : '' }}
                                        {{ $day['is_busy'] ? 'bg-orange-500 text-white shadow-sm' : '' }}
                                        {{ $day['is_today'] ? 'ring-2 ring-orange-300' : '' }}
                                        {{ $day['in_month'] && !$day['is_busy'] && !$day['date']->isSunday() ? 'bg-slate-100 text-slate-700' : '' }}
                                        flex h-12 w-full items-center justify-center rounded-2xl text-sm font-semibold
                                    ">
                                        {{ $day['day'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium">
                        <span class="rounded-full bg-orange-500 px-3 py-1 text-white">Lectura programada</span>
                        <span class="rounded-full border border-dashed border-orange-200 bg-orange-50 px-3 py-1 text-orange-600">Domingo libre</span>
                    </div>
                </article>

                <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Siguiente ronda de lecturas</h3>
                            <p class="theme-muted mt-2 text-sm text-slate-500 dark:text-slate-400">Recordatorios sugeridos por medidor activo.</p>
                        </div>
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-200">
                            {{ $upcomingReadings->count() }} pendientes
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($upcomingReadings as $reading)
                            <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $reading->socio }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $reading->numero_serie }} · {{ $reading->codigo }}</p>
                                    </div>
                                    <span class="rounded-2xl {{ $reading->is_overdue ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200' }} px-3 py-1 text-xs font-semibold">
                                        {{ $reading->due_date->format('d/m') }}
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span>Ultima lectura: {{ $reading->last_reading_date ?? 'Sin historial' }}</span>
                                    <span>{{ $reading->is_overdue ? 'Vencida' : ($reading->days_left <= 0 ? 'Hoy' : 'En ' . $reading->days_left . ' dias') }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Aun no hay lecturaciones proximas para mostrar.
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $card)
                    <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/70">
                        <h3 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] }}</h3>
                        <p class="theme-muted mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ $card['description'] }}</p>
                        <a href="{{ $card['route'] }}" class="mt-6 inline-flex items-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-orange-500 dark:text-white dark:hover:bg-orange-600">
                            {{ $card['cta'] }}
                        </a>
                    </article>
                @endforeach
            </section>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const endpoint = @json(route('api.dashboard.tecnico-metrics'));

        fetch(endpoint, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((data) => {
                ['medidores_registrados', 'medidores_activos', 'lecturas_cargadas', 'pendientes_tecnicos']
                    .forEach((key) => {
                        const node = document.querySelector(`[data-tecnico-metric="${key}"]`);
                        if (node) {
                            node.textContent = data[key] ?? '--';
                        }
                    });
            })
            .catch(() => {});
    })();
</script>
@endpush
