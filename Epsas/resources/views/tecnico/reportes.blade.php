@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Sistema',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
])
    <section class="mt-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Nuevo reporte tecnico</h2>
            <form method="POST" action="{{ route('tecnico.reportes-tecnicos.store') }}" class="mt-6 grid gap-4">
                @csrf
                <input name="titulo" value="{{ old('titulo') }}" placeholder="Titulo del reporte" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                <div class="grid gap-4 md:grid-cols-3">
                    <select name="categoria" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="rotura">Rotura</option>
                        <option value="baja_presion">Baja presion</option>
                        <option value="bombas">Bombas</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="incidencia">Incidencia</option>
                    </select>
                    <input type="date" name="fecha_reporte" value="{{ old('fecha_reporte', now()->toDateString()) }}" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <select name="estado" class="theme-soft h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="borrador">Borrador</option>
                        <option value="emitido">Emitido</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <textarea name="resumen" class="theme-soft min-h-28 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Resumen técnico del hallazgo">{{ old('resumen') }}</textarea>
                <textarea name="recomendaciones" class="theme-soft min-h-24 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Recomendaciones o acciones sugeridas">{{ old('recomendaciones') }}</textarea>
                <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Guardar reporte</button>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Reportes recientes</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentReports as $report)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $report->titulo }}</p>
                            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($report->estado) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $report->resumen }}</p>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">Aun no hay reportes tecnicos registrados.</div>
                @endforelse
            </div>
        </article>
    </section>
@endcomponent
