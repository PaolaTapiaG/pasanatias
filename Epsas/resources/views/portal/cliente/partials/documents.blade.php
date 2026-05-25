<section class="bg-white py-20 lg:py-28">

    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:px-8 lg:grid-cols-2 lg:items-center">

        {{-- IMAGE --}}
        <div class="order-2 lg:order-1">
            <div class="overflow-hidden rounded-2xl shadow-lg">
                <img src="{{ asset('portal/gazette.png') }}" alt="Documentos institucionales" class="w-full">
            </div>
        </div>

        {{-- TEXT --}}
        <div class="order-1 lg:order-2 flex flex-col justify-center">

            <p class="water-kicker">Biblioteca digital</p>

            <h2 class="section-title mt-4">
                En kiosco
            </h2>

            <p class="section-subtitle mt-6">
                Documentos PDF institucionales, comunicados oficiales y guías de atención para los socios del servicio. Acceso rápido a información importante.
            </p>

            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('portal.page', ['documentos']) }}" class="water-button">
                    Ver documentos
                </a>
                <a href="{{ route('portal.page', ['comunicados']) }}" class="water-btn-secondary">
                    Comunicados
                </a>
            </div>

        </div>

    </div>

</section>

