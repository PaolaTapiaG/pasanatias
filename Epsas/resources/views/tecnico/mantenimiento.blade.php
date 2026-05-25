@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Campo',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Incidencias', 'href' => route('tecnico.incidencias.index'), 'variant' => 'soft'],
    ],
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Orden de trabajo</h2>
            <form method="POST" action="{{ route('tecnico.mantenimiento.store') }}" class="mt-6 grid gap-4">
                @csrf
                <input name="referencia" value="{{ old('referencia') }}" placeholder="Tipo de mantenimiento o referencia" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <input name="zona" value="{{ old('zona') }}" placeholder="Zona afectada" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <div class="grid gap-4 md:grid-cols-3">
                    <input type="date" name="fecha_programada" value="{{ old('fecha_programada', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="coord_x" value="{{ old('coord_x') }}" placeholder="Posicion X (0-100)" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="coord_y" value="{{ old('coord_y') }}" placeholder="Posicion Y (0-100)" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="prioridad" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="alta">Alta</option>
                        <option value="media" selected>Media</option>
                        <option value="baja">Baja</option>
                    </select>
                    <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>
                <textarea name="descripcion" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Detalle de reparación o inspección">{{ old('descripcion') }}</textarea>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Crear orden</button>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="flex items-center justify-between gap-3">
                <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Mapa operativo</h2>
                <span class="text-sm font-medium text-orange-500">Puntos de red</span>
            </div>
            <div class="mt-5 overflow-hidden rounded-[1.8rem] border border-slate-200 bg-[radial-gradient(circle_at_top,_rgba(251,146,60,0.18),_transparent_45%),linear-gradient(180deg,#f8fafc_0%,#e2e8f0_100%)] p-4 dark:border-slate-800 dark:bg-slate-900/60">
                <div class="relative h-[320px] rounded-[1.4rem] border border-dashed border-orange-300/60 bg-white/70 dark:bg-slate-950/60">
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.12)_1px,transparent_1px)] bg-[size:24px_24px]"></div>
                    @forelse ($mapPoints as $point)
                        <div class="absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $point['x'] }}%; top: {{ $point['y'] }}%;">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full {{ $point['priority'] === 'alta' ? 'bg-rose-500' : ($point['priority'] === 'media' ? 'bg-amber-500' : 'bg-emerald-500') }} shadow-[0_0_0_6px_rgba(255,255,255,0.55)]"></div>
                            <div class="mt-2 min-w-[120px] rounded-xl bg-slate-950 px-3 py-2 text-xs text-white shadow-lg">{{ $point['label'] }} · {{ ucfirst($point['status']) }}</div>
                        </div>
                    @empty
                        <div class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-slate-500">
                            Aun no hay puntos con coordenadas cargadas. Puedes usar los campos X/Y del formulario para ubicarlos en este mapa.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @forelse ($zoneMap as $zone)
                    <div class="rounded-[1.7rem] border border-slate-200 bg-slate-50/90 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $zone['zone'] }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $zone['active'] }} frente(s) activos · {{ $zone['critical'] }} críticos</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-orange-600 shadow-sm dark:bg-slate-950 dark:text-orange-300">
                                {{ count($zone['items']) }} punto(s)
                            </span>
                        </div>

                        <div class="relative mt-4 h-40 overflow-hidden rounded-[1.3rem] border border-dashed border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950/70">
                            <div class="absolute inset-y-0 left-[33%] w-px bg-slate-200 dark:bg-slate-700"></div>
                            <div class="absolute inset-y-0 left-[66%] w-px bg-slate-200 dark:bg-slate-700"></div>
                            @foreach ($zone['items'] as $item)
                                <div class="absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $item['x'] }}%; top: {{ $item['y'] }}%;">
                                    <div class="flex h-3.5 w-3.5 items-center justify-center rounded-full {{ $item['priority'] === 'alta' ? 'bg-rose-500' : ($item['priority'] === 'media' ? 'bg-amber-500' : 'bg-emerald-500') }} shadow-[0_0_0_5px_rgba(255,255,255,0.8)]"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-sm text-slate-500 lg:col-span-2">
                        Aún no hay zonas suficientes para mostrar un mapa visual agrupado.
                    </div>
                @endforelse
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($recentOrders as $order)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $order->zona ?: 'Sin zona' }}</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $order->descripcion }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
@endcomponent
