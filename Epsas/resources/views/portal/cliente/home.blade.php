@extends('portal.cliente.layout')

@section('title', 'Inicio')

@section('content')

    @include('portal.cliente.partials.hero')

    @include('portal.cliente.partials.territory')

    @include('portal.cliente.partials.news-carousel')

    @include('portal.cliente.partials.documents')

    @include('portal.cliente.partials.alerts')

    {{-- Featured Content Section --}}
    <section class="bg-white py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[.9fr_1.1fr] lg:items-start">
                
                <div>
                    <p class="water-kicker">Más contenido</p>
                    <h2 class="section-title mt-4">
                        Explorar portal
                    </h2>
                    <p class="section-subtitle mt-6">
                        Accede a toda la información institucional, noticias actualizadas y gestiona tus pagos de forma segura.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['title' => 'Sobre nosotros', 'text' => 'Misión, visión y trazabilidad del sistema de agua potable.', 'page' => 'sobre-nosotros', 'image' => 'water-network.svg'],
                        ['title' => 'Pagos online', 'text' => 'Consulta deuda, genera orden QR y sube comprobante de pago.', 'page' => 'pagos-online', 'image' => 'qr-payment.svg'],
                        ['title' => 'Proyectos', 'text' => 'Mantenimiento, instalaciones y trabajo de campo en ejecución.', 'page' => 'proyectos', 'image' => 'field-service.svg'],
                        ['title' => 'EPSAS informa', 'text' => 'Guías de consumo, lectura y cuidado del agua potable.', 'page' => 'epsas-informa', 'image' => 'water-video-poster.svg'],
                    ] as $card)
                        <a href="{{ route('portal.page', [$card['page']]) }}" class="water-card group overflow-hidden rounded-xl transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="h-32 w-full bg-gradient-to-br from-[#018abe] to-[#97cadb] overflow-hidden">
                                <img src="{{ asset('portal/' . $card['image']) }}" alt="{{ $card['title'] }}" class="h-full w-full object-cover group-hover:scale-110 transition">
                            </div>
                            <div class="p-5">
                                <h3 class="font-black text-[#001b48]">{{ $card['title'] }}</h3>
                                <p class="mt-2 text-sm text-[#064663]">{{ $card['text'] }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

            </div>
        </div>
    </section>

    {{-- Map Section --}}
    <section class="bg-gradient-to-b from-[#f0f7fb] to-white py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_.9fr]">

                <article class="overflow-hidden rounded-2xl border border-[#d6e8ee] bg-white shadow-sm">
                    <div class="p-8">
                        <p class="water-kicker">Ubicación</p>
                        <h2 class="section-title mt-4">Encuentra nuestra oficina</h2>
                        <p class="section-subtitle mt-4">
                            Usa el mapa interactivo para ubicar la oficina o punto de referencia configurado por administración.
                        </p>
                    </div>
                    <x-map-marker 
                        :lat="(float) ($company['gps_latitude'] ?? -16.500000)"
                        :lng="(float) ($company['gps_longitude'] ?? -68.150000)"
                        :label="$company['map_label'] ?? ($company['company_name'] ?? 'EPSAS El Portillo')"
                        height="300px"
                    />
                </article>

                <aside class="grid gap-4 lg:grid-cols-1 auto-rows-max">
                    <x-water-card class="p-6">
                        <p class="water-kicker">Atención</p>
                        <h3 class="mt-3 text-xl font-black text-[#001b48]">Horarios de referencia</h3>
                        <p class="mt-3 text-sm leading-6 text-[#064663]">Lunes a viernes de 08:00 a 16:00. Consulta comunicados por feriados o mantenimientos.</p>
                    </x-water-card>

                    <x-water-card class="p-6">
                        <p class="water-kicker">Pagos QR</p>
                        <h3 class="mt-3 text-xl font-black text-[#001b48]">Proceso protegido</h3>
                        <p class="mt-3 text-sm leading-6 text-[#064663]">El pago se realiza por orden específica y se verifica antes de marcar facturas como pagadas.</p>
                        <a href="{{ route('portal.page', ['pagos-online']) }}" class="water-button mt-5 inline-flex">
                            Ir a pagos
                        </a>
                    </x-water-card>
                </aside>

            </div>
        </div>
    </section>

    @include('portal.cliente.partials.footer')

@endsection

