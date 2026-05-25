@extends('portal.cliente.layout')

@section('title', 'Inicio')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('content')
    @php
        $portal = \App\Support\PortalContent::merge($portal ?? []);
        $mapLat = (float) ($company['gps_latitude'] ?? -16.500000);
        $mapLng = (float) ($company['gps_longitude'] ?? -68.150000);
        $mapLabel = $company['map_label'] ?? ($company['company_name'] ?? 'EPSAS El Portillo');
        $homeVideo = !empty($portal['home_video_path'])
            ? asset($portal['home_video_path'])
            : ($portal['home_video_url'] ?? 'https://cdn.pixabay.com/video/2020/05/26/40252-425831511_large.mp4');
        $homeImages = [
            !empty($portal['home_image_1']) ? asset($portal['home_image_1']) : asset('portal/water-network.svg'),
            !empty($portal['home_image_2']) ? asset($portal['home_image_2']) : asset('portal/qr-payment.svg'),
            !empty($portal['home_image_3']) ? asset($portal['home_image_3']) : asset('portal/field-service.svg'),
        ];
    @endphp

    <section class="relative overflow-hidden rounded-[2.2rem] border border-[#d6e8ee] bg-white shadow-[0_28px_80px_rgba(0,27,72,.16)] sm:rounded-[3rem]">
        <div class="absolute inset-0 water-wave opacity-95"></div>
        <div class="absolute inset-0 water-ripple opacity-25"></div>
        <div class="absolute -right-24 top-16 h-72 w-72 rounded-full bg-white/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-16 h-72 w-72 rounded-full bg-[#97cadb]/40 blur-3xl"></div>

        <div class="relative grid min-h-[620px] gap-8 px-5 py-8 text-white sm:px-8 lg:grid-cols-[.88fr_1.12fr] lg:items-center lg:px-12">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.35em] text-[#d6e8ee]">{{ $portal['home_kicker'] ?? 'Portal ciudadano' }}</p>
                <h1 class="display-font mt-5 max-w-3xl text-4xl font-black leading-[1.02] sm:text-6xl lg:text-7xl">
                    {{ $portal['home_title'] ?? 'EPSAS El Portillo Pagos QR.' }}
                </h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-white/84 sm:text-lg">
                    {{ $portal['home_intro'] ?? 'Informacion publica, comunicados, proyectos y orientacion de pagos por orden QR para socios del servicio de agua potable.' }}
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('portal.page', ['pagos-online']) }}" class="water-button-light">Ir a pagos online</a>
                    <a href="{{ route('portal.page', ['epsas-informa']) }}" class="water-button-light">EPSAS informa</a>
                </div>

                <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
                    <div class="rounded-3xl border border-white/20 bg-white/16 p-4 backdrop-blur">
                        <p class="display-font text-3xl font-black">QR</p>
                        <p class="mt-1 text-xs font-bold text-white/70">Orden exacta</p>
                    </div>
                    <div class="rounded-3xl border border-white/20 bg-white/16 p-4 backdrop-blur">
                        <p class="display-font text-3xl font-black">2026</p>
                        <p class="mt-1 text-xs font-bold text-white/70">Gestion activa</p>
                    </div>
                    <div class="rounded-3xl border border-white/20 bg-white/16 p-4 backdrop-blur">
                        <p class="display-font text-3xl font-black">24h</p>
                        <p class="mt-1 text-xs font-bold text-white/70">Portal web</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="rounded-[2.4rem] border border-white/30 bg-white/18 p-4 shadow-[0_30px_80px_rgba(0,27,72,.24)] backdrop-blur">
                    <div class="overflow-hidden rounded-[2rem] bg-[#001b48]">
                        <video
                            class="aspect-video h-full w-full object-cover"
                            autoplay
                            muted
                            loop
                            playsinline
                            poster="{{ asset('portal/water-video-poster.svg') }}"
                        >
                            <source src="{{ $homeVideo }}" type="video/mp4">
                        </video>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <img src="{{ $homeImages[0] }}" alt="Red de agua potable" class="h-28 w-full rounded-3xl object-cover">
                        <img src="{{ $homeImages[1] }}" alt="Pago QR seguro" class="h-28 w-full rounded-3xl object-cover">
                        <img src="{{ $homeImages[2] }}" alt="Trabajo tecnico de campo" class="h-28 w-full rounded-3xl object-cover">
                    </div>
                </div>
            </div>
        </div>

        <svg class="relative -mt-14 block w-full text-white" viewBox="0 0 1440 160" preserveAspectRatio="none" aria-hidden="true">
            <path fill="currentColor" d="M0 96L80 90.7C160 85 320 75 480 85.3C640 96 800 128 960 122.7C1120 117 1280 75 1360 53.3L1440 32V160H0V96Z"/>
        </svg>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-4">
        @foreach ([
            ['value' => '01', 'label' => 'Informacion clara', 'text' => 'Comunicados, horarios y atencion al publico en un solo lugar.'],
            ['value' => '02', 'label' => 'Pagos QR', 'text' => 'La consulta de deuda vive en Pagos online para evitar confusion.'],
            ['value' => '03', 'label' => 'Proyectos', 'text' => 'Seguimiento de red, instalaciones y mejoras de servicio.'],
            ['value' => '04', 'label' => 'Ubicacion', 'text' => 'Mapa interactivo para encontrar la oficina central.'],
        ] as $metric)
            <article class="water-card rounded-[2rem] p-5">
                <p class="display-font text-4xl font-black text-[#02457a]">{{ $metric['value'] }}</p>
                <p class="mt-3 font-black text-[#001b48]">{{ $metric['label'] }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $metric['text'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-12 grid gap-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
        <div>
            <p class="water-kicker">Contenido destacado</p>
            <h2 class="display-font mt-3 text-4xl font-black leading-tight text-[#001b48] sm:text-5xl">
                {{ $portal['featured_title'] ?? 'Portal publico con noticias, imagenes y acceso ordenado a pagos.' }}
            </h2>
            <p class="mt-5 text-base leading-8 text-slate-600">
                {{ $portal['featured_text'] ?? 'El inicio presenta la identidad del servicio, informacion ciudadana y contenido visual. El modulo de deuda vive en Pagos online para que el usuario sepa exactamente donde iniciar el proceso.' }}
            </p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([
                ['title' => 'Sobre nosotros', 'text' => 'Mision, servicio y trazabilidad del sistema.', 'page' => 'sobre-nosotros', 'image' => 'water-network.svg'],
                ['title' => 'Pagos online', 'text' => 'Consulta deuda, genera orden QR y sube comprobante.', 'page' => 'pagos-online', 'image' => 'qr-payment.svg'],
                ['title' => 'Proyectos', 'text' => 'Mantenimiento, instalaciones y trabajo de campo.', 'page' => 'proyectos', 'image' => 'field-service.svg'],
                ['title' => 'EPSAS informa', 'text' => 'Guias de consumo, lectura y cuidado del agua.', 'page' => 'epsas-informa', 'image' => 'water-video-poster.svg'],
            ] as $card)
                <a href="{{ route('portal.page', [$card['page']]) }}" class="water-card group overflow-hidden rounded-[2rem] transition hover:-translate-y-1 hover:shadow-[0_30px_70px_rgba(0,27,72,.13)]">
                    <img src="{{ asset('portal/' . $card['image']) }}" alt="{{ $card['title'] }}" class="h-40 w-full object-cover">
                    <div class="p-5">
                        <h3 class="text-xl font-black text-[#001b48]">{{ $card['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $card['text'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-12 grid gap-6 lg:grid-cols-[1fr_.9fr]">
        <article class="overflow-hidden rounded-[2.3rem] border border-[#d6e8ee] bg-white shadow-[0_28px_70px_rgba(0,27,72,.12)]">
            <div class="p-6 sm:p-8">
                <p class="water-kicker">Mapa interactivo</p>
                <h2 class="display-font mt-3 text-4xl font-black text-[#001b48]">Encuentra nuestra oficina.</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600">
                    Usa el mapa para ubicar la oficina o punto de referencia configurado por administracion.
                </p>
            </div>
            <div id="portal-map" class="h-[360px] w-full bg-[#d6e8ee]"></div>
        </article>

        <aside class="grid gap-4">
            <article class="water-card rounded-[2rem] p-6">
                <p class="water-kicker">Atencion</p>
                <h3 class="mt-3 text-2xl font-black text-[#001b48]">Horario de referencia</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $portal['schedule_summary'] ?? 'Lunes a viernes de 08:00 a 16:00. Consulta comunicados por feriados o mantenimientos.' }}</p>
            </article>
            <article class="water-card rounded-[2rem] p-6">
                <p class="water-kicker">Pagos QR</p>
                <h3 class="mt-3 text-2xl font-black text-[#001b48]">Proceso protegido</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $portal['payment_note'] ?? 'El pago se realiza por orden especifica y se verifica antes de marcar facturas como pagadas.' }}</p>
                <a href="{{ route('portal.page', ['pagos-online']) }}" class="water-button mt-5">Ir a pagos</a>
            </article>
        </aside>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const mapNode = document.getElementById('portal-map');
            if (!mapNode || typeof L === 'undefined') {
                return;
            }

            const lat = @json($mapLat);
            const lng = @json($mapLng);
            const label = @json($mapLabel);
            const map = L.map(mapNode, {
                scrollWheelZoom: false,
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map).bindPopup(label).openPopup();
        })();
    </script>
@endpush
