<section class="bg-gradient-to-b from-[#f0f7fb] to-white py-20 lg:py-28">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div>
            <p class="water-kicker">Comunicaciones</p>
            <h2 class="section-title mt-4">
                Alertas y comunicados
            </h2>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">

            @if (isset($alerts) && $alerts->count())
                @foreach($alerts as $alert)
                    <x-water-card class="p-6 border-l-4 border-[#e63946]">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-[#e63946]/10">
                                <svg class="h-6 w-6 text-[#e63946]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-[#001b48]">{{ $alert->title ?? 'Alerta' }}</h3>
                                <p class="mt-2 text-sm text-[#064663]">{{ Str::limit($alert->message ?? '', 100) }}</p>
                            </div>
                        </div>
                    </x-water-card>
                @endforeach
            @else
                <x-water-card class="p-6 col-span-full md:col-span-2 lg:col-span-3">
                    <p class="text-center text-[#064663]">No hay alertas activas en este momento.</p>
                </x-water-card>
            @endif

        </div>

    </div>

</section>

