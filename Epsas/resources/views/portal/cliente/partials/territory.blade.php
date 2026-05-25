<section class="bg-white py-20 lg:py-28">

    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:px-8 lg:grid-cols-2 lg:items-center">

        {{-- TEXT --}}
        <div>
            <p class="water-kicker">Cobertura y proyectos</p>
            
            <h2 class="section-title mt-4">
                Territorio
            </h2>

            <p class="section-subtitle mt-6">
                Gestión de cobertura, ríos, redes hidráulicas y zonas urbanas. Conoce los proyectos en ejecución y la expansión de servicios en toda la región.
            </p>

            <a href="{{ route('portal.page', ['proyectos']) }}" class="water-button mt-8">
                Descubrir territorio
            </a>

        </div>

        {{-- MAP --}}
        <div class="relative flex justify-center">
            @include('portal.cliente.svg.territory-map')
        </div>

    </div>

</section>

