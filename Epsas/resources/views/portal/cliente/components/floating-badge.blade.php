@props(['value', 'label', 'position' => 'top-10 left-10'])

<div class="absolute {{ $position }} rounded-full bg-white px-5 py-3 shadow-xl backdrop-blur">
    <p class="display-font text-2xl font-black text-[#001b48]">{{ $value }}</p>
    <p class="text-xs font-bold text-[#018abe] uppercase tracking-wider">{{ $label }}</p>
</div>
