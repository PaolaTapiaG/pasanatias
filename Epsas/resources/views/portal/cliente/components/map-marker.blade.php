@props(['lat', 'lng', 'label', 'id' => 'portal-map', 'height' => '360px'])

<div id="{{ $id }}" 
     class="rounded-[2rem] overflow-hidden bg-[#d6e8ee]" 
     style="height: {{ $height }}; width: 100%;"
     data-lat="{{ $lat }}"
     data-lng="{{ $lng }}"
     data-label="{{ $label }}">
</div>

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapNode = document.getElementById('{{ $id }}');
            if (!mapNode || typeof L === 'undefined') return;

            const lat = parseFloat(mapNode.dataset.lat);
            const lng = parseFloat(mapNode.dataset.lng);
            const label = mapNode.dataset.label;

            const map = L.map(mapNode, { scrollWheelZoom: false }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map).bindPopup(label).openPopup();
        });
    </script>
@endpush
