@props(['title', 'kicker' => '', 'class' => ''])

<div class="{{ $class }}">
    @if ($kicker)
        <p class="water-kicker">{{ $kicker }}</p>
    @endif
    <h2 class="display-font mt-3 text-4xl font-black leading-tight text-[#001b48] sm:text-5xl">
        {{ $title }}
    </h2>
</div>
