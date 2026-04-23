@extends('layouts.app')

@section('title', 'Iniciar sesion')

@section('content')
<div class="min-h-screen bg-[linear-gradient(180deg,#1e6bbc_0%,#155dab_45%,#0f4f95_100%)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center justify-center">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-[0_30px_80px_rgba(7,42,89,0.35)] lg:grid-cols-[1.03fr_0.97fr]">
            <section class="relative hidden overflow-hidden bg-[linear-gradient(180deg,#2a82d6_0%,#1760af_55%,#114c91_100%)] lg:block">
                <div class="absolute inset-y-[-8%] right-[-22%] w-[48%] rounded-l-[999px] bg-white"></div>
                <div class="absolute left-10 top-12 h-20 w-20 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-10 -left-8 h-44 w-44 rounded-full bg-[radial-gradient(circle_at_30%_30%,#5bb0ff_0%,#3288df_55%,#1660af_100%)]"></div>
                <div class="absolute bottom-0 left-40 h-36 w-36 rounded-full bg-[#5da5ee]"></div>

                <div class="relative z-10 flex h-full max-w-[310px] flex-col justify-center px-12 py-14">
                    <p class="mb-6 text-xs font-semibold uppercase tracking-[0.42em] text-blue-100/95">
                        Sistema EPSAS
                    </p>
                    <h1 class="text-5xl font-extrabold leading-none tracking-tight text-white">
                        Bienvenido
                    </h1>
                    <p class="mt-6 text-base leading-8 text-blue-50/90">
                        Gestiona cobros, lecturas, medidores y reportes desde una sola plataforma segura.
                    </p>
                </div>
            </section>

            <section class="bg-white px-6 py-8 sm:px-8 lg:px-12 lg:py-12">
                <div class="mx-auto flex h-full w-full max-w-md flex-col justify-center">
                    <div class="mb-8">
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                            Iniciar sesion
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Accede con tu correo institucional para continuar.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                                Correo electronico
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 2a4 4 0 100 8 4 4 0 000-8zm-7 14a7 7 0 1114 0H3z" />
                                    </svg>
                                </span>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    placeholder="ejemplo@correo.com"
                                    class="h-12 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 @error('email') border-red-300 focus:ring-red-100 @else border-slate-200 focus:ring-blue-100 @enderror"
                                >
                            </div>
                            @error('email')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                                Contrasena
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5 8V6a5 5 0 1110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2V6a3 3 0 10-6 0v2h6z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    placeholder="Ingresa tu contrasena"
                                    class="h-12 w-full rounded-2xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 @error('password') border-red-300 focus:ring-red-100 @else border-slate-200 focus:ring-blue-100 @enderror"
                                >
                            </div>
                            @error('password')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="remember" class="flex items-center gap-3 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-200"
                            >
                            <span>Recordarme</span>
                        </label>

                        <div class="space-y-3 pt-2">
                            <button
                                type="submit"
                                class="flex h-12 w-full items-center justify-center rounded-2xl bg-[linear-gradient(180deg,#1b6cc2,#0d57a9)] text-sm font-bold text-white shadow-[0_14px_28px_rgba(13,87,169,0.22)] transition hover:opacity-95 focus:outline-none focus:ring-4 focus:ring-blue-200"
                            >
                                Iniciar sesion
                            </button>

                            <a
                                href="#"
                                class="flex h-12 w-full items-center justify-center rounded-2xl border border-blue-200 bg-white text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
                            >
                                Recuperar la cuenta
                            </a>
                        </div>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        Si olvidaste tu contrasena, solicita un codigo de recuperacion.
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
