<svg viewBox="0 0 400 500" class="w-full drop-shadow-[0_30px_50px_rgba(0,0,0,.2)] animate-float">
    {{-- Background --}}
    <rect width="400" height="500" fill="#e8f4f8" rx="20"/>
    
    {{-- Rivers/Water --}}
    <path d="M 50 100 Q 100 120 150 130 T 250 150" stroke="#018abe" stroke-width="8" fill="none" stroke-linecap="round"/>
    <path d="M 80 200 Q 120 220 160 230 T 280 250" stroke="#018abe" stroke-width="6" fill="none" stroke-linecap="round" opacity="0.8"/>
    
    {{-- Urban Areas (zones) --}}
    <g>
        <rect x="150" y="280" width="120" height="100" fill="#f5c563" rx="10" opacity="0.8"/>
        <circle cx="210" cy="330" r="8" fill="#d4a94d"/>
        <circle cx="170" cy="310" r="6" fill="#d4a94d"/>
        <circle cx="250" cy="310" r="6" fill="#d4a94d"/>
    </g>
    
    {{-- Forest/Green areas --}}
    <circle cx="80" cy="100" r="30" fill="#4ade80" opacity="0.6"/>
    <circle cx="300" cy="150" r="35" fill="#4ade80" opacity="0.6"/>
    <circle cx="320" cy="350" r="25" fill="#4ade80" opacity="0.6"/>
    
    {{-- Network lines --}}
    <line x1="210" y1="280" x2="210" y2="150" stroke="#018abe" stroke-width="2" stroke-dasharray="5,5" opacity="0.5"/>
    <line x1="150" y1="230" x2="300" y2="230" stroke="#018abe" stroke-width="2" stroke-dasharray="5,5" opacity="0.5"/>
    
    {{-- Markers --}}
    <circle cx="210" cy="140" r="4" fill="#e63946" opacity="0.8"/>
    <circle cx="160" cy="320" r="4" fill="#e63946" opacity="0.8"/>
</svg>
