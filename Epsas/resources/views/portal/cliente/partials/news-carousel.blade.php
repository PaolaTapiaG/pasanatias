<section class="bg-gradient-to-b from-white to-[#f0f7fb] py-20 lg:py-28">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div>
            <p class="water-kicker">Lo más reciente</p>
            <h2 class="section-title mt-4">
                Actualidades
            </h2>
        </div>

        <div class="mt-12 swiper newsSwiper">

            <div class="swiper-wrapper">

                @if (isset($news) && $news->count())
                    @foreach($news as $item)

                        <div class="swiper-slide">

                            <x-water-card class="overflow-hidden h-full">

                                @if ($item->image ?? false)
                                    <img src="{{ $item->image }}" alt="{{ $item->title }}" class="h-48 w-full object-cover">
                                @else
                                    <div class="h-48 w-full bg-gradient-to-br from-[#018abe] to-[#97cadb]"></div>
                                @endif

                                <div class="p-6">

                                    <h3 class="text-xl font-black text-[#001b48]">
                                        {{ $item->title ?? 'Sin título' }}
                                    </h3>

                                    <p class="mt-3 text-sm leading-6 text-[#064663]">
                                        {{ Str::limit($item->excerpt ?? $item->description ?? '', 120) }}
                                    </p>

                                    <a href="#" class="mt-4 inline-flex text-sm font-bold text-[#02457a] hover:text-[#001b48] transition">
                                        Leer más →
                                    </a>

                                </div>

                            </x-water-card>

                        </div>

                    @endforeach
                @else
                    <div class="swiper-slide">
                        <x-water-card class="p-10 text-center">
                            <p class="text-[#064663]">No hay noticias disponibles en este momento.</p>
                        </x-water-card>
                    </div>
                @endif

            </div>

        </div>

    </div>

</section>

