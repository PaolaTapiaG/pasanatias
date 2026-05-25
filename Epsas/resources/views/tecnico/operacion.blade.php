@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Sistema',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Registrar operacion</h2>
            <form method="POST" action="{{ route('tecnico.operacion.store') }}" class="mt-6 grid gap-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <select name="tipo_operacion" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="bomba">Bomba</option>
                        <option value="distribucion">Distribucion</option>
                        <option value="valvula">Valvula</option>
                        <option value="abastecimiento">Abastecimiento</option>
                        <option value="inspeccion">Inspeccion</option>
                    </select>
                    <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="operativa">Operativa</option>
                        <option value="ajustada">Ajustada</option>
                        <option value="alerta">Alerta</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <input name="zona" value="{{ old('zona') }}" placeholder="Zona de distribucion" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <input name="horario" value="{{ old('horario') }}" placeholder="Horario operativo" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <input type="date" name="fecha_operacion" value="{{ old('fecha_operacion', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <textarea name="descripcion" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Observaciones de presion, caudal y maniobras">{{ old('descripcion') }}</textarea>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Guardar operacion</button>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Bitacora reciente</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentOperations as $operation)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ucfirst($operation->tipo_operacion) }} · {{ $operation->zona }}</p>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ ucfirst($operation->estado) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $operation->descripcion }}</p>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">Aun no hay operaciones registradas.</div>
                @endforelse
            </div>
        </article>
    </section>
@endcomponent
