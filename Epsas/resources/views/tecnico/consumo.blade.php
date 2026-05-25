@component('tecnico.partials.module-layout', [
    'moduleEyebrow' => 'Lecturacion',
    'moduleTitle' => $moduleTitle,
    'moduleDescription' => $moduleDescription,
    'moduleStats' => $moduleStats,
    'moduleActions' => [
        ['label' => 'Historial de lecturas', 'href' => route('tecnico.lecturas.index'), 'variant' => 'soft'],
    ],
])
    @php
        $readingIssue = session('reading_issue');
    @endphp

    @if ($readingIssue)
        <section class="mb-4 rounded-[1.6rem] border border-rose-300/70 bg-[linear-gradient(180deg,rgba(190,24,93,0.14),rgba(244,63,94,0.10))] px-4 py-4 shadow-[0_14px_24px_rgba(190,24,93,0.10)] sm:mb-6 sm:px-5">
            <p class="text-sm font-semibold text-rose-900">Lectura inconsistente detectada</p>
            <p class="mt-2 text-sm leading-6 text-rose-900/85">
                La lectura actual ({{ number_format((float) ($readingIssue['current'] ?? 0), 2) }}) no puede ser menor a la registrada el periodo anterior
                ({{ number_format((float) ($readingIssue['previous'] ?? 0), 2) }}).
                Verifica si hubo error de tipeo, medidor dañado o reemplazo del equipo.
            </p>
            <div class="mt-3 grid gap-2.5 sm:flex sm:flex-wrap">
                <a href="{{ route('tecnico.anomalias.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                    Reportar medidor dañado
                </a>
                <a href="{{ route('tecnico.medidores.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-white/70 px-4 py-2.5 text-sm font-semibold text-rose-900 transition hover:bg-white">
                    Revisar medidor activo
                </a>
            </div>
            <p class="mt-3 text-xs leading-5 text-rose-900/70">
                Si el medidor fue reemplazado, registra la siguiente lectura sobre el nuevo medidor activo. El nuevo equipo empieza desde 0 o desde su lectura inicial de instalacion.
            </p>
        </section>
    @endif

    <section class="mt-4 grid gap-4 sm:mt-6 sm:gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Registro de lecturas</h2>
            <p class="theme-muted mt-2 text-sm text-slate-500 dark:text-slate-400">Captura la lectura del día desde una lista corta de medidores activos y conserva observaciones del técnico.</p>

            <form method="POST" action="{{ route('tecnico.lecturas.store') }}" class="mt-6 grid gap-4 lg:grid-cols-2">
                @csrf
                <input type="hidden" name="redirect_to" value="tecnico.consumo.index">

                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Medidor activo</label>
                    <select id="quick-medidor" name="id_medidor" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="">Seleccionar medidor activo</option>
                        @foreach ($medidoresDisponibles as $medidor)
                            <option
                                value="{{ $medidor->id_medidor }}"
                                data-anterior="{{ $medidor->lectura_sugerida }}"
                                data-meta="{{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }} · {{ $medidor->zona }}"
                                @selected(old('id_medidor') == $medidor->id_medidor)
                            >
                                {{ $medidor->numero_serie }} · {{ $medidor->codigo_usuario }} · {{ $medidor->socio_nombre }}
                            </option>
                        @endforeach
                    </select>
                    <p id="quick-medidor-meta" class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Selecciona un medidor para ver la ultima lectura guardada y la zona del socio.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Lectura anterior registrada</label>
                    <input id="quick-anterior" name="lectura_anterior" value="{{ old('lectura_anterior') }}" readonly class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm text-slate-600 outline-none">
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Este valor lo calcula el sistema y no puede modificarse.</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Lectura actual del medidor</label>
                    <input name="lectura_actual" type="number" step="0.01" min="0" value="{{ old('lectura_actual') }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                    <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Ingresa el numero que hoy muestra el medidor. Si no hubo consumo, puedes repetir la lectura anterior y el cargo fijo se cobrara igual.</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Fecha de lectura</label>
                    <input type="date" name="fecha_lectura" value="{{ old('fecha_lectura', now()->toDateString()) }}" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Estado de lectura</label>
                    <select name="estado_lectura" class="theme-soft h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none">
                        <option value="normal" @selected(old('estado_lectura') === 'normal')>Normal</option>
                        <option value="observada" @selected(old('estado_lectura') === 'observada')>Observada</option>
                        <option value="requiere_verificacion" @selected(old('estado_lectura') === 'requiere_verificacion')>Requiere verificacion</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Observaciones</label>
                    <textarea name="observaciones" class="theme-soft min-h-28 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none" placeholder="Observaciones de consumo o alertas detectadas">{{ old('observaciones') }}</textarea>
                </div>
                <div class="lg:col-span-2 flex flex-wrap gap-3">
                    <button type="submit" class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">Guardar lectura</button>
                    <a href="{{ route('tecnico.anomalias.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">Escalar a anomalias</a>
                </div>
            </form>
        </article>

        <article class="theme-card rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-950/70">
            <h2 class="theme-text text-xl font-semibold text-slate-900 dark:text-slate-100">Lecturas recientes</h2>
            <div class="mt-5 space-y-3">
                @forelse ($recentReadings as $reading)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $reading->numero_serie }} · {{ $reading->codigo_usuario }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $reading->socio_nombre }}</p>
                            </div>
                            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">{{ \Illuminate\Support\Carbon::parse($reading->fecha_lectura)->format('d/m') }}</span>
                        </div>
                        <div class="mt-3 flex items-center gap-4 text-sm text-slate-600 dark:text-slate-300">
                            <span>Lectura: {{ number_format((float) $reading->lectura_actual, 2) }}</span>
                            <span>Consumo: {{ number_format((float) $reading->consumo_m3, 2) }} m3</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                        Aun no hay lecturas registradas en este modulo.
                    </div>
                @endforelse
            </div>

            <div class="mt-6 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <div class="rounded-2xl bg-orange-50 px-4 py-3 dark:bg-orange-500/10">Verifica el numero de serie del medidor antes de guardar.</div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/60">Confirma la lectura anterior sugerida por el sistema.</div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-900/60">Si el salto de consumo es atipico, deja observacion o escala una anomalia.</div>
            </div>
        </article>
    </section>

    @push('scripts')
    <script>
        const quickMedidor = document.getElementById('quick-medidor');
        const quickAnterior = document.getElementById('quick-anterior');
        const quickMeta = document.getElementById('quick-medidor-meta');

        function syncQuickConsumption() {
            const selected = quickMedidor?.selectedOptions?.[0];
            if (!selected) return;

            if (quickAnterior && selected.dataset.anterior && !quickAnterior.value) {
                quickAnterior.value = selected.dataset.anterior;
            }

            if (quickMeta) {
                quickMeta.textContent = selected.dataset.meta || 'Selecciona un medidor para ver la ultima lectura guardada y la zona del socio.';
            }
        }

        quickMedidor?.addEventListener('change', () => {
            const selected = quickMedidor.selectedOptions[0];
            if (quickAnterior) {
                quickAnterior.value = selected?.dataset.anterior || '';
            }
            if (quickMeta) {
                quickMeta.textContent = selected?.dataset.meta || 'Selecciona un medidor para ver la ultima lectura guardada y la zona del socio.';
            }
        });

        syncQuickConsumption();
    </script>
    @endpush
@endcomponent
