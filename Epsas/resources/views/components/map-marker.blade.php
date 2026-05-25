@props([
    'lat' => -16.5,
    'lng' => -68.15,
    'label' => 'EPSAS El Portillo',
    'height' => '300px',
])

@php
    $lat = (float) $lat;
    $lng = (float) $lng;
    $delta = 0.01;
    $bbox = implode(',', [
        $lng - $delta,
        $lat - $delta,
        $lng + $delta,
        $lat + $delta,
    ]);
    $mapUrl = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox) . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-[#d6e8ee]']) }} style="height: {{ $height }};">
    <iframe
        title="Mapa {{ $label }}"
        src="{{ $mapUrl }}"
        class="h-full w-full border-0"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
    ></iframe>
    <div class="pointer-events-none absolute left-4 top-4 rounded-2xl bg-white/90 px-4 py-3 text-sm font-black text-[#001b48] shadow-lg backdrop-blur">
        {{ $label }}
    </div>
</div>
