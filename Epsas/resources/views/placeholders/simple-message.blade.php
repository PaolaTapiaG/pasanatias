@extends('layouts.app')

@section('title', ($title ?? 'Modulo') . ' - EPSAS')

@section('content')
<div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-xl rounded-[2rem] border border-slate-200 bg-white p-10 text-center shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-700">{{ $title ?? 'Modulo' }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $message ?? 'En desarrollo' }}</h1>
        <p class="mt-3 text-sm text-slate-500">Esta seccion aun no tiene implementacion completa.</p>
        <a href="{{ route('dashboard') }}" class="mt-8 inline-flex rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
            Volver al panel
        </a>
    </div>
</div>
@endsection
