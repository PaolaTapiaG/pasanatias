@extends('layouts.app')

@section('title', ($moduleTitle ?? 'Tecnico') . ' - EPSAS')

@section('content')
@php
    $profileActions = $moduleActions ?? [];
@endphp
<div class="page-background min-h-screen">
    @include('slideboard.sidebartec')

    <div data-tech-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => $moduleEyebrow ?? 'Tecnico',
            'headerTitle' => $moduleTitle ?? 'Modulo tecnico',
        ])

        <main class="mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-8 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            @if (!empty($moduleDescription) || !empty($profileActions))
                <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600 dark:text-orange-300">Acciones del modulo</p>
                            @if (!empty($moduleDescription))
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $moduleDescription }}</p>
                            @else
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Usa los accesos operativos sin cargar el encabezado.</p>
                            @endif
                        </div>
                        @if (!empty($profileActions))
                            <div class="grid w-full gap-3 sm:flex sm:w-auto sm:max-w-full sm:flex-wrap sm:items-center sm:justify-end">
                                @foreach ($profileActions as $action)
                                    <a href="{{ $action['href'] }}" class="{{ $action['variant'] === 'solid' ? 'bg-orange-500 text-white hover:bg-orange-600 dark:bg-orange-500 dark:text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900/60' }} inline-flex min-h-11 w-full items-center justify-center rounded-2xl px-4 py-2.5 text-center text-sm font-semibold transition sm:w-auto">
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if (!empty($moduleStats))
                @php
                    $statCount = count($moduleStats);
                @endphp
                <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
                    @foreach ($moduleStats as $stat)
                        @php
                            $toneMap = [
                                'orange' => 'text-orange-600 dark:text-orange-300',
                                'amber' => 'text-amber-600 dark:text-amber-300',
                                'rose' => 'text-rose-600 dark:text-rose-300',
                                'emerald' => 'text-emerald-600 dark:text-emerald-300',
                                'slate' => 'text-slate-900 dark:text-slate-100',
                            ];
                            $mobileSpan = $statCount % 2 !== 0 && $loop->last ? 'col-span-2 xl:col-span-1' : '';
                        @endphp
                        <article class="theme-card {{ $mobileSpan }} rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:rounded-[1.75rem] sm:p-5 dark:border-slate-800 dark:bg-slate-950/70">
                            <p class="theme-muted text-[0.92rem] leading-5 text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                            <p class="mt-2 text-[2rem] font-bold leading-none sm:mt-3 sm:text-3xl {{ $toneMap[$stat['tone'] ?? 'slate'] ?? $toneMap['slate'] }}">{{ $stat['value'] }}</p>
                        </article>
                    @endforeach
                </section>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
@endsection
