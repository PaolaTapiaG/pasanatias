@php
    $portal = \App\Support\PortalContent::merge($portal ?? []);
    $homeVideo = !empty($portal['home_video_path'])
        ? asset($portal['home_video_path'])
        : ($portal['home_video_url'] ?? 'https://cdn.pixabay.com/video/2020/05/26/40252-425831511_large.mp4');
    $homeImages = [
        !empty($portal['home_image_1']) ? asset($portal['home_image_1']) : asset('portal/water-network.svg'),
        !empty($portal['home_image_2']) ? asset($portal['home_image_2']) : asset('portal/qr-payment.svg'),
        !empty($portal['home_image_3']) ? asset($portal['home_image_3']) : asset('portal/field-service.svg'),
    ];
@endphp

<section class="relative overflow-hidden bg-gradient-to-b from-[#f0f7fb] to-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            
            {{-- CONTENIDO IZQUIERDO --}}
            <div class="flex flex-col justify-center">
                <p class="text-sm font-black uppercase tracking-[0.35em] text-[#018abe]">
                    {{ $portal['home_kicker'] ?? 'Portal ciudadano' }}
                </p>
                
                <h1 class="display-font mt-4 text-5xl font-black leading-[1.1] text-[#001b48] sm:text-6xl">
                    {{ $portal['home_title'] ?? 'EPSAS El Portillo' }}
                </h1>
                
                <p class="mt-6 text-lg leading-8 text-[#064663]">
                    {{ $portal['home_intro'] ?? 'Información pública, comunicados, proyectos y orientación de pagos por orden QR para socios del servicio de agua potable.' }}
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('portal.page', ['pagos-online']) }}" class="water-button">
                        Ir a pagos online
                    </a>
                    <a href="{{ route('portal.page', ['epsas-informa']) }}" class="rounded-lg border-2 border-[#02457a] px-6 py-3 font-bold uppercase tracking-wider text-[#02457a] transition hover:bg-[#f0f7fb]">
                        EPSAS informa
                    </a>
                </div>

                {{-- STATS --}}
                <div class="mt-12 grid grid-cols-3 gap-4">
                    <div class="rounded-lg border border-[#d6e8ee] bg-white p-4 text-center shadow-sm">
                        <p class="display-font text-2xl font-black text-[#02457a]">QR</p>
                        <p class="mt-1 text-xs font-bold text-[#018abe] uppercase">Orden exacta</p>
                    </div>
                    <div class="rounded-lg border border-[#d6e8ee] bg-white p-4 text-center shadow-sm">
                        <p class="display-font text-2xl font-black text-[#02457a]">2026</p>
                        <p class="mt-1 text-xs font-bold text-[#018abe] uppercase">Gestión activa</p>
                    </div>
                    <div class="rounded-lg border border-[#d6e8ee] bg-white p-4 text-center shadow-sm">
                        <p class="display-font text-2xl font-black text-[#02457a]">24h</p>
                        <p class="mt-1 text-xs font-bold text-[#018abe] uppercase">Portal web</p>
                    </div>
                </div>
            </div>

            {{-- VIDEO DERECHA --}}
            <div class="relative">
                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#02457a] to-[#018abe] shadow-xl">
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
                    
                    {{-- PLAY BUTTON OVERLAY --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 backdrop-blur transition hover:bg-white hover:scale-110">
                            <svg class="h-6 w-6 text-[#02457a] ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- FEATURE CARDS --}}
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="overflow-hidden rounded-xl shadow-md">
                        <img src="{{ $homeImages[0] }}" alt="Red de agua potable" class="h-24 w-full object-cover">
                        <p class="bg-white px-3 py-2 text-center text-xs font-bold text-[#001b48]">Red potable</p>
                    </div>
                    <div class="overflow-hidden rounded-xl shadow-md">
                        <img src="{{ $homeImages[1] }}" alt="Pago QR seguro" class="h-24 w-full object-cover">
                        <p class="bg-white px-3 py-2 text-center text-xs font-bold text-[#001b48]">Pagos QR</p>
                    </div>
                    <div class="overflow-hidden rounded-xl shadow-md">
                        <img src="{{ $homeImages[2] }}" alt="Trabajo técnico de campo" class="h-24 w-full object-cover">
                        <p class="bg-white px-3 py-2 text-center text-xs font-bold text-[#001b48]">Proyectos</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

