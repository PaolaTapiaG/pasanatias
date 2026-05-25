@extends('layouts.app')

@section('title', 'Empleados - EPSAS')

@section('content')
<div class="page-background min-h-screen">
    @include('slideboard.sidebaradmin')

    <div data-admin-main class="min-h-screen transition-[padding] duration-300 ease-out md:pl-72">
        @include('partials.header-with-notifications', [
            'headerRole' => 'Empleados',
            'headerTitle' => 'Gestion de empleados',
        ])

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <section class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Acciones de empleados</p>
                        <p class="mt-1 text-sm text-slate-500">Exporta la vista filtrada o registra personal nuevo.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                        <a href="{{ route('admin.empleados.export', ['format' => 'excel'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:w-auto">Exportar Excel</a>
                        <a href="{{ route('admin.empleados.export', ['format' => 'pdf'] + request()->query()) }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Exportar PDF</a>
                        <a href="{{ route('admin.empleados.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto">Registrar empleado</a>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Activos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['activos'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Inactivos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['inactivos'] }}</p>
                </article>
                <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Tecnicos</p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">{{ $totales['tecnicos'] }}</p>
                </article>
            </section>

            <section class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 lg:grid-cols-[1.4fr_0.8fr_0.8fr_auto]">
                    <input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre, CI o rol..." class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                    <select name="estado" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los estados</option>
                        @foreach (['activo', 'inactivo', 'suspendido'] as $estado)
                            <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                        @endforeach
                    </select>
                    <select name="rol" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected((string) request('rol') === (string) $rol->id_rol)>{{ ucfirst($rol->nombre) }}</option>
                        @endforeach
                    </select>
                    <button class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Filtrar
                    </button>
                </form>
            </section>

            <section class="mt-6 grid gap-4 md:hidden">
                @forelse ($empleados as $empleado)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3">
                            @if ($empleado->persona?->foto_url)
                                <img src="{{ $empleado->persona->foto_url }}" alt="Foto" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-200">
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-500">
                                    {{ strtoupper(substr($empleado->persona?->nombres ?? 'E', 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-base font-semibold text-slate-900">{{ $empleado->persona?->nombre_completo }}</p>
                                <p class="mt-1 text-xs text-slate-500">CI {{ $empleado->persona?->cedula_identidad }}</p>
                                <p class="mt-2 text-sm text-slate-700">{{ ucfirst($empleado->rol?->nombre ?? 'Sin rol') }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $empleado->estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : ($empleado->estado === 'suspendido' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ ucfirst($empleado->estado) }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <p>{{ $empleado->persona?->telefono ?: 'Sin telefono' }}</p>
                            <p class="break-all">{{ $empleado->persona?->email ?: 'Sin correo' }}</p>
                            <p class="text-blue-700">Usuario: {{ $empleado->user?->username ?: 'Sin usuario' }}</p>
                            <p>Ingreso: {{ optional($empleado->fecha_ingreso)->format('d/m/Y') }}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <a href="{{ route('admin.empleados.show', $empleado->id_empleado) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Ver</a>
                            <a href="{{ route('admin.empleados.edit', $empleado->id_empleado) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Editar</a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500 shadow-sm">
                        No se encontraron empleados.
                    </div>
                @endforelse
            </section>

            <section class="mt-6 hidden overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-4">Empleado</th>
                                <th class="px-5 py-4">Contacto</th>
                                <th class="px-5 py-4">Rol</th>
                                <th class="px-5 py-4">Ingreso</th>
                                <th class="px-5 py-4">Estado</th>
                                <th class="px-5 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse ($empleados as $empleado)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($empleado->persona?->foto_url)
                                                <img src="{{ $empleado->persona->foto_url }}" alt="Foto" class="h-11 w-11 rounded-2xl object-cover ring-1 ring-slate-200">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-500">
                                                    {{ strtoupper(substr($empleado->persona?->nombres ?? 'E', 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-semibold text-slate-900">{{ $empleado->persona?->nombre_completo }}</div>
                                                <div class="mt-1 text-xs text-slate-500">CI {{ $empleado->persona?->cedula_identidad }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div>{{ $empleado->persona?->telefono ?: 'Sin telefono' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $empleado->persona?->email ?: 'Sin correo' }}</div>
                                        <div class="mt-1 text-xs text-blue-700">Usuario: {{ $empleado->user?->username ?: 'Sin usuario' }}</div>
                                    </td>
                                    <td class="px-5 py-4">{{ ucfirst($empleado->rol?->nombre ?? 'Sin rol') }}</td>
                                    <td class="px-5 py-4">{{ optional($empleado->fecha_ingreso)->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $empleado->estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : ($empleado->estado === 'suspendido' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-700') }}">
                                            {{ ucfirst($empleado->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.empleados.show', $empleado->id_empleado) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Ver</a>
                                            <a href="{{ route('admin.empleados.edit', $empleado->id_empleado) }}" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                                        No se encontraron empleados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                {{ $empleados->links() }}
            </div>
        </main>
    </div>
</div>
@endsection
