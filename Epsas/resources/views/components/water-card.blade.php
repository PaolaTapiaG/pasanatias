@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'water-card rounded-[2rem] ' . $class]) }}>
    {{ $slot }}
</div>
