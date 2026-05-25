@props(['href' => '#', 'variant' => 'primary', 'class' => ''])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-full font-black uppercase tracking-wider transition-all duration-300';
    $variants = [
        'primary' => 'water-btn-primary',
        'secondary' => 'water-btn-secondary',
        'light' => 'water-button-light',
    ];
    $variantClasses = $variants[$variant] ?? $variants['primary'];
@endphp

<a href="{{ $href }}" class="{{ $baseClasses }} {{ $variantClasses }} {{ $class }}">
    {{ $slot }}
</a>
